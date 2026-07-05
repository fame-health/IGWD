<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 20)->default('android');
            $table->text('token');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });

        Schema::create('dialysis_schedule_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialysis_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('reminder_type', 40);
            $table->dateTime('scheduled_at');
            $table->dateTime('due_at');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['dialysis_schedule_id', 'reminder_type'], 'dialysis_schedule_reminder_once');
            $table->index(['reminder_type', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialysis_schedule_reminders');
        Schema::dropIfExists('device_tokens');
    }
};
