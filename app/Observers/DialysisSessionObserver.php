<?php

namespace App\Observers;

use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Services\IdwgCalculationService;
use App\Services\PreviousHdWeightService;
use App\Services\PushNotificationService;
use App\Services\RiskAlertService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DialysisSessionObserver
{
    public function saving(DialysisSession $dialysisSession): void
    {
        if (! $dialysisSession->created_by && Auth::id()) {
            $dialysisSession->created_by = Auth::id();
        }

        if (Auth::id()) {
            $dialysisSession->updated_by = Auth::id();
        }

        if ($dialysisSession->previous_post_hd_weight === null) {
            $previousWeight = app(PreviousHdWeightService::class)->resolveForSession($dialysisSession);

            if ($previousWeight !== null) {
                $dialysisSession->previous_post_hd_weight = $previousWeight;
            }
        }

        $dialysisSession->forceFill(app(IdwgCalculationService::class)->calculate($dialysisSession));
    }

    public function saved(DialysisSession $dialysisSession): void
    {
        if ($dialysisSession->dialysis_schedule_id) {
            $dialysisSession->schedule()
                ->where('attendance_status', 'Terjadwal')
                ->update(['attendance_status' => 'Hadir']);
        }

        app(RiskAlertService::class)->checkDialysisSession($dialysisSession);

        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'perawat', 'dokter'])) {
            app(PushNotificationService::class)->sendDialysisSessionUpdated($dialysisSession);
        }
    }

    public function created(DialysisSession $session): void
    {
        // Auto-generate next schedule (3 days after current session)
        try {
            $nextDate = Carbon::parse($session->session_date)->addDays(3);

            $existingSchedule = DialysisSchedule::where('patient_id', $session->patient_id)
                ->whereDate('hd_date', $nextDate->toDateString())
                ->first();

            if (! $existingSchedule) {
                // Try to find source metadata from current session's schedule
                $originalSchedule = $session->schedule;

                // If not found (manual session), try to find the most recent schedule for this patient
                if (! $originalSchedule) {
                    $originalSchedule = DialysisSchedule::where('patient_id', $session->patient_id)
                        ->orderBy('hd_date', 'desc')
                        ->first();
                }

                $user = Auth::user();

                DialysisSchedule::create([
                    'patient_id' => $session->patient_id,
                    'hd_date' => $nextDate->toDateString(),
                    'day_name' => $nextDate->translatedFormat('l'),
                    'start_time' => $originalSchedule?->start_time,
                    'end_time' => $originalSchedule?->end_time,
                    'shift' => $session->shift ?: ($originalSchedule?->shift ?: 'Pagi'),
                    'room' => $originalSchedule?->room,
                    'machine_number' => $originalSchedule?->machine_number,
                    'nurse_name' => ($user && $user->role === 'perawat') ? $user->name : $originalSchedule?->nurse_name,
                    'doctor_name' => $originalSchedule?->doctor_name,
                    'attendance_status' => 'Terjadwal',
                    'notes' => 'Jadwal otomatis (Dibuat otomatis setelah Sesi HD)',
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail or log auto-scheduling error so it doesn't break the session submission
            Log::error('Auto-scheduling failed in Observer: '.$e->getMessage());
        }
    }
}
