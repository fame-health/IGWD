<?php

namespace App\Filament\Resources\RiskAlerts;

use App\Filament\Resources\RiskAlerts\Pages\ManageRiskAlerts;
use App\Filament\Support\ResourceUi;
use App\Models\RiskAlert;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RiskAlertResource extends Resource
{
    protected static ?string $model = RiskAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Notifikasi Risiko Dini';

    protected static ?string $pluralModelLabel = 'Notifikasi Risiko Dini';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring Harian';

    protected static ?string $navigationLabel = 'Notifikasi Risiko Dini';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('risk_alerts'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('risk_alerts'),
            ])
            ->filters([
                ...ResourceUi::filters('risk_alerts'),
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
            'index' => ManageRiskAlerts::route('/'),
        ];
    }
}
