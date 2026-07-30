<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DialysisScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = Carbon::now()->toDateString();
        $isToday = $this->hd_date && Carbon::parse($this->hd_date)->toDateString() === $today;

        // Tampilan yang Rapi: Masukkan info "HARI INI" ke dalam Shift agar baris lokasi tetap utuh
        $shiftLabel = $this->shift ?: 'Pagi';
        if ($isToday) {
            $shiftLabel = "{$shiftLabel} (HARI INI ★)";
        }

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'hd_date' => $this->hd_date?->toDateString(),
            'day_name' => $this->day_name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'shift' => $shiftLabel,
            'location' => $this->location ?? $this->room,
            'room' => $this->room,
            'machine_number' => $this->machine_number,
            'doctor_name' => $this->doctor_name,
            'nurse_name' => $this->nurse_name,
            'attendance_status' => $this->attendance_status,
            'notes' => $this->notes,
        ];
    }
}
