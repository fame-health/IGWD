<?php

namespace App\Filament\Pages;

use App\Filament\Support\ResourceUi;
use App\Models\RiskAlert;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class RiskPatientReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Pasien Risiko';

    protected static ?string $title = 'Laporan Pasien Risiko';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.report-table';

    public function table(Table $table): Table
    {
        return $table
            ->query(RiskAlert::query()->with('patient')->whereIn('alert_level', ['Tinggi', 'Darurat'])->latest())
            ->columns(ResourceUi::columns('risk_alerts'))
            ->filters(ResourceUi::filters('risk_alerts'));
    }
}
