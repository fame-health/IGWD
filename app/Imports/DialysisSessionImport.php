<?php

namespace App\Imports;

use App\Models\DialysisSession;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DialysisSessionImport implements ToModel, WithHeadingRow, WithUpserts, SkipsEmptyRows
{
    public function model(array $row)
    {
        // Helper to find key in row despite slight slug variations
        $get = function($possibilities) use ($row) {
            foreach ($possibilities as $p) {
                if (isset($row[$p])) return $row[$p];
                $slug = str_replace(['(', ')', '-', ' ', '/'], '_', strtolower($p));
                if (isset($row[$slug])) return $row[$slug];
            }
            return null;
        };

        // Identify Patient
        $patientInput = $get(['pasien_id', 'pasien', 'patient_id', 'patient']);
        $patientId = null;

        if (is_numeric($patientInput)) {
            $patientId = (int) $patientInput;
        } elseif ($patientInput) {
            $patient = Patient::where('name', 'like', '%' . trim($patientInput) . '%')->first();
            $patientId = $patient?->id;
        }

        if (!$patientId) {
            return null; // Skip if no patient found to avoid SQL errors
        }

        // Identify Date
        $dateValue = $get(['tanggal_sesi', 'session_date', 'tanggal_sesi_yyyy_mm_dd']);
        $sessionDate = $this->transformDate($dateValue);

        if (!$sessionDate) {
            return null; // Skip if no date found
        }

        $data = [
            'patient_id' => $patientId,
            'session_date' => $sessionDate,
            'shift' => $get(['shift', 'shift_pagi_siang_sore_malam']),
            'previous_post_hd_weight' => $this->parseNumeric($get(['bb_pre_hd_terakhir', 'previous_post_hd_weight'])),
            'current_pre_hd_weight' => $this->parseNumeric($get(['bb_datang', 'current_pre_hd_weight'])),
            'target_ultrafiltration' => $this->parseNumeric($get(['target_uf_l', 'target_ultrafiltration'])),
            'blood_pressure_before' => $get(['td_pre', 'blood_pressure_before', 'td_pre_contoh_120_80']),
            'blood_sugar_before' => $this->parseNumeric($get(['gd_pre', 'blood_sugar_before'])),
            'blood_pressure_after' => $get(['td_post', 'blood_pressure_after']),
            'blood_sugar_after' => $this->parseNumeric($get(['gd_post', 'blood_sugar_after'])),
            'current_post_hd_weight' => $this->parseNumeric($get(['bb_pulang', 'current_post_hd_weight'])),
            'hd_duration_minutes' => (int) ($get(['durasi_menit', 'hd_duration_minutes', 'durasi_180_240_dst']) ?? 240),
            'staff_notes' => $get(['catatan_petugas', 'staff_notes']),
        ];

        $id = $get(['id']);
        if ($id && is_numeric($id)) {
            $session = DialysisSession::find($id);
            if ($session) {
                $data['updated_by'] = Auth::id();
                $session->fill($data);
                return $session;
            }
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return new DialysisSession($data);
    }

    public function uniqueBy()
    {
        return 'id';
    }

    private function transformDate($value)
    {
        if (!$value) return null;

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseNumeric($value)
    {
        if ($value === null || $value === '') return null;
        // Handle Indonesian format (57,5) and standard (57.5)
        $clean = str_replace(',', '.', (string) $value);
        return is_numeric($clean) ? (float) $clean : null;
    }
}
