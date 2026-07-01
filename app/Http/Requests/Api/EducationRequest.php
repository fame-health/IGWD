<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class EducationRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'education_date' => ['required', 'date'],
            'education_materials' => ['nullable', 'string'],
            'patient_understanding' => ['nullable', Rule::in(['Baik', 'Cukup', 'Kurang'])],
            'fluid_compliance' => ['nullable', Rule::in(['Baik', 'Cukup', 'Kurang'])],
            'schedule_compliance' => ['nullable', Rule::in(['Hadir', 'Terlambat', 'Tidak Hadir'])],
            'follow_up_notes' => ['nullable', 'string'],
            'educator_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
