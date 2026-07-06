<?php

namespace App\Providers;

use App\Models\DailyMonitoring;
use App\Models\DialysisSchedule;
use App\Models\DialysisSession;
use App\Models\Education;
use App\Models\Patient;
use App\Models\PatientMedicalProfile;
use App\Models\RiskAlert;
use App\Models\RiskSymptom;
use App\Models\User;
use App\Observers\DailyMonitoringObserver;
use App\Observers\DialysisScheduleObserver;
use App\Observers\DialysisSessionObserver;
use App\Observers\EducationObserver;
use App\Observers\PatientMedicalProfileObserver;
use App\Observers\PatientObserver;
use App\Observers\RiskAlertObserver;
use App\Observers\RiskSymptomObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DailyMonitoring::observe(DailyMonitoringObserver::class);
        DialysisSchedule::observe(DialysisScheduleObserver::class);
        DialysisSession::observe(DialysisSessionObserver::class);
        Education::observe(EducationObserver::class);
        Patient::observe(PatientObserver::class);
        PatientMedicalProfile::observe(PatientMedicalProfileObserver::class);
        RiskAlert::observe(RiskAlertObserver::class);
        RiskSymptom::observe(RiskSymptomObserver::class);
        User::observe(UserObserver::class);
    }
}
