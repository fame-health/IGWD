<?php

namespace Tests\Feature;

use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DialysisSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_session_from_schedule_marks_schedule_as_attended(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-TEST-0001',
            'name' => 'Pasien HD',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        $schedule = DialysisSchedule::create([
            'patient_id' => $patient->id,
            'hd_date' => '2026-07-07',
            'day_name' => 'Selasa',
            'shift' => 'Pagi',
            'attendance_status' => 'Terjadwal',
        ]);

        DialysisSession::create([
            'patient_id' => $patient->id,
            'dialysis_schedule_id' => $schedule->id,
            'session_date' => '2026-07-07',
            'shift' => 'Pagi',
            'previous_post_hd_weight' => 60,
            'current_pre_hd_weight' => 62,
            'dry_weight' => 60,
        ]);

        $this->assertDatabaseHas('dialysis_schedules', [
            'id' => $schedule->id,
            'attendance_status' => 'Hadir',
        ]);

        $this->assertDatabaseHas('dialysis_sessions', [
            'patient_id' => $patient->id,
            'dialysis_schedule_id' => $schedule->id,
            'idwg_kg' => 2,
            'risk_category' => 'Waspada',
        ]);
    }

    public function test_first_session_uses_initial_previous_weight_when_patient_matches_seed_data(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-TEST-0002',
            'name' => 'Nurul Raudhatul',
            'gender' => 'perempuan',
            'patient_status' => 'Aktif',
        ]);

        $session = DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-07',
            'shift' => 'Pagi',
            'current_pre_hd_weight' => 52,
            'dry_weight' => 50,
        ])->refresh();

        $this->assertSame('49.70', $session->previous_post_hd_weight);
        $this->assertSame('2.30', $session->idwg_kg);
        $this->assertSame('4.60', $session->idwg_percent);
        $this->assertSame('Tinggi', $session->risk_category);
    }

    public function test_session_uses_previous_post_weight_before_initial_seed_weight(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-TEST-0003',
            'name' => 'Bayu johandri',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-04',
            'shift' => 'Pagi',
            'current_post_hd_weight' => 41.2,
        ]);

        $session = DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-07',
            'shift' => 'Pagi',
            'current_pre_hd_weight' => 43,
            'dry_weight' => 42,
        ])->refresh();

        $this->assertSame('41.20', $session->previous_post_hd_weight);
        $this->assertSame('1.80', $session->idwg_kg);
    }
}
