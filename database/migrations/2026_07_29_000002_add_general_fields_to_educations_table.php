<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            // Ubah patient_id menjadi nullable untuk edukasi umum
            $table->foreignId('patient_id')->nullable()->change();

            // Tambahkan kolom untuk materi umum
            $table->string('title')->nullable()->after('id');
            $table->enum('category', ['Video', 'Poster', 'Artikel', 'Materi Pasien'])->default('Materi Pasien')->after('title');
            $table->text('content')->nullable()->after('education_materials');
            $table->string('youtube_url')->nullable()->after('content');
            $table->string('image_path')->nullable()->after('youtube_url');
            $table->boolean('is_general')->default(false)->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable(false)->change();
            $table->dropColumn(['title', 'category', 'content', 'youtube_url', 'image_path', 'is_general']);
        });
    }
};
