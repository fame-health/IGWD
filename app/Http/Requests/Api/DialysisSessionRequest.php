<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class DialysisSessionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'dialysis_schedule_id' => ['nullable', 'exists:dialysis_schedules,id'],
            'session_date' => ['required', 'date'],
            'shift' => ['nullable', Rule::in(['Pagi', 'Siang', 'Sore', 'Malam'])],
            'previous_post_hd_weight' => ['nullable', 'numeric', 'gt:0'],
            'current_pre_hd_weight' => ['nullable', 'numeric', 'gt:0'],
            'dry_weight' => ['nullable', 'numeric', 'gt:0'],
            'current_post_hd_weight' => ['nullable', 'numeric', 'gt:0'],
            'target_ultrafiltration' => ['nullable', 'numeric', 'min:0'],
            'hd_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'staff_notes' => ['nullable', 'string'],
        ];
    }
}
