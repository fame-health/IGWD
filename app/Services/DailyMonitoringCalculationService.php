<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DailyMonitoring;

class DailyMonitoringCalculationService
{
    public function calculate(DailyMonitoring $monitoring): array
    {
        $fluidLimit = $monitoring->daily_fluid_limit_ml
            ?? (int) AppSetting::value('default_daily_fluid_limit_ml', 1000);

        $dailyWeightGain = null;
        if ($monitoring->last_post_hd_weight !== null) {
            $dailyWeightGain = round((float) $monitoring->today_weight - (float) $monitoring->last_post_hd_weight, 2);
        }

        $fluidDifference = null;
        $fluidStatus = null;
        if ($monitoring->fluid_intake_ml !== null) {
            $fluidDifference = (int) $monitoring->fluid_intake_ml - $fluidLimit;
            $fluidStatus = (int) $monitoring->fluid_intake_ml <= $fluidLimit ? 'Aman' : 'Melebihi Batas';
        }

        return [
            'daily_fluid_limit_ml' => $fluidLimit,
            'daily_weight_gain_kg' => $dailyWeightGain,
            'fluid_difference_ml' => $fluidDifference,
            'fluid_status' => $fluidStatus,
            'risk_status' => $this->riskStatus($dailyWeightGain, $fluidDifference),
        ];
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
