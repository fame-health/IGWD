<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'medical_record_number',
        'name',
        'nik',
        'birth_date',
        'gender',
        'ethnic_group',
        'education',
        'occupation',
        'marital_status',
        'address',
        'phone',
        'responsible_person_name',
        'responsible_person_phone',
        'payment_status',
        'patient_status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(PatientMedicalProfile::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dialysisSchedules(): HasMany
    {
        return $this->hasMany(DialysisSchedule::class);
    }

    public function dialysisSessions(): HasMany
    {
        return $this->hasMany(DialysisSession::class);
    }

    public function latestDialysisSession(): HasOne
    {
        return $this->hasOne(DialysisSession::class)->latestOfMany('session_date');
    }

    public function dailyMonitorings(): HasMany
    {
        return $this->hasMany(DailyMonitoring::class);
    }

    public function latestDailyMonitoring(): HasOne
    {
        return $this->hasOne(DailyMonitoring::class)->latestOfMany('monitoring_date');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function riskSymptoms(): HasMany
    {
        return $this->hasMany(RiskSymptom::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function riskAlerts(): HasMany
    {
        return $this->hasMany(RiskAlert::class);
    }
}
