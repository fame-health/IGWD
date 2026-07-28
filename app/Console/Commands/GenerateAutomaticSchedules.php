<?php

namespace App\Console\Commands;

use App\Models\DialysisSchedule;
use App\Models\Patient;
use Illuminate\Console\Command;

class GenerateAutomaticSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-automatic-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate upcoming dialysis schedules automatically with a 3-day interval.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
        $today = now($timezone);

        $patients = Patient::where('patient_status', 'Aktif')
            ->with('medicalProfile')
            ->get();
        $count = 0;

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        foreach ($patients as $patient) {
            $latestSchedule = DialysisSchedule::where('patient_id', $patient->id)
                ->orderBy('hd_date', 'desc')
                ->first();

            if (! $latestSchedule) {
                continue;
            }

            $frequency = $patient->medicalProfile?->hemodialysis_frequency ?? '2x per minggu';
            $targetDate = $latestSchedule->hd_date->copy();

            // Generate if the next schedule is within the next 14 days
            while ($targetDate->diffInDays($today, false) <= 14) {
                $currentDayOfWeek = $targetDate->dayOfWeek; // 0 (Sun) to 6 (Sat)
                $addDays = 3; // Default fallback

                if ($frequency === '2x per minggu') {
                    // Pattern: Mon-Thu (3-4), Tue-Fri (3-4), Wed-Sat (3-4)
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3 => 3, // Mon, Tue, Wed -> add 3 days
                        4, 5, 6 => 4, // Thu, Fri, Sat -> add 4 days
                        0 => 1,       // Sun -> skip to Mon
                    };
                } elseif ($frequency === '3x per minggu') {
                    // Pattern: Mon-Wed-Fri (2-2-3), Tue-Thu-Sat (2-2-3)
                    $addDays = match ($currentDayOfWeek) {
                        1, 2, 3, 4 => 2, // Mon, Tue, Wed, Thu -> add 2 days
                        5, 6 => 3,       // Fri, Sat -> add 3 days
                        0 => 1,          // Sun -> skip to Mon
                    };
                } elseif ($frequency === '1x per minggu') {
                    $addDays = 7;
                }

                $targetDate = $targetDate->addDays($addDays);

                // Don't generate for Sunday (standard dialysis practice)
                if ($targetDate->dayOfWeek === 0) {
                    $targetDate = $targetDate->addDays(1);
                }

                $exists = DialysisSchedule::where('patient_id', $patient->id)
                    ->whereDate('hd_date', $targetDate->toDateString())
                    ->exists();

                if (! $exists) {
                    $dayName = $dayNames[$targetDate->dayOfWeek];

                    DialysisSchedule::create([
                        'patient_id' => $patient->id,
                        'hd_date' => $targetDate->toDateString(),
                        'day_name' => $dayName,
                        'start_time' => $latestSchedule->start_time,
                        'end_time' => $latestSchedule->end_time,
                        'shift' => $latestSchedule->shift,
                        'room' => $latestSchedule->room,
                        'machine_number' => $latestSchedule->machine_number,
                        'doctor_name' => $latestSchedule->doctor_name,
                        'nurse_name' => $latestSchedule->nurse_name,
                        'attendance_status' => 'Terjadwal',
                        'notes' => "Otomatis dibuat oleh sistem (Pola {$frequency})",
                    ]);

                    $count++;
                }
            }
        }

        $this->info("Generated {$count} upcoming schedules.");

        return self::SUCCESS;
    }
}
