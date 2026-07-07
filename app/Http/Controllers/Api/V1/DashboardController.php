<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\DailyMonitoringResource;
use App\Http\Resources\Api\DialysisScheduleResource;
use App\Http\Resources\Api\EducationResource;
use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Education;
use App\Models\Patient;
use App\Models\RiskAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->requiresPatientProfile()) {
            return $this->error('Lengkapi biodata pasien terlebih dahulu.', [
                'patient_profile' => ['Biodata pasien wajib diisi sebelum masuk dashboard.'],
            ], 409);
        }

        $role = $user->role;
        $today = now()->toDateString();

        $data = match ($role) {
            'perawat', 'admin' => [
                'total_pasien_aktif' => Patient::where('patient_status', 'Aktif')->count(),
                'jadwal_hd_hari_ini' => DialysisSchedule::whereDate('hd_date', $today)->count(),
                'monitoring_harian_hari_ini' => DailyMonitoring::whereDate('monitoring_date', $today)->count(),
                'alert_baru' => RiskAlert::where('status', 'Baru')->count(),
                'alert_tinggi_darurat' => RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
                'pasien_perlu_dipantau' => DailyMonitoring::whereIn('risk_status', ['Waspada', 'Tinggi', 'Darurat'])->distinct('patient_id')->count('patient_id'),
            ],
            'dokter' => [
                'total_pasien_aktif' => Patient::where('patient_status', 'Aktif')->count(),
                'alert_tinggi_darurat' => RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
                'pasien_risiko_tinggi' => RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->distinct('patient_id')->count('patient_id'),
                'sesi_hd_hari_ini' => DialysisSession::whereDate('session_date', $today)->count(),
                'notifikasi_belum_ditindaklanjuti' => RiskAlert::whereIn('status', ['Baru', 'Dibaca'])->count(),
            ],
            'pasien' => [
                'jadwal_hd_berikutnya' => DialysisScheduleResource::make(DialysisSchedule::where('patient_id', $user->patient_id)->whereDate('hd_date', '>=', $today)->orderBy('hd_date')->first()),
                'monitoring_harian_terakhir' => DailyMonitoringResource::make(DailyMonitoring::where('patient_id', $user->patient_id)->latest('monitoring_date')->first()),
                'edukasi_terbaru' => EducationResource::make(Education::where('patient_id', $user->patient_id)->latest('education_date')->first()),
                'status_risiko_terakhir' => DailyMonitoring::where('patient_id', $user->patient_id)->latest('monitoring_date')->value('risk_status'),
            ],
            default => [
                'total_pasien_aktif' => Patient::where('patient_status', 'Aktif')->count(),
                'total_alert' => RiskAlert::count(),
                'alert_tinggi_darurat' => RiskAlert::whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
            ],
        };

        return $this->success($data);
    }
}
