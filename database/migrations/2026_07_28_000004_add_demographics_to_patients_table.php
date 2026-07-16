<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('ethnic_group')->nullable()->after('gender');
            $table->string('education')->nullable()->after('ethnic_group');
            $table->string('occupation')->nullable()->after('education');
            $table->string('marital_status')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['ethnic_group', 'education', 'occupation', 'marital_status']);
        });
    }
};
