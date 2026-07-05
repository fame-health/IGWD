<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use Filament\Widgets\Widget;

class SystemHealthWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.system-health';
    
    protected ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        $today = now()->toDateString();
        
        // Database health - check recent activity
        $recentMonitoring = DailyMonitoring::whereDate('monitoring_date', '>=', now()->subDays(1))->count();
        $recentSessions = DialysisSession::whereDate('session_date', '>=', now()->subDays(1))->count();
        
        // System status
        $totalPatients = Patient::count();
        $activePatients = Patient::where('patient_status', 'Aktif')->count();
        $totalAlerts = RiskAlert::count();
        $unresolvedAlerts = RiskAlert::whereIn('status', ['Baru', 'Dibaca'])->count();
        
        // Data quality indicators
        $todayMonitoringRate = $activePatients > 0 
            ? round((DailyMonitoring::whereDate('monitoring_date', $today)->distinct('patient_id')->count('patient_id') / $activePatients) * 100, 1)
            : 0;
            
        $sessionRecordingRate = $recentSessions > 0 ? 100 : 0;
        
        // Overall system health score (0-100)
        $healthScore = round(
            ($todayMonitoringRate * 0.4) + // 40% weight for monitoring compliance
            ($sessionRecordingRate * 0.3) + // 30% weight for session recording
            (($unresolvedAlerts === 0 ? 100 : max(0, 100 - ($unresolvedAlerts * 5))) * 0.3) // 30% weight for alert resolution
        );
        
        $healthStatus = $healthScore >= 80 ? 'excellent' : ($healthScore >= 60 ? 'good' : ($healthScore >= 40 ? 'fair' : 'poor'));
        
        return [
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'metrics' => [
                [
                    'label' => 'Total Pasien',
                    'value' => $totalPatients,
                    'subtext' => "{$activePatients} aktif",
                    'icon' => 'heroicon-o-users',
                    'status' => 'info',
                ],
                [
                    'label' => 'Aktivitas 24 Jam',
                    'value' => $recentMonitoring + $recentSessions,
                    'subtext' => 'monitoring & sesi',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'status' => $recentMonitoring + $recentSessions > 0 ? 'success' : 'warning',
                ],
                [
                    'label' => 'Total Alert',
                    'value' => $totalAlerts,
                    'subtext' => "{$unresolvedAlerts} belum selesai",
                    'icon' => 'heroicon-o-bell-alert',
                    'status' => $unresolvedAlerts > 10 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Kepatuhan Hari Ini',
                    'value' => $todayMonitoringRate . '%',
                    'subtext' => 'monitoring pasien',
                    'icon' => 'heroicon-o-chart-bar',
                    'status' => $todayMonitoringRate >= 70 ? 'success' : ($todayMonitoringRate >= 50 ? 'warning' : 'danger'),
                ],
            ],
            'lastUpdate' => now()->format('d M Y, H:i:s'),
        ];
    }
}
