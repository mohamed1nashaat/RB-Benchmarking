<?php

namespace App\Livewire\Pages;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Services\RolePermissionService;
use Livewire\Component;
use Illuminate\Support\Str;

class RoleManagement extends Component
{
    // Role form modal
    public bool $showFormModal = false;
    public ?int $editingRoleId = null;
    public string $roleDisplayName = '';
    public string $roleDescription = '';
    public array $selectedPermissions = [];
    public bool $isSystemRole = false;

    // Delete dialog
    public bool $showDeleteDialog = false;
    public ?int $roleToDeleteId = null;
    public string $roleToDeleteName = '';
    public int $roleToDeleteUsersCount = 0;

    public bool $saving = false;
    public bool $deleteLoading = false;

    public function getTenantProperty(): ?Tenant
    {
        $tenantId = session('current_tenant_id');
        if (!$tenantId) return null;
        return Tenant::find($tenantId);
    }

    public function getRolesProperty()
    {
        $tenant = $this->tenant;
        if (!$tenant) return collect();

        $service = app(RolePermissionService::class);
        $roles = $service->getRolesForTenant($tenant);

        // Auto-create system roles if missing
        if ($roles->isEmpty()) {
            $service->createSystemRolesForTenant($tenant);
            $roles = $service->getRolesForTenant($tenant);
        }

        return $roles;
    }

    public function getGroupedPermissionsProperty(): array
    {
        $service = app(RolePermissionService::class);
        return $service->getGroupedPermissions();
    }

    public function getUsersCountForRole(int $roleId): int
    {
        $service = app(RolePermissionService::class);
        $role = TenantRole::find($roleId);
        return $role ? $service->getUsersCount($role) : 0;
    }

    // Create modal
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    // Edit modal
    public function openEditModal(int $roleId): void
    {
        $role = TenantRole::with('permissions')->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->roleDisplayName = $role->display_name;
        $this->roleDescription = $role->description ?? '';
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->isSystemRole = $role->is_system;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingRoleId = null;
        $this->roleDisplayName = '';
        $this->roleDescription = '';
        $this->selectedPermissions = [];
        $this->isSystemRole = false;
    }

    public function togglePermission(int $permissionId): void
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, [$permissionId]));
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    public function toggleGroup(string $groupName): void
    {
        $groupPermissions = $this->groupedPermissions[$groupName] ?? [];
        $groupIds = array_column($groupPermissions, 'id');
        $allSelected = count(array_intersect($groupIds, $this->selectedPermissions)) === count($groupIds);

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupIds));
        } else {
            $newIds = array_diff($groupIds, $this->selectedPermissions);
            $this->selectedPermissions = array_values(array_merge($this->selectedPermissions, $newIds));
        }
    }

    public function toggleAll(): void
    {
        $allIds = [];
        foreach ($this->groupedPermissions as $group) {
            foreach ($group as $permission) {
                $allIds[] = $permission['id'];
            }
        }

        if (count($this->selectedPermissions) === count($allIds)) {
            $this->selectedPermissions = [];
        } else {
            $this->selectedPermissions = $allIds;
        }
    }

    public function isGroupSelected(string $groupName): bool
    {
        $groupPermissions = $this->groupedPermissions[$groupName] ?? [];
        $groupIds = array_column($groupPermissions, 'id');
        return count($groupIds) > 0 && count(array_intersect($groupIds, $this->selectedPermissions)) === count($groupIds);
    }

    public function getAllSelectedProperty(): bool
    {
        $allIds = [];
        foreach ($this->groupedPermissions as $group) {
            foreach ($group as $permission) {
                $allIds[] = $permission['id'];
            }
        }
        return count($allIds) > 0 && count($this->selectedPermissions) === count($allIds);
    }

    public function saveRole(): void
    {
        $tenant = $this->tenant;
        if (!$tenant) return;

        if (empty($this->selectedPermissions)) {
            session()->flash('error', __('pages.roles.permissions_required'));
            return;
        }

        if (!$this->isSystemRole && empty($this->roleDisplayName)) {
            session()->flash('error', __('pages.roles.name_required'));
            return;
        }

        $this->saving = true;

        try {
            $service = app(RolePermissionService::class);

            if ($this->editingRoleId) {
                $role = TenantRole::findOrFail($this->editingRoleId);

                $data = [
                    'permissions' => $this->selectedPermissions,
                ];

                if (!$role->is_system) {
                    $data['name'] = Str::slug($this->roleDisplayName);
                    $data['display_name'] = $this->roleDisplayName;
                    $data['description'] = $this->roleDescription ?: null;
                }

                $service->updateRole($role, $data);
                session()->flash('success', __('pages.roles.role_updated'));
            } else {
                $name = Str::slug($this->roleDisplayName);

                if (!$service->isRoleNameAvailable($tenant, $name)) {
                    session()->flash('error', __('pages.roles.name_exists'));
                    $this->saving = false;
                    return;
                }

                $service->createRole($tenant, [
                    'name' => $name,
                    'display_name' => $this->roleDisplayName,
                    'description' => $this->roleDescription ?: null,
                    'permissions' => $this->selectedPermissions,
                ]);
                session()->flash('success', __('pages.roles.role_created'));
            }

            $this->closeFormModal();
        } catch (\Exception $e) {
            session()->flash('error', __('pages.roles.save_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    // Delete
    public function confirmDeleteRole(int $roleId): void
    {
        $role = TenantRole::findOrFail($roleId);
        $service = app(RolePermissionService::class);

        $this->roleToDeleteId = $role->id;
        $this->roleToDeleteName = $role->display_name;
        $this->roleToDeleteUsersCount = $service->getUsersCount($role);
        $this->showDeleteDialog = true;
    }

    public function deleteRole(): void
    {
        if (!$this->roleToDeleteId) return;

        $this->deleteLoading = true;

        try {
            $role = TenantRole::findOrFail($this->roleToDeleteId);
            $service = app(RolePermissionService::class);
            $service->deleteRole($role);

            $this->showDeleteDialog = false;
            $this->roleToDeleteId = null;
            session()->flash('success', __('pages.roles.role_deleted'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->deleteLoading = false;
        }
    }

    public function closeDeleteDialog(): void
    {
        $this->showDeleteDialog = false;
        $this->roleToDeleteId = null;
        $this->roleToDeleteName = '';
        $this->roleToDeleteUsersCount = 0;
    }

    public function render()
    {
        return view('livewire.pages.role-management')
            ->layout('layouts.app', ['title' => __('pages.roles.title')]);
    }
}
