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

        // We run this every day now, so we remove the day-of-week check

        $patients = Patient::where('patient_status', 'Aktif')->get();
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
            // Get the latest schedule for this patient to determine the next one
            $latestSchedule = DialysisSchedule::where('patient_id', $patient->id)
                ->orderBy('hd_date', 'desc')
                ->first();

            if (! $latestSchedule) {
                // Admin must create the first schedule manually for new patients
                continue;
            }

            // Target date is 3 days after the latest schedule
            $targetDate = $latestSchedule->hd_date->copy()->addDays(3);

            // Generate if the next schedule is within the next 10 days
            // This ensures patients have their next ~3 sessions visible
            while ($targetDate->diffInDays($today, false) <= 10) {
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
                        'notes' => 'Otomatis dibuat oleh sistem (Interval 3 Hari)',
                    ]);

                    $count++;
                }

                // Move to the next potential schedule date for the while loop
                $targetDate = $targetDate->addDays(3);
            }
        }

        $this->info("Generated {$count} upcoming schedules.");

        return self::SUCCESS;
    }
}
