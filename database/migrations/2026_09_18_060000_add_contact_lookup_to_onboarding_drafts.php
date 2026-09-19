<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_drafts', function (Blueprint $table) {
            $table->string('email')->nullable()->index()->after('candidate_type');
            $table->string('phone', 40)->nullable()->index()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_drafts', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            $table->dropColumn(['email', 'phone']);
        });
    }
};
