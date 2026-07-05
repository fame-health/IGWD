<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use Filament\Widgets\Widget;

class QuickInsightsWidget extends Widget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-insights';

    protected function getViewData(): array
    {
        $today = now()->toDateString();
        $thisWeek = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();

        // Compliance Rate - berapa persen pasien yang melakukan monitoring hari ini
        $activePatientsCount = Patient::where('patient_status', 'Aktif')->count();
        $todayMonitoringCount = DailyMonitoring::whereDate('monitoring_date', $today)->distinct('patient_id')->count('patient_id');
        $complianceRate = $activePatientsCount > 0 ? round(($todayMonitoringCount / $activePatientsCount) * 100, 1) : 0;

        // Average session duration this week
        $avgSessionDuration = DialysisSession::whereDate('session_date', '>=', $thisWeek)
            ->whereNotNull('dialysis_duration')
            ->avg('dialysis_duration');
        $avgSessionDuration = $avgSessionDuration ? round($avgSessionDuration, 1) : 0;

        // Critical alerts today
        $criticalAlertsToday = RiskAlert::whereDate('alert_date', $today)
            ->whereIn('alert_level', ['Tinggi', 'Darurat'])
            ->where('status', 'Baru')
            ->count();

        // Week over week comparison
        $thisWeekAlerts = RiskAlert::whereDate('alert_date', '>=', $thisWeek)->count();
        $lastWeekAlerts = RiskAlert::whereBetween('alert_date', [$lastWeek, $thisWeek])->count();
        $alertTrend = $lastWeekAlerts > 0 ? round((($thisWeekAlerts - $lastWeekAlerts) / $lastWeekAlerts) * 100, 1) : 0;
        $alertTrendDirection = $alertTrend > 0 ? 'up' : ($alertTrend < 0 ? 'down' : 'stable');

        // Patients needing attention (high risk or overdue monitoring)
        $patientsNeedingAttention = RiskAlert::where('status', 'Baru')
            ->whereIn('alert_level', ['Tinggi', 'Darurat'])
            ->distinct('patient_id')
            ->count('patient_id');

        // High IDWG percentage patients this week
        $highIdwgPatients = DialysisSession::whereDate('session_date', '>=', $thisWeek)
            ->where('idwg_percent', '>', 5)
            ->distinct('patient_id')
            ->count('patient_id');

        return [
            'insights' => [
                [
                    'label' => 'Kepatuhan Monitoring',
                    'value' => $complianceRate,
                    'unit' => '%',
                    'description' => "{$todayMonitoringCount} dari {$activePatientsCount} pasien aktif",
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'color' => $complianceRate >= 80 ? 'success' : ($complianceRate >= 60 ? 'warning' : 'danger'),
                ],
                [
                    'label' => 'Durasi Rata-rata HD',
                    'value' => $avgSessionDuration,
                    'unit' => 'jam',
                    'description' => 'Minggu ini',
                    'icon' => 'heroicon-o-clock',
                    'color' => 'info',
                ],
                [
                    'label' => 'Alert Kritis Hari Ini',
                    'value' => $criticalAlertsToday,
                    'unit' => 'alert',
                    'description' => 'Perlu tindakan segera',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => $criticalAlertsToday > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Pasien Perlu Perhatian',
                    'value' => $patientsNeedingAttention,
                    'unit' => 'pasien',
                    'description' => 'Risiko tinggi atau darurat',
                    'icon' => 'heroicon-o-user-group',
                    'color' => $patientsNeedingAttention > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'IDWG > 5%',
                    'value' => $highIdwgPatients,
                    'unit' => 'pasien',
                    'description' => 'Minggu ini',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => $highIdwgPatients > 3 ? 'warning' : 'info',
                ],
                [
                    'label' => 'Tren Alert Mingguan',
                    'value' => abs($alertTrend),
                    'unit' => '%',
                    'description' => $alertTrendDirection === 'up' ? 'Meningkat' : ($alertTrendDirection === 'down' ? 'Menurun' : 'Stabil'),
                    'icon' => $alertTrendDirection === 'up' ? 'heroicon-o-arrow-trending-up' : ($alertTrendDirection === 'down' ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus'),
                    'color' => $alertTrendDirection === 'up' ? 'danger' : ($alertTrendDirection === 'down' ? 'success' : 'info'),
                ],
            ],
        ];
    }
}
