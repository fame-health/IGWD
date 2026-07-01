<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DailyMonitoringRequest;
use App\Http\Resources\Api\DailyMonitoringResource;
use App\Models\DailyMonitoring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyMonitoringController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = DailyMonitoring::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('risk_status'), fn ($query) => $query->where('risk_status', $request->risk_status))
            ->when($request->filled('fluid_status'), fn ($query) => $query->where('fluid_status', $request->fluid_status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'monitoring_date');

        return $this->success(DailyMonitoringResource::collection($query->orderByDesc('monitoring_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, DailyMonitoring $dailyMonitoring): JsonResponse
    {
        if (! $this->patientAllowed($request, $dailyMonitoring->patient_id)) {
            return $this->deny();
        }

        return $this->success(DailyMonitoringResource::make($dailyMonitoring->load('patient')));
    }

    public function store(DailyMonitoringRequest $request): JsonResponse
    {
        if (! $this->patientAllowed($request, (int) $request->patient_id)) {
            return $this->deny();
        }

        $monitoring = DailyMonitoring::create($request->validated());

        return $this->success(DailyMonitoringResource::make($monitoring), 'Monitoring harian berhasil dibuat.', 201);
    }

    public function update(DailyMonitoringRequest $request, DailyMonitoring $dailyMonitoring): JsonResponse
    {
        if (! $this->patientAllowed($request, $dailyMonitoring->patient_id)) {
            return $this->deny();
        }

        $dailyMonitoring->update($request->validated());

        return $this->success(DailyMonitoringResource::make($dailyMonitoring), 'Monitoring harian berhasil diperbarui.');
    }

    public function destroy(DailyMonitoring $dailyMonitoring): JsonResponse
    {
        $dailyMonitoring->delete();

        return $this->success(null, 'Monitoring harian berhasil dihapus.');
    }
}
