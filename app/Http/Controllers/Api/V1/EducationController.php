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
        $query = Education::query()->with(['patient', 'createdBy']);

        if ($request->user()?->role === 'pasien') {
            $patientId = $request->user()->patient_id;
            $query->where(function ($q) use ($patientId) {
                $q->where('is_general', true)
                  ->orWhere('patient_id', $patientId);
            });
        } else {
            $query->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
                  ->when($request->boolean('general_only'), fn ($q) => $q->where('is_general', true));
        }

        $this->applyDateFilters($query, $request, 'education_date');

        return $this->success(EducationResource::collection($query->orderByDesc('is_general')->orderByDesc('education_date')->paginate($request->integer('per_page', 15))));
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
        if (! $this->patientAllowed($request, (int) $request->patient_id)) {
            return $this->deny();
        }

        $data = $request->validated();
        $data['created_by'] ??= $request->user()?->id;

        $education = Education::create($data);

        return $this->success(EducationResource::make($education->load(['patient', 'createdBy'])), 'Edukasi berhasil dibuat.', 201);
    }

    public function update(EducationRequest $request, Education $education): JsonResponse
    {
        if (
            ! $this->patientAllowed($request, $education->patient_id) ||
            ! $this->patientAllowed($request, (int) $request->patient_id)
        ) {
            return $this->deny();
        }

        $education->update($request->validated());

        return $this->success(EducationResource::make($education), 'Edukasi berhasil diperbarui.');
    }

    public function destroy(Education $education): JsonResponse
    {
        $education->delete();

        return $this->success(null, 'Edukasi berhasil dihapus.');
    }
}
