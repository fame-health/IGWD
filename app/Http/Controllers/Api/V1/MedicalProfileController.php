<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\MedicalProfileRequest;
use App\Http\Resources\Api\MedicalProfileResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalProfileController extends BaseApiController
{
    public function show(Request $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        return $this->success(MedicalProfileResource::make($patient->medicalProfile));
    }

    public function store(MedicalProfileRequest $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        $profile = $patient->medicalProfile()->updateOrCreate([], $request->validated());

        return $this->success(MedicalProfileResource::make($profile), 'Data medis berhasil disimpan.', 201);
    }

    public function update(MedicalProfileRequest $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        $profile = $patient->medicalProfile()->updateOrCreate([], $request->validated());

        return $this->success(MedicalProfileResource::make($profile), 'Data medis berhasil diperbarui.');
    }
}
