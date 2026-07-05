<?php

namespace App\Filament\Resources\DialysisSchedules;

use App\Filament\Resources\DialysisSchedules\Pages\ManageDialysisSchedules;
use App\Filament\Resources\DialysisSchedules\Widgets\DialysisScheduleStats;
use App\Filament\Support\ResourceUi;
use App\Models\DialysisSchedule;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DialysisScheduleResource extends Resource
{
    protected static ?string $model = DialysisSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Jadwal HD';

    protected static ?string $pluralModelLabel = 'Jadwal HD';

    protected static string|\UnitEnum|null $navigationGroup = 'Hemodialisis';

    protected static ?string $navigationLabel = 'Jadwal HD';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pasien dan Waktu HD')
                    ->description('Tentukan pasien, tanggal tindakan, dan status jadwal.')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->compact()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                            ->schema([
                                Select::make('patient_id')
                                    ->label('Pasien')
                                    ->relationship('patient', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),
                                DatePicker::make('hd_date')
                                    ->label('Tanggal HD')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('day_name', self::dayNameFromDate($state)))
                                    ->required(),
                                Select::make('day_name')
                                    ->label('Hari')
                                    ->options(self::dayOptions())
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Otomatis mengikuti tanggal HD.'),
                                Select::make('attendance_status')
                                    ->label('Status Kehadiran')
                                    ->options(self::attendanceOptions())
                                    ->default('Terjadwal')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Shift dan Lokasi')
                    ->description('Isi detail operasional agar petugas mudah menemukan ruangan dan mesin.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->compact()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Select::make('shift')
                                    ->label('Shift')
                                    ->options(self::shiftOptions())
                                    ->required(),
                                TextInput::make('room')
                                    ->label('Ruangan')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: HD 1'),
                                TextInput::make('machine_number')
                                    ->label('Nomor Mesin')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: M-08'),
                            ]),
                    ]),

                Section::make('Tim Medis dan Catatan')
                    ->description('Tambahkan petugas penanggung jawab dan instruksi singkat bila diperlukan.')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->compact()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Select::make('doctor_name')
                                    ->label('Dokter')
                                    ->options(fn (): array => self::staffOptions('dokter'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Pilih dokter dari data user')
                                    ->helperText('Menampilkan user aktif dengan role dokter.'),
                                Select::make('nurse_name')
                                    ->label('Perawat')
                                    ->options(fn (): array => self::staffOptions('perawat'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Pilih perawat dari data user')
                                    ->helperText('Menampilkan user aktif dengan role perawat.'),
                            ]),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Contoh: pasien datang dengan pendamping, siapkan akses AV fistula.'),
                    ]),
            ]);
    }

    public static function createSteps(): array
    {
        return [
            Step::make('Pasien')
                ->description('Pilih pasien dan tanggal tindakan.')
                ->icon(Heroicon::OutlinedUser)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    Select::make('patient_id')
                        ->label('Pasien')
                        ->relationship('patient', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    DatePicker::make('hd_date')
                        ->label('Tanggal HD')
                        ->live()
                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('day_name', self::dayNameFromDate($state)))
                        ->required(),
                    Select::make('day_name')
                        ->label('Hari')
                        ->options(self::dayOptions())
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Otomatis mengikuti tanggal HD dan tidak bisa diubah manual.'),
                    Select::make('attendance_status')
                        ->label('Status Awal')
                        ->options(self::attendanceOptions())
                        ->default('Terjadwal')
                        ->required()
                        ->helperText('Biasanya gunakan Terjadwal saat membuat jadwal baru.'),
                ]),

            Step::make('Shift dan Lokasi')
                ->description('Atur shift, ruangan, dan mesin.')
                ->icon(Heroicon::OutlinedMapPin)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->columns([
                    'default' => 1,
                    'md' => 3,
                ])
                ->schema([
                    Select::make('shift')
                        ->label('Shift')
                        ->options(self::shiftOptions())
                        ->required(),
                    TextInput::make('room')
                        ->label('Ruangan')
                        ->maxLength(255)
                        ->placeholder('Contoh: HD 1'),
                    TextInput::make('machine_number')
                        ->label('Nomor Mesin')
                        ->maxLength(255)
                        ->placeholder('Contoh: M-08'),
                ]),

            Step::make('Tim Medis')
                ->description('Isi penanggung jawab tindakan.')
                ->icon(Heroicon::OutlinedUserGroup)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    Select::make('doctor_name')
                        ->label('Dokter')
                        ->options(fn (): array => self::staffOptions('dokter'))
                        ->searchable()
                        ->preload()
                        ->placeholder('Pilih dokter dari data user')
                        ->helperText('Menampilkan user aktif dengan role dokter.'),
                    Select::make('nurse_name')
                        ->label('Perawat')
                        ->options(fn (): array => self::staffOptions('perawat'))
                        ->searchable()
                        ->preload()
                        ->placeholder('Pilih perawat dari data user')
                        ->helperText('Menampilkan user aktif dengan role perawat.'),
                ]),

            Step::make('Catatan')
                ->description('Tambahkan instruksi tambahan sebelum menyimpan.')
                ->icon(Heroicon::OutlinedDocumentText)
                ->completedIcon(Heroicon::OutlinedCheckBadge)
                ->schema([
                    Placeholder::make('review_hint')
                        ->label('Cek sebelum simpan')
                        ->content('Pastikan pasien, tanggal, shift, ruangan, mesin, dan status jadwal sudah sesuai.'),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(4)
                        ->placeholder('Contoh: pasien datang dengan pendamping, siapkan akses AV fistula.'),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('patient')
                ->withCount('sessions'))
            ->columns([
                TextColumn::make('hd_date')
                    ->label('Jadwal')
                    ->date('d M Y')
                    ->description(fn (DialysisSchedule $record): string => $record->day_name ?: $record->hd_date?->translatedFormat('l') ?: '-')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->sortable(),
                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon(Heroicon::OutlinedUser)
                    ->description(fn (DialysisSchedule $record): string => filled($record->patient?->medical_record_number)
                        ? 'RM ' . $record->patient->medical_record_number
                        : 'No. RM belum tersedia'),
                TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Siang' => 'warning',
                        'Sore' => 'success',
                        'Malam' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'Malam' => 'heroicon-o-moon',
                        default => 'heroicon-o-clock',
                    }),
                TextColumn::make('room')
                    ->label('Lokasi')
                    ->placeholder('-')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->description(fn (DialysisSchedule $record): string => filled($record->machine_number)
                        ? 'Mesin ' . $record->machine_number
                        : 'Mesin belum diisi'),
                TextColumn::make('doctor_name')
                    ->label('Tim Medis')
                    ->placeholder('-')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->description(fn (DialysisSchedule $record): string => filled($record->nurse_name)
                        ? 'Perawat: ' . $record->nurse_name
                        : 'Perawat belum diisi')
                    ->toggleable(),
                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => ResourceUi::statusColor($state))
                    ->icon(fn (?string $state): string => match ($state) {
                        'Hadir' => 'heroicon-o-check-circle',
                        'Tidak Hadir' => 'heroicon-o-x-circle',
                        'Reschedule' => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-clock',
                    }),
                TextColumn::make('sessions_count')
                    ->label('Sesi')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} tercatat" : 'Belum ada')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(44)
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('tanggal_hd')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('hd_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('hd_date', '<=', $date))),
                Filter::make('hari_ini')
                    ->label('Hari ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('hd_date', today())),
                Filter::make('minggu_ini')
                    ->label('7 hari ke depan')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('hd_date', '>=', today())
                        ->whereDate('hd_date', '<=', today()->addDays(7))),
                SelectFilter::make('shift')
                    ->label('Shift')
                    ->options(self::shiftOptions()),
                SelectFilter::make('attendance_status')
                    ->label('Status Kehadiran')
                    ->options(self::attendanceOptions()),
                SelectFilter::make('patient_id')
                    ->label('Pasien')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw('case when hd_date < ? then 1 else 0 end', [today()->toDateString()])
                ->orderBy('hd_date')
                ->orderByRaw("case shift when 'Pagi' then 1 when 'Siang' then 2 when 'Sore' then 3 when 'Malam' then 4 else 5 end"))
            ->defaultSortOptionLabel('Tanggal terdekat')
            ->searchPlaceholder('Cari pasien, ruangan, mesin, dokter, atau perawat')
            ->striped()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada jadwal HD')
            ->emptyStateDescription('Tambahkan jadwal agar ruang, shift, dan petugas HD bisa dipantau dari satu halaman.')
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays)
            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->icon(Heroicon::OutlinedPencilSquare),
                DeleteAction::make()
                    ->label('Hapus')
                    ->icon(Heroicon::OutlinedTrash),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            DialysisScheduleStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDialysisSchedules::route('/'),
        ];
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

    private static function attendanceOptions(): array
    {
        return [
            'Terjadwal' => 'Terjadwal',
            'Hadir' => 'Hadir',
            'Tidak Hadir' => 'Tidak Hadir',
            'Reschedule' => 'Reschedule',
        ];
    }

    private static function dayOptions(): array
    {
        return [
            'Senin' => 'Senin',
            'Selasa' => 'Selasa',
            'Rabu' => 'Rabu',
            'Kamis' => 'Kamis',
            'Jumat' => 'Jumat',
            'Sabtu' => 'Sabtu',
            'Minggu' => 'Minggu',
        ];
    }

    private static function dayNameFromDate(?string $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][Carbon::parse($date)->dayOfWeekIso];
    }

    private static function staffOptions(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }
}
