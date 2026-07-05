<?php

namespace App\Console\Commands;

use App\Models\DialysisSchedule;
use App\Models\DialysisScheduleReminder;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDialysisScheduleReminders extends Command
{
    protected $signature = 'app:send-dialysis-schedule-reminders {--dry-run}';

    protected $description = 'Mengirim reminder H-1 dan H-2 jam untuk jadwal HD pasien.';

    public function handle(PushNotificationService $pushNotificationService): int
    {
        $timezone = config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
        $now = now($timezone);
        $windowStart = $now->copy()->subMinutes(max(1, (int) config('hd.reminder_window_minutes', 10)));
        $reminders = config('hd.reminders', []);
        $sent = 0;
        $due = 0;

        DialysisSchedule::query()
            ->where('attendance_status', 'Terjadwal')
            ->whereDate('hd_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('hd_date', '<=', $now->copy()->addDays(2)->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($schedules) use ($pushNotificationService, $reminders, $windowStart, $now, &$due, &$sent): void {
                foreach ($schedules as $schedule) {
                    $scheduledAt = $pushNotificationService->scheduleDateTime($schedule);

                    foreach ($reminders as $reminderType => $hoursBefore) {
                        $dueAt = $scheduledAt->copy()->subHours((int) $hoursBefore);

                        if (! $this->isDue($dueAt, $windowStart, $now)) {
                            continue;
                        }

                        $due++;

                        if ($this->reminderAlreadySent($schedule->id, $reminderType)) {
                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->line("Due: jadwal {$schedule->id} {$reminderType} pada ".$dueAt->toDateTimeString());

                            continue;
                        }

                        $reminder = DialysisScheduleReminder::query()->create([
                            'dialysis_schedule_id' => $schedule->id,
                            'reminder_type' => $reminderType,
                            'scheduled_at' => $scheduledAt,
                            'due_at' => $dueAt,
                        ]);

                        $pushNotificationService->sendDialysisScheduleReminder($schedule, $reminderType, $scheduledAt);

                        $reminder->forceFill(['sent_at' => now()])->save();
                        $sent++;
                    }
                }
            });

        $this->info("Reminder due: {$due}, dikirim: {$sent}");

        return self::SUCCESS;
    }

    private function isDue(Carbon $dueAt, Carbon $windowStart, Carbon $now): bool
    {
        return $dueAt->greaterThanOrEqualTo($windowStart) && $dueAt->lessThanOrEqualTo($now);
    }

    private function reminderAlreadySent(int $scheduleId, string $reminderType): bool
    {
        return DialysisScheduleReminder::query()
            ->where('dialysis_schedule_id', $scheduleId)
            ->where('reminder_type', $reminderType)
            ->exists();
    }
}
