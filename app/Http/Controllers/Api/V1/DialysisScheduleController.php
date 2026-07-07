<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DialysisScheduleRequest;
use App\Http\Resources\Api\DialysisScheduleResource;
use App\Models\DialysisSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialysisScheduleController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = DialysisSchedule::query()->with('patient')
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->patient_id))
            ->when($request->filled('shift'), fn ($query) => $query->where('shift', $request->shift))
            ->when($request->filled('attendance_status'), fn ($query) => $query->where('attendance_status', $request->attendance_status));

        $this->scopeForPatientRole($query, $request);
        $this->applyDateFilters($query, $request, 'hd_date');

        return $this->success(DialysisScheduleResource::collection($query->orderByDesc('hd_date')->paginate($request->integer('per_page', 15))));
    }

    public function show(Request $request, DialysisSchedule $dialysisSchedule): JsonResponse
    {
        if (! $this->patientAllowed($request, $dialysisSchedule->patient_id)) {
            return $this->deny();
        }

        return $this->success(DialysisScheduleResource::make($dialysisSchedule->load('patient')));
    }

    public function store(DialysisScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->role === 'perawat') {
            $data['nurse_name'] = $request->user()->name;
        }

        $schedule = DialysisSchedule::create($data);

        return $this->success(DialysisScheduleResource::make($schedule), 'Jadwal HD berhasil dibuat.', 201);
    }

    public function update(DialysisScheduleRequest $request, DialysisSchedule $dialysisSchedule): JsonResponse
    {
        if (
            ! $this->patientAllowed($request, $dialysisSchedule->patient_id) ||
            ! $this->patientAllowed($request, (int) $request->patient_id)
        ) {
            return $this->deny();
        }

        $data = $request->validated();

        if ($request->user()->role === 'perawat') {
            $data['nurse_name'] = $request->user()->name;
        }

        $dialysisSchedule->update($data);

        return $this->success(DialysisScheduleResource::make($dialysisSchedule), 'Jadwal HD berhasil diperbarui.');
    }

    public function attendance(Request $request, DialysisSchedule $dialysisSchedule): JsonResponse
    {
        if (! $this->patientAllowed($request, $dialysisSchedule->patient_id)) {
            return $this->deny();
        }

        $data = $request->validate(['attendance_status' => ['required', 'in:Terjadwal,Hadir,Tidak Hadir,Reschedule']]);
        $dialysisSchedule->update($data);

        return $this->success(DialysisScheduleResource::make($dialysisSchedule), 'Status kehadiran berhasil diperbarui.');
    }

    public function destroy(DialysisSchedule $dialysisSchedule): JsonResponse
    {
        $dialysisSchedule->delete();

        return $this->success(null, 'Jadwal HD berhasil dihapus.');
    }
}
