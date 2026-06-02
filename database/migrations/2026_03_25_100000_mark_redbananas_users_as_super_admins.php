<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@redbananas.com')
            ->update(['is_super_admin' => true]);
    }

    public function down(): void
    {
        // Revert all except the original super admin (user ID 1)
        DB::table('users')
            ->where('email', 'like', '%@redbananas.com')
            ->where('id', '!=', 1)
            ->update(['is_super_admin' => false]);
    }
};
