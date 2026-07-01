<?php

namespace App\Filament\Resources\DialysisSessions\Pages;

use App\Filament\Resources\DialysisSessions\DialysisSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDialysisSessions extends ManageRecords
{
    protected static string $resource = DialysisSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
