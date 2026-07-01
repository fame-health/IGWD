<?php

namespace App\Filament\Resources\DailyMonitorings\Pages;

use App\Filament\Resources\DailyMonitorings\DailyMonitoringResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDailyMonitorings extends ManageRecords
{
    protected static string $resource = DailyMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
