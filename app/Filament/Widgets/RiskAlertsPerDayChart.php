<?php

namespace App\Filament\Widgets;

use App\Models\RiskAlert;
use Filament\Widgets\ChartWidget;

class RiskAlertsPerDayChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Jumlah Alert per Hari';

    protected function getData(): array
    {
        $rows = RiskAlert::query()
            ->whereDate('alert_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('alert_date as label, count(*) as value')
            ->groupBy('alert_date')
            ->orderBy('alert_date')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Jumlah Alert',
                'data' => $rows->pluck('value')->map(fn ($value) => (int) $value)->all(),
            ]],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
