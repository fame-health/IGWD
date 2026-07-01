<?php

namespace App\Providers;

use App\Models\DailyMonitoring;
use App\Models\DialysisSession;
use App\Models\RiskSymptom;
use App\Observers\DailyMonitoringObserver;
use App\Observers\DialysisSessionObserver;
use App\Observers\RiskSymptomObserver;
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
        DialysisSession::observe(DialysisSessionObserver::class);
        RiskSymptom::observe(RiskSymptomObserver::class);
    }
}
