<?php

namespace App\Http\Requests\Api;

class VitalSignRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'dialysis_session_id' => ['nullable', 'exists:dialysis_sessions,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'measurement_date' => ['required', 'date'],
            'blood_pressure_before' => ['nullable', 'string', 'max:255'],
            'pulse_before' => ['nullable', 'integer', 'min:0'],
            'temperature' => ['nullable', 'numeric'],
            'respiration' => ['nullable', 'integer', 'min:0'],
            'oxygen_saturation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'blood_pressure_during' => ['nullable', 'string', 'max:255'],
            'blood_pressure_after' => ['nullable', 'string', 'max:255'],
            'complaints' => ['nullable', 'string'],
        ];
    }
}
