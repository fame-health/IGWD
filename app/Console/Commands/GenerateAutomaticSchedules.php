<?php

namespace App\Console\Commands;

use App\Models\DialysisSchedule;
use App\Models\Patient;
use App\Models\DialysisSession;
use Illuminate\Console\Command;

class GenerateAutomaticSchedules extends Command
{
    protected $signature = 'app:generate-automatic-schedules';
    protected $description = 'Generate upcoming dialysis schedules by filling gaps from last known session.';

    public function handle(): int
    {
        $timezone = config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
        $today = now($timezone)->startOfDay();
        $stopDate = $today->copy()->addDays(14);

        $patients = Patient::where('patient_status', 'Aktif')->with('medicalProfile')->get();
        $count = 0;

        $dayNames = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

        foreach ($patients as $patient) {
            // LOGIKA BARU: Cari data terakhir yang SUDAH BERLALU (Kemarin atau lebih lama)
            // Ini agar kita bisa mendeteksi "bolong" jadwal mulai dari hari ini.
            $latestPastEntry = DialysisSchedule::where('patient_id', $patient->id)
                ->where('hd_date', '<', $today)
                ->orderBy('hd_date', 'desc')
                ->first();

            if (!$latestPastEntry) {
                $latestPastEntry = DialysisSession::where('patient_id', $patient->id)
                    ->where('session_date', '<', $today)
                    ->orderBy('session_date', 'desc')
                    ->first();
                if ($latestPastEntry) { $latestPastEntry->hd_date = $latestPastEntry->session_date; }
            }

            // Jika benar-benar tidak ada history masa lalu, ambil data terbaru yang ada (mungkin masa depan)
            if (!$latestPastEntry) {
                $latestPastEntry = DialysisSchedule::where('patient_id', $patient->id)->orderBy('hd_date', 'desc')->first();
            }

            if (!$latestPastEntry) continue;

            $frequency = $patient->medicalProfile?->hemodialysis_frequency ?? '2x per minggu';
            $targetDate = $latestPastEntry->hd_date->copy();

            // Safety: jika data terakhir sangat lama (lebih dari 30 hari), mulai dari seminggu lalu saja
            if ($targetDate->diffInDays($today) > 30) {
                $targetDate = $today->copy()->subDays(7);
            }

            $safetyCounter = 0;
            while ($targetDate->isBefore($stopDate) && $safetyCounter < 15) {
                $currentDayOfWeek = $targetDate->dayOfWeek;
                $addDays = 3;

                if ($frequency === '2x per minggu') {
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3 => 3, // Sen->Kam, Sel->Jum, Rab->Sab
                        4, 5, 6 => 4, // Kam->Sen, Jum->Sel, Sab->Rab
                        0 => 1,
                    };
                } elseif ($frequency === '3x per minggu') {
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3, 4 => 2, // Sen-Rab-Jum atau Sel-Kam-Sab
                        5, 6 => 3,       // Jum->Sen atau Sab->Sel
                        0 => 1,
                    };
                } elseif ($frequency === '1x per minggu') {
                    $addDays = 7;
                }

                $targetDate = $targetDate->addDays($addDays);
                if ($targetDate->dayOfWeek === 0) { $targetDate->addDays(1); }

                $safetyCounter++;

                // HANYA proses jika tanggalnya hari ini atau ke depan
                if ($targetDate->isBefore($today)) continue;

                // Cek apakah jadwal di tanggal tersebut sudah ada
                $exists = DialysisSchedule::where('patient_id', $patient->id)
                    ->whereDate('hd_date', $targetDate->toDateString())
                    ->exists();

                if (!$exists) {
                    DialysisSchedule::create([
                        'patient_id' => $patient->id,
                        'hd_date' => $targetDate->toDateString(),
                        'day_name' => $dayNames[$targetDate->dayOfWeek],
                        'start_time' => $latestPastEntry->start_time ?? '07:00',
                        'end_time' => $latestPastEntry->end_time ?? '12:00',
                        'shift' => $latestPastEntry->shift ?? 'Pagi',
                        'room' => $latestPastEntry->room,
                        'machine_number' => $latestPastEntry->machine_number,
                        'doctor_name' => $latestPastEntry->doctor_name,
                        'nurse_name' => $latestPastEntry->nurse_name,
                        'attendance_status' => 'Terjadwal',
                        'notes' => "Otomatis (Pola {$frequency})",
                    ]);
                    $count++;
                }
            }
        }

        $this->info("Generated {$count} upcoming schedules.");
        return self::SUCCESS;
    }
}
