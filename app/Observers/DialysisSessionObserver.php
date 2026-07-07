<?php

namespace App\Observers;

use App\Models\DialysisSession;
use App\Services\IdwgCalculationService;
use App\Services\PushNotificationService;
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
        if ($dialysisSession->dialysis_schedule_id) {
            $dialysisSession->schedule()
                ->where('attendance_status', 'Terjadwal')
                ->update(['attendance_status' => 'Hadir']);
        }

        app(RiskAlertService::class)->checkDialysisSession($dialysisSession);

        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'perawat', 'dokter'])) {
            app(PushNotificationService::class)->sendDialysisSessionUpdated($dialysisSession);
        }
    }
}
