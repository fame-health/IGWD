<?php

namespace App\Services;

use App\Models\Patient;

class InitialHdPostWeightService
{
    private const INITIAL_POST_WEIGHT_ROWS = [
        ['patient' => 'Nurul Raudhatul', 'weight' => 50.20],
        ['patient' => 'Bayu johandri', 'weight' => 42.00],
        ['patient' => 'Sarpius Said', 'weight' => 69.80],
        ['patient' => 'Murniati', 'weight' => 64.00],
        ['patient' => 'Tengku Darwin', 'weight' => 59.00],
        ['patient' => 'Lili Nurhayati', 'weight' => 57.50],
        ['patient' => 'Agus Dani saputra', 'weight' => 46.80],
        ['patient' => 'Mariana', 'weight' => 51.00],
        ['patient' => 'Christine Gema dina', 'weight' => 54.50],
        ['patient' => 'srianioktaviani', 'weight' => 96.00],
        ['patient' => 'Dedi Sumardi', 'weight' => 78.00],
        ['patient' => 'Marsel putra Pratama', 'weight' => 31.00],
        ['patient' => 'Siti Fatimah', 'weight' => 48.80],
        ['patient' => 'Indra lunas', 'weight' => 66.80],
        ['patient' => 'Martinus Jek', 'weight' => 53.00],
        ['patient' => 'Jumadi', 'weight' => 57.50],
        ['patient' => "Hanna D'Arza", 'weight' => 86.00],
        ['patient' => 'Nurull Syamsiah', 'weight' => 35.50],
        ['patient' => 'khairul amri', 'weight' => 60.60],
        ['patient' => 'Husnul hotimah', 'weight' => 48.00],
        ['patient' => 'Reza sukma', 'weight' => 46.00],
        ['patient' => 'Karina', 'weight' => 39.25],
        ['patient' => 'Epi Suharyani', 'weight' => 69.00],
        ['patient' => 'Siti Aminah', 'weight' => 47.00],
        ['patient' => 'Lusi Andriani', 'weight' => 55.00],
        ['patient' => 'Murni', 'weight' => 43.50],
        ['patient' => 'Munifa', 'weight' => 49.35],
        ['patient' => 'Mona Lisa Gultom', 'weight' => 66.80],
        ['patient' => 'Misnawati', 'weight' => 41.40],
        ['patient' => 'Misandi irnovan', 'weight' => 52.50],
        ['patient' => 'Maya Mardiana', 'weight' => 77.45],
        ['patient' => 'leni hariyanti', 'weight' => 56.20],
        ['patient' => 'Kurniawan Bagus Saputra', 'weight' => 51.00],
        ['patient' => 'Edy sujarwo', 'weight' => 69.00],
        ['patient' => 'Darsih', 'weight' => 60.00],
        ['patient' => 'Angelica Florentina', 'weight' => 48.00],
        ['patient' => 'Andy Adham', 'weight' => 83.10],
        ['patient' => 'Mestia', 'weight' => 35.30],
        ['patient' => 'Nurul Raudhatul', 'weight' => 49.70],
        ['patient' => 'Sutiar', 'weight' => 65.00],
        ['patient' => 'Suherman', 'weight' => 68.50],
        ['patient' => 'Muhammad lutfi', 'weight' => 57.00],
        ['patient' => 'Iis Mulyawati', 'weight' => 56.00],
        ['patient' => 'Dewi Suratingsih', 'weight' => 50.50],
        ['patient' => 'Bayu johandri', 'weight' => 42.00],
    ];

    public function firstWeightForPatient(Patient|string|null $patient): ?float
    {
        $name = $patient instanceof Patient ? $patient->name : $patient;
        $normalizedName = PreviousHdWeightService::normalizePatientName($name);

        if ($normalizedName === '') {
            return null;
        }

        return $this->firstWeightsByPatient()[$normalizedName] ?? null;
    }

    private function firstWeightsByPatient(): array
    {
        static $weights = null;

        if ($weights !== null) {
            return $weights;
        }

        $weights = [];

        foreach (self::INITIAL_POST_WEIGHT_ROWS as $row) {
            $normalizedName = PreviousHdWeightService::normalizePatientName($row['patient']);
            $weights[$normalizedName] ??= $row['weight'];
        }

        return $weights;
    }
}
