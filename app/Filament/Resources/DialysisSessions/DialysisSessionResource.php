<?php

namespace App\Filament\Resources\DialysisSessions;

use App\Filament\Resources\DialysisSessions\Pages\ManageDialysisSessions;
use App\Filament\Support\ResourceUi;
use App\Models\DialysisSession;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DialysisSessionResource extends Resource
{
    protected static ?string $model = DialysisSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Sesi HD';

    protected static ?string $pluralModelLabel = 'Sesi HD';

    protected static string|\UnitEnum|null $navigationGroup = 'Hemodialisis';

    protected static ?string $navigationLabel = 'Sesi HD';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('sessions'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('sessions'),
            ])
            ->filters([
                ...ResourceUi::filters('sessions'),
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
            'index' => ManageDialysisSessions::route('/'),
        ];
    }
}
