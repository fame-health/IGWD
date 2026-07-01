<?php

namespace App\Filament\Pages;

use App\Filament\Support\ResourceUi;
use App\Models\DialysisSession;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class DialysisSessionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Sesi HD';

    protected static ?string $title = 'Laporan Sesi HD';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.report-table';

    public function table(Table $table): Table
    {
        return $table
            ->query(DialysisSession::query()->with('patient')->latest('session_date'))
            ->columns(ResourceUi::columns('sessions'))
            ->filters(ResourceUi::filters('sessions'));
    }
}
