<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskSymptomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'dialysis_session_id' => $this->dialysis_session_id,
            'daily_monitoring_id' => $this->daily_monitoring_id,
            'symptom_date' => $this->symptom_date?->toDateString(),
            'shortness_of_breath' => $this->shortness_of_breath,
            'edema' => $this->edema,
            'muscle_cramp' => (bool) $this->muscle_cramp,
            'dizziness_or_weakness' => (bool) $this->dizziness_or_weakness,
            'nausea_or_vomiting' => (bool) $this->nausea_or_vomiting,
            'chest_pain' => (bool) $this->chest_pain,
            'headache' => (bool) $this->headache,
            'description' => $this->description,
            'system_risk_status' => $this->system_risk_status,
        ];
    }
}
