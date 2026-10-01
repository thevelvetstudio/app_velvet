<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('record_key', 80)->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload');
            $table->timestamps();
            $table->unique(['type', 'record_key', 'owner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
