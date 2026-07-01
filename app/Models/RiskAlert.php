<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'source_type',
        'source_id',
        'alert_date',
        'alert_time',
        'alert_level',
        'alert_type',
        'title',
        'message',
        'trigger_value',
        'threshold_value',
        'recommendation',
        'status',
        'assigned_to',
        'read_at',
        'followed_up_at',
        'resolved_at',
        'follow_up_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'alert_date' => 'date',
            'read_at' => 'datetime',
            'followed_up_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
