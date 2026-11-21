<?php

namespace App\Livewire\Components;

use Livewire\Component;

class UserMenu extends Component
{
    public function logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect('/login', navigate: false);
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // Get current tenant role
        $tenantId = session('current_tenant_id');
        $currentTenantRole = null;
        $currentTenantName = null;
        $currentTenantRoleName = null;

        if ($tenantId && $user) {
            $tenant = $user->tenants()->where('tenants.id', $tenantId)->first();
            if ($tenant) {
                $currentTenantRole = $tenant->pivot->role ?? null;
                $currentTenantRoleName = $tenant->pivot->tenantRole?->display_name ?? $currentTenantRole;
                $currentTenantName = $tenant->name;
            }
        }

        $isAdmin = $isSuperAdmin || $currentTenantRole === 'admin';

        $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;
        $canViewUsers = $isSuperAdmin || ($user && $tenant && $user->hasPermissionForTenant($tenant, 'view_users'));
        $canManageRoles = $isSuperAdmin || ($user && $tenant && $user->hasPermissionForTenant($tenant, 'manage_roles'));

        return view('livewire.components.user-menu', [
            'user' => $user,
            'isSuperAdmin' => $isSuperAdmin,
            'isAdmin' => $isAdmin,
            'canViewUsers' => $canViewUsers,
            'canManageRoles' => $canManageRoles,
            'currentTenantRole' => $currentTenantRole,
            'currentTenantRoleName' => $currentTenantRoleName,
            'currentTenantName' => $currentTenantName,
        ]);
    }
}
