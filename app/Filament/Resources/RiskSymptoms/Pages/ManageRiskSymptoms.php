<?php

namespace App\Filament\Resources\RiskSymptoms\Pages;

use App\Filament\Resources\RiskSymptoms\RiskSymptomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRiskSymptoms extends ManageRecords
{
    protected static string $resource = RiskSymptomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
