<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function updated(User $user): void
    {
        if (
            ! Auth::check()
            || ! Auth::user()->isRole('admin')
            || $user->role !== 'pasien'
            || Auth::id() === $user->id
        ) {
            return;
        }

        if (! $user->wasChanged([
            'name',
            'email',
            'patient_id',
            'is_active',
        ])) {
            return;
        }

        app(PushNotificationService::class)->sendPatientAccountUpdated($user);
    }
}
