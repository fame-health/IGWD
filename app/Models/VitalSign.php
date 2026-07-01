<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    use HasFactory;

    protected $fillable = [
        'dialysis_session_id',
        'patient_id',
        'measurement_date',
        'blood_pressure_before',
        'pulse_before',
        'temperature',
        'respiration',
        'oxygen_saturation',
        'blood_pressure_during',
        'blood_pressure_after',
        'complaints',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'measurement_date' => 'date',
            'temperature' => 'decimal:1',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dialysisSession(): BelongsTo
    {
        return $this->belongsTo(DialysisSession::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
