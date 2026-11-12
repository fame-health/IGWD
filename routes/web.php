<?php

use App\Http\Controllers\ReportPdfController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('privacy');
});

Route::get('/admin/reports/{report}/pdf', ReportPdfController::class)
    ->where('report', 'all-reports|dashboard|daily-monitoring|dialysis-sessions|patient-biodata|risk-patients|risk-alerts')
    ->middleware(FilamentAuthenticate::class)
    ->name('filament.reports.pdf');
