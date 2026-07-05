<?php

namespace App\Filament\Resources\DailyMonitorings;

use App\Filament\Resources\DailyMonitorings\Pages\ManageDailyMonitorings;
use App\Filament\Support\ResourceUi;
use App\Models\DailyMonitoring;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyMonitoringResource extends Resource
{
    protected static ?string $model = DailyMonitoring::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'Monitoring Harian';

    protected static ?string $pluralModelLabel = 'Monitoring Harian';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring Harian';

    protected static ?string $navigationLabel = 'Monitoring Harian';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('daily_monitorings'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('daily_monitorings'),
            ])
            ->filters([
                ...ResourceUi::filters('daily_monitorings'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDailyMonitorings::route('/'),
        ];
    }
}
