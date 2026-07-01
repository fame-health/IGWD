<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        return $user->role !== 'pasien' || (int) $user->patient_id === $patientId;
    }

    protected function scopeForPatientRole(Builder $query, Request $request, string $column = 'patient_id'): Builder
    {
        if ($request->user()->role === 'pasien') {
            $query->where($column, $request->user()->patient_id);
        }

        return $query;
    }

    protected function applyDateFilters(Builder $query, Request $request, string $column): Builder
    {
        return $query
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate($column, $request->date))
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereDate($column, '>=', $request->start_date))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereDate($column, '<=', $request->end_date));
    }
}
