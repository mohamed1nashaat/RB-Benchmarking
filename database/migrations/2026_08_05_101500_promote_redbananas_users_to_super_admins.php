<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * All users registered with the @redbananas.com domain are granted
     * super-admin and cross-tenant visibility so they can manage the
     * platform without being blocked by per-tenant scopes.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@redbananas.com')
            ->update([
                'is_super_admin' => true,
                'view_all_tenants' => true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migration.
     *
     * Restoring the previous state is not safe because we do not know
     * which users were already super admins. This down migration clears
     * the flags for the affected domain only; review before running in
     * production.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@redbananas.com')
            ->update([
                'is_super_admin' => false,
                'view_all_tenants' => false,
                'updated_at' => now(),
            ]);
    }
};
