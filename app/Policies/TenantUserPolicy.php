<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantUserPolicy
{
    /**
     * Determine whether the user can view any tenant users.
     */
    public function viewAny(User $user, Tenant $tenant): bool
    {
        // Super admins can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only admins of this tenant can view users
        return $user->isAdminForTenant($tenant);
    }

    /**
     * Determine whether the user can view a specific tenant user.
     */
    public function view(User $user, Tenant $tenant, User $targetUser): bool
    {
        // Super admins can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only admins of this tenant can view users
        return $user->isAdminForTenant($tenant);
    }

    /**
     * Determine whether the user can invite users to the tenant.
     */
    public function invite(User $user, Tenant $tenant): bool
    {
        // Super admins can invite anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only admins of this tenant can invite
        return $user->isAdminForTenant($tenant);
    }

    /**
     * Determine whether the user can update a tenant user's role.
     */
    public function updateRole(User $user, Tenant $tenant, User $targetUser): bool
    {
        // Super admins can update anyone except themselves
        if ($user->isSuperAdmin()) {
            return $user->id !== $targetUser->id;
        }

        // Cannot change your own role
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only admins can update roles
        return $user->isAdminForTenant($tenant);
    }

    /**
     * Determine whether the user can remove a user from the tenant.
     */
    public function remove(User $user, Tenant $tenant, User $targetUser): bool
    {
        // Super admins can remove anyone except themselves
        if ($user->isSuperAdmin()) {
            return $user->id !== $targetUser->id;
        }

        // Cannot remove yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only admins can remove users
        return $user->isAdminForTenant($tenant);
    }

    /**
     * Determine whether the removal would leave the tenant without an admin.
     */
    public function canSafelyRemove(Tenant $tenant, User $targetUser): bool
    {
        // If target is not an admin, removal is always safe
        if (!$targetUser->isAdminForTenant($tenant)) {
            return true;
        }

        // Count admins in this tenant
        $adminCount = $tenant->users()
            ->wherePivot('role', 'admin')
            ->count();

        // Cannot remove if this is the last admin
        return $adminCount > 1;
    }

    /**
     * Determine whether the role change would leave the tenant without an admin.
     */
    public function canSafelyChangeRole(Tenant $tenant, User $targetUser, string $newRole): bool
    {
        // If changing to admin or target is not an admin, change is safe
        if ($newRole === 'admin' || !$targetUser->isAdminForTenant($tenant)) {
            return true;
        }

        // Count admins in this tenant
        $adminCount = $tenant->users()
            ->wherePivot('role', 'admin')
            ->count();

        // Cannot demote if this is the last admin
        return $adminCount > 1;
    }
}
