<?php

namespace App\Filament\Imports;

use App\Models\DialysisSession;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class DialysisSessionImporter extends Importer
{
    protected static ?string $model = DialysisSession::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('patient')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('dialysis_schedule_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('session_date')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('shift'),
            ImportColumn::make('previous_post_hd_weight')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('current_pre_hd_weight')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('dry_weight')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('idwg_kg')
                ->numeric()
                ->rules(['numeric']),
            ImportColumn::make('idwg_percent')
                ->numeric()
                ->rules(['numeric']),
            ImportColumn::make('risk_category'),
            ImportColumn::make('current_post_hd_weight')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('target_ultrafiltration')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('hd_duration_minutes')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('staff_notes'),
            ImportColumn::make('doctor_notes'),
            ImportColumn::make('created_by')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('updated_by')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('blood_sugar_before')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('blood_sugar_after')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('blood_pressure_before'),
            ImportColumn::make('blood_pressure_after'),
            ImportColumn::make('daily_fluid_intake_target_ml')
                ->numeric()
                ->rules(['integer']),
        ];
    }

    public function resolveRecord(): DialysisSession
    {
        return new DialysisSession([
            'created_by' => auth()->id(),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your dialysis session import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
