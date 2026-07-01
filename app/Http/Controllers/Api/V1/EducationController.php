<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\EducationRequest;
use App\Http\Resources\Api\EducationResource;
use App\Models\Education;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Education::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'education_date');

        return $this->success(EducationResource::collection($query->orderByDesc('education_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, Education $education): JsonResponse
    {
        if (! $this->patientAllowed($request, $education->patient_id)) {
            return $this->deny();
        }

        return $this->success(EducationResource::make($education->load('patient')));
    }

    public function store(EducationRequest $request): JsonResponse
    {
        $education = Education::create($request->validated());

        return $this->success(EducationResource::make($education), 'Edukasi berhasil dibuat.', 201);
    }

    public function update(EducationRequest $request, Education $education): JsonResponse
    {
        $education->update($request->validated());

        return $this->success(EducationResource::make($education), 'Edukasi berhasil diperbarui.');
    }

    public function destroy(Education $education): JsonResponse
    {
        $education->delete();

        return $this->success(null, 'Edukasi berhasil dihapus.');
    }
}
