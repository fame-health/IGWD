<?php

namespace App\Filament\Resources\DialysisSchedules\Pages;

use App\Filament\Resources\DialysisSchedules\DialysisScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDialysisSchedules extends ManageRecords
{
    protected static string $resource = DialysisScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
