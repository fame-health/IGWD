<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\DailyMonitoringResource;
use App\Http\Resources\Api\DialysisSessionResource;
use App\Http\Resources\Api\PatientResource;
use App\Http\Resources\Api\RiskAlertResource;
use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\Patient;
use App\Models\RiskAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function dailyMonitoring(Request $request): JsonResponse
    {
        $query = DailyMonitoring::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('fluid_status'), fn ($query) => $query->where('fluid_status', $request->fluid_status))
            ->when($request->filled('risk_status'), fn ($query) => $query->where('risk_status', $request->risk_status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'monitoring_date');
        $data = $query->orderByDesc('monitoring_date')->get();

        return $this->success([
            'data' => DailyMonitoringResource::collection($data),
            'summary' => [
                'total' => $data->count(),
                'melebihi_batas' => $data->where('fluid_status', 'Melebihi Batas')->count(),
                'risiko_tinggi_darurat' => $data->whereIn('risk_status', ['Tinggi', 'Darurat'])->count(),
            ],
            'periode' => $this->period($request),
        ]);
    }

    public function dialysisSessions(Request $request): JsonResponse
    {
        $query = DialysisSession::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('shift'), fn ($query) => $query->where('shift', $request->shift))
            ->when($request->filled('risk_category'), fn ($query) => $query->where('risk_category', $request->risk_category));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'session_date');
        $data = $query->orderByDesc('session_date')->get();

        return $this->success([
            'data' => DialysisSessionResource::collection($data),
            'summary' => [
                'total' => $data->count(),
                'rata_rata_idwg_percent' => round((float) $data->avg('idwg_percent'), 2),
                'risiko_tinggi_darurat' => $data->whereIn('risk_category', ['Tinggi', 'Darurat'])->count(),
            ],
            'periode' => $this->period($request),
        ]);
    }

    public function riskPatients(Request $request): JsonResponse
    {
        $query = RiskAlert::query()
            ->with('patient')
            ->whereIn('alert_level', $request->filled('alert_level') ? [$request->alert_level] : ['Tinggi', 'Darurat'])
            ->whereNotNull('patient_id');

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'alert_date');
        $alerts = $query->latest()->get();
        $patientQuery = Patient::whereIn('id', $alerts->pluck('patient_id')->unique());
        $this->scopePatientList($patientQuery, $request);
        $patients = $patientQuery->get();

        return $this->success([
            'data' => $patients->map(fn (Patient $patient) => [
                'patient' => PatientResource::make($patient),
                'alert_count' => $alerts->where('patient_id', $patient->id)->count(),
                'highest_level' => $alerts->where('patient_id', $patient->id)->contains('alert_level', 'Darurat') ? 'Darurat' : 'Tinggi',
            ])->values(),
            'summary' => [
                'total_pasien_risiko' => $patients->count(),
                'total_alert' => $alerts->count(),
            ],
            'periode' => $this->period($request),
        ]);
    }

    public function riskAlerts(Request $request): JsonResponse
    {
        $query = RiskAlert::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('alert_level'), fn ($query) => $query->where('alert_level', $request->alert_level))
            ->when($request->filled('alert_type'), fn ($query) => $query->where('alert_type', $request->alert_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'alert_date');
        $data = $query->latest()->get();

        return $this->success([
            'data' => RiskAlertResource::collection($data),
            'summary' => [
                'total' => $data->count(),
                'baru' => $data->where('status', 'Baru')->count(),
                'tinggi_darurat' => $data->whereIn('alert_level', ['Tinggi', 'Darurat'])->count(),
            ],
            'periode' => $this->period($request),
        ]);
    }

    private function period(Request $request): array
    {
        return [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];
    }
}
