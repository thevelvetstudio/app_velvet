<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->json('prequalification_data')->nullable()->after('status');
            $table->string('prequalification_token_hash', 64)->nullable()->unique()->after('prequalification_data');
            $table->timestamp('prequalification_sent_at')->nullable()->after('prequalification_token_hash');
            $table->timestamp('prequalification_completed_at')->nullable()->after('prequalification_sent_at');
            $table->timestamp('prequalification_expires_at')->nullable()->after('prequalification_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['prequalification_token_hash']);
            $table->dropColumn([
                'prequalification_data',
                'prequalification_token_hash',
                'prequalification_sent_at',
                'prequalification_completed_at',
                'prequalification_expires_at',
            ]);
        });
    }
};
