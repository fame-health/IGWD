<?php

namespace App\Filament\Resources\DialysisSessions;

use App\Filament\Resources\DialysisSessions\Pages\ManageDialysisSessions;
use App\Filament\Support\ResourceUi;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Services\IdwgCalculationService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DialysisSessionResource extends Resource
{
    protected static ?string $model = DialysisSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Sesi HD';

    protected static ?string $pluralModelLabel = 'Sesi HD';

    protected static string|\UnitEnum|null $navigationGroup = 'Hemodialisis';

    protected static ?string $navigationLabel = 'Sesi HD';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Jadwal')
                        ->description('Pilih jadwal, data utama akan terisi otomatis.')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->completedIcon(Heroicon::OutlinedCheckCircle)
                        ->columns([
                            'default' => 1,
                            'md' => 3,
                        ])
                        ->schema([
                            Select::make('dialysis_schedule_id')
                                ->label('Jadwal HD')
                                ->relationship(
                                    'schedule',
                                    'hd_date',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query
                                        ->with('patient')
                                        ->orderByDesc('hd_date')
                                )
                                ->getOptionLabelFromRecordUsing(fn (DialysisSchedule $record): string => self::scheduleOptionLabel($record))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->placeholder('Cari berdasarkan tanggal atau pasien')
                                ->helperText('Pilih ini dulu jika sesi berasal dari jadwal.')
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    self::applyScheduleToForm($set, $state);
                                    self::refreshIdwgPreview($set, $get);
                                })
                                ->columnSpanFull(),
                            Placeholder::make('schedule_summary')
                                ->label('Ringkasan')
                                ->content(fn (Get $get): string => self::selectedScheduleSummary($get))
                                ->columnSpanFull(),
                            Select::make('patient_id')
                                ->label('Pasien')
                                ->relationship(
                                    'patient',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query
                                        ->select(['id', 'name', 'medical_record_number'])
                                        ->orderBy('name')
                                )
                                ->getOptionLabelFromRecordUsing(fn (Patient $record): string => self::patientOptionLabel($record))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->disabled(fn (Get $get): bool => filled($get('dialysis_schedule_id')))
                                ->dehydrated()
                                ->helperText(fn (Get $get): ?string => filled($get('dialysis_schedule_id')) ? 'Terisi dari jadwal.' : null)
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    self::applyPatientDefaults($set, $get, $state);
                                    self::refreshIdwgPreview($set, $get);
                                }),
                            DatePicker::make('session_date')
                                ->label('Tanggal Sesi')
                                ->default(today())
                                ->required()
                                ->live()
                                ->disabled(fn (Get $get): bool => filled($get('dialysis_schedule_id')))
                                ->dehydrated()
                                ->afterStateUpdated(function (Set $set, Get $get): void {
                                    self::applyPatientDefaults($set, $get, $get('patient_id'));
                                    self::refreshIdwgPreview($set, $get);
                                }),
                            Select::make('shift')
                                ->label('Shift')
                                ->options(self::shiftOptions())
                                ->live()
                                ->disabled(fn (Get $get): bool => filled($get('dialysis_schedule_id')))
                                ->dehydrated(),
                        ]),

                    Step::make('Berat')
                        ->description('Isi data timbang utama. IDWG dihitung otomatis dari berat pasien.')
                        ->icon(Heroicon::OutlinedScale)
                        ->completedIcon(Heroicon::OutlinedCheckCircle)
                        ->columns([
                            'default' => 1,
                            'md' => 3,
                        ])
                        ->schema([
                            TextInput::make('previous_post_hd_weight')
                                ->label('BB Pulang HD Terakhir')
                                ->numeric()
                                ->suffix('kg')
                                ->minValue(1)
                                ->live(onBlur: true)
                                ->helperText('Biasanya otomatis dari sesi HD sebelumnya.')
                                ->afterStateUpdated(fn (Set $set, Get $get): null => self::refreshIdwgPreview($set, $get)),
                            TextInput::make('current_pre_hd_weight')
                                ->label('BB Saat Datang')
                                ->numeric()
                                ->suffix('kg')
                                ->minValue(1)
                                ->live(onBlur: true)
                                ->helperText('Berat pasien sebelum tindakan HD dimulai.')
                                ->afterStateUpdated(fn (Set $set, Get $get): null => self::refreshIdwgPreview($set, $get)),
                            TextInput::make('dry_weight')
                                ->label('Berat Kering')
                                ->numeric()
                                ->suffix('kg')
                                ->minValue(1)
                                ->live(onBlur: true)
                                ->helperText('Otomatis dari profil medis bila ada.')
                                ->afterStateUpdated(fn (Set $set, Get $get): null => self::refreshIdwgPreview($set, $get)),
                            Placeholder::make('calculation_summary')
                                ->label('Hasil hitung sementara')
                                ->content(fn (Get $get): string => self::calculationSummary($get))
                                ->columnSpanFull(),
                        ]),

                    Step::make('Hasil')
                        ->description('Diisi saat tindakan selesai. Bagian ini boleh dilengkapi belakangan.')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->completedIcon(Heroicon::OutlinedCheckCircle)
                        ->columns([
                            'default' => 1,
                            'md' => 3,
                        ])
                        ->schema([
                            TextInput::make('current_post_hd_weight')
                                ->label('BB Pulang')
                                ->numeric()
                                ->suffix('kg')
                                ->minValue(1)
                                ->helperText('Berat pasien setelah sesi HD selesai.'),
                            TextInput::make('target_ultrafiltration')
                                ->label('Target Cairan Keluar')
                                ->numeric()
                                ->suffix('L')
                                ->minValue(0)
                                ->helperText('Opsional. Biasanya ditentukan tim HD/mesin, bukan pasien.'),
                            Select::make('hd_duration_minutes')
                                ->label('Durasi HD')
                                ->options([
                                    180 => '3 jam',
                                    210 => '3 jam 30 menit',
                                    240 => '4 jam',
                                    270 => '4 jam 30 menit',
                                    300 => '5 jam',
                                ])
                                ->default(240)
                                ->helperText('Default umum: 4 jam. Ubah hanya jika jadwal unit berbeda.'),
                            Textarea::make('staff_notes')
                                ->label('Catatan Petugas')
                                ->rows(3)
                                ->columnSpanFull()
                                ->placeholder('Contoh: akses lancar, pasien stabil selama tindakan.'),
                            Textarea::make('doctor_notes')
                                ->label('Catatan Dokter')
                                ->rows(3)
                                ->columnSpanFull()
                                ->placeholder('Diisi bila ada instruksi atau evaluasi dokter.'),
                        ]),
                ])
                    ->contained(false)
                    ->nextAction(fn ($action) => $action->label('Lanjut'))
                    ->previousAction(fn ($action) => $action->label('Kembali'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'patient:id,name,medical_record_number',
                    'schedule:id,hd_date,shift,room,machine_number',
                ]))
            ->heading('Daftar Sesi Hemodialisis')
            ->description('Data tindakan HD aktual, perhitungan IDWG, dan kategori risiko pasien.')
            ->columns([
                TextColumn::make('session_date')
                    ->label('Tanggal Sesi')
                    ->date('d M Y')
                    ->sortable()
                    ->icon(Heroicon::OutlinedCalendar)
                    ->description(fn (DialysisSession $record): string => filled($record->shift) ? 'Shift '.$record->shift : 'Shift belum diisi'),
                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon(Heroicon::OutlinedUser)
                    ->description(fn (DialysisSession $record): string => filled($record->patient?->medical_record_number)
                        ? 'RM '.$record->patient->medical_record_number
                        : 'No. RM belum tersedia')
                    ->lineClamp(1),
                TextColumn::make('schedule.hd_date')
                    ->label('Jadwal Asal')
                    ->date('d M Y')
                    ->placeholder('Manual')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->description(fn (DialysisSession $record): string => self::scheduleLocationSummary($record->schedule))
                    ->toggleable(),
                TextColumn::make('current_pre_hd_weight')
                    ->label('BB Pre')
                    ->suffix(' kg')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('current_post_hd_weight')
                    ->label('BB Post')
                    ->suffix(' kg')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('idwg_kg')
                    ->label('IDWG')
                    ->suffix(' kg')
                    ->placeholder('-')
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('idwg_percent')
                    ->label('IDWG %')
                    ->suffix('%')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('risk_category')
                    ->label('Risiko')
                    ->badge()
                    ->color(fn (?string $state): string => ResourceUi::statusColor($state))
                    ->placeholder('-'),
                TextColumn::make('target_ultrafiltration')
                    ->label('Target UF')
                    ->suffix(' L')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hd_duration_minutes')
                    ->label('Durasi')
                    ->suffix(' menit')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('tanggal_sesi')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('session_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('session_date', '<=', $date))),
                Filter::make('hari_ini')
                    ->label('Hari ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('session_date', today())),
                SelectFilter::make('risk_category')
                    ->label('Kategori Risiko')
                    ->options(self::riskOptions()),
                SelectFilter::make('shift')
                    ->label('Shift')
                    ->options(self::shiftOptions()),
                SelectFilter::make('patient_id')
                    ->label('Pasien')
                    ->relationship(
                        'patient',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                    )
                    ->searchable()
                    ->optionsLimit(25),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('session_date', 'desc')
            ->searchPlaceholder('Cari pasien atau No. RM')
            ->striped()
            ->stackedOnMobile()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada sesi HD')
            ->emptyStateDescription('Catat sesi HD dari jadwal agar data pasien, tanggal, dan shift terisi otomatis.')
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Ubah')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading('Ubah Sesi Hemodialisis')
                        ->modalWidth(Width::FiveExtraLarge)
                        ->modalSubmitActionLabel('Simpan Perubahan'),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->icon(Heroicon::OutlinedTrash),
                ])
                    ->iconButton()
                    ->tooltip('Aksi')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDialysisSessions::route('/'),
        ];
    }

    private static function applyScheduleToForm(Set $set, ?string $scheduleId): void
    {
        if (blank($scheduleId)) {
            return;
        }

        $schedule = DialysisSchedule::query()
            ->with('patient.medicalProfile')
            ->find($scheduleId);

        if (! $schedule) {
            return;
        }

        $set('patient_id', $schedule->patient_id);
        $set('session_date', $schedule->hd_date?->toDateString());
        $set('shift', $schedule->shift);

        self::setPatientClinicalDefaults($set, $schedule->patient_id, $schedule->hd_date?->toDateString());
    }

    private static function applyPatientDefaults(Set $set, Get $get, ?string $patientId): void
    {
        if (blank($patientId)) {
            return;
        }

        self::setPatientClinicalDefaults($set, (int) $patientId, $get('session_date'));
    }

    private static function setPatientClinicalDefaults(Set $set, int $patientId, ?string $sessionDate = null): void
    {
        $patient = Patient::query()
            ->with('medicalProfile')
            ->find($patientId);

        if (! $patient) {
            return;
        }

        if ($patient->medicalProfile?->dry_weight !== null) {
            $set('dry_weight', self::decimalState($patient->medicalProfile->dry_weight));
        }

        $previousSession = DialysisSession::query()
            ->where('patient_id', $patientId)
            ->whereNotNull('current_post_hd_weight')
            ->when(
                filled($sessionDate),
                fn (Builder $query): Builder => $query->whereDate('session_date', '<', Carbon::parse($sessionDate)->toDateString())
            )
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->first();

        if ($previousSession?->current_post_hd_weight !== null) {
            $set('previous_post_hd_weight', self::decimalState($previousSession->current_post_hd_weight));
        }
    }

    private static function refreshIdwgPreview(Set $set, Get $get): null
    {
        $previousWeight = self::numericState($get('previous_post_hd_weight'));
        $currentPreWeight = self::numericState($get('current_pre_hd_weight'));
        $dryWeight = self::numericState($get('dry_weight'));

        if ($previousWeight === null || $currentPreWeight === null) {
            $set('idwg_kg', null);
            $set('idwg_percent', null);
            $set('risk_category', null);

            return null;
        }

        $idwgKg = round($currentPreWeight - $previousWeight, 2);
        $divider = $dryWeight ?: $currentPreWeight;
        $idwgPercent = $divider > 0 ? round(($idwgKg / $divider) * 100, 2) : null;

        $set('idwg_kg', self::decimalState($idwgKg));
        $set('idwg_percent', $idwgPercent !== null ? self::decimalState($idwgPercent) : null);
        $set('risk_category', $idwgPercent !== null ? app(IdwgCalculationService::class)->riskCategory($idwgPercent) : null);

        return null;
    }

    private static function scheduleOptionLabel(DialysisSchedule $schedule): string
    {
        $date = $schedule->hd_date?->format('d M Y') ?? '-';
        $patient = $schedule->patient?->name ?? 'Pasien tidak diketahui';
        $shift = $schedule->shift ? "Shift {$schedule->shift}" : 'Shift belum diisi';

        return "{$date} - {$patient} - {$shift}";
    }

    private static function patientOptionLabel(Patient $patient): string
    {
        $medicalRecord = filled($patient->medical_record_number) ? " ({$patient->medical_record_number})" : '';

        return "{$patient->name}{$medicalRecord}";
    }

    private static function selectedScheduleSummary(Get $get): string
    {
        if (blank($get('dialysis_schedule_id'))) {
            return 'Sesi dibuat manual tanpa mengambil data dari jadwal HD.';
        }

        $schedule = DialysisSchedule::query()
            ->with('patient')
            ->find($get('dialysis_schedule_id'));

        return $schedule ? self::scheduleOptionLabel($schedule).' - '.self::scheduleLocationSummary($schedule) : 'Jadwal tidak ditemukan.';
    }

    private static function calculationSummary(Get $get): string
    {
        $previousWeight = self::numericState($get('previous_post_hd_weight'));
        $currentPreWeight = self::numericState($get('current_pre_hd_weight'));
        $dryWeight = self::numericState($get('dry_weight'));

        if ($previousWeight === null || $currentPreWeight === null) {
            return 'Isi BB pulang HD terakhir dan BB saat datang untuk melihat IDWG otomatis.';
        }

        $idwgKg = round($currentPreWeight - $previousWeight, 2);
        $divider = $dryWeight ?: $currentPreWeight;
        $idwgPercent = $divider > 0 ? round(($idwgKg / $divider) * 100, 2) : null;

        if ($idwgPercent === null) {
            return 'Berat pembagi tidak valid. Cek kembali berat kering atau BB sebelum HD.';
        }

        $risk = app(IdwgCalculationService::class)->riskCategory($idwgPercent);

        return 'IDWG '.self::decimalState($idwgKg).' kg ('.self::decimalState($idwgPercent).'%) - Risiko '.$risk.'.';
    }

    private static function scheduleLocationSummary(?DialysisSchedule $schedule): string
    {
        if (! $schedule) {
            return '-';
        }

        $parts = array_filter([
            $schedule->room ? "Ruang {$schedule->room}" : null,
            $schedule->machine_number ? "Mesin {$schedule->machine_number}" : null,
        ]);

        return $parts ? implode(' - ', $parts) : 'Lokasi belum diisi';
    }

    private static function shiftOptions(): array
    {
        return [
            'Pagi' => 'Pagi',
            'Siang' => 'Siang',
            'Sore' => 'Sore',
            'Malam' => 'Malam',
        ];
    }

    private static function riskOptions(): array
    {
        return [
            'Aman' => 'Aman',
            'Waspada' => 'Waspada',
            'Tinggi' => 'Tinggi',
            'Darurat' => 'Darurat',
        ];
    }

    private static function numericState(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private static function decimalState(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
