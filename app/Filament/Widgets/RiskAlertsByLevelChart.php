<?php

namespace App\Filament\Widgets;

use App\Models\RiskAlert;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class RiskAlertsByLevelChart extends ChartWidget
{
    private const LEVELS = ['Normal', 'Waspada', 'Tinggi', 'Darurat'];

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected string $color = 'danger';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = '30s';

    public function getHeading(): ?string
    {
        return 'Distribusi Level Alert';
    }

    public function getDescription(): ?string
    {
        $total = RiskAlert::query()->count();

        return 'Proporsi alert berdasarkan tingkat risiko. Total '.number_format($total, 0, ',', '.').' alert tercatat.';
    }

    protected function getData(): array
    {
        $counts = RiskAlert::query()
            ->selectRaw('alert_level, count(*) as total')
            ->groupBy('alert_level')
            ->pluck('total', 'alert_level');

        $data = array_map(fn ($level) => (int) ($counts[$level] ?? 0), self::LEVELS);

        return [
            'datasets' => [[
                'label' => 'Jumlah Alert',
                'data' => $data,
                'backgroundColor' => [
                    'rgba(16, 185, 129, 0.92)',
                    'rgba(245, 158, 11, 0.92)',
                    'rgba(249, 115, 22, 0.94)',
                    'rgba(225, 29, 72, 0.94)',
                ],
                'borderWidth' => 4,
                'borderRadius' => 8,
                'hoverOffset' => 8,
                'spacing' => 3,
            ]],
            'labels' => self::LEVELS,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                cutout: '68%',
                radius: '90%',
                layout: {
                    padding: {
                        top: 4,
                        right: 8,
                        bottom: 0,
                        left: 8,
                    },
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxHeight: 8,
                            boxWidth: 8,
                            padding: 14,
                            pointStyle: 'circle',
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: 600,
                            },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.94)',
                        borderColor: 'rgba(148, 163, 184, 0.25)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        titleFont: {
                            size: 13,
                            weight: 700,
                        },
                        bodyFont: {
                            size: 12,
                            weight: 500,
                        },
                        callbacks: {
                            label: function (context) {
                                const values = context.dataset.data || [];
                                const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
                                const value = Number(context.parsed || 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                return `${context.label}: ${value} alert (${percentage}%)`;
                            },
                        },
                    },
                },
            }
        JS);
    }
}
