<?php

namespace Tests\Feature;

use App\Models\DailyMonitoring;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyMonitoringCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_adult_monitoring_calculates_iwl_output_and_fluid_balance(): void
    {
        Carbon::setTestNow('2026-07-15');

        $patient = Patient::create([
            'medical_record_number' => 'RM-IWL-ADULT',
            'name' => 'Pasien Dewasa',
            'birth_date' => '1986-07-15',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        $monitoring = DailyMonitoring::create([
            'patient_id' => $patient->id,
            'monitoring_date' => '2026-07-15',
            'last_post_hd_weight' => 68,
            'today_weight' => 70,
            'fluid_intake_ml' => 1200,
        ]);

        $this->assertSame(700, $monitoring->insensible_water_loss_ml);
        $this->assertSame(700, $monitoring->fluid_output_ml);
        $this->assertSame(500, $monitoring->fluid_difference_ml);
        $this->assertSame('Melebihi Batas', $monitoring->fluid_status);
        $this->assertSame('Tinggi', $monitoring->risk_status);
    }

    public function test_child_monitoring_calculates_iwl_from_holliday_segar_maintenance(): void
    {
        Carbon::setTestNow('2026-07-15');

        $patient = Patient::create([
            'medical_record_number' => 'RM-IWL-CHILD',
            'name' => 'Pasien Anak',
            'birth_date' => '2016-07-15',
            'gender' => 'perempuan',
            'patient_status' => 'Aktif',
        ]);

        $monitoring = DailyMonitoring::create([
            'patient_id' => $patient->id,
            'monitoring_date' => '2026-07-15',
            'today_weight' => 32,
            'fluid_intake_ml' => 500,
        ]);

        $this->assertSame(580, $monitoring->insensible_water_loss_ml);
        $this->assertSame(580, $monitoring->fluid_output_ml);
        $this->assertSame(-80, $monitoring->fluid_difference_ml);
        $this->assertSame('Aman', $monitoring->fluid_status);
        $this->assertSame('Normal', $monitoring->risk_status);
    }
}
