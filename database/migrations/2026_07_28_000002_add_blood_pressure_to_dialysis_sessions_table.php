<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('dialysis_sessions', 'blood_pressure_before')) {
                $table->string('blood_pressure_before')->nullable()->after('hd_duration_minutes');
            }
            if (!Schema::hasColumn('dialysis_sessions', 'blood_pressure_after')) {
                $table->string('blood_pressure_after')->nullable()->after('blood_pressure_before');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            $table->dropColumn(['blood_pressure_before', 'blood_pressure_after']);
        });
    }
};
