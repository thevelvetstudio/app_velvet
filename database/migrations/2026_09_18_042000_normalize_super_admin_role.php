<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('slug', 'super-admin')->update(['slug' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'super_admin')->update(['slug' => 'super-admin']);
    }
};
