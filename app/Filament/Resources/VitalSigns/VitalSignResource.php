<?php

namespace App\Filament\Resources\VitalSigns;

use App\Filament\Resources\VitalSigns\Pages\ManageVitalSigns;
use App\Filament\Support\ResourceUi;
use App\Models\VitalSign;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VitalSignResource extends Resource
{
    protected static ?string $model = VitalSign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $modelLabel = 'Tanda Vital';

    protected static ?string $pluralModelLabel = 'Tanda Vital';

    protected static string|\UnitEnum|null $navigationGroup = 'Gejala & Tanda Vital';

    protected static ?string $navigationLabel = 'Tanda Vital';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('vital_signs'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('vital_signs'),
            ])
            ->filters([
                ...ResourceUi::filters('vital_signs'),
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
            'index' => ManageVitalSigns::route('/'),
        ];
    }
}
