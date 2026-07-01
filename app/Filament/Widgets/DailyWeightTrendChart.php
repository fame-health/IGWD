<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use Filament\Widgets\ChartWidget;

class DailyWeightTrendChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Monitoring Berat Badan Harian';

    protected function getData(): array
    {
        $rows = DailyMonitoring::query()
            ->whereDate('monitoring_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('monitoring_date as label, avg(today_weight) as value')
            ->groupBy('monitoring_date')
            ->orderBy('monitoring_date')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Rata-rata BB',
                'data' => $rows->pluck('value')->map(fn ($value) => round((float) $value, 2))->all(),
            ]],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
