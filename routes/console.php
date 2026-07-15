<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:check-missing-daily-monitoring')->dailyAt('20:00');
Schedule::command('app:send-dialysis-schedule-reminders')->everyFiveMinutes();
Schedule::command('app:generate-automatic-schedules')->mondays()->fridays()->at('06:00');
