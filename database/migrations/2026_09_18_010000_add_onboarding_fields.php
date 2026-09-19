<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('motivation')->nullable()->after('experience');
            $table->json('goals')->nullable()->after('motivation');
            $table->timestamp('privacy_accepted_at')->nullable()->after('landing_page');
        });
        Schema::create('lead_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('identity');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->string('status')->default('PENDING')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_documents');
        Schema::table('leads', fn (Blueprint $table) => $table->dropColumn(['motivation', 'goals', 'privacy_accepted_at']));
    }
};
