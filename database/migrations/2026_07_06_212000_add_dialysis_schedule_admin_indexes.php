<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialysis_schedules', function (Blueprint $table) {
            $table->index(['hd_date', 'shift'], 'dialysis_schedules_hd_date_shift_index');
            $table->index(['hd_date', 'attendance_status'], 'dialysis_schedules_hd_date_status_index');
            $table->index(['patient_id', 'hd_date'], 'dialysis_schedules_patient_hd_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('dialysis_schedules', function (Blueprint $table) {
            $table->dropIndex('dialysis_schedules_hd_date_shift_index');
            $table->dropIndex('dialysis_schedules_hd_date_status_index');
            $table->dropIndex('dialysis_schedules_patient_hd_date_index');
        });
    }
};
