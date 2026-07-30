<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DialysisScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $scheduleDate = Carbon::parse($this->hd_date)->startOfDay();
        $diffDays = $today->diffInDays($scheduleDate, false);

        $shiftOriginal = $this->shift ?: 'Pagi';
        $shiftUpper = strtoupper($shiftOriginal);
        $shiftLabel = $shiftOriginal;

        // Logika Teks Berdasarkan Jarak Hari
        if ($diffDays === 0) {
            // HARI INI: Pakai Sirine Merah dan Shift Besar
            $shiftLabel = $shiftOriginal . "\n🚨 HARI INI ($shiftUpper) 🚨";
        } elseif ($diffDays === 1) {
            // H-1
            $shiftLabel = $shiftOriginal . "\n(BESOK YA)";
        } elseif ($diffDays === 2) {
            // H-2
            $shiftLabel = $shiftOriginal . "\n(2 HARI LAGI YA)";
        } elseif ($diffDays === 3) {
            // H-3
            $shiftLabel = $shiftOriginal . "\n(3 HARI LAGI YA)";
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
