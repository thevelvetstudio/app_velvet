<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('candidate_type');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 40);
            $table->string('email');
            $table->string('city');
            $table->date('birth_date');
            $table->text('experience')->nullable();
            $table->string('availability');
            $table->string('work_mode');
            $table->string('source')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->text('referrer')->nullable();
            $table->text('landing_page')->nullable();
            $table->string('status')->default('NEW')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['candidate_type', 'created_at']);
            $table->index(['email', 'phone']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('candidate_type')->index();
            $table->string('status')->default('NEW')->index();
            $table->foreignId('assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('discarded_at')->nullable();
            $table->timestamp('contracted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_activities');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
