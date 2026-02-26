<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Link existing users to their corresponding system roles based on legacy role enum
        // This runs after seeders have created the system roles
        $tenantUsers = DB::table('tenant_users')->get();

        foreach ($tenantUsers as $tenantUser) {
            $roleName = $tenantUser->role; // 'admin' or 'viewer'

            // Find the system role for this tenant
            $tenantRole = DB::table('tenant_roles')
                ->where('tenant_id', $tenantUser->tenant_id)
                ->where('name', $roleName)
                ->where('is_system', true)
                ->first();

            if ($tenantRole) {
                DB::table('tenant_users')
                    ->where('tenant_id', $tenantUser->tenant_id)
                    ->where('user_id', $tenantUser->user_id)
                    ->update(['tenant_role_id' => $tenantRole->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear tenant_role_id from all tenant_users
        DB::table('tenant_users')->update(['tenant_role_id' => null]);
    }
};
