<?php

namespace App\Services;

use App\Models\DialysisSession;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PreviousHdWeightService
{
    private const INITIAL_WEIGHTS_BY_PATIENT = [
        'Nurul Raudhatul' => 49.70,
        'Bayu johandri' => 42.00,
        'Sarpius Said' => 69.80,
        'Murniati' => 64.00,
        'Tengku Darwin' => 59.00,
        'Lili Nurhayati' => 57.50,
        'Agus Dani saputra' => 46.80,
        'Mariana' => 51.00,
        'Christine Gema dina' => 54.50,
        'srianioktaviani' => 96.00,
        'Dedi Sumardi' => 78.00,
        'Marsel putra Pratama' => 31.00,
        'Siti Fatimah' => 48.80,
        'Indra lunas' => 66.80,
        'Martinus Jek' => 53.00,
        'Jumadi' => 57.50,
        "Hanna D'Arza" => 86.00,
        'Nurull Syamsiah' => 35.50,
        'khairul amri' => 60.60,
        'Husnul hotimah' => 48.00,
        'Reza sukma' => 46.00,
        'Karina' => 39.25,
        'Epi Suharyani' => 69.00,
        'Siti Aminah' => 47.00,
        'Lusi Andriani' => 55.00,
        'Murni' => 43.50,
        'Munifa' => 49.35,
        'Mona Lisa Gultom' => 66.80,
        'Misnawati' => 41.40,
        'Misandi irnovan' => 52.50,
        'Maya Mardiana' => 77.45,
        'leni hariyanti' => 56.20,
        'Kurniawan Bagus Saputra' => 51.00,
        'Edy sujarwo' => 69.00,
        'Darsih' => 60.00,
        'Angelica Florentina' => 48.00,
        'Andy Adham' => 83.10,
        'Mestia' => 35.30,
        'Sutiar' => 65.00,
        'Suherman' => 68.50,
        'Muhammad lutfi' => 57.00,
        'Iis Mulyawati' => 56.00,
        'Dewi Suratingsih' => 50.50,
    ];

    public function resolveForSession(DialysisSession $session): ?float
    {
        if ($session->previous_post_hd_weight !== null || blank($session->patient_id)) {
            return null;
        }

        return $this->resolveForPatientContext(
            (int) $session->patient_id,
            $session->session_date,
            $session->getKey()
        );
    }

    public function resolveForPatientContext(int $patientId, mixed $sessionDate = null, int|string|null $ignoreSessionId = null): ?float
    {
        $previousSession = $this->previousPostHdSession($patientId, $sessionDate, $ignoreSessionId);

        if ($previousSession?->current_post_hd_weight !== null) {
            return (float) $previousSession->current_post_hd_weight;
        }

        return $this->initialWeightForFirstSession($patientId, $sessionDate, $ignoreSessionId);
    }

    public function initialWeightForPatient(Patient|string|null $patient): ?float
    {
        $name = $patient instanceof Patient ? $patient->name : $patient;
        $normalizedName = self::normalizePatientName($name);

        if ($normalizedName === '') {
            return null;
        }

        return $this->normalizedInitialWeights()[$normalizedName] ?? null;
    }

    private function previousPostHdSession(int $patientId, mixed $sessionDate = null, int|string|null $ignoreSessionId = null): ?DialysisSession
    {
        $date = $this->dateString($sessionDate);

        return DialysisSession::query()
            ->where('patient_id', $patientId)
            ->whereNotNull('current_post_hd_weight')
            ->when($ignoreSessionId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreSessionId))
            ->when($date !== null, fn (Builder $query): Builder => $query->whereDate('session_date', '<', $date))
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->first();
    }

    private function initialWeightForFirstSession(int $patientId, mixed $sessionDate = null, int|string|null $ignoreSessionId = null): ?float
    {
        if ($this->hasEarlierSession($patientId, $sessionDate, $ignoreSessionId)) {
            return null;
        }

        $patientName = Patient::query()
            ->whereKey($patientId)
            ->value('name');

        return $this->initialWeightForPatient($patientName);
    }

    private function hasEarlierSession(int $patientId, mixed $sessionDate = null, int|string|null $ignoreSessionId = null): bool
    {
        $date = $this->dateString($sessionDate);

        return DialysisSession::query()
            ->where('patient_id', $patientId)
            ->when($ignoreSessionId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreSessionId))
            ->when($date !== null, fn (Builder $query): Builder => $query->whereDate('session_date', '<', $date))
            ->exists();
    }

    private function normalizedInitialWeights(): array
    {
        static $weights = null;

        if ($weights !== null) {
            return $weights;
        }

        $weights = [];

        foreach (self::INITIAL_WEIGHTS_BY_PATIENT as $patientName => $weight) {
            $weights[self::normalizePatientName($patientName)] = $weight;
        }

        return $weights;
    }

    public static function normalizePatientName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $name = str_replace("\u{00A0}", ' ', $name);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return strtolower(trim($name));
    }

    private function dateString(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        return Carbon::parse($date)->toDateString();
    }
}
