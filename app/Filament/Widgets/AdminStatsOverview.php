<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DailyMonitorings\DailyMonitoringResource;
use App\Filament\Resources\DialysisSessions\DialysisSessionResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\RiskAlerts\RiskAlertResource;
use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class AdminStatsOverview extends Widget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-stats-overview';

    protected function getViewData(): array
    {
        $today = now()->toDateString();

        $activePatients = Patient::where('patient_status', 'Aktif')->count();
        $todaySchedules = DialysisSchedule::whereDate('hd_date', $today)->count();
        $todaySessions = DialysisSession::whereDate('session_date', $today)->count();
        $highRiskPatients = RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])
            ->distinct('patient_id')
            ->count('patient_id');
        $fluidLimitAlerts = RiskAlert::where('alert_type', 'Cairan Melebihi Batas')->count();
        $newAlerts = RiskAlert::where('status', 'Baru')->count();
        $watchedPatients = DailyMonitoring::whereIn('risk_status', ['Waspada', 'Tinggi', 'Darurat'])
            ->distinct('patient_id')
            ->count('patient_id');
        $unresolvedAlerts = RiskAlert::whereIn('status', ['Baru', 'Dibaca'])->count();

        return [
            'headline' => [
                'label' => 'Pasien Risiko Tinggi',
                'value' => $highRiskPatients,
                'meta' => 'Pasien unik dengan alert Tinggi atau Darurat',
                'icon' => Heroicon::OutlinedShieldExclamation,
                'url' => RiskAlertResource::getUrl(),
            ],
            'cards' => [
                [
                    'label' => 'Pasien Aktif',
                    'value' => $activePatients,
                    'meta' => 'Basis monitoring',
                    'icon' => Heroicon::OutlinedUserGroup,
                    'tone' => 'success',
                    'url' => PatientResource::getUrl(),
                ],
                [
                    'label' => 'HD Hari Ini',
                    'value' => "{$todaySessions}/{$todaySchedules}",
                    'meta' => 'Sesi / jadwal',
                    'icon' => Heroicon::OutlinedCalendarDays,
                    'tone' => 'info',
                    'url' => DialysisSessionResource::getUrl(),
                ],
                [
                    'label' => 'Alert Baru',
                    'value' => $newAlerts,
                    'meta' => 'Belum dibaca',
                    'icon' => Heroicon::OutlinedBellAlert,
                    'tone' => 'danger',
                    'url' => RiskAlertResource::getUrl(),
                ],
                [
                    'label' => 'Pasien Perlu Dipantau',
                    'value' => $watchedPatients,
                    'meta' => 'Waspada sampai Darurat',
                    'icon' => Heroicon::OutlinedEye,
                    'tone' => 'slate',
                    'url' => DailyMonitoringResource::getUrl(),
                ],
            ],
            'followUps' => [
                [
                    'label' => 'Alert belum selesai',
                    'value' => $unresolvedAlerts,
                    'tone' => 'warning',
                    'url' => RiskAlertResource::getUrl(),
                ],
                [
                    'label' => 'Cairan melebihi batas',
                    'value' => $fluidLimitAlerts,
                    'tone' => 'warning',
                    'url' => DailyMonitoringResource::getUrl(),
                ],
            ],
            'updatedAt' => now()->format('d M Y, H:i'),
        ];
    }
}
