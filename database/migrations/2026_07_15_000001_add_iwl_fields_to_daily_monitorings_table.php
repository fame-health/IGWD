<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_monitorings', function (Blueprint $table) {
            $table->integer('insensible_water_loss_ml')->nullable()->after('fluid_intake_ml');
            $table->integer('fluid_output_ml')->nullable()->after('insensible_water_loss_ml');
        });
    }

    public function down(): void
    {
        Schema::table('daily_monitorings', function (Blueprint $table) {
            $table->dropColumn([
                'insensible_water_loss_ml',
                'fluid_output_ml',
            ]);
        });
    }
};
