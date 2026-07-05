<?php

namespace App\Filament\Widgets;

use App\Models\DailyMonitoring;
use Filament\Widgets\ChartWidget;

class DailyWeightTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected string $color = 'success';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $maxHeight = '300px';
    
    protected ?string $pollingInterval = '30s';

    public function getHeading(): ?string
    {
        return '⚖️ Monitoring Berat Badan Harian';
    }

    public function getDescription(): ?string
    {
        return 'Rata-rata berat badan pasien yang di-monitoring setiap hari dalam 14 hari terakhir';
    }

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
                'label' => 'Rata-rata Berat Badan (kg)',
                'data' => $rows->pluck('value')->map(fn ($value) => round((float) $value, 2))->all(),
                'borderColor' => 'rgb(16, 185, 129)',
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.4,
                'pointBackgroundColor' => 'rgb(16, 185, 129)',
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 5,
                'pointHoverRadius' => 7,
                'pointHoverBackgroundColor' => 'rgb(16, 185, 129)',
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
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toFixed(2) + " kg"; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'grid' => [
                        'display' => true,
                        'drawBorder' => false,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return value + " kg"; }',
                        'font' => [
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Berat Badan (kg)',
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
