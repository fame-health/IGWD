<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class EducationRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'required_if:is_general,false', 'exists:patients,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['Video', 'Poster', 'Artikel', 'Materi Pasien'])],
            'is_general' => ['sometimes', 'boolean'],
            'education_date' => ['required', 'date'],
            'education_materials' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'url'],
            'image_path' => ['nullable', 'string'],
            'patient_understanding' => ['nullable', Rule::in(['Baik', 'Cukup', 'Kurang'])],
            'fluid_compliance' => ['nullable', Rule::in(['Baik', 'Cukup', 'Kurang'])],
            'schedule_compliance' => ['nullable', Rule::in(['Hadir', 'Terlambat', 'Tidak Hadir'])],
            'follow_up_notes' => ['nullable', 'string'],
            'educator_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
