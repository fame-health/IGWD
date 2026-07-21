<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('dialysis_sessions', 'daily_fluid_intake_target_ml')) {
                $table->integer('daily_fluid_intake_target_ml')->nullable()->after('blood_sugar_after');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            $table->dropColumn('daily_fluid_intake_target_ml');
        });
    }
};
