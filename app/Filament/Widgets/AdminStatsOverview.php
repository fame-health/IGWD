<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();

        return [
            Stat::make('Total Pasien Aktif', Patient::where('patient_status', 'Aktif')->count())->color('success'),
            Stat::make('Jadwal HD Hari Ini', DialysisSchedule::whereDate('hd_date', $today)->count())->color('info'),
            Stat::make('Pasien Risiko Tinggi', RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->distinct('patient_id')->count('patient_id'))->color('danger'),
            Stat::make('Alert Cairan Melebihi Batas', RiskAlert::where('alert_type', 'Cairan Melebihi Batas')->count())->color('danger'),
            Stat::make('Total Alert Baru', RiskAlert::where('status', 'Baru')->count())->color('danger'),
            Stat::make('Alert Tinggi dan Darurat', RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->count())->color('danger'),
            Stat::make('Pasien Perlu Dipantau', DailyMonitoring::whereIn('risk_status', ['Waspada', 'Tinggi', 'Darurat'])->distinct('patient_id')->count('patient_id'))->color('warning'),
            Stat::make('Alert Belum Ditindaklanjuti', RiskAlert::whereIn('status', ['Baru', 'Dibaca'])->count())->color('warning'),
            Stat::make('Sesi HD Hari Ini', DialysisSession::whereDate('session_date', $today)->count())->color('info'),
        ];
    }
}
