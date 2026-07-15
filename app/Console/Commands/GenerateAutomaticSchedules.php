<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\DialysisSchedule;
use Carbon\Carbon;
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
    protected $description = 'Generate dialysis schedules automatically for Monday and Friday.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\PushNotificationService $pushNotificationService): int
    {
        $today = now();
        $dayOfWeek = $today->dayOfWeek;

        // We only generate for Monday (1) and Friday (5) as requested
        if (!in_array($dayOfWeek, [1, 5])) {
            $this->info("Today is not Monday or Friday. Skipping.");
            return self::SUCCESS;
        }

        $dayNames = [
            1 => 'Senin',
            5 => 'Jumat',
        ];
        $dayName = $dayNames[$dayOfWeek];

        $patients = Patient::where('patient_status', 'Aktif')->get();
        $count = 0;

        foreach ($patients as $patient) {
            // Check if schedule already exists for today
            $exists = DialysisSchedule::where('patient_id', $patient->id)
                ->whereDate('hd_date', $today->toDateString())
                ->exists();

            if (!$exists) {
                // Get shift from the most recent previous schedule
                $lastSchedule = DialysisSchedule::where('patient_id', $patient->id)
                    ->whereDate('hd_date', '<', $today->toDateString())
                    ->orderBy('hd_date', 'desc')
                    ->first();

                $shift = $lastSchedule ? $lastSchedule->shift : 'Pagi';

                $schedule = DialysisSchedule::create([
                    'patient_id' => $patient->id,
                    'hd_date' => $today->toDateString(),
                    'day_name' => $dayName,
                    'shift' => $shift,
                    'attendance_status' => 'Terjadwal',
                    'notes' => 'Otomatis dibuat oleh sistem (Jadwal Rutin)',
                ]);

                // Send notification
                $pushNotificationService->sendDialysisScheduleCreated($schedule);

                $count++;
            }
        }

        $this->info("Generated {$count} schedules and sent notifications for {$dayName}.");

        return self::SUCCESS;
    }
}
