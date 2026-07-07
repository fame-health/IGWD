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
        $query = DialysisSession::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('risk_category'), fn ($query) => $query->where('risk_category', $request->risk_category))
            ->when($request->filled('shift'), fn ($query) => $query->where('shift', $request->shift));

        $this->scopeForPatientRole($query, $request);
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

        $session = DialysisSession::create($request->validated());

        return $this->success(DialysisSessionResource::make($session), 'Sesi HD berhasil dibuat.', 201);
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
