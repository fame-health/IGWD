<?php

namespace App\Filament\Resources\DialysisSessions\Pages;

use App\Filament\Resources\DialysisSessions\DialysisSessionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction as ExcelExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use App\Filament\Imports\DialysisSessionImporter;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageDialysisSessions extends ManageRecords
{
    protected static string $resource = DialysisSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelExportAction::make('export_template')
                ->label('Download Template')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('info')
                ->exports([
                    ExcelExport::make()
                        ->withFilename('Template-Impor-Sesi-HD')
                        ->withColumns([
                            Column::make('patient')->heading('Pasien (ID)'),
                            Column::make('session_date')->heading('Tanggal Sesi (YYYY-MM-DD)'),
                            Column::make('shift')->heading('Shift (Pagi/Siang/Sore/Malam)'),
                            Column::make('previous_post_hd_weight')->heading('BB Pre HD Terakhir'),
                            Column::make('current_pre_hd_weight')->heading('BB Datang'),
                            Column::make('target_ultrafiltration')->heading('Target UF (L)'),
                            Column::make('blood_pressure_before')->heading('TD Pre (Contoh: 120/80)'),
                            Column::make('blood_sugar_before')->heading('GD Pre'),
                            Column::make('blood_pressure_after')->heading('TD Post'),
                            Column::make('blood_sugar_after')->heading('GD Post'),
                            Column::make('current_post_hd_weight')->heading('BB Pulang'),
                            Column::make('hd_duration_minutes')->heading('Durasi (180/240/dst)'),
                            Column::make('staff_notes')->heading('Catatan Petugas'),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->limit(0)),
                ]),
            ImportAction::make()
                ->importer(DialysisSessionImporter::class)
                ->label('Impor Excel')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('warning'),
            ExcelExportAction::make()
                ->label('Ekspor Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(fn () => 'Sesi-HD-' . date('Y-m-d'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('patient.name')->heading('Pasien'),
                            Column::make('session_date')->heading('Tanggal Sesi'),
                            Column::make('shift')->heading('Shift'),
                            Column::make('previous_post_hd_weight')->heading('BB Pre HD Terakhir'),
                            Column::make('current_pre_hd_weight')->heading('BB Datang'),
                            Column::make('dry_weight')->heading('Berat Kering'),
                            Column::make('target_ultrafiltration')->heading('Target UF'),
                            Column::make('idwg_kg')->heading('IDWG (kg)'),
                            Column::make('idwg_percent')->heading('IDWG (%)'),
                            Column::make('risk_category')->heading('Risiko'),
                            Column::make('blood_pressure_before')->heading('TD Pre'),
                            Column::make('blood_pressure_after')->heading('TD Post'),
                            Column::make('blood_sugar_before')->heading('GD Pre'),
                            Column::make('blood_sugar_after')->heading('GD Post'),
                            Column::make('current_post_hd_weight')->heading('BB Pulang'),
                            Column::make('hd_duration_minutes')->heading('Durasi (Menit)'),
                            Column::make('staff_notes')->heading('Catatan Petugas'),
                        ]),
                ]),
            CreateAction::make()
                ->label('Catat Sesi HD')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->modalHeading('Catat Sesi Hemodialisis')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalSubmitActionLabel('Simpan Sesi'),
        ];
    }
}
