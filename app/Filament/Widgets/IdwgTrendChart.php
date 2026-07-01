<?php

namespace App\Filament\Widgets;

use App\Models\DialysisSession;
use Filament\Widgets\ChartWidget;

class IdwgTrendChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Tren IDWG';

    protected function getData(): array
    {
        $rows = DialysisSession::query()
            ->whereNotNull('idwg_percent')
            ->whereDate('session_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('session_date as label, avg(idwg_percent) as value')
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Rata-rata IDWG %',
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
