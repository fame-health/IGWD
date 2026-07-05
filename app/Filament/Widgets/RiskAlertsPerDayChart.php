<?php

namespace App\Filament\Widgets;

use App\Models\RiskAlert;
use Filament\Widgets\ChartWidget;

class RiskAlertsPerDayChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected string $color = 'danger';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $maxHeight = '300px';
    
    protected ?string $pollingInterval = '30s';

    public function getHeading(): ?string
    {
        return '📈 Volume Alert Harian';
    }

    public function getDescription(): ?string
    {
        return 'Jumlah notifikasi risiko yang terdeteksi setiap hari dalam 14 hari terakhir';
    }

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
                'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                'borderColor' => 'rgb(239, 68, 68)',
                'borderWidth' => 2,
                'borderRadius' => 8,
                'borderSkipped' => false,
                'hoverBackgroundColor' => 'rgba(239, 68, 68, 1)',
            ]],
            'labels' => $rows->pluck('label')->map(fn($date) => \Carbon\Carbon::parse($date)->format('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y + " alert"; }',
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
                        'stepSize' => 1,
                        'font' => [
                            'size' => 11,
                            'weight' => '600',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Alert',
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
