<?php

namespace App\Filament\Resources\RiskSymptoms;

use App\Filament\Resources\RiskSymptoms\Pages\ManageRiskSymptoms;
use App\Filament\Support\ResourceUi;
use App\Models\RiskSymptom;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RiskSymptomResource extends Resource
{
    protected static ?string $model = RiskSymptom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Gejala Risiko';

    protected static ?string $pluralModelLabel = 'Gejala Risiko';

    protected static string|\UnitEnum|null $navigationGroup = 'Gejala & Tanda Vital';

    protected static ?string $navigationLabel = 'Gejala Risiko';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('risk_symptoms'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('risk_symptoms'),
            ])
            ->filters([
                ...ResourceUi::filters('risk_symptoms'),
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
            'index' => ManageRiskSymptoms::route('/'),
        ];
    }
}
