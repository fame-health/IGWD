<?php

namespace App\Filament\Widgets;

use App\Models\RiskAlert;
use Filament\Widgets\ChartWidget;

class RiskAlertsByLevelChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Alert Berdasarkan Level';

    protected function getData(): array
    {
        $levels = ['Normal', 'Waspada', 'Tinggi', 'Darurat'];
        $counts = RiskAlert::query()
            ->selectRaw('alert_level, count(*) as total')
            ->groupBy('alert_level')
            ->pluck('total', 'alert_level');

        return [
            'datasets' => [[
                'label' => 'Alert',
                'data' => array_map(fn ($level) => (int) ($counts[$level] ?? 0), $levels),
            ]],
            'labels' => $levels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
