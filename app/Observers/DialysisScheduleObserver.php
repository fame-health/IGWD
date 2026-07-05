<?php

namespace App\Observers;

use App\Models\DialysisSchedule;
use App\Services\PushNotificationService;

class DialysisScheduleObserver
{
    public function created(DialysisSchedule $dialysisSchedule): void
    {
        app(PushNotificationService::class)->sendDialysisScheduleCreated($dialysisSchedule);
    }

    public function updated(DialysisSchedule $dialysisSchedule): void
    {
        if (! $dialysisSchedule->wasChanged([
            'patient_id',
            'hd_date',
            'day_name',
            'shift',
            'room',
            'machine_number',
            'doctor_name',
            'nurse_name',
            'attendance_status',
            'notes',
        ])) {
            return;
        }

        app(PushNotificationService::class)->sendDialysisScheduleUpdated($dialysisSchedule);
    }
}
