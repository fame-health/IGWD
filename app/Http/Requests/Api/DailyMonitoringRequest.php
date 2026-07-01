<?php

namespace App\Http\Requests\Api;

class DailyMonitoringRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'last_dialysis_session_id' => ['nullable', 'exists:dialysis_sessions,id'],
            'monitoring_date' => ['required', 'date'],
            'day_after_hd' => ['nullable', 'integer', 'min:0'],
            'last_hd_date' => ['nullable', 'date'],
            'next_hd_date' => ['nullable', 'date'],
            'last_post_hd_weight' => ['nullable', 'numeric', 'gt:0'],
            'today_weight' => ['required', 'numeric', 'gt:0'],
            'fluid_intake_ml' => ['nullable', 'integer', 'min:0'],
            'daily_fluid_limit_ml' => ['nullable', 'integer', 'min:0'],
            'symptom_notes' => ['nullable', 'string'],
            'staff_notes' => ['nullable', 'string'],
        ];
    }
}
