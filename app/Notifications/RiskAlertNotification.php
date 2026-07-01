<?php

namespace App\Notifications;

use App\Models\RiskAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RiskAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RiskAlert $riskAlert)
    {
        $this->riskAlert->loadMissing('patient');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'risk_alert_id' => $this->riskAlert->id,
            'patient_id' => $this->riskAlert->patient_id,
            'patient_name' => $this->riskAlert->patient?->name,
            'title' => $this->riskAlert->title,
            'message' => $this->riskAlert->message,
            'alert_level' => $this->riskAlert->alert_level,
            'alert_type' => $this->riskAlert->alert_type,
            'created_at' => $this->riskAlert->created_at?->toISOString(),
        ];
    }
}
