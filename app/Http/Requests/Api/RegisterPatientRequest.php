<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class RegisterPatientRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'medical_record_number' => ['nullable', 'string', 'max:255', Rule::unique('patients', 'medical_record_number')],
            'nik' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'responsible_person_name' => ['nullable', 'string', 'max:255'],
            'responsible_person_phone' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', Rule::in(['BPJS', 'Umum', 'Asuransi', 'Lainnya'])],

            'medical_profile' => ['sometimes', 'array'],
            'medical_profile.main_diagnosis' => ['nullable', 'string', 'max:255'],
            'medical_profile.medical_history' => ['nullable', 'string'],
            'medical_profile.comorbidities' => ['nullable', 'string'],
            'medical_profile.hemodialysis_start_date' => ['nullable', 'date'],
            'medical_profile.hemodialysis_frequency' => ['nullable', Rule::in(['1x per minggu', '2x per minggu', '3x per minggu'])],
            'medical_profile.dry_weight' => ['nullable', 'numeric', 'gt:0'],
            'medical_profile.vascular_access' => ['nullable', Rule::in(['AV Fistula', 'CDL', 'Graft', 'Lainnya'])],
            'medical_profile.allergies' => ['nullable', 'string'],
            'medical_profile.routine_medications' => ['nullable', 'string'],
            'medical_profile.important_notes' => ['nullable', 'string'],
        ];
    }
}
