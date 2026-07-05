<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyMonitoringController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DialysisScheduleController;
use App\Http\Controllers\Api\V1\DialysisSessionController;
use App\Http\Controllers\Api\V1\EducationController;
use App\Http\Controllers\Api\V1\MedicalProfileController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RiskAlertController;
use App\Http\Controllers\Api\V1\RiskSymptomController;
use App\Http\Controllers\Api\V1\VitalSignController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register-patient', [AuthController::class, 'registerPatient']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('change-password', [AuthController::class, 'changePassword']);

        Route::get('dashboard', DashboardController::class)
            ->middleware('role:admin,perawat,dokter,manajemen,pasien');

        Route::get('patients', [PatientController::class, 'index'])->middleware('role:admin,perawat,dokter');
        Route::get('patients/{patient}', [PatientController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('patients/{patient}/summary', [PatientController::class, 'summary'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('patients', [PatientController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('patients/{patient}', [PatientController::class, 'update'])->middleware('role:admin,perawat');
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->middleware('role:admin');

        Route::get('patients/{patient}/medical-profile', [MedicalProfileController::class, 'show'])->middleware('role:admin,perawat,dokter');
        Route::post('patients/{patient}/medical-profile', [MedicalProfileController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('patients/{patient}/medical-profile', [MedicalProfileController::class, 'update'])->middleware('role:admin,perawat');

        Route::get('dialysis-schedules', [DialysisScheduleController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('dialysis-schedules/{dialysisSchedule}', [DialysisScheduleController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('dialysis-schedules', [DialysisScheduleController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('dialysis-schedules/{dialysisSchedule}', [DialysisScheduleController::class, 'update'])->middleware('role:admin,perawat');
        Route::patch('dialysis-schedules/{dialysisSchedule}/attendance', [DialysisScheduleController::class, 'attendance'])->middleware('role:admin,perawat');
        Route::delete('dialysis-schedules/{dialysisSchedule}', [DialysisScheduleController::class, 'destroy'])->middleware('role:admin');

        Route::get('dialysis-sessions', [DialysisSessionController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('dialysis-sessions/{dialysisSession}', [DialysisSessionController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('dialysis-sessions', [DialysisSessionController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('dialysis-sessions/{dialysisSession}', [DialysisSessionController::class, 'update'])->middleware('role:admin,perawat');
        Route::patch('dialysis-sessions/{dialysisSession}/doctor-note', [DialysisSessionController::class, 'doctorNote'])->middleware('role:admin,dokter');
        Route::delete('dialysis-sessions/{dialysisSession}', [DialysisSessionController::class, 'destroy'])->middleware('role:admin');

        Route::get('daily-monitorings', [DailyMonitoringController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('daily-monitorings/{dailyMonitoring}', [DailyMonitoringController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('daily-monitorings', [DailyMonitoringController::class, 'store'])->middleware('role:admin,perawat,pasien');
        Route::put('daily-monitorings/{dailyMonitoring}', [DailyMonitoringController::class, 'update'])->middleware('role:admin,perawat,pasien');
        Route::delete('daily-monitorings/{dailyMonitoring}', [DailyMonitoringController::class, 'destroy'])->middleware('role:admin');

        Route::get('vital-signs', [VitalSignController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('vital-signs/{vitalSign}', [VitalSignController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('vital-signs', [VitalSignController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('vital-signs/{vitalSign}', [VitalSignController::class, 'update'])->middleware('role:admin,perawat');
        Route::delete('vital-signs/{vitalSign}', [VitalSignController::class, 'destroy'])->middleware('role:admin');

        Route::get('risk-symptoms', [RiskSymptomController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('risk-symptoms/{riskSymptom}', [RiskSymptomController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('risk-symptoms', [RiskSymptomController::class, 'store'])->middleware('role:admin,perawat,pasien');
        Route::put('risk-symptoms/{riskSymptom}', [RiskSymptomController::class, 'update'])->middleware('role:admin,perawat,pasien');
        Route::delete('risk-symptoms/{riskSymptom}', [RiskSymptomController::class, 'destroy'])->middleware('role:admin');

        Route::get('educations', [EducationController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('educations/{education}', [EducationController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::post('educations', [EducationController::class, 'store'])->middleware('role:admin,perawat');
        Route::put('educations/{education}', [EducationController::class, 'update'])->middleware('role:admin,perawat');
        Route::delete('educations/{education}', [EducationController::class, 'destroy'])->middleware('role:admin');

        Route::get('risk-alerts', [RiskAlertController::class, 'index'])->middleware('role:admin,perawat,dokter,pasien');
        Route::get('risk-alerts/{riskAlert}', [RiskAlertController::class, 'show'])->middleware('role:admin,perawat,dokter,pasien');
        Route::patch('risk-alerts/{riskAlert}/mark-as-read', [RiskAlertController::class, 'markAsRead'])->middleware('role:admin,perawat,dokter');
        Route::patch('risk-alerts/{riskAlert}/follow-up', [RiskAlertController::class, 'followUp'])->middleware('role:admin,perawat,dokter');
        Route::patch('risk-alerts/{riskAlert}/resolve', [RiskAlertController::class, 'resolve'])->middleware('role:admin,perawat,dokter');

        Route::get('notifications', [NotificationController::class, 'index'])->middleware('role:admin,perawat,dokter');
        Route::patch('notifications/{id}/read', [NotificationController::class, 'read'])->middleware('role:admin,perawat,dokter');

        Route::middleware('role:admin,perawat,dokter,manajemen')->prefix('reports')->group(function () {
            Route::get('daily-monitoring', [ReportController::class, 'dailyMonitoring']);
            Route::get('dialysis-sessions', [ReportController::class, 'dialysisSessions']);
            Route::get('risk-patients', [ReportController::class, 'riskPatients']);
            Route::get('risk-alerts', [ReportController::class, 'riskAlerts']);
        });
    });
});
