<?php

namespace App\Filament\Resources\DialysisSchedules;

use App\Filament\Resources\DialysisSchedules\Pages\ManageDialysisSchedules;
use App\Filament\Support\ResourceUi;
use App\Models\DialysisSchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DialysisScheduleResource extends Resource
{
    protected static ?string $model = DialysisSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Jadwal HD';

    protected static ?string $pluralModelLabel = 'Jadwal HD';

    protected static string|\UnitEnum|null $navigationGroup = 'Hemodialisis';

    protected static ?string $navigationLabel = 'Jadwal HD';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('schedules'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('schedules'),
            ])
            ->filters([
                ...ResourceUi::filters('schedules'),
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
            'index' => ManageDialysisSchedules::route('/'),
        ];
    }
}
