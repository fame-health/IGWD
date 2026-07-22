<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DialysisSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'hd_date',
        'day_name',
        'start_time',
        'end_time',
        'shift',
        'room',
        'machine_number',
        'doctor_name',
        'nurse_name',
        'attendance_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hd_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DialysisSession::class);
    }
}
