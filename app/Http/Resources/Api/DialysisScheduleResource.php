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

        // Jika hari ini, kita modifikasi outputnya agar sangat menonjol di HP
        $shiftLabel = $this->shift;
        $locationLabel = $this->location ?? $this->room;
        $notesLabel = $this->notes;

        if ($isToday) {
            $shiftLabel = "⭐ " . strtoupper($this->shift ?: 'Pagi') . " (HARI INI) ⭐";
            $locationLabel = "🚨 JADWAL ANDA HARI INI 🚨";
            $notesLabel = "⚠️ PENTING: Jadwal HD Anda adalah HARI INI. Mohon segera bersiap. " . $notesLabel;
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
            'location' => $locationLabel,
            'room' => $this->room,
            'machine_number' => $this->machine_number,
            'doctor_name' => $this->doctor_name,
            'nurse_name' => $this->nurse_name,
            'attendance_status' => $this->attendance_status,
            'notes' => $notesLabel,
        ];
    }
}
