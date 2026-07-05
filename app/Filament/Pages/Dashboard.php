<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DailyMonitorings\DailyMonitoringResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\RiskAlerts\RiskAlertResource;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\CombinedChartsWidget;
use App\Filament\Widgets\LatestRiskAlertsTable;
use App\Filament\Widgets\RiskAlertsByLevelChart;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard IDWG';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Monitoring real-time cairan, berat badan, dan risiko pasien hemodialisis';
    }

    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 4,
        ];
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            AdminStatsOverview::class,
            CombinedChartsWidget::class,
            RiskAlertsByLevelChart::class,
            LatestRiskAlertsTable::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('patients')
                ->label('Data Pasien')
                ->icon(Heroicon::OutlinedUserGroup)
                ->url(PatientResource::getUrl())
                ->color('gray'),
            Action::make('dailyMonitoring')
                ->label('Monitoring')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(DailyMonitoringResource::getUrl())
                ->color('info'),
            Action::make('riskAlerts')
                ->label('Alert')
                ->icon(Heroicon::OutlinedBellAlert)
                ->url(RiskAlertResource::getUrl())
                ->color('danger')
                ->badge(fn() => \App\Models\RiskAlert::where('status', 'Baru')->count())
                ->badgeColor('danger'),
        ];
    }
}
