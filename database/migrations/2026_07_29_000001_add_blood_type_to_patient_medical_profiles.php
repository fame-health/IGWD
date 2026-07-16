<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_medical_profiles', 'blood_type')) {
                $table->string('blood_type')->nullable()->after('medical_history');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            $table->dropColumn('blood_type');
        });
    }
};
