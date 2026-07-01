<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyMonitoringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'last_dialysis_session_id' => $this->last_dialysis_session_id,
            'monitoring_date' => $this->monitoring_date?->toDateString(),
            'day_after_hd' => $this->day_after_hd,
            'last_hd_date' => $this->last_hd_date?->toDateString(),
            'next_hd_date' => $this->next_hd_date?->toDateString(),
            'last_post_hd_weight' => $this->last_post_hd_weight,
            'today_weight' => $this->today_weight,
            'daily_weight_gain_kg' => $this->daily_weight_gain_kg,
            'fluid_intake_ml' => $this->fluid_intake_ml,
            'daily_fluid_limit_ml' => $this->daily_fluid_limit_ml,
            'fluid_difference_ml' => $this->fluid_difference_ml,
            'fluid_status' => $this->fluid_status,
            'symptom_notes' => $this->symptom_notes,
            'staff_notes' => $this->staff_notes,
            'risk_status' => $this->risk_status,
        ];
    }
}
