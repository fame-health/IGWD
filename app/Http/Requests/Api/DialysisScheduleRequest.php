<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class DialysisScheduleRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'hd_date' => ['required', 'date'],
            'day_name' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'string', 'max:255'],
            'end_time' => ['nullable', 'string', 'max:255'],
            'shift' => ['required', Rule::in(['Pagi', 'Siang', 'Sore', 'Malam'])],
            'room' => ['nullable', 'string', 'max:255'],
            'machine_number' => ['nullable', 'string', 'max:255'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'nurse_name' => ['nullable', 'string', 'max:255'],
            'attendance_status' => ['nullable', Rule::in(['Terjadwal', 'Hadir', 'Tidak Hadir', 'Reschedule'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
