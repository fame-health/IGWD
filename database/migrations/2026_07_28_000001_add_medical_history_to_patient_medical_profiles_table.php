<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            $table->text('medical_history')->nullable()->after('main_diagnosis');
        });
    }

    public function down(): void
    {
        Schema::table('patient_medical_profiles', function (Blueprint $table) {
            $table->dropColumn('medical_history');
        });
    }
};
