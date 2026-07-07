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
}
