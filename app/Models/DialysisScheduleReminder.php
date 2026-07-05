<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialysisScheduleReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'dialysis_schedule_id',
        'reminder_type',
        'scheduled_at',
        'due_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function dialysisSchedule(): BelongsTo
    {
        return $this->belongsTo(DialysisSchedule::class);
    }
}
