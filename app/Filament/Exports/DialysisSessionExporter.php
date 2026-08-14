<?php

namespace App\Filament\Exports;

use App\Models\DialysisSession;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DialysisSessionExporter extends Exporter
{
    protected static ?string $model = DialysisSession::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('patient.name'),
            ExportColumn::make('dialysis_schedule_id'),
            ExportColumn::make('session_date'),
            ExportColumn::make('shift'),
            ExportColumn::make('previous_post_hd_weight'),
            ExportColumn::make('current_pre_hd_weight'),
            ExportColumn::make('dry_weight'),
            ExportColumn::make('idwg_kg'),
            ExportColumn::make('idwg_percent'),
            ExportColumn::make('risk_category'),
            ExportColumn::make('current_post_hd_weight'),
            ExportColumn::make('target_ultrafiltration'),
            ExportColumn::make('hd_duration_minutes'),
            ExportColumn::make('staff_notes'),
            ExportColumn::make('doctor_notes'),
            ExportColumn::make('created_by'),
            ExportColumn::make('updated_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('blood_sugar_before'),
            ExportColumn::make('blood_sugar_after'),
            ExportColumn::make('blood_pressure_before'),
            ExportColumn::make('blood_pressure_after'),
            ExportColumn::make('daily_fluid_intake_target_ml'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your dialysis session export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
