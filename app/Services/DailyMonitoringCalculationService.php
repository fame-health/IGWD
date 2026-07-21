<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DailyMonitoring;
use App\Models\Patient;
use Carbon\CarbonInterface;

class DailyMonitoringCalculationService
{
    private const ADULT_IWL_ML_PER_KG_PER_DAY = 10;
    private const IWL_MAINTENANCE_FRACTION = 3;

    public function calculate(DailyMonitoring $monitoring): array
    {
        $fluidLimit = $monitoring->daily_fluid_limit_ml;

        if ($fluidLimit === null) {
            $latestSession = DialysisSession::query()
                ->where('patient_id', $monitoring->patient_id)
                ->whereNotNull('daily_fluid_intake_target_ml')
                ->latest('session_date')
                ->latest('id')
                ->first();

            $fluidLimit = $latestSession?->daily_fluid_intake_target_ml
                ?? (int) AppSetting::value('default_daily_fluid_limit_ml', 1000);
        }

        $dailyWeightGain = null;
        if ($monitoring->last_post_hd_weight !== null) {
            $dailyWeightGain = round((float) $monitoring->today_weight - (float) $monitoring->last_post_hd_weight, 2);
        }

        $insensibleWaterLoss = $this->insensibleWaterLoss($monitoring);
        $fluidOutput = $insensibleWaterLoss;

        $fluidDifference = null;
        $fluidStatus = null;
        if ($monitoring->fluid_intake_ml !== null) {
            $effectiveOutput = $fluidOutput ?? $fluidLimit;
            $fluidDifference = (int) $monitoring->fluid_intake_ml - $effectiveOutput;
            $fluidStatus = $fluidDifference <= 0 ? 'Aman' : 'Melebihi Batas';
        }

        return [
            'daily_fluid_limit_ml' => $fluidLimit,
            'daily_weight_gain_kg' => $dailyWeightGain,
            'insensible_water_loss_ml' => $insensibleWaterLoss,
            'fluid_output_ml' => $fluidOutput,
            'fluid_difference_ml' => $fluidDifference,
            'fluid_status' => $fluidStatus,
            'risk_status' => $this->riskStatus($dailyWeightGain, $fluidDifference),
        ];
    }

    public function insensibleWaterLoss(DailyMonitoring $monitoring): ?int
    {
        $weight = (float) $monitoring->today_weight;
        if ($weight <= 0) {
            return null;
        }

        $ageYears = $this->ageYears($monitoring);
        if ($ageYears !== null && $ageYears < 18) {
            return (int) round($this->hollidaySegarMaintenance($weight) / self::IWL_MAINTENANCE_FRACTION);
        }

        return (int) round($weight * self::ADULT_IWL_ML_PER_KG_PER_DAY);
    }

    private function ageYears(DailyMonitoring $monitoring): ?int
    {
        $patient = $monitoring->relationLoaded('patient')
            ? $monitoring->patient
            : Patient::query()->find($monitoring->patient_id);

        $birthDate = $patient?->birth_date;
        if (! $birthDate instanceof CarbonInterface) {
            return null;
        }

        $monitoringDate = $monitoring->monitoring_date instanceof CarbonInterface
            ? $monitoring->monitoring_date
            : now();

        return (int) floor($birthDate->diffInYears($monitoringDate));
    }

    private function hollidaySegarMaintenance(float $weight): float
    {
        return match (true) {
            $weight <= 10 => $weight * 100,
            $weight <= 20 => 1000 + (($weight - 10) * 50),
            default => 1500 + (($weight - 20) * 20),
        };
    }

    public function riskStatus(?float $dailyWeightGain, ?int $fluidDifference): ?string
    {
        $level = 'Normal';

        if ($dailyWeightGain !== null) {
            $level = $this->maxLevel($level, match (true) {
                $dailyWeightGain >= (float) AppSetting::value('daily_weight_gain_emergency_kg', 3.0) => 'Darurat',
                $dailyWeightGain >= (float) AppSetting::value('daily_weight_gain_high_kg', 2.0) => 'Tinggi',
                $dailyWeightGain >= (float) AppSetting::value('daily_weight_gain_warning_kg', 1.0) => 'Waspada',
                default => 'Normal',
            });
        }

        if ($fluidDifference !== null && $fluidDifference > 0) {
            $level = $this->maxLevel($level, match (true) {
                $fluidDifference >= (int) AppSetting::value('fluid_over_limit_emergency_ml', 1000) => 'Darurat',
                $fluidDifference >= (int) AppSetting::value('fluid_over_limit_high_ml', 500) => 'Tinggi',
                default => 'Waspada',
            });
        }

        return $level;
    }

    private function maxLevel(string $current, string $candidate): string
    {
        $rank = ['Normal' => 0, 'Waspada' => 1, 'Tinggi' => 2, 'Darurat' => 3];

        return $rank[$candidate] > $rank[$current] ? $candidate : $current;
    }
}
