<?php

namespace App\Observers;

use App\Models\DailyMonitoring;
use App\Services\DailyMonitoringCalculationService;
use App\Services\PushNotificationService;
use App\Services\RiskAlertService;
use Illuminate\Support\Facades\Auth;

class DailyMonitoringObserver
{
    public function saving(DailyMonitoring $dailyMonitoring): void
    {
        if (! $dailyMonitoring->created_by && Auth::id()) {
            $dailyMonitoring->created_by = Auth::id();
        }

        if (Auth::id()) {
            $dailyMonitoring->updated_by = Auth::id();
        }

        $dailyMonitoring->forceFill(app(DailyMonitoringCalculationService::class)->calculate($dailyMonitoring));
    }

    public function saved(DailyMonitoring $dailyMonitoring): void
    {
        $dailyMonitoring->loadMissing('patient.medicalProfile');

        app(RiskAlertService::class)->checkDailyMonitoring($dailyMonitoring);
        app(RiskAlertService::class)->checkPredictionRisk($dailyMonitoring);

        // Jika diupdate oleh admin/staff (bukan oleh pasien sendiri), kirim notifikasi realtime
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'perawat', 'dokter'])) {
            app(PushNotificationService::class)->sendDailyMonitoringUpdated($dailyMonitoring);
        }
    }
}
