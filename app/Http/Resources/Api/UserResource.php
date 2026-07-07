<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'patient_id' => $this->patient_id,
            'profile_complete' => ! $this->requiresPatientProfile(),
            'requires_patient_profile' => $this->requiresPatientProfile(),
            'is_active' => (bool) $this->is_active,
            'avatar_url' => $this->avatar_url,
        ];
    }
}
