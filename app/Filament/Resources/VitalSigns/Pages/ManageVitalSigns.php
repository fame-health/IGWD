<?php

namespace App\Filament\Resources\VitalSigns\Pages;

use App\Filament\Resources\VitalSigns\VitalSignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVitalSigns extends ManageRecords
{
    protected static string $resource = VitalSignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
