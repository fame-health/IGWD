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
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $heading = 'Alert Risiko Terbaru';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Alert Risiko Terbaru')
            ->description('6 alert terakhir berdasarkan tanggal dan waktu kejadian.')
            ->query(fn (): Builder => RiskAlert::query()
                ->with('patient')
                ->orderByDesc('alert_date')
                ->orderByDesc('alert_time')
                ->orderByDesc('id')
                ->limit(6))
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->weight('semibold')
                    ->lineClamp(1)
                    ->description(fn (RiskAlert $record): ?string => $record->patient?->medical_record_number
                        ? 'RM '.$record->patient->medical_record_number
                        : null),
                TextColumn::make('title')
                    ->label('Ringkasan')
                    ->searchable()
                    ->limit(48)
                    ->lineClamp(2)
                    ->tooltip(fn (RiskAlert $record): ?string => $record->title)
                    ->description(fn (RiskAlert $record): string => $record->alert_type)
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('alert_level')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (?string $state): string => ResourceUi::statusColor($state))
                    ->icon(fn (?string $state): string => match ($state) {
                        'Darurat' => 'heroicon-o-fire',
                        'Tinggi' => 'heroicon-o-exclamation-triangle',
                        'Waspada' => 'heroicon-o-exclamation-circle',
                        default => 'heroicon-o-information-circle',
                    })
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Tindak Lanjut')
                    ->badge()
                    ->color(fn (?string $state): string => ResourceUi::statusColor($state))
                    ->icon(fn (?string $state): string => match ($state) {
                        'Ditindaklanjuti' => 'heroicon-o-check-circle',
                        'Selesai' => 'heroicon-o-check-badge',
                        'Dibaca' => 'heroicon-o-eye',
                        default => 'heroicon-o-bell',
                    })
                    ->visibleFrom('md'),
                TextColumn::make('alert_date')
                    ->label('Waktu')
                    ->date('d M Y')
                    ->description(fn (RiskAlert $record): ?string => filled($record->alert_time)
                        ? substr((string) $record->alert_time, 0, 5).' WIB'
                        : null)
                    ->icon('heroicon-o-clock')
                    ->weight('medium')
                    ->visibleFrom('lg'),
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('semuaAlert')
                    ->label('Lihat Semua')
                    ->icon('heroicon-o-arrow-up-right')
                    ->color('gray')
                    ->url(RiskAlertResource::getUrl()),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Detail')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->iconButton()
                    ->tooltip('Lihat detail alert')
                    ->url(fn (RiskAlert $record): string => RiskAlertResource::getUrl('index', ['tableSearch' => $record->title])),
            ])
            ->emptyStateHeading('Tidak ada alert')
            ->emptyStateDescription('Belum ada notifikasi risiko yang tercatat')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
