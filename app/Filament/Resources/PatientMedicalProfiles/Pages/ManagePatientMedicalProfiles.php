<?php

namespace App\Filament\Resources\PatientMedicalProfiles\Pages;

use App\Filament\Resources\PatientMedicalProfiles\PatientMedicalProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePatientMedicalProfiles extends ManageRecords
{
    protected static string $resource = PatientMedicalProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
