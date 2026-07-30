<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DialysisScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $scheduleDate = Carbon::parse($this->hd_date)->startOfDay();

        $diffDays = (int) $today->diffInDays($scheduleDate, false);

        $shiftOriginal = $this->shift ?: 'Pagi';
        $shiftUpper = strtoupper($shiftOriginal);
        $shiftLabel = $shiftOriginal;

        // Pindahkan SEMUA pengingat ke Shift Label agar baris Lokasi tetap bersih
        if ($diffDays === 0) {
            $shiftLabel = $shiftOriginal . "\n🚨 HARI INI ($shiftUpper) 🚨";
        } elseif ($diffDays === 1) {
            $shiftLabel = $shiftOriginal . "\n⏰ BESOK YA (H-1)";
        } elseif ($diffDays === 2) {
            $shiftLabel = $shiftOriginal . "\n🗓️ 2 HARI LAGI YA (H-2)";
        } elseif ($diffDays === 3) {
            $shiftLabel = $shiftOriginal . "\n🗓️ 3 HARI LAGI YA (H-3)";
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
