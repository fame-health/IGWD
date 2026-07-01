<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VitalSignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dialysis_session_id' => $this->dialysis_session_id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'measurement_date' => $this->measurement_date?->toDateString(),
            'blood_pressure_before' => $this->blood_pressure_before,
            'pulse_before' => $this->pulse_before,
            'temperature' => $this->temperature,
            'respiration' => $this->respiration,
            'oxygen_saturation' => $this->oxygen_saturation,
            'blood_pressure_during' => $this->blood_pressure_during,
            'blood_pressure_after' => $this->blood_pressure_after,
            'complaints' => $this->complaints,
        ];
    }
}
