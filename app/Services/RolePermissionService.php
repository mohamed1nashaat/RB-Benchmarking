<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\TenantUser;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Collection;

class RolePermissionService
{
    /**
     * Get all permissions grouped by their group attribute.
     */
    public function getGroupedPermissions(): array
    {
        return Permission::orderBy('group')
            ->orderBy('display_name')
            ->get()
            ->groupBy('group')
            ->map(function ($permissions) {
                return $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                    ];
                })->values();
            })
            ->toArray();
    }

    /**
     * Get all roles for a tenant.
     */
    public function getRolesForTenant(Tenant $tenant): Collection
    {
        return TenantRole::where('tenant_id', $tenant->id)
            ->with('permissions')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a specific role with permissions.
     */
    public function getRole(TenantRole $role): TenantRole
    {
        return $role->load('permissions');
    }

    /**
     * Create a new custom role for a tenant.
     */
    public function createRole(Tenant $tenant, array $data): TenantRole
    {
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update an existing role.
     */
    public function updateRole(TenantRole $role, array $data): TenantRole
    {
        // System roles can only have their permissions updated
        if ($role->is_system) {
            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }
        } else {
            $role->update([
                'name' => $data['name'] ?? $role->name,
                'display_name' => $data['display_name'] ?? $role->display_name,
                'description' => $data['description'] ?? $role->description,
            ]);

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }
        }

        return $role->load('permissions');
    }

    /**
     * Delete a custom role.
     */
    public function deleteRole(TenantRole $role): bool
    {
        if ($role->is_system) {
            throw new \InvalidArgumentException('System roles cannot be deleted.');
        }

        // Get the viewer role for this tenant to reassign users
        $viewerRole = TenantRole::where('tenant_id', $role->tenant_id)
            ->where('name', 'viewer')
            ->where('is_system', true)
            ->first();

        if ($viewerRole) {
            // Reassign users to viewer role
            TenantUser::where('tenant_role_id', $role->id)
                ->update([
                    'tenant_role_id' => $viewerRole->id,
                    'role' => 'viewer', // Also update legacy role
                ]);
        }

        return $role->delete();
    }

    /**
     * Check if a role name is available for a tenant.
     */
    public function isRoleNameAvailable(Tenant $tenant, string $name, ?int $excludeRoleId = null): bool
    {
        $query = TenantRole::where('tenant_id', $tenant->id)
            ->where('name', $name);

        if ($excludeRoleId) {
            $query->where('id', '!=', $excludeRoleId);
        }

        return !$query->exists();
    }

    /**
     * Get users count for a role.
     */
    public function getUsersCount(TenantRole $role): int
    {
        return TenantUser::where('tenant_role_id', $role->id)->count();
    }

    /**
     * Create system roles for a new tenant.
     */
    public function createSystemRolesForTenant(Tenant $tenant): void
    {
        $seeder = new TenantRoleSeeder();
        $seeder->createSystemRolesForTenant($tenant);
    }

    /**
     * Get default role (viewer) for a tenant.
     */
    public function getDefaultRole(Tenant $tenant): ?TenantRole
    {
        return TenantRole::where('tenant_id', $tenant->id)
            ->where('name', 'viewer')
            ->where('is_system', true)
            ->first();
    }

    /**
     * Get admin role for a tenant.
     */
    public function getAdminRole(Tenant $tenant): ?TenantRole
    {
        return TenantRole::where('tenant_id', $tenant->id)
            ->where('name', 'admin')
            ->where('is_system', true)
            ->first();
    }

    /**
     * Assign a role to a user in a tenant.
     */
    public function assignRole(Tenant $tenant, int $userId, int $roleId): void
    {
        $role = TenantRole::where('id', $roleId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->update([
                'tenant_role_id' => $role->id,
                'role' => $role->is_system ? $role->name : 'viewer', // Map to legacy role
            ]);
    }
}
