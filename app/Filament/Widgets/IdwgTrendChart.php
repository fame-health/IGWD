<?php

namespace App\Filament\Widgets;

use App\Models\DialysisSession;
use Filament\Widgets\ChartWidget;

class IdwgTrendChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected string $color = 'info';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $maxHeight = '300px';
    
    protected ?string $pollingInterval = '30s';

    public function getHeading(): ?string
    {
        return '📊 Tren IDWG (Interdialytic Weight Gain)';
    }

    public function getDescription(): ?string
    {
        return 'Persentase kenaikan berat badan pasien antara sesi dialisis dalam 14 hari terakhir. Target ideal: < 5%';
    }

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
                'label' => 'Rata-rata IDWG (%)',
                'data' => $rows->pluck('value')->map(fn ($value) => round((float) $value, 2))->all(),
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.4,
                'pointBackgroundColor' => 'rgb(59, 130, 246)',
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 5,
                'pointHoverRadius' => 7,
                'pointHoverBackgroundColor' => 'rgb(59, 130, 246)',
                'pointHoverBorderWidth' => 3,
            ]],
            'labels' => $rows->pluck('label')->map(fn($date) => \Carbon\Carbon::parse($date)->format('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'titleFont' => [
                        'size' => 14,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'size' => 13,
                    ],
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toFixed(2) + "%"; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'drawBorder' => false,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                        'font' => [
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Persentase IDWG',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Tanggal',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
        ];
    }
}
