<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\RiskAlert;
use Filament\Widgets\Widget;

class CombinedChartsWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.combined-charts';

    protected function getViewData(): array
    {
        // IDWG Trend Data
        $idwgRows = DialysisSession::query()
            ->whereNotNull('idwg_percent')
            ->whereDate('session_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('session_date as label, avg(idwg_percent) as value')
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get();

        // Daily Weight Data
        $weightRows = DailyMonitoring::query()
            ->whereDate('monitoring_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('monitoring_date as label, avg(today_weight) as value')
            ->groupBy('monitoring_date')
            ->orderBy('monitoring_date')
            ->get();

        // Risk Alerts Per Day
        $alertRows = RiskAlert::query()
            ->whereDate('alert_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('alert_date as label, count(*) as value')
            ->groupBy('alert_date')
            ->orderBy('alert_date')
            ->get();

        return [
            'charts' => [
                [
                    'id' => 'idwgChart',
                    'title' => 'Tren IDWG',
                    'subtitle' => 'Rata-rata kenaikan BB antar dialisis (Target: <5%)',
                    'icon' => 'heroicon-o-chart-bar',
                    'color' => 'blue',
                    'unit' => '%',
                    'data' => [
                        'labels' => $idwgRows->pluck('label')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
                        'values' => $idwgRows->pluck('value')->map(fn($v) => round((float)$v, 2))->all(),
                    ],
                ],
                [
                    'id' => 'weightChart',
                    'title' => 'Berat Badan Harian',
                    'subtitle' => 'Rata-rata monitoring berat badan pasien',
                    'icon' => 'heroicon-o-scale',
                    'color' => 'green',
                    'unit' => 'kg',
                    'data' => [
                        'labels' => $weightRows->pluck('label')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
                        'values' => $weightRows->pluck('value')->map(fn($v) => round((float)$v, 2))->all(),
                    ],
                ],
                [
                    'id' => 'alertChart',
                    'title' => 'Volume Alert',
                    'subtitle' => 'Jumlah notifikasi risiko per hari',
                    'icon' => 'heroicon-o-bell-alert',
                    'color' => 'red',
                    'unit' => '',
                    'data' => [
                        'labels' => $alertRows->pluck('label')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
                        'values' => $alertRows->pluck('value')->map(fn($v) => (int)$v)->all(),
                    ],
                ],
            ],
        ];
    }
}
