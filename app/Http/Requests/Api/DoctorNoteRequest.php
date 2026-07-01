<?php

namespace App\Http\Requests\Api;

class DoctorNoteRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'doctor_notes' => ['required', 'string'],
        ];
    }
}
