<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DialysisSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'Berhasil', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function deny(): JsonResponse
    {
        return $this->error('Akses ditolak.', [], 403);
    }

    protected function patientAllowed(Request $request, int $patientId): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'manajemen'], true)) {
            return true;
        }

        if ($user->role === 'pasien') {
            return (int) $user->patient_id === $patientId;
        }

        if (in_array($user->role, ['perawat', 'dokter'], true)) {
            return in_array($patientId, $this->assignedPatientIds($user), true);
        }

        return false;
    }

    protected function scopeForPatientRole(Builder $query, Request $request, string $column = 'patient_id'): Builder
    {
        return $this->scopeForAccessiblePatients($query, $request, $column);
    }

    protected function scopeForAccessiblePatients(Builder $query, Request $request, string $column = 'patient_id'): Builder
    {
        $user = $request->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($user->role, ['admin', 'manajemen'], true)) {
            return $query;
        }

        if ($user->role === 'pasien') {
            return $query->where($column, $user->patient_id);
        }

        if (in_array($user->role, ['perawat', 'dokter'], true)) {
            return $query->whereIn($column, $this->assignedPatientIds($user));
        }

        return $query->whereRaw('1 = 0');
    }

    protected function scopePatientList(Builder $query, Request $request): Builder
    {
        return $this->scopeForAccessiblePatients($query, $request, 'id');
    }

    protected function applyDateFilters(Builder $query, Request $request, string $column): Builder
    {
        return $query
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate($column, $request->date))
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereDate($column, '>=', $request->start_date))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereDate($column, '<=', $request->end_date));
    }

    /**
     * Staff ownership follows the current schedule assignment design:
     * nurses are matched by nurse_name, doctors by doctor_name.
     *
     * @return array<int, int>
     */
    private function assignedPatientIds(User $user): array
    {
        $column = match ($user->role) {
            'perawat' => 'nurse_name',
            'dokter' => 'doctor_name',
            default => null,
        };

        if (! $column) {
            return [];
        }

        return DialysisSchedule::query()
            ->where($column, $user->name)
            ->distinct()
            ->pluck('patient_id')
            ->map(fn ($patientId): int => (int) $patientId)
            ->all();
    }
}
