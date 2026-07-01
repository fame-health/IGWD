<?php

namespace App\Observers;

use App\Models\RiskSymptom;
use App\Services\RiskAlertService;
use Illuminate\Support\Facades\Auth;

class RiskSymptomObserver
{
    public function saving(RiskSymptom $riskSymptom): void
    {
        if (! $riskSymptom->created_by && Auth::id()) {
            $riskSymptom->created_by = Auth::id();
        }

        if (Auth::id()) {
            $riskSymptom->updated_by = Auth::id();
        }

        $riskSymptom->system_risk_status = app(RiskAlertService::class)->riskStatusForSymptom($riskSymptom);
    }

    public function saved(RiskSymptom $riskSymptom): void
    {
        app(RiskAlertService::class)->checkRiskSymptom($riskSymptom);
    }
}
