<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RiskAlertFollowUpRequest;
use App\Http\Resources\Api\RiskAlertResource;
use App\Models\RiskAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskAlertController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = RiskAlert::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('alert_level'), fn ($query) => $query->where('alert_level', $request->alert_level))
            ->when($request->filled('alert_type'), fn ($query) => $query->where('alert_type', $request->alert_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'alert_date');

        return $this->success(RiskAlertResource::collection($query->latest()->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, RiskAlert $riskAlert): JsonResponse
    {
        if (! $this->patientAllowed($request, $riskAlert->patient_id)) {
            return $this->deny();
        }

        return $this->success(RiskAlertResource::make($riskAlert->load('patient')));
    }

    public function markAsRead(RiskAlert $riskAlert): JsonResponse
    {
        $riskAlert->update([
            'status' => 'Dibaca',
            'read_at' => now(),
        ]);

        return $this->success(RiskAlertResource::make($riskAlert), 'Alert ditandai dibaca.');
    }

    public function followUp(RiskAlertFollowUpRequest $request, RiskAlert $riskAlert): JsonResponse
    {
        $riskAlert->update([
            'status' => 'Ditindaklanjuti',
            'followed_up_at' => now(),
            'follow_up_note' => $request->follow_up_note,
            'updated_by' => $request->user()->id,
        ]);

        return $this->success(RiskAlertResource::make($riskAlert), 'Alert berhasil ditindaklanjuti.');
    }

    public function resolve(RiskAlertFollowUpRequest $request, RiskAlert $riskAlert): JsonResponse
    {
        $riskAlert->update([
            'status' => 'Selesai',
            'resolved_at' => now(),
            'follow_up_note' => $request->follow_up_note,
            'updated_by' => $request->user()->id,
        ]);

        return $this->success(RiskAlertResource::make($riskAlert), 'Alert berhasil diselesaikan.');
    }
}
