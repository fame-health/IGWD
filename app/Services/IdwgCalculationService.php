<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DialysisSession;

class IdwgCalculationService
{
    public function calculate(DialysisSession $session): array
    {
        $idwgKg = null;
        $idwgPercent = null;
        $riskCategory = null;

        if ($session->current_pre_hd_weight !== null && $session->previous_post_hd_weight !== null) {
            $idwgKg = round((float) $session->current_pre_hd_weight - (float) $session->previous_post_hd_weight, 2);
            $divider = (float) ($session->dry_weight ?: $session->current_pre_hd_weight);

            if ($divider > 0) {
                $idwgPercent = round(($idwgKg / $divider) * 100, 2);
                $riskCategory = $this->riskCategory($idwgPercent);
            }
        }

        return [
            'idwg_kg' => $idwgKg,
            'idwg_percent' => $idwgPercent,
            'risk_category' => $riskCategory,
        ];
    }

    public function riskCategory(float $idwgPercent): string
    {
        $safeMax = (float) AppSetting::value('idwg_safe_max', 3.0);
        $warningMax = (float) AppSetting::value('idwg_warning_max', 4.5);
        $emergencyMin = (float) AppSetting::value('idwg_emergency_min', 6.0);

        return match (true) {
            $idwgPercent < $safeMax => 'Aman',
            $idwgPercent >= $safeMax && $idwgPercent <= $warningMax => 'Waspada',
            $idwgPercent > $warningMax && $idwgPercent < $emergencyMin => 'Tinggi',
            default => 'Darurat',
        };
    }
}
