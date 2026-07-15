<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\VitalSignRequest;
use App\Http\Resources\Api\VitalSignResource;
use App\Models\VitalSign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitalSignController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = VitalSign::query()->with(['patient', 'createdBy'])
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('dialysis_session_id'), fn ($query) => $query->where('dialysis_session_id', $request->dialysis_session_id));

        $this->scopeForPatientRole($query, $request);
        $this->scopeForCreator($query, $request);
        $this->applyDateFilters($query, $request, 'measurement_date');

        return $this->success(VitalSignResource::collection($query->orderByDesc('measurement_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, VitalSign $vitalSign): JsonResponse
    {
        if (! $this->patientAllowed($request, $vitalSign->patient_id)) {
            return $this->deny();
        }

        return $this->success(VitalSignResource::make($vitalSign->load('patient')));
    }

    public function store(VitalSignRequest $request): JsonResponse
    {
        if (! $this->patientAllowed($request, (int) $request->patient_id)) {
            return $this->deny();
        }

        $data = $request->validated();
        $data['created_by'] ??= $request->user()?->id;

        $vitalSign = VitalSign::create($data);

        return $this->success(VitalSignResource::make($vitalSign->load(['patient', 'createdBy'])), 'Tanda vital berhasil dibuat.', 201);
    }

    public function update(VitalSignRequest $request, VitalSign $vitalSign): JsonResponse
    {
        if (
            ! $this->patientAllowed($request, $vitalSign->patient_id) ||
            ! $this->patientAllowed($request, (int) $request->patient_id)
        ) {
            return $this->deny();
        }

        $vitalSign->update($request->validated());

        return $this->success(VitalSignResource::make($vitalSign), 'Tanda vital berhasil diperbarui.');
    }

    public function destroy(VitalSign $vitalSign): JsonResponse
    {
        $vitalSign->delete();

        return $this->success(null, 'Tanda vital berhasil dihapus.');
    }
}
