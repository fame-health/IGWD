<?php

namespace App\Filament\Resources\PatientMedicalProfiles;

use App\Filament\Resources\PatientMedicalProfiles\Pages\ManagePatientMedicalProfiles;
use App\Filament\Support\ResourceUi;
use App\Models\PatientMedicalProfile;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PatientMedicalProfileResource extends Resource
{
    protected static ?string $model = PatientMedicalProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'Data Medis Pasien';

    protected static ?string $pluralModelLabel = 'Data Medis Pasien';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Data Medis Pasien';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ResourceUi::form('medical_profiles'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...ResourceUi::columns('medical_profiles'),
            ])
            ->filters([
                ...ResourceUi::filters('medical_profiles'),
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
            'index' => ManagePatientMedicalProfiles::route('/'),
        ];
    }
}
