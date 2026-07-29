<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek pasien yang belum input monitoring harian (Alert untuk Perawat/Admin)
Schedule::command('app:check-missing-daily-monitoring')
    ->dailyAt('07:00')
    ->timezone(config('hd.timezone', config('app.timezone', 'Asia/Jakarta')));

// Kirim reminder jadwal HD ke HP Pasien (H-1 dan H-2 jam)
Schedule::command('app:send-dialysis-schedule-reminders')
    ->everyFiveMinutes()
    ->timezone(config('hd.timezone', config('app.timezone', 'Asia/Jakarta')));

// Buat jadwal otomatis untuk pasien aktif (Setiap 2 Menit sekali - Untuk Testing)
Schedule::command('app:generate-automatic-schedules')
    ->everyTwoMinutes()
    ->timezone(config('hd.timezone', config('app.timezone', 'Asia/Jakarta')));
