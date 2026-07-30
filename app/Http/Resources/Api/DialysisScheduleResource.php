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

        // Hitung selisih hari secara presisi
        $diffDays = (int) $today->diffInDays($scheduleDate, false);

        $shiftOriginal = $this->shift ?: 'Pagi';
        $shiftUpper = strtoupper($shiftOriginal);
        $shiftLabel = $shiftOriginal;
        $locationLabel = $this->location ?? $this->room ?? 'Lokasi belum tersedia';

        if ($diffDays === 0) {
            // HARI INI - Sangat Menonjol di Judul dengan Sirine Merah
            $shiftLabel = $shiftOriginal . "\n🚨 HARI INI ($shiftUpper) 🚨";
            // locationLabel dibiarkan normal (menampilkan ruangan/lokasi asli)
        } elseif ($diffDays === 1) {
            // H-1 - Muncul di Baris Lokasi
            $locationLabel = "⏰ BESOK YA (H-1)";
        } elseif ($diffDays === 2) {
            // H-2
            $locationLabel = "🗓️ 2 HARI LAGI YA (H-2)";
        } elseif ($diffDays === 3) {
            // H-3
            $locationLabel = "🗓️ 3 HARI LAGI YA (H-3)";
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
            'notes' => $this->notes,
        ];
    }
}
