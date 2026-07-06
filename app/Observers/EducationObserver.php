<?php

namespace App\Observers;

use App\Models\Education;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;

class EducationObserver
{
    public function created(Education $education): void
    {
        if ($this->shouldNotify()) {
            app(PushNotificationService::class)->sendEducationCreated($education);
        }
    }

    public function updated(Education $education): void
    {
        if (! $this->shouldNotify()) {
            return;
        }

        if (! $education->wasChanged([
            'education_date',
            'education_materials',
            'patient_understanding',
            'fluid_compliance',
            'schedule_compliance',
            'follow_up_notes',
            'educator_name',
        ])) {
            return;
        }

        app(PushNotificationService::class)->sendEducationUpdated($education);
    }

    private function shouldNotify(): bool
    {
        return Auth::check() && Auth::user()->isRole('admin', 'perawat', 'dokter');
    }
}
