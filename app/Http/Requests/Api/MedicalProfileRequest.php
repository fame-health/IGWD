<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class MedicalProfileRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'main_diagnosis' => ['nullable', 'string', 'max:255'],
            'comorbidities' => ['nullable', 'string'],
            'hemodialysis_start_date' => ['nullable', 'date'],
            'hemodialysis_frequency' => ['nullable', Rule::in(['1x per minggu', '2x per minggu', '3x per minggu'])],
            'dry_weight' => ['nullable', 'numeric', 'gt:0'],
            'vascular_access' => ['nullable', Rule::in(['AV Fistula', 'CDL', 'Graft', 'Lainnya'])],
            'allergies' => ['nullable', 'string'],
            'routine_medications' => ['nullable', 'string'],
            'important_notes' => ['nullable', 'string'],
        ];
    }
}
