<?php

namespace Tests\Feature;

use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffPatientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_only_sees_their_own_dialysis_sessions(): void
    {
        [$assignedPatient, $otherPatient, $assignedSession, $otherSession] = $this->makeAssignedPatients();
        $patientUser = User::factory()->create([
            'role' => 'pasien',
            'patient_id' => $assignedPatient->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($patientUser);

        $response = $this->getJson('/api/v1/dialysis-sessions');

        $response->assertOk();
        $sessionIds = collect($this->responseItems($response))->pluck('id')->all();

        $this->assertContains($assignedSession->id, $sessionIds);
        $this->assertNotContains($otherSession->id, $sessionIds);

        $this->getJson("/api/v1/dialysis-sessions/{$otherSession->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ditolak.');
    }

    public function test_nurse_only_sees_patients_assigned_to_their_schedule(): void
    {
        [$assignedPatient, $otherPatient, $assignedSession, $otherSession] = $this->makeAssignedPatients();
        $nurse = User::factory()->create([
            'name' => 'Perawat A',
            'role' => 'perawat',
            'is_active' => true,
        ]);

        Sanctum::actingAs($nurse);

        $patientsResponse = $this->getJson('/api/v1/patients');
        $patientsResponse->assertOk();
        $patientIds = collect($this->responseItems($patientsResponse))->pluck('id')->all();

        $this->assertContains($assignedPatient->id, $patientIds);
        $this->assertNotContains($otherPatient->id, $patientIds);

        $sessionsResponse = $this->getJson('/api/v1/dialysis-sessions');
        $sessionsResponse->assertOk();
        $sessionIds = collect($this->responseItems($sessionsResponse))->pluck('id')->all();

        $this->assertContains($assignedSession->id, $sessionIds);
        $this->assertNotContains($otherSession->id, $sessionIds);

        $reportsResponse = $this->getJson('/api/v1/reports/dialysis-sessions');
        $reportsResponse->assertOk();
        $reportSessionIds = collect($this->responseItems($reportsResponse))->pluck('id')->all();

        $this->assertContains($assignedSession->id, $reportSessionIds);
        $this->assertNotContains($otherSession->id, $reportSessionIds);

        $this->getJson("/api/v1/patients/{$otherPatient->id}")->assertForbidden();
        $this->getJson("/api/v1/dialysis-sessions/{$otherSession->id}")->assertForbidden();
    }

    public function test_doctor_only_sees_patients_assigned_to_their_schedule(): void
    {
        [$assignedPatient, $otherPatient, $assignedSession, $otherSession] = $this->makeAssignedPatients();
        $doctor = User::factory()->create([
            'name' => 'Dokter A',
            'role' => 'dokter',
            'is_active' => true,
        ]);

        Sanctum::actingAs($doctor);

        $patientsResponse = $this->getJson('/api/v1/patients');
        $patientsResponse->assertOk();
        $patientIds = collect($this->responseItems($patientsResponse))->pluck('id')->all();

        $this->assertContains($assignedPatient->id, $patientIds);
        $this->assertNotContains($otherPatient->id, $patientIds);

        $sessionsResponse = $this->getJson('/api/v1/dialysis-sessions');
        $sessionsResponse->assertOk();
        $sessionIds = collect($this->responseItems($sessionsResponse))->pluck('id')->all();

        $this->assertContains($assignedSession->id, $sessionIds);
        $this->assertNotContains($otherSession->id, $sessionIds);

        $reportsResponse = $this->getJson('/api/v1/reports/dialysis-sessions');
        $reportsResponse->assertOk();
        $reportSessionIds = collect($this->responseItems($reportsResponse))->pluck('id')->all();

        $this->assertContains($assignedSession->id, $reportSessionIds);
        $this->assertNotContains($otherSession->id, $reportSessionIds);

        $this->getJson("/api/v1/patients/{$otherPatient->id}")->assertForbidden();
        $this->getJson("/api/v1/dialysis-sessions/{$otherSession->id}")->assertForbidden();
    }

    private function makeAssignedPatients(): array
    {
        $assignedPatient = Patient::create([
            'medical_record_number' => 'RM-ACCESS-0001',
            'name' => 'Pasien A',
            'gender' => 'perempuan',
            'patient_status' => 'Aktif',
        ]);

        $otherPatient = Patient::create([
            'medical_record_number' => 'RM-ACCESS-0002',
            'name' => 'Pasien B',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        $assignedSchedule = DialysisSchedule::create([
            'patient_id' => $assignedPatient->id,
            'hd_date' => '2026-07-07',
            'shift' => 'Pagi',
            'doctor_name' => 'Dokter A',
            'nurse_name' => 'Perawat A',
            'attendance_status' => 'Terjadwal',
        ]);

        $otherSchedule = DialysisSchedule::create([
            'patient_id' => $otherPatient->id,
            'hd_date' => '2026-07-08',
            'shift' => 'Siang',
            'doctor_name' => 'Dokter B',
            'nurse_name' => 'Perawat B',
            'attendance_status' => 'Terjadwal',
        ]);

        $assignedSession = DialysisSession::create([
            'patient_id' => $assignedPatient->id,
            'dialysis_schedule_id' => $assignedSchedule->id,
            'session_date' => '2026-07-07',
            'shift' => 'Pagi',
            'previous_post_hd_weight' => 60,
            'current_pre_hd_weight' => 62,
            'dry_weight' => 60,
        ]);

        $otherSession = DialysisSession::create([
            'patient_id' => $otherPatient->id,
            'dialysis_schedule_id' => $otherSchedule->id,
            'session_date' => '2026-07-08',
            'shift' => 'Siang',
            'previous_post_hd_weight' => 58,
            'current_pre_hd_weight' => 59,
            'dry_weight' => 58,
        ]);

        return [$assignedPatient, $otherPatient, $assignedSession, $otherSession];
    }

    private function responseItems($response): array
    {
        return $response->json('data.data') ?? $response->json('data') ?? [];
    }
}
