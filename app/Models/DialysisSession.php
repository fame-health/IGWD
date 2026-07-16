<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DialysisSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'dialysis_schedule_id',
        'session_date',
        'shift',
        'previous_post_hd_weight',
        'current_pre_hd_weight',
        'dry_weight',
        'idwg_kg',
        'idwg_percent',
        'risk_category',
        'current_post_hd_weight',
        'target_ultrafiltration',
        'hd_duration_minutes',
        'blood_pressure_before',
        'blood_pressure_after',
        'staff_notes',
        'doctor_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'previous_post_hd_weight' => 'decimal:2',
            'current_pre_hd_weight' => 'decimal:2',
            'dry_weight' => 'decimal:2',
            'idwg_kg' => 'decimal:2',
            'idwg_percent' => 'decimal:2',
            'current_post_hd_weight' => 'decimal:2',
            'target_ultrafiltration' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DialysisSchedule::class, 'dialysis_schedule_id');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function riskSymptoms(): HasMany
    {
        return $this->hasMany(RiskSymptom::class);
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
