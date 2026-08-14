<?php

namespace App\Imports;

use App\Models\DialysisSession;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Illuminate\Support\Facades\Auth;

class DialysisSessionImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function model(array $row)
    {
        // Skip empty rows
        if (!isset($row['pasien']) && !isset($row['pasien_id']) && !isset($row['id'])) {
            return null;
        }

        $sessionDate = null;
        if (isset($row['tanggal_sesi_yyyy_mm_dd'])) {
            try {
                $sessionDate = Carbon::parse($row['tanggal_sesi_yyyy_mm_dd'])->toDateString();
            } catch (\Exception $e) {}
        }

        $data = [
            'patient_id' => $row['pasien_id'] ?? $row['pasien'] ?? null,
            'session_date' => $sessionDate,
            'shift' => $row['shift_pagi_siang_sore_malam'] ?? $row['shift'] ?? null,
            'previous_post_hd_weight' => $this->parseNumeric($row['bb_pre_hd_terakhir'] ?? null),
            'current_pre_hd_weight' => $this->parseNumeric($row['bb_datang'] ?? null),
            'target_ultrafiltration' => $this->parseNumeric($row['target_uf_l'] ?? null),
            'blood_pressure_before' => $row['td_pre_contoh_120_80'] ?? $row['td_pre'] ?? null,
            'blood_sugar_before' => $this->parseNumeric($row['gd_pre'] ?? null),
            'blood_pressure_after' => $row['td_post'] ?? null,
            'blood_sugar_after' => $this->parseNumeric($row['gd_post'] ?? null),
            'current_post_hd_weight' => $this->parseNumeric($row['bb_pulang'] ?? null),
            'hd_duration_minutes' => (int) ($row['durasi_180_240_dst'] ?? $row['durasi_menit'] ?? 240),
            'staff_notes' => $row['catatan_petugas'] ?? null,
        ];

        // For new records, set creator
        if (!isset($row['id']) || !$row['id']) {
            $data['created_by'] = Auth::id();
        }

        $data['updated_by'] = Auth::id();

        // If ID exists, we'll let WithUpserts handle it, but we need to return the model instance
        if (isset($row['id']) && $row['id']) {
            $session = DialysisSession::find($row['id']);
            if ($session) {
                $session->fill($data);
                return $session;
            }
        }

        return new DialysisSession($data);
    }

    public function uniqueBy()
    {
        return 'id';
    }

    private function parseNumeric($value)
    {
        if ($value === null || $value === '') return null;
        return (float) str_replace(',', '.', (string) $value);
    }
}
