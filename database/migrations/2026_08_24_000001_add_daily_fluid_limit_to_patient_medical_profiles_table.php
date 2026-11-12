<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_medical_profiles', 'daily_fluid_limit_ml')) {
                $table->integer('daily_fluid_limit_ml')->nullable()->after('dry_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('patient_medical_profiles', 'daily_fluid_limit_ml')) {
                $table->dropColumn('daily_fluid_limit_ml');
            }
        });
    }
};
