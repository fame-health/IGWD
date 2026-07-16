<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ResourceUi
{
    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'Aman', 'Normal', 'Selesai', 'Aktif', 'Baik', 'Hadir' => 'success',
            'Waspada', 'Dibaca', 'Cukup', 'Terlambat', 'Terjadwal' => 'warning',
            'Tinggi', 'Darurat', 'Melebihi Batas', 'Baru', 'Kurang', 'Tidak Hadir', 'Meninggal' => 'danger',
            'Ditindaklanjuti', 'Reschedule', 'Pindah' => 'info',
            default => 'gray',
        };
    }

    public static function patientSelect(bool $required = true): Select
    {
        $select = Select::make('patient_id')
            ->label('Pasien')
            ->relationship('patient', 'name')
            ->searchable()
            ->preload();

        return $required ? $select->required() : $select;
    }

    public static function userSelect(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->relationship(str($name)->camel()->toString(), 'name')
            ->searchable()
            ->preload();
    }

    public static function form(string $key): array
    {
        return match ($key) {
            'patients' => [
                TextInput::make('medical_record_number')->label('No. Rekam Medis')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('name')->label('Nama Pasien')->required()->maxLength(255),
                TextInput::make('nik')->label('NIK')->maxLength(255),
                DatePicker::make('birth_date')->label('Tanggal Lahir'),
                Select::make('gender')->label('Jenis Kelamin')->options(['laki-laki' => 'Laki-laki', 'perempuan' => 'Perempuan'])->required(),
                Textarea::make('address')->label('Alamat')->columnSpanFull(),
                TextInput::make('phone')->label('Telepon')->maxLength(255),
                TextInput::make('responsible_person_name')->label('Nama Penanggung Jawab')->maxLength(255),
                TextInput::make('responsible_person_phone')->label('Telepon Penanggung Jawab')->maxLength(255),
                Select::make('payment_status')->label('Status Pembayaran')->options(self::options(['BPJS', 'Umum', 'Asuransi', 'Lainnya'])),
                Select::make('patient_status')->label('Status Pasien')->options(self::options(['Aktif', 'Tidak Aktif', 'Pindah', 'Meninggal']))->default('Aktif')->required(),
            ],
            'medical_profiles' => [
                self::patientSelect(),
                TextInput::make('main_diagnosis')->label('Diagnosis Utama')->maxLength(255),
                Textarea::make('comorbidities')->label('Komorbid')->columnSpanFull(),
                DatePicker::make('hemodialysis_start_date')->label('Mulai Hemodialisis'),
                Select::make('hemodialysis_frequency')->label('Frekuensi HD')->options(self::options(['1x per minggu', '2x per minggu', '3x per minggu'])),
                TextInput::make('dry_weight')->label('Berat Kering')->numeric()->suffix('kg'),
                Select::make('vascular_access')->label('Akses Vaskular')->options(self::options(['AV Fistula', 'CDL', 'Graft', 'Lainnya'])),
                Textarea::make('allergies')->label('Alergi')->columnSpanFull(),
                Textarea::make('routine_medications')->label('Obat Rutin')->columnSpanFull(),
                Textarea::make('important_notes')->label('Catatan Penting')->columnSpanFull(),
            ],
            'schedules' => [
                self::patientSelect(),
                DatePicker::make('hd_date')->label('Tanggal HD')->required(),
                TextInput::make('day_name')->label('Hari')->maxLength(255),
                Select::make('shift')->label('Shift')->options(self::options(['Pagi', 'Siang', 'Sore', 'Malam']))->required(),
                TextInput::make('room')->label('Ruangan')->maxLength(255),
                TextInput::make('machine_number')->label('Nomor Mesin')->maxLength(255),
                TextInput::make('doctor_name')->label('Dokter')->maxLength(255),
                TextInput::make('nurse_name')->label('Perawat')->maxLength(255),
                Select::make('attendance_status')->label('Status Kehadiran')->options(self::options(['Terjadwal', 'Hadir', 'Tidak Hadir', 'Reschedule']))->default('Terjadwal')->required(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ],
            'sessions' => [
                self::patientSelect(),
                Select::make('dialysis_schedule_id')->label('Jadwal HD')->relationship('schedule', 'hd_date')->searchable()->preload(),
                DatePicker::make('session_date')->label('Tanggal Sesi')->required(),
                Select::make('shift')->label('Shift')->options(self::options(['Pagi', 'Siang', 'Sore', 'Malam'])),
                TextInput::make('previous_post_hd_weight')->label('BB Setelah HD Sebelumnya')->numeric()->suffix('kg'),
                TextInput::make('current_pre_hd_weight')->label('BB Sebelum HD')->numeric()->suffix('kg'),
                TextInput::make('dry_weight')->label('Berat Kering')->numeric()->suffix('kg'),
                TextInput::make('idwg_kg')->label('IDWG kg')->numeric()->disabled()->dehydrated(),
                TextInput::make('idwg_percent')->label('IDWG %')->numeric()->disabled()->dehydrated(),
                Select::make('risk_category')->label('Kategori Risiko')->options(self::options(['Aman', 'Waspada', 'Tinggi', 'Darurat']))->disabled()->dehydrated(),
                TextInput::make('current_post_hd_weight')->label('BB Setelah HD')->numeric()->suffix('kg'),
                TextInput::make('target_ultrafiltration')->label('Target Ultrafiltrasi')->numeric(),
                TextInput::make('hd_duration_minutes')->label('Durasi HD')->numeric()->suffix('menit'),
                Textarea::make('staff_notes')->label('Catatan Petugas')->columnSpanFull(),
                Textarea::make('doctor_notes')->label('Catatan Dokter')->columnSpanFull(),
            ],
            'daily_monitorings' => [
                self::patientSelect(),
                Select::make('last_dialysis_session_id')->label('Sesi HD Terakhir')->relationship('lastDialysisSession', 'session_date')->searchable()->preload(),
                DatePicker::make('monitoring_date')->label('Tanggal Monitoring')->required(),
                TextInput::make('day_after_hd')->label('Hari Setelah HD')->numeric(),
                DatePicker::make('last_hd_date')->label('Tanggal HD Terakhir'),
                DatePicker::make('next_hd_date')->label('Tanggal HD Berikutnya'),
                TextInput::make('last_post_hd_weight')->label('BB Post HD Terakhir')->numeric()->suffix('kg'),
                TextInput::make('today_weight')->label('BB Hari Ini')->numeric()->required()->suffix('kg'),
                TextInput::make('daily_weight_gain_kg')->label('Kenaikan BB')->numeric()->disabled()->dehydrated()->suffix('kg'),
                TextInput::make('fluid_intake_ml')->label('Cairan Masuk')->numeric()->suffix('ml'),
                TextInput::make('insensible_water_loss_ml')->label('IWL')->numeric()->disabled()->dehydrated()->suffix('ml'),
                TextInput::make('fluid_output_ml')->label('Cairan Keluar')->numeric()->disabled()->dehydrated()->suffix('ml'),
                TextInput::make('daily_fluid_limit_ml')->label('Batas Cairan')->numeric()->suffix('ml'),
                TextInput::make('fluid_difference_ml')->label('Balance Cairan')->numeric()->disabled()->dehydrated()->suffix('ml'),
                Select::make('fluid_status')->label('Status Cairan')->options(self::options(['Aman', 'Melebihi Batas']))->disabled()->dehydrated(),
                Select::make('risk_status')->label('Status Risiko')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat']))->disabled()->dehydrated(),
                Textarea::make('symptom_notes')->label('Catatan Gejala')->columnSpanFull(),
                Textarea::make('staff_notes')->label('Catatan Petugas')->columnSpanFull(),
            ],
            'vital_signs' => [
                self::patientSelect(),
                Select::make('dialysis_session_id')->label('Sesi HD')->relationship('dialysisSession', 'session_date')->searchable()->preload(),
                DatePicker::make('measurement_date')->label('Tanggal Pengukuran')->required(),
                TextInput::make('blood_pressure_before')->label('TD Sebelum')->maxLength(255),
                TextInput::make('pulse_before')->label('Nadi')->numeric(),
                TextInput::make('temperature')->label('Suhu')->numeric()->suffix('C'),
                TextInput::make('respiration')->label('Respirasi')->numeric(),
                TextInput::make('oxygen_saturation')->label('SpO2')->numeric()->suffix('%'),
                TextInput::make('blood_pressure_during')->label('TD Saat HD')->maxLength(255),
                TextInput::make('blood_pressure_after')->label('TD Setelah')->maxLength(255),
                Textarea::make('complaints')->label('Keluhan')->columnSpanFull(),
            ],
            'risk_symptoms' => [
                self::patientSelect(),
                Select::make('dialysis_session_id')->label('Sesi HD')->relationship('dialysisSession', 'session_date')->searchable()->preload(),
                Select::make('daily_monitoring_id')->label('Monitoring Harian')->relationship('dailyMonitoring', 'monitoring_date')->searchable()->preload(),
                DatePicker::make('symptom_date')->label('Tanggal Gejala')->required(),
                Select::make('shortness_of_breath')->label('Sesak Napas')->options(self::options(['Tidak', 'Ringan', 'Sedang', 'Berat']))->default('Tidak')->required(),
                Select::make('edema')->label('Edema')->options(self::options(['Tidak', 'Kaki', 'Tangan', 'Wajah', 'Lainnya']))->default('Tidak')->required(),
                Toggle::make('muscle_cramp')->label('Kram Otot'),
                Toggle::make('dizziness_or_weakness')->label('Pusing/Lemas'),
                Toggle::make('nausea_or_vomiting')->label('Mual/Muntah'),
                Toggle::make('chest_pain')->label('Nyeri Dada'),
                Toggle::make('headache')->label('Sakit Kepala'),
                Select::make('system_risk_status')->label('Status Risiko Sistem')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat']))->disabled()->dehydrated(),
                Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
            ],
            'educations' => [
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('title')->label('Judul Edukasi')->required()->maxLength(255),
                        Select::make('category')->label('Kategori')->options(self::options(['Video', 'Poster', 'Artikel', 'Materi Pasien']))->required(),
                        Toggle::make('is_general')->label('Edukasi Umum (Dilihat Semua Pasien)')->default(false)->reactive(),
                        self::patientSelect(false)->hidden(fn ($get) => $get('is_general')),
                        DatePicker::make('education_date')->label('Tanggal')->default(now())->required(),
                    ])->columns(2),

                Section::make('Konten Edukasi')
                    ->schema([
                        Textarea::make('content')->label('Deskripsi/Konten Artikel')->rows(5)->columnSpanFull(),
                        TextInput::make('youtube_url')->label('Link YouTube')->placeholder('https://www.youtube.com/watch?v=...')->url(),
                        FileUpload::make('image_path')->label('Poster/Gambar')->image()->directory('education-posters'),
                        Textarea::make('education_materials')->label('Catatan Materi Tambahan')->columnSpanFull(),
                    ]),

                Section::make('Evaluasi (Khusus Per Pasien)')
                    ->hidden(fn ($get) => $get('is_general'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('patient_understanding')->label('Pemahaman')->options(self::options(['Baik', 'Cukup', 'Kurang'])),
                                Select::make('fluid_compliance')->label('Kepatuhan Cairan')->options(self::options(['Baik', 'Cukup', 'Kurang'])),
                                Select::make('schedule_compliance')->label('Kepatuhan Jadwal')->options(self::options(['Hadir', 'Terlambat', 'Tidak Hadir'])),
                            ]),
                        TextInput::make('educator_name')->label('Edukator')->maxLength(255),
                        Textarea::make('follow_up_notes')->label('Catatan Tindak Lanjut')->columnSpanFull(),
                    ]),
            ],
            'risk_alerts' => [
                self::patientSelect(),
                TextInput::make('source_type')->label('Sumber')->maxLength(255),
                TextInput::make('source_id')->label('ID Sumber')->numeric(),
                DatePicker::make('alert_date')->label('Tanggal Alert')->required(),
                Select::make('alert_level')->label('Level Alert')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat']))->required(),
                Select::make('alert_type')->label('Jenis Alert')->options(self::options(['Kenaikan Berat Badan', 'IDWG Tinggi', 'Cairan Melebihi Batas', 'Gejala Risiko', 'Prediksi Risiko', 'Tidak Input Data Harian']))->required(),
                TextInput::make('title')->label('Judul')->required()->maxLength(255),
                Textarea::make('message')->label('Pesan')->required()->columnSpanFull(),
                TextInput::make('trigger_value')->label('Nilai Pemicu')->maxLength(255),
                TextInput::make('threshold_value')->label('Nilai Batas')->maxLength(255),
                Select::make('status')->label('Status')->options(self::options(['Baru', 'Dibaca', 'Ditindaklanjuti', 'Selesai']))->default('Baru')->required(),
                Select::make('assigned_to')->label('Ditugaskan Kepada')->relationship('assignedTo', 'name')->searchable()->preload(),
                Textarea::make('recommendation')->label('Rekomendasi')->columnSpanFull(),
                Textarea::make('follow_up_note')->label('Catatan Tindak Lanjut')->columnSpanFull(),
            ],
            'users' => [
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('password')->label('Password')->password()->revealable()->dehydrated(fn ($state) => filled($state))->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')->label('Role')->options(self::options(['admin', 'perawat', 'dokter', 'manajemen', 'pasien']))->required(),
                Select::make('patient_id')
                    ->label('Pasien Terkait')
                    ->relationship(
                        'patient',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                    )
                    ->searchable()
                    ->preload()
                    ->optionsLimit(25),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ],
            'app_settings' => [
                Section::make('Konfigurasi Aplikasi')
                    ->description('Kelola parameter global aplikasi di sini untuk mengontrol perilaku sistem.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('key')
                                    ->label('Kunci (Key)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('contoh: minimal_idwg_alert')
                                    ->helperText('Gunakan format snake_case. Kunci ini digunakan dalam kode program.'),
                                TextInput::make('value')
                                    ->label('Nilai (Value)')
                                    ->required()
                                    ->placeholder('Masukkan nilai pengaturan'),
                            ]),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Jelaskan secara singkat kegunaan pengaturan ini...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ],
            default => [],
        };
    }

    public static function columns(string $key): array
    {
        return match ($key) {
            'patients' => [
                TextColumn::make('medical_record_number')->label('No. RM')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('gender')->label('JK')->badge(),
                TextColumn::make('phone')->label('Telepon')->searchable(),
                TextColumn::make('patient_status')->label('Status')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'medical_profiles' => [
                TextColumn::make('patient.medical_record_number')->label('No. RM')->searchable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('main_diagnosis')->label('Diagnosis')->searchable(),
                TextColumn::make('hemodialysis_frequency')->label('Frekuensi')->badge(),
                TextColumn::make('dry_weight')->label('BB Kering')->suffix(' kg')->sortable(),
            ],
            'schedules' => [
                TextColumn::make('hd_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('shift')->label('Shift')->badge(),
                TextColumn::make('room')->label('Ruang'),
                TextColumn::make('attendance_status')->label('Kehadiran')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'sessions' => [
                TextColumn::make('session_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('current_pre_hd_weight')->label('BB Pre')->suffix(' kg'),
                TextColumn::make('idwg_kg')->label('IDWG')->suffix(' kg'),
                TextColumn::make('idwg_percent')->label('IDWG %')->suffix('%'),
                TextColumn::make('risk_category')->label('Risiko')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'daily_monitorings' => [
                TextColumn::make('monitoring_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('today_weight')->label('BB')->suffix(' kg'),
                TextColumn::make('daily_weight_gain_kg')->label('Kenaikan')->suffix(' kg'),
                TextColumn::make('fluid_intake_ml')->label('Masuk')->suffix(' ml'),
                TextColumn::make('fluid_output_ml')->label('Keluar')->suffix(' ml'),
                TextColumn::make('fluid_difference_ml')->label('Balance')->suffix(' ml'),
                TextColumn::make('fluid_status')->label('Status Cairan')->badge()->color(fn (?string $state): string => self::statusColor($state)),
                TextColumn::make('risk_status')->label('Risiko')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'vital_signs' => [
                TextColumn::make('measurement_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('blood_pressure_before')->label('TD Sebelum'),
                TextColumn::make('pulse_before')->label('Nadi'),
                TextColumn::make('temperature')->label('Suhu')->suffix(' C'),
                TextColumn::make('oxygen_saturation')->label('SpO2')->suffix('%'),
            ],
            'risk_symptoms' => [
                TextColumn::make('symptom_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('shortness_of_breath')->label('Sesak')->badge(),
                TextColumn::make('edema')->label('Edema')->badge(),
                IconColumn::make('chest_pain')->label('Nyeri Dada')->boolean(),
                TextColumn::make('system_risk_status')->label('Risiko')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'educations' => [
                TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                TextColumn::make('category')->label('Kategori')->badge()->color(fn (string $state): string => match($state) {
                    'Video' => 'danger',
                    'Poster' => 'success',
                    'Artikel' => 'info',
                    default => 'gray',
                }),
                IconColumn::make('is_general')->label('Umum')->boolean(),
                TextColumn::make('patient.name')->label('Pasien')->placeholder('Semua Pasien'),
                TextColumn::make('education_date')->label('Tanggal')->date()->sortable(),
            ],
            'risk_alerts' => [
                TextColumn::make('alert_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable(),
                TextColumn::make('alert_type')->label('Jenis')->badge(),
                TextColumn::make('alert_level')->label('Level')->badge()->color(fn (?string $state): string => self::statusColor($state)),
                TextColumn::make('title')->label('Judul')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (?string $state): string => self::statusColor($state)),
            ],
            'users' => [
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->limit(34)->sortable(),
                TextColumn::make('role')->label('Role')->badge()->searchable()->sortable(),
                TextColumn::make('patient.name')->label('Pasien')->searchable()->limit(28)->placeholder('-'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ],
            'app_settings' => [
                TextColumn::make('key')
                    ->label('Kunci')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Kunci disalin ke clipboard'),
                TextColumn::make('value')
                    ->label('Nilai')
                    ->limit(50)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record): string => $record->value),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(80)
                    ->color('gray')
                    ->wrap(),
            ],
            default => [],
        };
    }

    public static function filters(string $key): array
    {
        return match ($key) {
            'patients' => [
                SelectFilter::make('patient_status')->label('Status Pasien')->options(self::options(['Aktif', 'Tidak Aktif', 'Pindah', 'Meninggal'])),
            ],
            'schedules' => [
                SelectFilter::make('shift')->label('Shift')->options(self::options(['Pagi', 'Siang', 'Sore', 'Malam'])),
                SelectFilter::make('attendance_status')->label('Kehadiran')->options(self::options(['Terjadwal', 'Hadir', 'Tidak Hadir', 'Reschedule'])),
            ],
            'sessions' => [
                SelectFilter::make('risk_category')->label('Kategori Risiko')->options(self::options(['Aman', 'Waspada', 'Tinggi', 'Darurat'])),
                SelectFilter::make('shift')->label('Shift')->options(self::options(['Pagi', 'Siang', 'Sore', 'Malam'])),
            ],
            'daily_monitorings' => [
                SelectFilter::make('fluid_status')->label('Status Cairan')->options(self::options(['Aman', 'Melebihi Batas'])),
                SelectFilter::make('risk_status')->label('Status Risiko')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat'])),
            ],
            'risk_symptoms' => [
                SelectFilter::make('system_risk_status')->label('Status Risiko')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat'])),
            ],
            'risk_alerts' => [
                SelectFilter::make('alert_level')->label('Level')->options(self::options(['Normal', 'Waspada', 'Tinggi', 'Darurat'])),
                SelectFilter::make('alert_type')->label('Jenis')->options(self::options(['Kenaikan Berat Badan', 'IDWG Tinggi', 'Cairan Melebihi Batas', 'Gejala Risiko', 'Prediksi Risiko', 'Tidak Input Data Harian'])),
                SelectFilter::make('status')->label('Status')->options(self::options(['Baru', 'Dibaca', 'Ditindaklanjuti', 'Selesai'])),
            ],
            'users' => [],
            default => [],
        };
    }

    private static function options(array $values): array
    {
        return array_combine($values, $values);
    }
}
