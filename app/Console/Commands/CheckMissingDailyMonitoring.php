<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\RiskAlertService;
use Illuminate\Console\Command;

class CheckMissingDailyMonitoring extends Command
{
    protected $signature = 'app:check-missing-daily-monitoring';

    protected $description = 'Membuat alert untuk pasien aktif yang belum input monitoring harian.';

    public function handle(RiskAlertService $riskAlertService): int
    {
        $today = now()->toDateString();
        $created = 0;

        Patient::query()
            ->where('patient_status', 'Aktif')
            ->chunkById(100, function ($patients) use ($today, $riskAlertService, &$created) {
                foreach ($patients as $patient) {
                    $lastSession = $patient->dialysisSessions()
                        ->latest('session_date')
                        ->first();
                    $nextSchedule = $patient->dialysisSchedules()
                        ->whereDate('hd_date', '>=', $today)
                        ->orderBy('hd_date')
                        ->first();

                    if (! $lastSession || ! $nextSchedule) {
                        continue;
                    }

                    if ($lastSession->session_date->toDateString() >= $today || $nextSchedule->hd_date->toDateString() <= $today) {
                        continue;
                    }

                    $hasMonitoring = $patient->dailyMonitorings()
                        ->whereDate('monitoring_date', $today)
                        ->exists();

                    if ($hasMonitoring) {
                        continue;
                    }

                    $alert = $riskAlertService->createAlert([
                        'patient_id' => $patient->id,
                        'source_type' => 'missing_daily_monitoring',
                        'source_id' => null,
                        'alert_date' => $today,
                        'alert_level' => 'Waspada',
                        'alert_type' => 'Tidak Input Data Harian',
                        'title' => 'Data Monitoring Harian Belum Diisi',
                        'message' => 'Pasien '.$patient->name.' belum memiliki data berat badan/cairan harian pada tanggal '.$today.'.',
                        'status' => 'Baru',
                    ]);

                    if ($alert) {
                        $created++;
                    }
                }
            });

        $this->info("Alert monitoring kosong dibuat: {$created}");

        return self::SUCCESS;
    }
}
