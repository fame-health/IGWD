<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\PatientRequest;
use App\Http\Resources\Api\DailyMonitoringResource;
use App\Http\Resources\Api\DialysisScheduleResource;
use App\Http\Resources\Api\DialysisSessionResource;
use App\Http\Resources\Api\MedicalProfileResource;
use App\Http\Resources\Api\PatientResource;
use App\Http\Resources\Api\RiskAlertResource;
use App\Models\Patient;
use App\Models\RiskAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('medical_record_number', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('patient_status'), fn ($query) => $query->where('patient_status', $request->patient_status));

        $this->scopePatientList($query, $request);

        $patients = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return $this->success(PatientResource::collection($patients));
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        return $this->success(PatientResource::make($patient));
    }

    public function store(PatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return $this->success(PatientResource::make($patient), 'Pasien berhasil dibuat.', 201);
    }

    public function update(PatientRequest $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        $patient->update($request->validated());

        return $this->success(PatientResource::make($patient), 'Pasien berhasil diperbarui.');
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();

        return $this->success(null, 'Pasien berhasil dihapus.');
    }

    public function summary(Request $request, Patient $patient): JsonResponse
    {
        if (! $this->patientAllowed($request, $patient->id)) {
            return $this->deny();
        }

        $patient->load('medicalProfile');
        $nextSchedule = $patient->dialysisSchedules()->whereDate('hd_date', '>=', now()->toDateString())->orderBy('hd_date')->first();
        $lastSession = $patient->dialysisSessions()->latest('session_date')->first();
        $lastMonitoring = $patient->dailyMonitorings()->latest('monitoring_date')->first();
        $latestAlerts = $patient->riskAlerts()->latest()->limit(5)->get();

        $medicalProfileResource = $patient->medicalProfile
            ? MedicalProfileResource::make($patient->medicalProfile)
            : null;
        $nextScheduleResource = $nextSchedule
            ? DialysisScheduleResource::make($nextSchedule)
            : null;
        $lastSessionResource = $lastSession
            ? DialysisSessionResource::make($lastSession)
            : null;
        $lastMonitoringResource = $lastMonitoring
            ? DailyMonitoringResource::make($lastMonitoring)
            : null;

        return $this->success([
            'patient' => PatientResource::make($patient),
            'medical_profile' => $medicalProfileResource,
            'medical_data' => $medicalProfileResource,
            'next_schedule' => $nextScheduleResource,
            'last_dialysis_session' => $lastSessionResource,
            'last_session' => $lastSessionResource,
            'last_daily_monitoring' => $lastMonitoringResource,
            'last_monitoring' => $lastMonitoringResource,
            'last_idwg' => [
                'idwg_kg' => $lastSession?->idwg_kg,
                'idwg_percent' => $lastSession?->idwg_percent,
                'risk_category' => $lastSession?->risk_category,
            ],
            'latest_alerts' => RiskAlertResource::collection($latestAlerts),
            'alert_count_last_7_days' => RiskAlert::where('patient_id', $patient->id)->whereDate('alert_date', '>=', now()->subDays(7)->toDateString())->count(),
            'latest_risk_status' => $latestAlerts->first()?->alert_level ?? $lastMonitoring?->risk_status,
        ]);
    }
}
