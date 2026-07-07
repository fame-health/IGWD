<?php

namespace App\Filament\Resources\DialysisSessions\Pages;

use App\Filament\Resources\DialysisSessions\DialysisSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageDialysisSessions extends ManageRecords
{
    protected static string $resource = DialysisSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Sesi HD')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->modalHeading('Catat Sesi Hemodialisis')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalSubmitActionLabel('Simpan Sesi'),
        ];
    }
}
