<?php

namespace App\Observers;

use App\Models\Patient;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;

class PatientObserver
{
    public function updated(Patient $patient): void
    {
        if (! $this->shouldNotify()) {
            return;
        }

        if (! $patient->wasChanged([
            'medical_record_number',
            'name',
            'nik',
            'birth_date',
            'gender',
            'address',
            'phone',
            'responsible_person_name',
            'responsible_person_phone',
            'payment_status',
            'patient_status',
        ])) {
            return;
        }

        app(PushNotificationService::class)->sendPatientDataUpdated($patient);
    }

    private function shouldNotify(): bool
    {
        return Auth::check() && Auth::user()->isRole('admin', 'perawat', 'dokter');
    }
}
