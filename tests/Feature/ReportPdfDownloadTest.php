<?php

namespace Tests\Feature;

use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\PatientMedicalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_report_pdfs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        foreach (['dashboard', 'daily-monitoring', 'dialysis-sessions', 'dialysis-sessions-hd-1-2', 'patient-biodata', 'risk-patients', 'risk-alerts'] as $report) {
            $response = $this->get("/admin/reports/{$report}/pdf");

            $response->assertOk();
            $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_combined_hd_report_contains_hd_one_and_two_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'medical_record_number' => 'RM-PDF-HD',
            'name' => 'Pasien HD PDF',
            'gender' => 'perempuan',
            'patient_status' => 'Aktif',
        ]);

        $scheduleOne = DialysisSchedule::create([
            'patient_id' => $patient->id,
            'hd_date' => '2026-08-20',
            'day_name' => 'Kamis',
            'shift' => 'Pagi',
            'room' => 'HD 1',
            'machine_number' => 'M-01',
        ]);

        $scheduleTwo = DialysisSchedule::create([
            'patient_id' => $patient->id,
            'hd_date' => '2026-08-22',
            'day_name' => 'Sabtu',
            'shift' => 'Siang',
            'room' => 'HD 2',
            'machine_number' => 'M-02',
        ]);

        DialysisSession::create([
            'patient_id' => $patient->id,
            'dialysis_schedule_id' => $scheduleOne->id,
            'session_date' => '2026-08-20',
            'shift' => 'Pagi',
            'current_pre_hd_weight' => 60,
            'current_post_hd_weight' => 58,
            'idwg_percent' => 3.1,
            'risk_category' => 'Waspada',
        ]);

        DialysisSession::create([
            'patient_id' => $patient->id,
            'dialysis_schedule_id' => $scheduleTwo->id,
            'session_date' => '2026-08-22',
            'shift' => 'Siang',
            'current_pre_hd_weight' => 61,
            'current_post_hd_weight' => 58.5,
            'idwg_percent' => 4.2,
            'risk_category' => 'Waspada',
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports/dialysis-sessions-hd-1-2/pdf');

        $response->assertOk();
        $this->assertStringContainsString('Sesi HD 1', $response->getContent());
        $this->assertStringContainsString('Sesi HD 2', $response->getContent());
        $this->assertStringContainsString('Pasien HD PDF', $response->getContent());
    }

    public function test_patient_biodata_report_contains_complete_biodata_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'medical_record_number' => 'RM-PDF-BIO',
            'name' => 'Pasien Biodata PDF',
            'nik' => '3171000000000099',
            'birth_date' => '1975-02-10',
            'gender' => 'laki-laki',
            'ethnic_group' => 'Minang',
            'education' => 'SMA',
            'occupation' => 'Wiraswasta',
            'marital_status' => 'Menikah',
            'address' => 'Jl. Mawar No. 1',
            'phone' => '081299999999',
            'responsible_person_name' => 'Keluarga Pasien',
            'responsible_person_phone' => '081288888888',
            'payment_status' => 'BPJS',
            'patient_status' => 'Aktif',
        ]);

        PatientMedicalProfile::create([
            'patient_id' => $patient->id,
            'main_diagnosis' => 'CKD Stage 5',
            'medical_history' => 'Hipertensi lama',
            'comorbidities' => 'Diabetes Melitus',
            'hemodialysis_start_date' => '2025-01-15',
            'hemodialysis_frequency' => '2x per minggu',
            'dry_weight' => 58,
            'daily_fluid_limit_ml' => 1000,
            'vascular_access' => 'AV Fistula',
            'allergies' => 'Tidak ada',
            'routine_medications' => 'Amlodipine',
            'important_notes' => 'Pantau cairan harian',
            'blood_type' => 'O',
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports/patient-biodata/pdf');

        $response->assertOk();
        $this->assertStringContainsString('Identitas Pasien', $response->getContent());
        $this->assertStringContainsString('Kontak dan Demografi', $response->getContent());
        $this->assertStringContainsString('Profil Medis Hemodialisis', $response->getContent());
        $this->assertStringContainsString('Pasien Biodata PDF', $response->getContent());
    }
}
