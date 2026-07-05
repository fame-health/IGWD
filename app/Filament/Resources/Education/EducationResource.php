<?php

namespace App\Filament\Resources\Education;

use App\Filament\Resources\Education\Pages\ManageEducation;
use App\Filament\Support\ResourceUi;
use App\Models\Education;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EducationResource extends Resource
{
    protected static ?string $model = Education::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'Edukasi Pasien';

    protected static ?string $pluralModelLabel = 'Edukasi Pasien';

    protected static string|\UnitEnum|null $navigationGroup = 'Edukasi';

    protected static ?string $navigationLabel = 'Edukasi Pasien';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('educations'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('educations'),
            ])
            ->filters([
                ...ResourceUi::filters('educations'),
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
            'index' => ManageEducation::route('/'),
        ];
    }
}
