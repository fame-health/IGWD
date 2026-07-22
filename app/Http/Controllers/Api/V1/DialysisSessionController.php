<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DialysisSessionRequest;
use App\Http\Requests\Api\DoctorNoteRequest;
use App\Http\Resources\Api\DialysisSessionResource;
use App\Models\DialysisSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialysisSessionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = DialysisSession::query()->with(['patient', 'createdBy'])
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('risk_category'), fn ($query) => $query->where('risk_category', $request->risk_category))
            ->when($request->filled('shift'), fn ($query) => $query->where('shift', $request->shift));

        $this->scopeForPatientRole($query, $request);
        $this->scopeForCreator($query, $request);
        $this->applyDateFilters($query, $request, 'session_date');

        return $this->success(DialysisSessionResource::collection($query->orderByDesc('session_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, DialysisSession $dialysisSession): JsonResponse
    {
        if (! $this->patientAllowed($request, $dialysisSession->patient_id)) {
            return $this->deny();
        }

        return $this->success(DialysisSessionResource::make($dialysisSession->load('patient')));
    }

    public function store(DialysisSessionRequest $request): JsonResponse
    {
        if (! $this->patientAllowed($request, (int) $request->patient_id)) {
            return $this->deny();
        }

        $data = $request->validated();
        $data['created_by'] ??= $request->user()?->id;

        $session = DialysisSession::create($data);

        // Auto-generate next schedule (3 days after current session)
        try {
            $nextDate = \Illuminate\Support\Carbon::parse($session->session_date)->addDays(3);

            $existingSchedule = \App\Models\DialysisSchedule::where('patient_id', $session->patient_id)
                ->whereDate('hd_date', $nextDate->toDateString())
                ->first();

            if (!$existingSchedule) {
                $originalSchedule = $session->schedule;
                \App\Models\DialysisSchedule::create([
                    'patient_id' => $session->patient_id,
                    'hd_date' => $nextDate->toDateString(),
                    'day_name' => $nextDate->translatedFormat('l'),
                    'start_time' => $originalSchedule?->start_time,
                    'end_time' => $originalSchedule?->end_time,
                    'shift' => $session->shift,
                    'room' => $originalSchedule?->room,
                    'machine_number' => $originalSchedule?->machine_number,
                    'nurse_name' => $request->user()->role === 'perawat' ? $request->user()->name : $originalSchedule?->nurse_name,
                    'doctor_name' => $originalSchedule?->doctor_name,
                    'attendance_status' => 'Terjadwal',
                    'notes' => 'Jadwal otomatis (3 hari setelah sesi terakhir)',
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail or log auto-scheduling error so it doesn't break the session submission
            \Illuminate\Support\Facades\Log::error("Auto-scheduling failed: " . $e->getMessage());
        }

        return $this->success(DialysisSessionResource::make($session->load(['patient', 'createdBy'])), 'Sesi HD berhasil dibuat.', 201);
    }

    public function update(DialysisSessionRequest $request, DialysisSession $dialysisSession): JsonResponse
    {
        if (
            ! $this->patientAllowed($request, $dialysisSession->patient_id) ||
            ! $this->patientAllowed($request, (int) $request->patient_id)
        ) {
            return $this->deny();
        }

        $dialysisSession->update($request->validated());

        return $this->success(DialysisSessionResource::make($dialysisSession), 'Sesi HD berhasil diperbarui.');
    }

    public function doctorNote(DoctorNoteRequest $request, DialysisSession $dialysisSession): JsonResponse
    {
        if (! $this->patientAllowed($request, $dialysisSession->patient_id)) {
            return $this->deny();
        }

        $dialysisSession->update($request->validated());

        return $this->success(DialysisSessionResource::make($dialysisSession), 'Catatan dokter berhasil disimpan.');
    }

    public function destroy(DialysisSession $dialysisSession): JsonResponse
    {
        $dialysisSession->delete();

        return $this->success(null, 'Sesi HD berhasil dihapus.');
    }
}
