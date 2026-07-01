<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RiskSymptomRequest;
use App\Http\Resources\Api\RiskSymptomResource;
use App\Models\RiskSymptom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskSymptomController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = RiskSymptom::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('system_risk_status'), fn ($query) => $query->where('system_risk_status', $request->system_risk_status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'symptom_date');

        return $this->success(RiskSymptomResource::collection($query->orderByDesc('symptom_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, RiskSymptom $riskSymptom): JsonResponse
    {
        if (! $this->patientAllowed($request, $riskSymptom->patient_id)) {
            return $this->deny();
        }

        return $this->success(RiskSymptomResource::make($riskSymptom->load('patient')));
    }

    public function store(RiskSymptomRequest $request): JsonResponse
    {
        if (! $this->patientAllowed($request, (int) $request->patient_id)) {
            return $this->deny();
        }

        $symptom = RiskSymptom::create($request->validated());

        return $this->success(RiskSymptomResource::make($symptom), 'Gejala risiko berhasil dibuat.', 201);
    }

    public function update(RiskSymptomRequest $request, RiskSymptom $riskSymptom): JsonResponse
    {
        if (! $this->patientAllowed($request, $riskSymptom->patient_id)) {
            return $this->deny();
        }

        $riskSymptom->update($request->validated());

        return $this->success(RiskSymptomResource::make($riskSymptom), 'Gejala risiko berhasil diperbarui.');
    }

    public function destroy(RiskSymptom $riskSymptom): JsonResponse
    {
        $riskSymptom->delete();

        return $this->success(null, 'Gejala risiko berhasil dihapus.');
    }
}
