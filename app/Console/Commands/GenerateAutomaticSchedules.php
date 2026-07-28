<?php

namespace App\Console\Commands;

use App\Models\DialysisSchedule;
use App\Models\Patient;
use App\Models\DialysisSession;
use Illuminate\Console\Command;

class GenerateAutomaticSchedules extends Command
{
    protected $signature = 'app:generate-automatic-schedules';
    protected $description = 'Generate upcoming dialysis schedules safely with limit.';

    public function handle(): int
    {
        $timezone = config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
        $today = now($timezone)->startOfDay();

        $patients = Patient::where('patient_status', 'Aktif')->with('medicalProfile')->get();
        $count = 0;

        $dayNames = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

        foreach ($patients as $patient) {
            $latestEntry = DialysisSchedule::where('patient_id', $patient->id)->orderBy('hd_date', 'desc')->first();

            if (!$latestEntry) {
                $latestEntry = DialysisSession::where('patient_id', $patient->id)->orderBy('session_date', 'desc')->first();
                if ($latestEntry) { $latestEntry->hd_date = $latestEntry->session_date; }
            }

            if (!$latestEntry) continue;

            $frequency = $patient->medicalProfile?->hemodialysis_frequency ?? '2x per minggu';
            $targetDate = $latestEntry->hd_date->copy();

            // SAFETY 1: Jika jadwal terakhir sudah sangat lama, mulai dari hari ini saja
            if ($targetDate->isBefore($today)) {
                $targetDate = $today->copy()->subDay();
            }

            $stopDate = $today->copy()->addDays(14);
            $safetyCounter = 0; // SAFETY 2: Maksimal 5 jadwal per pasien sekali jalan

            while ($targetDate->isBefore($stopDate) && $safetyCounter < 5) {
                $currentDayOfWeek = $targetDate->dayOfWeek;
                $addDays = 3;

                if ($frequency === '2x per minggu') {
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3 => 3,
                        4, 5, 6 => 4,
                        0 => 1,
                    };
                } elseif ($frequency === '3x per minggu') {
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3, 4 => 2,
                        5, 6 => 3,
                        0 => 1,
                    };
                } elseif ($frequency === '1x per minggu') {
                    $addDays = 7;
                }

                $targetDate = $targetDate->addDays($addDays);
                if ($targetDate->dayOfWeek === 0) { $targetDate->addDays(1); }

                // Hanya buat jika tanggalnya hari ini atau ke depan
                if ($targetDate->isBefore($today)) continue;

                $exists = DialysisSchedule::where('patient_id', $patient->id)
                    ->whereDate('hd_date', $targetDate->toDateString())
                    ->exists();

                if (!$exists) {
                    DialysisSchedule::create([
                        'patient_id' => $patient->id,
                        'hd_date' => $targetDate->toDateString(),
                        'day_name' => $dayNames[$targetDate->dayOfWeek],
                        'start_time' => $latestEntry->start_time ?? '07:00',
                        'end_time' => $latestEntry->end_time ?? '12:00',
                        'shift' => $latestEntry->shift ?? 'Pagi',
                        'room' => $latestEntry->room,
                        'machine_number' => $latestEntry->machine_number,
                        'doctor_name' => $latestEntry->doctor_name,
                        'nurse_name' => $latestEntry->nurse_name,
                        'attendance_status' => 'Terjadwal',
                        'notes' => "Otomatis (Pola {$frequency})",
                    ]);
                    $count++;
                    $safetyCounter++;
                }
            }
        }

        $this->info("Generated {$count} upcoming schedules.");
        return self::SUCCESS;
    }
}
