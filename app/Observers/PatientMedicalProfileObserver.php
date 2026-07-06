<?php

namespace App\Observers;

use App\Models\PatientMedicalProfile;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;

class PatientMedicalProfileObserver
{
    public function created(PatientMedicalProfile $profile): void
    {
        if ($this->shouldNotify()) {
            app(PushNotificationService::class)->sendMedicalProfileUpdated($profile);
        }
    }

    public function updated(PatientMedicalProfile $profile): void
    {
        if (! $this->shouldNotify()) {
            return;
        }

        if (! $profile->wasChanged([
            'main_diagnosis',
            'comorbidities',
            'hemodialysis_start_date',
            'hemodialysis_frequency',
            'dry_weight',
            'vascular_access',
            'allergies',
            'routine_medications',
            'important_notes',
        ])) {
            return;
        }

        app(PushNotificationService::class)->sendMedicalProfileUpdated($profile);
    }

    private function shouldNotify(): bool
    {
        return Auth::check() && Auth::user()->isRole('admin', 'perawat', 'dokter');
    }
}
