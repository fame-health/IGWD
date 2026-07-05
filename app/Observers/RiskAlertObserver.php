<?php

namespace App\Observers;

use App\Models\RiskAlert;
use App\Services\PushNotificationService;

class RiskAlertObserver
{
    public function created(RiskAlert $riskAlert): void
    {
        app(PushNotificationService::class)->sendRiskAlertCreated($riskAlert);
    }
}
