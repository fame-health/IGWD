<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\RiskAlert;
use App\Models\RiskSymptom;
use App\Models\User;
use App\Notifications\RiskAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class RiskAlertService
{
    public function checkDailyMonitoring(DailyMonitoring $dailyMonitoring): void
    {
        if ($dailyMonitoring->daily_weight_gain_kg !== null) {
            $level = match (true) {
                (float) $dailyMonitoring->daily_weight_gain_kg >= (float) AppSetting::value('daily_weight_gain_emergency_kg', 3.0) => 'Darurat',
                (float) $dailyMonitoring->daily_weight_gain_kg >= (float) AppSetting::value('daily_weight_gain_high_kg', 2.0) => 'Tinggi',
                (float) $dailyMonitoring->daily_weight_gain_kg >= (float) AppSetting::value('daily_weight_gain_warning_kg', 1.0) => 'Waspada',
                default => null,
            };

            if ($level) {
                $this->createAlert([
                    'patient_id' => $dailyMonitoring->patient_id,
                    'source_type' => 'daily_monitoring',
                    'source_id' => $dailyMonitoring->id,
                    'alert_date' => $dailyMonitoring->monitoring_date,
                    'alert_level' => $level,
                    'alert_type' => 'Kenaikan Berat Badan',
                    'title' => 'Kenaikan Berat Badan Harian',
                    'message' => 'Kenaikan berat badan harian pasien melebihi batas pemantauan.',
                    'trigger_value' => $dailyMonitoring->daily_weight_gain_kg.' kg',
                    'threshold_value' => AppSetting::value('daily_weight_gain_warning_kg', 1).' kg',
                    'recommendation' => 'Evaluasi asupan cairan, edukasi pasien, dan pantau gejala risiko.',
                ]);
            }
        }

        if ($dailyMonitoring->fluid_difference_ml !== null && $dailyMonitoring->fluid_difference_ml > 0) {
            $level = match (true) {
                (int) $dailyMonitoring->fluid_difference_ml >= (int) AppSetting::value('fluid_over_limit_emergency_ml', 1000) => 'Darurat',
                (int) $dailyMonitoring->fluid_difference_ml >= (int) AppSetting::value('fluid_over_limit_high_ml', 500) => 'Tinggi',
                default => 'Waspada',
            };

            $this->createAlert([
                'patient_id' => $dailyMonitoring->patient_id,
                'source_type' => 'daily_monitoring',
                'source_id' => $dailyMonitoring->id,
                'alert_date' => $dailyMonitoring->monitoring_date,
                'alert_level' => $level,
                'alert_type' => 'Cairan Melebihi Batas',
                'title' => 'Cairan Harian Melebihi Batas',
                'message' => 'Cairan masuk harian pasien melebihi perkiraan cairan keluar berdasarkan IWL.',
                'trigger_value' => $dailyMonitoring->fluid_intake_ml.' ml',
                'threshold_value' => ($dailyMonitoring->fluid_output_ml ?? $dailyMonitoring->daily_fluid_limit_ml).' ml',
                'recommendation' => 'Tinjau kepatuhan pembatasan cairan dan lakukan follow up.',
            ]);
        }
    }

    public function checkDialysisSession(DialysisSession $dialysisSession): void
    {
        if ($dialysisSession->idwg_percent === null || $dialysisSession->risk_category === 'Aman') {
            return;
        }

        $this->createAlert([
            'patient_id' => $dialysisSession->patient_id,
            'source_type' => 'dialysis_session',
            'source_id' => $dialysisSession->id,
            'alert_date' => $dialysisSession->session_date,
            'alert_level' => $dialysisSession->risk_category,
            'alert_type' => 'IDWG Tinggi',
            'title' => 'IDWG Pasien Meningkat',
            'message' => 'Persentase IDWG pasien berada pada kategori '.$dialysisSession->risk_category.'.',
            'trigger_value' => $dialysisSession->idwg_percent.'%',
            'threshold_value' => AppSetting::value('idwg_safe_max', 3).'%',
            'recommendation' => 'Pantau kondisi pasien dan evaluasi target ultrafiltrasi sesuai kewenangan klinis.',
        ]);
    }

    public function checkRiskSymptom(RiskSymptom $riskSymptom): void
    {
        $level = $this->riskStatusForSymptom($riskSymptom);

        if ($level === 'Normal') {
            return;
        }

        $this->createAlert([
            'patient_id' => $riskSymptom->patient_id,
            'source_type' => 'risk_symptom',
            'source_id' => $riskSymptom->id,
            'alert_date' => $riskSymptom->symptom_date,
            'alert_level' => $level,
            'alert_type' => 'Gejala Risiko',
            'title' => 'Gejala Risiko Terdeteksi',
            'message' => 'Pasien melaporkan gejala yang membutuhkan pemantauan risiko dini.',
            'trigger_value' => trim('Sesak: '.$riskSymptom->shortness_of_breath.', edema: '.$riskSymptom->edema),
            'threshold_value' => 'Aturan gejala risiko',
            'recommendation' => 'Lakukan asesmen lanjutan sesuai prosedur klinis. Sistem ini bukan diagnosis medis.',
        ]);
    }

    public function checkPredictionRisk(DailyMonitoring $dailyMonitoring): void
    {
        if (! $dailyMonitoring->last_hd_date || ! $dailyMonitoring->next_hd_date || $dailyMonitoring->daily_weight_gain_kg === null) {
            return;
        }

        $lastHdDate = Carbon::parse($dailyMonitoring->last_hd_date)->startOfDay();
        $nextHdDate = Carbon::parse($dailyMonitoring->next_hd_date)->startOfDay();
        $monitoringDate = Carbon::parse($dailyMonitoring->monitoring_date)->startOfDay();

        $totalDays = max(1, $lastHdDate->diffInDays($nextHdDate));
        $elapsedDays = max(1, $lastHdDate->diffInDays($monitoringDate));
        $averageGain = (float) $dailyMonitoring->daily_weight_gain_kg / $elapsedDays;
        $predictedWeightGain = round($averageGain * $totalDays, 2);

        $dryWeight = optional($dailyMonitoring->patient->medicalProfile)->dry_weight
            ?: $dailyMonitoring->last_post_hd_weight;

        if (! $dryWeight || (float) $dryWeight <= 0) {
            return;
        }

        $predictedPercent = round(($predictedWeightGain / (float) $dryWeight) * 100, 2);
        $level = match (true) {
            $predictedPercent >= (float) AppSetting::value('predicted_idwg_emergency_percent', 6.0) => 'Darurat',
            $predictedPercent > (float) AppSetting::value('predicted_idwg_high_percent', 4.5) => 'Tinggi',
            $predictedPercent >= (float) AppSetting::value('predicted_idwg_warning_percent', 3.0) => 'Waspada',
            default => null,
        };

        if ($level) {
            $this->createAlert([
                'patient_id' => $dailyMonitoring->patient_id,
                'source_type' => 'system_prediction',
                'source_id' => $dailyMonitoring->id,
                'alert_date' => $dailyMonitoring->monitoring_date,
                'alert_level' => $level,
                'alert_type' => 'Prediksi Risiko',
                'title' => 'Prediksi IDWG Berisiko',
                'message' => 'Prediksi kenaikan berat badan hingga jadwal HD berikutnya melewati batas risiko.',
                'trigger_value' => $predictedPercent.'%',
                'threshold_value' => AppSetting::value('predicted_idwg_warning_percent', 3).'%',
                'recommendation' => 'Lakukan pemantauan lebih ketat dan edukasi pembatasan cairan.',
            ]);
        }
    }

    public function createAlert(array $data): ?RiskAlert
    {
        $alertDate = Carbon::parse($data['alert_date'] ?? now())->toDateString();

        $query = RiskAlert::query()
            ->where('patient_id', $data['patient_id'])
            ->where('alert_type', $data['alert_type'])
            ->whereDate('alert_date', $alertDate);

        $data['source_type'] ??= null;
        $data['source_id'] ??= null;

        $data['source_type'] === null
            ? $query->whereNull('source_type')
            : $query->where('source_type', $data['source_type']);

        $data['source_id'] === null
            ? $query->whereNull('source_id')
            : $query->where('source_id', $data['source_id']);

        if ($query->exists()) {
            return null;
        }

        $alert = RiskAlert::query()->create([
            ...$data,
            'alert_date' => $alertDate,
            'alert_time' => $data['alert_time'] ?? now()->format('H:i:s'),
            'status' => $data['status'] ?? 'Baru',
        ]);

        $recipients = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'perawat', 'dokter'])
            ->get();

        Notification::send($recipients, new RiskAlertNotification($alert));

        return $alert;
    }

    public function riskStatusForSymptom(RiskSymptom $riskSymptom): string
    {
        return match (true) {
            (bool) $riskSymptom->chest_pain => 'Darurat',
            $riskSymptom->shortness_of_breath === 'Berat' => 'Darurat',
            $riskSymptom->shortness_of_breath === 'Sedang' => 'Tinggi',
            $riskSymptom->edema === 'Wajah' => 'Tinggi',
            (bool) $riskSymptom->dizziness_or_weakness && (bool) $riskSymptom->nausea_or_vomiting => 'Waspada',
            default => 'Normal',
        };
    }
}
