<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_number' => $this->medical_record_number,
            'name' => $this->name,
            'nik' => $this->nik,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'phone' => $this->phone,
            'responsible_person_name' => $this->responsible_person_name,
            'responsible_person_phone' => $this->responsible_person_phone,
            'payment_status' => $this->payment_status,
            'patient_status' => $this->patient_status,
        ];
    }
}
