<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->text('discard_reason')->nullable()->after('status');
        });

        Schema::table('candidates', function (Blueprint $table): void {
            $table->text('discard_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropColumn('discard_reason'));
        Schema::table('candidates', fn (Blueprint $table) => $table->dropColumn('discard_reason'));
    }
};
