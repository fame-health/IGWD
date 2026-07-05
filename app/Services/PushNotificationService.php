<?php

namespace App\Services;

use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\RiskAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PushNotificationService
{
    public function __construct(private readonly FirebaseCloudMessagingService $fcm) {}

    public function sendDialysisScheduleCreated(DialysisSchedule $schedule): void
    {
        $scheduledAt = $this->scheduleDateTime($schedule);

        $this->fcm->sendToUserIds(
            $this->patientUserIds($schedule->patient_id),
            'Jadwal HD Baru',
            'Jadwal HD Anda pada '.$this->scheduleLabel($schedule, $scheduledAt).' telah dibuat.',
            $this->scheduleData($schedule, 'dialysis_schedule', $scheduledAt),
            'schedule_reminders'
        );
    }

    public function sendDialysisScheduleUpdated(DialysisSchedule $schedule): void
    {
        $scheduledAt = $this->scheduleDateTime($schedule);

        $this->fcm->sendToUserIds(
            $this->patientUserIds($schedule->patient_id),
            'Jadwal HD Diperbarui',
            'Jadwal HD Anda pada '.$this->scheduleLabel($schedule, $scheduledAt).' telah diperbarui.',
            $this->scheduleData($schedule, 'dialysis_schedule', $scheduledAt),
            'schedule_reminders'
        );
    }

    public function sendDialysisScheduleReminder(DialysisSchedule $schedule, string $reminderType, ?Carbon $scheduledAt = null): void
    {
        $scheduledAt ??= $this->scheduleDateTime($schedule);
        $title = $reminderType === 'h_minus_1_day'
            ? 'Reminder Jadwal HD Besok'
            : 'Reminder Jadwal HD';
        $body = $reminderType === 'h_minus_1_day'
            ? 'Jadwal HD Anda besok pada '.$this->scheduleLabel($schedule, $scheduledAt).'.'
            : 'Jadwal HD Anda dimulai sekitar 2 jam lagi pada '.$this->scheduleLabel($schedule, $scheduledAt).'.';

        $this->fcm->sendToUserIds(
            $this->patientUserIds($schedule->patient_id),
            $title,
            $body,
            [
                ...$this->scheduleData($schedule, 'schedule_reminder', $scheduledAt),
                'reminder_type' => $reminderType,
            ],
            'schedule_reminders'
        );
    }

    public function sendRiskAlertCreated(RiskAlert $riskAlert): void
    {
        $riskAlert->loadMissing('patient');

        // Send to patient
        $this->fcm->sendToUserIds(
            $this->patientUserIds($riskAlert->patient_id),
            $riskAlert->title,
            $riskAlert->message,
            [
                'type' => 'risk_alert',
                'id' => 'alert_' . $riskAlert->id,
                'risk_alert_id' => $riskAlert->id,
                'patient_id' => $riskAlert->patient_id,
                'alert_level' => $riskAlert->alert_level,
                'alert_type' => $riskAlert->alert_type,
                'alert_date' => $riskAlert->alert_date?->toDateString(),
                'alert_time' => $riskAlert->alert_time,
            ],
            'risk_alerts'
        );

        // Send to staff (doctors and nurses)
        $staffIds = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['perawat', 'dokter'])
            ->pluck('id');

        if ($staffIds->isNotEmpty()) {
            $staffTitle = "Alert Risiko: " . ($riskAlert->patient?->name ?? 'Pasien');
            $this->fcm->sendToUserIds(
                $staffIds,
                $staffTitle,
                $riskAlert->message,
                [
                    'type' => 'risk_alert',
                    'id' => 'alert_' . $riskAlert->id,
                    'risk_alert_id' => $riskAlert->id,
                    'patient_id' => $riskAlert->patient_id,
                    'alert_level' => $riskAlert->alert_level,
                    'patient_name' => $riskAlert->patient?->name,
                ],
                'risk_alerts'
            );
        }
    }

    public function sendDailyMonitoringUpdated(DailyMonitoring $monitoring): void
    {
        $this->fcm->sendToUserIds(
            $this->patientUserIds($monitoring->patient_id),
            'Data Monitoring Diperbarui',
            'Data monitoring harian Anda telah diperbarui oleh petugas.',
            [
                'type' => 'data_update',
                'id' => 'monitoring_' . $monitoring->id,
                'monitoring_id' => $monitoring->id,
                'patient_id' => $monitoring->patient_id,
            ],
            'risk_alerts'
        );
    }

    public function sendDialysisSessionUpdated(DialysisSession $session): void
    {
        $this->fcm->sendToUserIds(
            $this->patientUserIds($session->patient_id),
            'Data Hemodialisis Diperbarui',
            'Data sesi hemodialisis Anda telah diperbarui.',
            [
                'type' => 'data_update',
                'id' => 'session_' . $session->id,
                'dialysis_session_id' => $session->id,
                'patient_id' => $session->patient_id,
            ],
            'risk_alerts'
        );
    }

    public function scheduleDateTime(DialysisSchedule $schedule): Carbon
    {
        $date = $schedule->hd_date?->toDateString() ?? now($this->timezone())->toDateString();
        $time = config('hd.shift_times.'.$schedule->shift, config('hd.shift_times.Pagi', '08:00'));

        return Carbon::parse($date.' '.$time, $this->timezone());
    }

    private function patientUserIds(int $patientId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('role', 'pasien')
            ->where('patient_id', $patientId)
            ->pluck('id');
    }

    private function scheduleLabel(DialysisSchedule $schedule, Carbon $scheduledAt): string
    {
        return $scheduledAt->format('d/m/Y H:i').' shift '.$schedule->shift;
    }

    private function scheduleData(DialysisSchedule $schedule, string $type, Carbon $scheduledAt): array
    {
        return [
            'type' => $type,
            'id' => 'schedule_' . $schedule->id,
            'dialysis_schedule_id' => $schedule->id,
            'patient_id' => $schedule->patient_id,
            'hd_date' => $schedule->hd_date?->toDateString(),
            'shift' => $schedule->shift,
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ];
    }

    private function timezone(): string
    {
        return config('hd.timezone', config('app.timezone', 'Asia/Jakarta'));
    }
}
