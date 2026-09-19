<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_token')->unique();
            $table->timestamp('accepted_at');
            $table->json('preferences')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_token')->unique();
            $table->string('candidate_type', 20)->default('MODEL')->index();
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->json('data');
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_drafts');
        Schema::dropIfExists('cookie_consents');
    }
};
