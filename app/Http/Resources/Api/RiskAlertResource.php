<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'alert_date' => $this->alert_date?->toDateString(),
            'alert_time' => $this->alert_time,
            'alert_level' => $this->alert_level,
            'alert_type' => $this->alert_type,
            'title' => $this->title,
            'message' => $this->message,
            'trigger_value' => $this->trigger_value,
            'threshold_value' => $this->threshold_value,
            'recommendation' => $this->recommendation,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'read_at' => $this->read_at?->toISOString(),
            'followed_up_at' => $this->followed_up_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'follow_up_note' => $this->follow_up_note,
        ];
    }
}
