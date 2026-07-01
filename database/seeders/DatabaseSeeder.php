<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Education;
use App\Models\Patient;
use App\Models\PatientMedicalProfile;
use App\Models\RiskAlert;
use App\Models\RiskSymptom;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'idwg_safe_max' => ['3', 'Batas maksimum IDWG kategori aman.'],
            'idwg_warning_max' => ['4.5', 'Batas maksimum IDWG kategori waspada.'],
            'idwg_emergency_min' => ['6', 'Batas minimum IDWG kategori darurat.'],
            'default_daily_fluid_limit_ml' => ['1000', 'Batas cairan harian default.'],
            'daily_weight_gain_warning_kg' => ['1', 'Batas kenaikan BB harian kategori waspada.'],
            'daily_weight_gain_high_kg' => ['2', 'Batas kenaikan BB harian kategori tinggi.'],
            'daily_weight_gain_emergency_kg' => ['3', 'Batas kenaikan BB harian kategori darurat.'],
            'fluid_over_limit_warning_ml' => ['1', 'Selisih cairan mulai dianggap waspada.'],
            'fluid_over_limit_high_ml' => ['500', 'Selisih cairan kategori tinggi.'],
            'fluid_over_limit_emergency_ml' => ['1000', 'Selisih cairan kategori darurat.'],
            'predicted_idwg_warning_percent' => ['3', 'Prediksi IDWG kategori waspada.'],
            'predicted_idwg_high_percent' => ['4.5', 'Prediksi IDWG kategori tinggi.'],
            'predicted_idwg_emergency_percent' => ['6', 'Prediksi IDWG kategori darurat.'],
            'daily_monitoring_required' => ['true', 'Wajib input monitoring harian.'],
            'missing_daily_monitoring_alert_time' => ['20:00', 'Jam cek data monitoring harian kosong.'],
        ];

        foreach ($settings as $key => [$value, $description]) {
            AppSetting::updateOrCreate(['key' => $key], compact('value', 'description'));
        }

        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'perawat@example.com'], [
            'name' => 'Perawat Demo',
            'password' => 'password',
            'role' => 'perawat',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'dokter@example.com'], [
            'name' => 'Dokter Demo',
            'password' => 'password',
            'role' => 'dokter',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'manajemen@example.com'], [
            'name' => 'Manajemen Demo',
            'password' => 'password',
            'role' => 'manajemen',
            'is_active' => true,
        ]);

        $patients = collect([
            [
                'medical_record_number' => 'RM-0001',
                'name' => 'Siti Aminah',
                'nik' => '3171000000000001',
                'birth_date' => '1972-04-12',
                'gender' => 'perempuan',
                'address' => 'Jakarta',
                'phone' => '081200000001',
                'payment_status' => 'BPJS',
            ],
            [
                'medical_record_number' => 'RM-0002',
                'name' => 'Budi Santoso',
                'nik' => '3171000000000002',
                'birth_date' => '1968-08-20',
                'gender' => 'laki-laki',
                'address' => 'Depok',
                'phone' => '081200000002',
                'payment_status' => 'Umum',
            ],
            [
                'medical_record_number' => 'RM-0003',
                'name' => 'Rina Kartika',
                'nik' => '3171000000000003',
                'birth_date' => '1980-11-05',
                'gender' => 'perempuan',
                'address' => 'Bekasi',
                'phone' => '081200000003',
                'payment_status' => 'Asuransi',
            ],
        ])->map(fn (array $data) => Patient::updateOrCreate(
            ['medical_record_number' => $data['medical_record_number']],
            $data + ['patient_status' => 'Aktif']
        ));

        User::updateOrCreate(['email' => 'pasien@example.com'], [
            'name' => 'Pasien Demo',
            'password' => 'password',
            'role' => 'pasien',
            'patient_id' => $patients->first()->id,
            'is_active' => true,
        ]);

        foreach ($patients as $index => $patient) {
            PatientMedicalProfile::updateOrCreate(['patient_id' => $patient->id], [
                'main_diagnosis' => 'CKD Stage 5 on Hemodialysis',
                'comorbidities' => $index === 0 ? 'Hipertensi' : 'Diabetes Melitus, Hipertensi',
                'hemodialysis_start_date' => now()->subMonths(18 + $index)->toDateString(),
                'hemodialysis_frequency' => '2x per minggu',
                'dry_weight' => 60 + $index,
                'vascular_access' => $index === 1 ? 'CDL' : 'AV Fistula',
                'routine_medications' => 'Amlodipine, CaCO3',
                'important_notes' => 'Monitoring cairan dan IDWG rutin.',
            ]);

            $lastDate = now()->subDays(2)->toDateString();
            $nextDate = now()->addDays(1 + $index)->toDateString();

            $schedule = DialysisSchedule::create([
                'patient_id' => $patient->id,
                'hd_date' => $nextDate,
                'day_name' => now()->addDays(1 + $index)->translatedFormat('l'),
                'shift' => $index === 0 ? 'Pagi' : 'Siang',
                'room' => 'HD-1',
                'machine_number' => 'M-0'.($index + 1),
                'doctor_name' => 'Dokter Demo',
                'nurse_name' => 'Perawat Demo',
                'attendance_status' => 'Terjadwal',
            ]);

            $session = DialysisSession::create([
                'patient_id' => $patient->id,
                'dialysis_schedule_id' => $schedule->id,
                'session_date' => $lastDate,
                'shift' => $schedule->shift,
                'previous_post_hd_weight' => 59 + $index,
                'current_pre_hd_weight' => 62 + $index,
                'dry_weight' => 60 + $index,
                'current_post_hd_weight' => 59 + $index,
                'target_ultrafiltration' => 2.5,
                'hd_duration_minutes' => 240,
                'staff_notes' => 'Sesi berjalan stabil.',
            ]);

            DailyMonitoring::create([
                'patient_id' => $patient->id,
                'last_dialysis_session_id' => $session->id,
                'monitoring_date' => now()->toDateString(),
                'day_after_hd' => 2,
                'last_hd_date' => $lastDate,
                'next_hd_date' => $nextDate,
                'last_post_hd_weight' => 59 + $index,
                'today_weight' => 61 + $index,
                'fluid_intake_ml' => $index === 0 ? 1300 : 900,
                'daily_fluid_limit_ml' => 1000,
                'symptom_notes' => $index === 0 ? 'Pasien merasa agak sesak saat aktivitas.' : null,
                'staff_notes' => 'Input monitoring demo.',
            ]);

            VitalSign::create([
                'patient_id' => $patient->id,
                'dialysis_session_id' => $session->id,
                'measurement_date' => $lastDate,
                'blood_pressure_before' => '150/90',
                'pulse_before' => 84,
                'temperature' => 36.7,
                'respiration' => 20,
                'oxygen_saturation' => 97,
                'complaints' => $index === 0 ? 'Lemas ringan' : null,
            ]);

            RiskSymptom::create([
                'patient_id' => $patient->id,
                'dialysis_session_id' => $session->id,
                'symptom_date' => now()->toDateString(),
                'shortness_of_breath' => $index === 0 ? 'Sedang' : 'Tidak',
                'edema' => $index === 1 ? 'Kaki' : 'Tidak',
                'dizziness_or_weakness' => $index === 2,
                'nausea_or_vomiting' => $index === 2,
                'description' => 'Data gejala demo.',
            ]);

            Education::create([
                'patient_id' => $patient->id,
                'education_date' => now()->toDateString(),
                'education_materials' => 'Pembatasan cairan, diet rendah garam, dan kepatuhan jadwal HD.',
                'patient_understanding' => 'Cukup',
                'fluid_compliance' => $index === 0 ? 'Kurang' : 'Cukup',
                'schedule_compliance' => 'Hadir',
                'follow_up_notes' => 'Evaluasi ulang pada kunjungan berikutnya.',
                'educator_name' => 'Perawat Demo',
            ]);
        }

        RiskAlert::firstOrCreate([
            'patient_id' => $patients->first()->id,
            'source_type' => 'manual_seed',
            'source_id' => null,
            'alert_type' => 'Cairan Melebihi Batas',
            'alert_date' => now()->toDateString(),
        ], [
            'alert_level' => 'Waspada',
            'title' => 'Alert Demo Cairan',
            'message' => 'Pasien demo memiliki cairan masuk melebihi batas harian.',
            'trigger_value' => '1300 ml',
            'threshold_value' => '1000 ml',
            'recommendation' => 'Lakukan edukasi pembatasan cairan.',
            'status' => 'Baru',
        ]);
    }
}
