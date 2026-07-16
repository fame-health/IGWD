<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrasi ini dikosongkan karena sudah dicakup oleh migrasi 2026_07_16_000001
        // untuk menghindari duplikasi kolom blood_sugar_before/after.
    }

    public function down(): void
    {
        // No action needed
    }
};
