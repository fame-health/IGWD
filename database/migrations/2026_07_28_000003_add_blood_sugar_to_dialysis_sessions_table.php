<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            $table->integer('blood_sugar_before')->nullable()->after('blood_pressure_after');
            $table->integer('blood_sugar_after')->nullable()->after('blood_sugar_before');
        });
    }

    public function down(): void
    {
        Schema::table('dialysis_sessions', function (Blueprint $table) {
            $table->dropColumn(['blood_sugar_before', 'blood_sugar_after']);
        });
    }
};
