<?php

namespace App\Filament\Resources\DialysisSchedules\Pages;

use App\Filament\Resources\DialysisSchedules\DialysisScheduleResource;
use App\Filament\Resources\DialysisSchedules\Widgets\DialysisScheduleStats;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageDialysisSchedules extends ManageRecords
{
    protected static string $resource = DialysisScheduleResource::class;

    protected ?string $heading = 'Jadwal Hemodialisis';

    protected ?string $subheading = 'Pantau jadwal HD, shift, ruangan, mesin, dan status kehadiran pasien dalam satu tampilan.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Jadwal')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->modalHeading('Buat Jadwal Hemodialisis')
                ->modalDescription('Lengkapi data secara bertahap agar jadwal mudah dipahami petugas.')
                ->modalWidth(Width::FiveExtraLarge)
                ->steps(DialysisScheduleResource::createSteps())
                ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard
                    ->nextAction(fn (Action $action): Action => $action
                        ->label('Lanjut')
                        ->icon(Heroicon::OutlinedArrowRight))
                    ->previousAction(fn (Action $action): Action => $action
                        ->label('Kembali')
                        ->icon(Heroicon::OutlinedArrowLeft)))
                ->createAnother(false)
                ->successNotificationTitle('Jadwal HD berhasil dibuat'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DialysisScheduleStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}
