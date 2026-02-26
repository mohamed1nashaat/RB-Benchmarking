<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantRole;
use Illuminate\Database\Seeder;

class TenantRoleSeeder extends Seeder
{
    /**
     * Viewer role permissions.
     */
    protected array $viewerPermissions = [
        'view_dashboard',
        'view_reports',
        'export_data',
        'view_campaigns',
        'view_accounts',
        'view_alerts',
        'view_benchmarks',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createSystemRolesForTenant($tenant);
        }
    }

    /**
     * Create system roles (admin and viewer) for a tenant.
     */
    public function createSystemRolesForTenant(Tenant $tenant): void
    {
        // Create Admin role with all permissions
        $adminRole = TenantRole::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'admin',
            ],
            [
                'display_name' => 'Admin',
                'description' => 'Full access to all features',
                'is_system' => true,
            ]
        );

        // Assign all permissions to admin
        $allPermissionIds = Permission::pluck('id')->toArray();
        $adminRole->permissions()->sync($allPermissionIds);

        // Create Viewer role with limited permissions
        $viewerRole = TenantRole::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'viewer',
            ],
            [
                'display_name' => 'Viewer',
                'description' => 'Read-only access to view data',
                'is_system' => true,
            ]
        );

        // Assign viewer permissions
        $viewerPermissionIds = Permission::whereIn('name', $this->viewerPermissions)
            ->pluck('id')
            ->toArray();
        $viewerRole->permissions()->sync($viewerPermissionIds);
    }
}
