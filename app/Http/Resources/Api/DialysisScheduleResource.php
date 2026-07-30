<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DialysisScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Pastikan kita hanya membandingkan TANGGAL (Y-m-d)
        $tz = 'Asia/Jakarta';
        $todayStr = Carbon::now($tz)->format('Y-m-d');
        $targetStr = Carbon::parse($this->hd_date)->format('Y-m-d');

        $dtToday = Carbon::parse($todayStr);
        $dtTarget = Carbon::parse($targetStr);

        // Selisih hari yang presisi
        $diffDays = $dtToday->diffInDays($dtTarget, false);

        $shiftOriginal = $this->shift ?: 'Pagi';
        $shiftUpper = strtoupper($shiftOriginal);
        $shiftLabel = $shiftOriginal;

        // Logika Teks Berdasarkan Jarak Hari (Presisi Tanggal)
        if ($diffDays === 0) {
            // HARI INI
            $shiftLabel = $shiftOriginal . "\n🚨 HARI INI ($shiftUpper) 🚨";
        } elseif ($diffDays === 1) {
            // BESOK
            $shiftLabel = $shiftOriginal . "\n(BESOK YA)";
        } elseif ($diffDays === 2) {
            // LUSA
            $shiftLabel = $shiftOriginal . "\n(2 HARI LAGI YA)";
        } elseif ($diffDays === 3) {
            // 3 HARI LAGI
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
