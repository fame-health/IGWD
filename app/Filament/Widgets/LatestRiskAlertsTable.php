<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\RiskAlerts\RiskAlertResource;
use App\Filament\Support\ResourceUi;
use App\Models\RiskAlert;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestRiskAlertsTable extends TableWidget
{
    protected static ?string $heading = 'Tabel Notifikasi Risiko Dini Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => RiskAlert::query()->with('patient')->latest())
            ->columns([
                TextColumn::make('alert_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Nama Pasien')->searchable(),
                TextColumn::make('patient.medical_record_number')->label('No. RM')->searchable(),
                TextColumn::make('alert_type')->label('Jenis Alert')->badge(),
                TextColumn::make('alert_level')->label('Level')->badge()->color(fn (?string $state): string => ResourceUi::statusColor($state)),
                TextColumn::make('title')->label('Judul')->limit(40)->searchable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (?string $state): string => ResourceUi::statusColor($state)),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat Detail')
                    ->url(fn (RiskAlert $record): string => RiskAlertResource::getUrl('index', ['tableSearch' => $record->title])),
            ]);
    }
}
