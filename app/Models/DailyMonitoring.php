<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMonitoring extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'last_dialysis_session_id',
        'monitoring_date',
        'day_after_hd',
        'last_hd_date',
        'next_hd_date',
        'last_post_hd_weight',
        'today_weight',
        'daily_weight_gain_kg',
        'fluid_intake_ml',
        'insensible_water_loss_ml',
        'fluid_output_ml',
        'daily_fluid_limit_ml',
        'fluid_difference_ml',
        'fluid_status',
        'symptom_notes',
        'staff_notes',
        'risk_status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_date' => 'date',
            'last_hd_date' => 'date',
            'next_hd_date' => 'date',
            'last_post_hd_weight' => 'decimal:2',
            'today_weight' => 'decimal:2',
            'daily_weight_gain_kg' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function lastDialysisSession(): BelongsTo
    {
        return $this->belongsTo(DialysisSession::class, 'last_dialysis_session_id');
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
