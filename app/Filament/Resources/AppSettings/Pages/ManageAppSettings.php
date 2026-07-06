<?php

namespace App\Filament\Resources\AppSettings\Pages;

use App\Filament\Resources\AppSettings\AppSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageAppSettings extends ManageRecords
{
    protected static string $resource = AppSettingResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Pengaturan Aplikasi';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Kelola variabel global dan konfigurasi sistem IGWD.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
