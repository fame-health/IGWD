<?php

namespace App\Filament\Pages;

use App\Filament\Support\ResourceUi;
use App\Models\DailyMonitoring;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class DailyMonitoringReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Monitoring Harian';

    protected static ?string $title = 'Laporan Monitoring Harian';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.report-table';

    public function table(Table $table): Table
    {
        return $table
            ->query(DailyMonitoring::query()->with('patient')->latest('monitoring_date'))
            ->columns(ResourceUi::columns('daily_monitorings'))
            ->filters(ResourceUi::filters('daily_monitorings'));
    }
}
