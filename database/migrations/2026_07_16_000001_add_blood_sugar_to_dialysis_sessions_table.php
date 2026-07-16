<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('dialysis_sessions', 'blood_sugar_before')) {
                $table->integer('blood_sugar_before')->nullable()->after('hd_duration_minutes')->comment('Gula darah sebelum HD (mg/dL)');
            }
            if (!Schema::hasColumn('dialysis_sessions', 'blood_sugar_after')) {
                $table->integer('blood_sugar_after')->nullable()->after('blood_sugar_before')->comment('Gula darah setelah HD (mg/dL)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            $table->dropColumn(['blood_sugar_before', 'blood_sugar_after']);
        });
    }
};
