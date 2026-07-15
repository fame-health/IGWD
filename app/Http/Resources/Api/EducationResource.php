<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'education_date' => $this->education_date?->toDateString(),
            'education_materials' => $this->education_materials,
            'patient_understanding' => $this->patient_understanding,
            'fluid_compliance' => $this->fluid_compliance,
            'schedule_compliance' => $this->schedule_compliance,
            'follow_up_notes' => $this->follow_up_notes,
            'educator_name' => $this->educator_name,
            'created_by' => $this->created_by,
            'created_by_name' => $this->createdBy?->name,
        ];
    }
}
