<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'main_diagnosis' => $this->main_diagnosis,
            'comorbidities' => $this->comorbidities,
            'hemodialysis_start_date' => $this->hemodialysis_start_date?->toDateString(),
            'hemodialysis_frequency' => $this->hemodialysis_frequency,
            'dry_weight' => $this->dry_weight,
            'vascular_access' => $this->vascular_access,
            'allergies' => $this->allergies,
            'routine_medications' => $this->routine_medications,
            'important_notes' => $this->important_notes,
        ];
    }
}
