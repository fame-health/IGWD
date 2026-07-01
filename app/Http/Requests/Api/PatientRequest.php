<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class PatientRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $patientId = $this->route('patient')?->id ?? $this->route('patient');

        return [
            'medical_record_number' => ['required', 'string', Rule::unique('patients', 'medical_record_number')->ignore($patientId)],
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'responsible_person_name' => ['nullable', 'string', 'max:255'],
            'responsible_person_phone' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', Rule::in(['BPJS', 'Umum', 'Asuransi', 'Lainnya'])],
            'patient_status' => ['nullable', Rule::in(['Aktif', 'Tidak Aktif', 'Pindah', 'Meninggal'])],
        ];
    }
}
