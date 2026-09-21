<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('AVAILABLE')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['starts_at', 'ends_at']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interview_slot_id')->nullable()->constrained('interview_slots')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('INVITED')->index();
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->dateTime('invitation_expires_at')->nullable();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('meeting_type', 30)->default('VIDEO');
            $table->string('meeting_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('interview_slots');
    }
};
