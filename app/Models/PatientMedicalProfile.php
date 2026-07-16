<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'main_diagnosis',
        'medical_history',
        'comorbidities',
        'hemodialysis_start_date',
        'hemodialysis_frequency',
        'dry_weight',
        'vascular_access',
        'allergies',
        'routine_medications',
        'important_notes',
        'blood_type',
    ];

    protected function casts(): array
    {
        return [
            'hemodialysis_start_date' => 'date',
            'dry_weight' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
