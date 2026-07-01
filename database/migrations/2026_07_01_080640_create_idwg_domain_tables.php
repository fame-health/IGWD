<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_number')->unique();
            $table->string('name');
            $table->string('nik')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['laki-laki', 'perempuan']);
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('responsible_person_name')->nullable();
            $table->string('responsible_person_phone')->nullable();
            $table->enum('payment_status', ['BPJS', 'Umum', 'Asuransi', 'Lainnya'])->nullable();
            $table->enum('patient_status', ['Aktif', 'Tidak Aktif', 'Pindah', 'Meninggal'])->default('Aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
        });

        Schema::create('patient_medical_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('main_diagnosis')->nullable();
            $table->text('comorbidities')->nullable();
            $table->date('hemodialysis_start_date')->nullable();
            $table->enum('hemodialysis_frequency', ['1x per minggu', '2x per minggu', '3x per minggu'])->nullable();
            $table->decimal('dry_weight', 5, 2)->nullable();
            $table->enum('vascular_access', ['AV Fistula', 'CDL', 'Graft', 'Lainnya'])->nullable();
            $table->text('allergies')->nullable();
            $table->text('routine_medications')->nullable();
            $table->text('important_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dialysis_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('hd_date');
            $table->string('day_name')->nullable();
            $table->enum('shift', ['Pagi', 'Siang', 'Sore', 'Malam']);
            $table->string('room')->nullable();
            $table->string('machine_number')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('nurse_name')->nullable();
            $table->enum('attendance_status', ['Terjadwal', 'Hadir', 'Tidak Hadir', 'Reschedule'])->default('Terjadwal');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dialysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dialysis_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date');
            $table->enum('shift', ['Pagi', 'Siang', 'Sore', 'Malam'])->nullable();
            $table->decimal('previous_post_hd_weight', 5, 2)->nullable();
            $table->decimal('current_pre_hd_weight', 5, 2)->nullable();
            $table->decimal('dry_weight', 5, 2)->nullable();
            $table->decimal('idwg_kg', 5, 2)->nullable();
            $table->decimal('idwg_percent', 5, 2)->nullable();
            $table->enum('risk_category', ['Aman', 'Waspada', 'Tinggi', 'Darurat'])->nullable();
            $table->decimal('current_post_hd_weight', 5, 2)->nullable();
            $table->decimal('target_ultrafiltration', 5, 2)->nullable();
            $table->integer('hd_duration_minutes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('daily_monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('last_dialysis_session_id')->nullable()->constrained('dialysis_sessions')->nullOnDelete();
            $table->date('monitoring_date');
            $table->integer('day_after_hd')->nullable();
            $table->date('last_hd_date')->nullable();
            $table->date('next_hd_date')->nullable();
            $table->decimal('last_post_hd_weight', 5, 2)->nullable();
            $table->decimal('today_weight', 5, 2);
            $table->decimal('daily_weight_gain_kg', 5, 2)->nullable();
            $table->integer('fluid_intake_ml')->nullable();
            $table->integer('daily_fluid_limit_ml')->nullable();
            $table->integer('fluid_difference_ml')->nullable();
            $table->enum('fluid_status', ['Aman', 'Melebihi Batas'])->nullable();
            $table->text('symptom_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->enum('risk_status', ['Normal', 'Waspada', 'Tinggi', 'Darurat'])->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialysis_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('measurement_date');
            $table->string('blood_pressure_before')->nullable();
            $table->integer('pulse_before')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('respiration')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->string('blood_pressure_during')->nullable();
            $table->string('blood_pressure_after')->nullable();
            $table->text('complaints')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('risk_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dialysis_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('daily_monitoring_id')->nullable()->constrained('daily_monitorings')->nullOnDelete();
            $table->date('symptom_date');
            $table->enum('shortness_of_breath', ['Tidak', 'Ringan', 'Sedang', 'Berat'])->default('Tidak');
            $table->enum('edema', ['Tidak', 'Kaki', 'Tangan', 'Wajah', 'Lainnya'])->default('Tidak');
            $table->boolean('muscle_cramp')->default(false);
            $table->boolean('dizziness_or_weakness')->default(false);
            $table->boolean('nausea_or_vomiting')->default(false);
            $table->boolean('chest_pain')->default(false);
            $table->boolean('headache')->default(false);
            $table->text('description')->nullable();
            $table->enum('system_risk_status', ['Normal', 'Waspada', 'Tinggi', 'Darurat'])->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('education_date');
            $table->text('education_materials')->nullable();
            $table->enum('patient_understanding', ['Baik', 'Cukup', 'Kurang'])->nullable();
            $table->enum('fluid_compliance', ['Baik', 'Cukup', 'Kurang'])->nullable();
            $table->enum('schedule_compliance', ['Hadir', 'Terlambat', 'Tidak Hadir'])->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->string('educator_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('alert_date');
            $table->time('alert_time')->nullable();
            $table->enum('alert_level', ['Normal', 'Waspada', 'Tinggi', 'Darurat']);
            $table->enum('alert_type', [
                'Kenaikan Berat Badan',
                'IDWG Tinggi',
                'Cairan Melebihi Batas',
                'Gejala Risiko',
                'Prediksi Risiko',
                'Tidak Input Data Harian',
            ]);
            $table->string('title');
            $table->text('message');
            $table->string('trigger_value')->nullable();
            $table->string('threshold_value')->nullable();
            $table->text('recommendation')->nullable();
            $table->enum('status', ['Baru', 'Dibaca', 'Ditindaklanjuti', 'Selesai'])->default('Baru');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('followed_up_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->text('follow_up_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'alert_date', 'alert_level']);
            $table->unique(['patient_id', 'source_type', 'source_id', 'alert_type', 'alert_date'], 'risk_alert_duplicate_guard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('educations');
        Schema::dropIfExists('risk_symptoms');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('daily_monitorings');
        Schema::dropIfExists('dialysis_sessions');
        Schema::dropIfExists('dialysis_schedules');
        Schema::dropIfExists('patient_medical_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::dropIfExists('patients');
    }
};
