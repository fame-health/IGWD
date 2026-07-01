<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskSymptom extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'dialysis_session_id',
        'daily_monitoring_id',
        'symptom_date',
        'shortness_of_breath',
        'edema',
        'muscle_cramp',
        'dizziness_or_weakness',
        'nausea_or_vomiting',
        'chest_pain',
        'headache',
        'description',
        'system_risk_status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'symptom_date' => 'date',
            'muscle_cramp' => 'boolean',
            'dizziness_or_weakness' => 'boolean',
            'nausea_or_vomiting' => 'boolean',
            'chest_pain' => 'boolean',
            'headache' => 'boolean',
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

    public function dailyMonitoring(): BelongsTo
    {
        return $this->belongsTo(DailyMonitoring::class);
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
