<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('identity_verification_status', 30)->nullable()->index()->after('status');
            $table->string('didit_session_id')->nullable()->unique()->after('identity_verification_status');
            $table->text('identity_verification_url')->nullable()->after('didit_session_id');
            $table->json('identity_verification_data')->nullable()->after('identity_verification_url');
            $table->timestamp('identity_verified_at')->nullable()->after('identity_verification_data');
            $table->text('identity_verification_failure_reason')->nullable()->after('identity_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['didit_session_id']);
            $table->dropColumn([
                'identity_verification_status',
                'didit_session_id',
                'identity_verification_url',
                'identity_verification_data',
                'identity_verified_at',
                'identity_verification_failure_reason',
            ]);
        });
    }
};
