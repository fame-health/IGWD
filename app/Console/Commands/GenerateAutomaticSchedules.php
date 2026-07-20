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
    protected $description = 'Generate upcoming dialysis schedules automatically from Monday and Friday.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
        $today = now($timezone);
        $dayOfWeek = $today->dayOfWeek;

        // We only generate for Monday (1) and Friday (5) as requested
        if (! in_array($dayOfWeek, [1, 5])) {
            $this->info('Today is not Monday or Friday. Skipping.');

            return self::SUCCESS;
        }

        $upcomingSchedules = [
            1 => [
                'date' => $today->copy()->addDays(4),
                'day_name' => 'Jumat',
            ],
            5 => [
                'date' => $today->copy()->addDays(3),
                'day_name' => 'Senin',
            ],
        ];
        $targetDate = $upcomingSchedules[$dayOfWeek]['date'];
        $dayName = $upcomingSchedules[$dayOfWeek]['day_name'];

        $patients = Patient::where('patient_status', 'Aktif')->get();
        $count = 0;

        foreach ($patients as $patient) {
            // Check if schedule already exists for the upcoming HD day.
            $exists = DialysisSchedule::where('patient_id', $patient->id)
                ->whereDate('hd_date', $targetDate->toDateString())
                ->exists();

            if (! $exists) {
                // Get shift from the most recent previous schedule
                $lastSchedule = DialysisSchedule::where('patient_id', $patient->id)
                    ->whereDate('hd_date', '<', $targetDate->toDateString())
                    ->orderBy('hd_date', 'desc')
                    ->first();

                $shift = $lastSchedule ? $lastSchedule->shift : 'Pagi';

                DialysisSchedule::create([
                    'patient_id' => $patient->id,
                    'hd_date' => $targetDate->toDateString(),
                    'day_name' => $dayName,
                    'shift' => $shift,
                    'attendance_status' => 'Terjadwal',
                    'notes' => 'Otomatis dibuat oleh sistem (Jadwal Rutin)',
                ]);

                $count++;
            }
        }

        $this->info("Generated {$count} schedules for {$dayName} ({$targetDate->toDateString()}).");

        return self::SUCCESS;
    }
}
