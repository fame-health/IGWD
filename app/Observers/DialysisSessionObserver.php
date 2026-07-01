<?php

namespace App\Observers;

use App\Models\DialysisSession;
use App\Services\IdwgCalculationService;
use App\Services\RiskAlertService;
use Illuminate\Support\Facades\Auth;

class DialysisSessionObserver
{
    public function saving(DialysisSession $dialysisSession): void
    {
        if (! $dialysisSession->created_by && Auth::id()) {
            $dialysisSession->created_by = Auth::id();
        }

        if (Auth::id()) {
            $dialysisSession->updated_by = Auth::id();
        }

        $dialysisSession->forceFill(app(IdwgCalculationService::class)->calculate($dialysisSession));
    }

    public function saved(DialysisSession $dialysisSession): void
    {
        app(RiskAlertService::class)->checkDialysisSession($dialysisSession);
    }
}
