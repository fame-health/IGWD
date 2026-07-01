<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class RiskSymptomRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'dialysis_session_id' => ['nullable', 'exists:dialysis_sessions,id'],
            'daily_monitoring_id' => ['nullable', 'exists:daily_monitorings,id'],
            'symptom_date' => ['required', 'date'],
            'shortness_of_breath' => ['nullable', Rule::in(['Tidak', 'Ringan', 'Sedang', 'Berat'])],
            'edema' => ['nullable', Rule::in(['Tidak', 'Kaki', 'Tangan', 'Wajah', 'Lainnya'])],
            'muscle_cramp' => ['nullable', 'boolean'],
            'dizziness_or_weakness' => ['nullable', 'boolean'],
            'nausea_or_vomiting' => ['nullable', 'boolean'],
            'chest_pain' => ['nullable', 'boolean'],
            'headache' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
