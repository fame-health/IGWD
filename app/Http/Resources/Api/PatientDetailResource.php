<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...PatientResource::make($this)->resolve($request),
            'medical_profile' => MedicalProfileResource::make($this->whenLoaded('medicalProfile')),
            'latest_dialysis_session' => DialysisSessionResource::make($this->whenLoaded('latestDialysisSession')),
            'latest_daily_monitoring' => DailyMonitoringResource::make($this->whenLoaded('latestDailyMonitoring')),
            'latest_alerts' => RiskAlertResource::collection($this->whenLoaded('riskAlerts')),
        ];
    }
}
