<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialysisSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'dialysis_schedule_id' => $this->dialysis_schedule_id,
            'session_date' => $this->session_date?->toDateString(),
            'shift' => $this->shift,
            'previous_post_hd_weight' => $this->previous_post_hd_weight,
            'current_pre_hd_weight' => $this->current_pre_hd_weight,
            'dry_weight' => $this->dry_weight,
            'idwg_kg' => $this->idwg_kg,
            'idwg_percent' => $this->idwg_percent,
            'risk_category' => $this->risk_category,
            'current_post_hd_weight' => $this->current_post_hd_weight,
            'target_ultrafiltration' => $this->target_ultrafiltration,
            'hd_duration_minutes' => $this->hd_duration_minutes,
            'staff_notes' => $this->staff_notes,
            'doctor_notes' => $this->doctor_notes,
        ];
    }
}
