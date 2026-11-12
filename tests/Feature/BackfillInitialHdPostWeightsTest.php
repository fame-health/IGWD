<?php

namespace Tests\Feature;

use App\Models\DialysisSession;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillInitialHdPostWeightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_corrects_first_hd_session_post_weight(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-POST-0001',
            'name' => 'Dewi Suratingsih',
            'gender' => 'perempuan',
            'patient_status' => 'Aktif',
        ]);

        $session = DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-28',
            'shift' => 'Pagi',
            'previous_post_hd_weight' => 50.5,
            'current_pre_hd_weight' => 51.4,
            'current_post_hd_weight' => 50.9,
            'dry_weight' => 50.5,
        ]);

        $this->artisan('app:backfill-initial-hd-post-weights --apply')
            ->assertSuccessful();

        $this->assertSame('50.50', $session->refresh()->current_post_hd_weight);
    }

    public function test_it_keeps_later_hd_sessions_unchanged(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-POST-0002',
            'name' => 'Suherman',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);

        DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-28',
            'shift' => 'Pagi',
            'current_post_hd_weight' => 65,
        ]);

        $laterSession = DialysisSession::create([
            'patient_id' => $patient->id,
            'session_date' => '2026-07-31',
            'shift' => 'Pagi',
            'current_post_hd_weight' => 65,
        ]);

        $this->artisan('app:backfill-initial-hd-post-weights --apply')
            ->assertSuccessful();

        $this->assertSame('65.00', $laterSession->refresh()->current_post_hd_weight);
    }
}
