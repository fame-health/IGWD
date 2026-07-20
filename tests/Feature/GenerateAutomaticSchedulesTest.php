<?php

namespace Tests\Feature;

use App\Models\DialysisSchedule;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateAutomaticSchedulesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_friday_schedules_on_monday_in_hd_timezone(): void
    {
        config(['hd.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow(Carbon::parse('2026-07-20 06:00:00', 'Asia/Jakarta'));

        $activePatient = Patient::create([
            'medical_record_number' => 'RM-AUTO-0001',
            'name' => 'Pasien Aktif',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        Patient::create([
            'medical_record_number' => 'RM-AUTO-0002',
            'name' => 'Pasien Nonaktif',
            'gender' => 'perempuan',
            'patient_status' => 'Tidak Aktif',
        ]);

        DialysisSchedule::create([
            'patient_id' => $activePatient->id,
            'hd_date' => '2026-07-20',
            'day_name' => 'Senin',
            'shift' => 'Siang',
            'attendance_status' => 'Terjadwal',
        ]);

        $this->artisan('app:generate-automatic-schedules')
            ->expectsOutput('Generated 1 schedules for Jumat (2026-07-24).')
            ->assertSuccessful();

        $generatedSchedule = DialysisSchedule::query()
            ->where('patient_id', $activePatient->id)
            ->whereDate('hd_date', '2026-07-24')
            ->first();

        $this->assertNotNull($generatedSchedule);
        $this->assertSame('2026-07-24', $generatedSchedule->hd_date->toDateString());
        $this->assertSame('Jumat', $generatedSchedule->day_name);
        $this->assertSame('Siang', $generatedSchedule->shift);
        $this->assertSame('Terjadwal', $generatedSchedule->attendance_status);
        $this->assertSame('Otomatis dibuat oleh sistem (Jadwal Rutin)', $generatedSchedule->notes);

        $this->assertSame(2, DialysisSchedule::count());
    }

    public function test_it_generates_next_monday_schedules_on_friday_in_hd_timezone(): void
    {
        config(['hd.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow(Carbon::parse('2026-07-24 06:00:00', 'Asia/Jakarta'));

        $activePatient = Patient::create([
            'medical_record_number' => 'RM-AUTO-0004',
            'name' => 'Pasien Aktif',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        DialysisSchedule::create([
            'patient_id' => $activePatient->id,
            'hd_date' => '2026-07-24',
            'day_name' => 'Jumat',
            'shift' => 'Sore',
            'attendance_status' => 'Terjadwal',
        ]);

        $this->artisan('app:generate-automatic-schedules')
            ->expectsOutput('Generated 1 schedules for Senin (2026-07-27).')
            ->assertSuccessful();

        $generatedSchedule = DialysisSchedule::query()
            ->where('patient_id', $activePatient->id)
            ->whereDate('hd_date', '2026-07-27')
            ->first();

        $this->assertNotNull($generatedSchedule);
        $this->assertSame('2026-07-27', $generatedSchedule->hd_date->toDateString());
        $this->assertSame('Senin', $generatedSchedule->day_name);
        $this->assertSame('Sore', $generatedSchedule->shift);
        $this->assertSame('Terjadwal', $generatedSchedule->attendance_status);

        $this->assertSame(2, DialysisSchedule::count());
    }

    public function test_it_skips_non_monday_or_friday(): void
    {
        config(['hd.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow(Carbon::parse('2026-07-21 06:00:00', 'Asia/Jakarta'));

        Patient::create([
            'medical_record_number' => 'RM-AUTO-0003',
            'name' => 'Pasien Aktif',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        $this->artisan('app:generate-automatic-schedules')
            ->expectsOutput('Today is not Monday or Friday. Skipping.')
            ->assertSuccessful();

        $this->assertDatabaseCount('dialysis_schedules', 0);
    }
}
