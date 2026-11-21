<?php

namespace App\Livewire\Pages;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\TenantUser;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\RolePermissionService;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $activeTab = 'users';

    // Super admin mode
    public bool $isSuperAdmin = false;

    // Filters
    public string $search = '';
    public ?int $roleFilter = null;
    public ?int $tenantFilter = null;
    public int $perPage = 10;
    public string $sortBy = 'name';
    public string $sortOrder = 'asc';

    // Invite modal (regular admin)
    public bool $showInviteModal = false;
    public string $inviteEmail = '';
    public ?int $inviteRoleId = null;
    public string $inviteError = '';
    public array $inviteErrors = [];
    public bool $inviteSending = false;

    // Remove user (regular admin)
    public bool $showRemoveDialog = false;
    public ?int $userToRemoveId = null;
    public string $userToRemoveName = '';
    public bool $removeLoading = false;

    // Role change (regular admin)
    public bool $roleChangeLoading = false;

    // Invitation actions
    public ?int $resendingId = null;
    public ?int $cancellingId = null;

    // Create User Modal (super admin)
    public bool $showCreateModal = false;
    public string $newUserName = '';
    public string $newUserEmail = '';
    public string $newUserPassword = '';
    public ?int $newUserTenantId = null;
    public ?int $newUserRoleId = null;

    // Edit User Modal (super admin)
    public bool $showEditModal = false;
    public ?int $editUserId = null;
    public string $editUserName = '';
    public string $editUserEmail = '';
    public string $editUserPassword = '';
    public array $editUserTenantRoles = [];

    // Assign Tenant Modal (super admin)
    public bool $showAssignModal = false;
    public ?int $assignUserId = null;
    public ?int $assignTenantId = null;
    public ?int $assignRoleId = null;

    // Delete User (super admin)
    public bool $showDeleteDialog = false;
    public ?int $deleteUserId = null;
    public string $deleteUserName = '';

    /** @var array<int, TenantRole|null> */
    protected array $roleCache = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => null, 'as' => 'role'],
        'tenantFilter' => ['except' => null, 'as' => 'tenant'],
        'activeTab' => ['except' => 'users'],
    ];

    public function mount(): void
    {
        $this->isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        if ($this->isSuperAdmin) {
            $this->sortBy = 'created_at';
            $this->sortOrder = 'desc';
        }

        $this->resetInviteForm();
        $this->resetCreateForm();
        $this->resetAssignForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTenantFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedNewUserTenantId(): void
    {
        $this->newUserRoleId = $this->getDefaultRoleIdForTenant($this->newUserTenantId);
    }

    public function updatedAssignTenantId(): void
    {
        $this->assignRoleId = $this->getDefaultRoleIdForTenant($this->assignTenantId);
    }

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

        return TenantRole::where('tenant_id', $tenant->id)
            ->orderBy('is_system', 'desc')
            ->orderBy('display_name')
            ->get();
    }

    public function getRoleOptionsProperty(): array
    {
        return $this->roles
            ->mapWithKeys(fn (TenantRole $role) => [$role->id => $role->display_name])
            ->all();
    }

    public function getUsersProperty()
    {
        if ($this->isSuperAdmin) {
            return $this->getSuperAdminUsers();
        }

        return $this->getTenantUsers();
    }

    private function getTenantUsers()
    {
        $tenant = $this->tenant;
        if (!$tenant) return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);

        $query = $tenant->users()
            ->withPivot('id', 'role', 'tenant_role_id', 'invited_at', 'joined_at', 'last_activity_at', 'invited_by');

        // Search filter
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($this->roleFilter) {
            $query->wherePivot('tenant_role_id', $this->roleFilter);
        }

        // Sorting
        if (in_array($this->sortBy, ['name', 'email', 'last_login_at'])) {
            $query->orderBy($this->sortBy, $this->sortOrder);
        } elseif ($this->sortBy === 'role') {
            $query->orderByPivot('tenant_role_id', $this->sortOrder);
        } elseif ($this->sortBy === 'joined_at') {
            $query->orderByPivot('joined_at', $this->sortOrder);
        } else {
            $query->orderBy('name', $this->sortOrder);
        }

        return $query->paginate($this->perPage);
    }

    private function getSuperAdminUsers()
    {
        $query = User::with(['tenants'])
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->tenantFilter, function ($q) {
                $q->whereHas('tenants', function ($q2) {
                    $q2->where('tenants.id', $this->tenantFilter);
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->whereHas('tenants', function ($q2) {
                    $q2->where('tenant_users.tenant_role_id', $this->roleFilter);
                });
            })
            ->orderBy($this->sortBy, $this->sortOrder);

        return $query->paginate($this->perPage);
    }

    public function getTenantsProperty()
    {
        if (!$this->isSuperAdmin) {
            return collect();
        }

        return Tenant::orderBy('name')->get(['id', 'name']);
    }

    public function getInvitationsProperty()
    {
        if ($this->isSuperAdmin) {
            return UserInvitation::pending()
                ->with(['inviter:id,name', 'tenant:id,name', 'tenantRole:id,display_name'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $tenant = $this->tenant;
        if (!$tenant) return collect();

        return UserInvitation::forTenant($tenant->id)
            ->pending()
            ->with(['inviter:id,name', 'tenantRole:id,display_name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTenantRoleOptions(int $tenantId): array
    {
        return TenantRole::where('tenant_id', $tenantId)
            ->orderBy('is_system', 'desc')
            ->orderBy('display_name')
            ->get()
            ->mapWithKeys(fn (TenantRole $role) => [$role->id => $role->display_name])
            ->all();
    }

    private function getDefaultRoleIdForTenant(?int $tenantId): ?int
    {
        if (!$tenantId) return null;

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return null;

        $role = app(RolePermissionService::class)->getDefaultRole($tenant);

        return $role?->id;
    }

    // ─── Permissions ─────────────────────────────────────────────────────

    public function canViewUsers(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        $tenant = $this->tenant;
        return $tenant && $user->hasPermissionForTenant($tenant, 'view_users');
    }

    public function canInviteUsers(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        $tenant = $this->tenant;
        return $tenant && $user->hasPermissionForTenant($tenant, 'invite_users');
    }

    public function canManageUsers(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        $tenant = $this->tenant;
        return $tenant && $user->hasPermissionForTenant($tenant, 'manage_users');
    }

    public function canManageRoles(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        $tenant = $this->tenant;
        return $tenant && $user->hasPermissionForTenant($tenant, 'manage_roles');
    }

    // Sorting
    public function handleSort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortOrder = $field === 'name' ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    // ─── Regular Admin Methods ───────────────────────────────────────────

    // Invite user
    public function openInviteModal(): void
    {
        if (!$this->canInviteUsers()) {
            session()->flash('error', __('pages.users.permissions.invite_users'));
            return;
        }

        $this->resetInviteForm();
        $this->showInviteModal = true;
    }

    public function closeInviteModal(): void
    {
        if (!$this->inviteSending) {
            $this->showInviteModal = false;
            $this->resetInviteForm();
        }
    }

    private function resetInviteForm(): void
    {
        $this->inviteEmail = '';
        $this->inviteRoleId = $this->getDefaultViewerRoleId();
        $this->inviteError = '';
        $this->inviteErrors = [];
    }

    private function getDefaultViewerRoleId(): ?int
    {
        $tenant = $this->tenant;
        if (!$tenant) return null;

        return $this->roles->firstWhere('name', 'viewer')?->id
            ?? $this->roles->first()?->id;
    }

    public function sendInvitation(): void
    {
        if (!$this->canInviteUsers()) {
            session()->flash('error', __('pages.users.permissions.invite_users'));
            return;
        }

        $tenant = $this->tenant;
        if (!$tenant) return;

        $this->inviteErrors = [];
        $this->inviteError = '';

        $allowedDomain = 'redbananas.com';

        $this->validate([
            'inviteEmail' => 'required|email|regex:/@' . preg_quote($allowedDomain, '/') . '$/i',
            'inviteRoleId' => 'required|exists:tenant_roles,id',
        ], [
            'inviteEmail.regex' => __('pages.users.email_domain_not_allowed', ['domain' => $allowedDomain]),
        ], [
            'inviteEmail' => __('labels.email'),
            'inviteRoleId' => __('labels.role'),
        ]);

        $role = TenantRole::where('id', $this->inviteRoleId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$role) {
            $this->inviteError = __('Selected role is not valid for this organization.');
            return;
        }

        $this->inviteSending = true;

        try {
            $service = app(UserInvitationService::class);
            $service->invite(
                $tenant,
                $this->inviteEmail,
                $role->id,
                auth()->user()
            );

            $this->showInviteModal = false;
            $this->resetInviteForm();
            session()->flash('success', __('pages.users.invitation_sent'));
        } catch (\InvalidArgumentException $e) {
            $this->inviteError = $e->getMessage();
        } catch (\Exception $e) {
            $this->inviteError = __('pages.users.invitation_failed');
        } finally {
            $this->inviteSending = false;
        }
    }

    // Change user role
    public function changeUserRole(int $userId, int $newRoleId): void
    {
        if (!$this->canManageUsers()) {
            session()->flash('error', __('pages.users.permissions.change_role'));
            return;
        }

        $tenant = $this->tenant;
        if (!$tenant) return;

        $this->roleChangeLoading = true;

        try {
            $targetUser = User::findOrFail($userId);

            // Cannot change own role
            if ($targetUser->id === auth()->id()) {
                session()->flash('error', __('pages.users.cannot_change_own_role'));
                return;
            }

            $service = app(RolePermissionService::class);
            $service->assignRole($tenant, $targetUser->id, $newRoleId);

            session()->flash('success', __('pages.users.role_updated'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.users.role_update_failed') . ': ' . $e->getMessage());
        } finally {
            $this->roleChangeLoading = false;
        }
    }

    // Remove user
    public function confirmRemoveUser(int $userId): void
    {
        if (!$this->canManageUsers()) {
            return;
        }

        $user = User::findOrFail($userId);
        $this->userToRemoveId = $user->id;
        $this->userToRemoveName = $user->name;
        $this->showRemoveDialog = true;
    }

    public function removeUser(): void
    {
        if (!$this->canManageUsers()) {
            session()->flash('error', __('pages.users.permissions.remove_users'));
            return;
        }

        $tenant = $this->tenant;
        if (!$tenant || !$this->userToRemoveId) return;

        $this->removeLoading = true;

        try {
            $targetUser = User::findOrFail($this->userToRemoveId);

            // Check if this would leave the tenant without an admin
            $adminRole = app(RolePermissionService::class)->getAdminRole($tenant);
            $adminCount = $adminRole
                ? TenantUser::where('tenant_id', $tenant->id)
                    ->where('tenant_role_id', $adminRole->id)
                    ->count()
                : TenantUser::where('tenant_id', $tenant->id)
                    ->where('role', 'admin')
                    ->count();

            $targetTenantUser = TenantUser::where('tenant_id', $tenant->id)
                ->where('user_id', $targetUser->id)
                ->first();

            $targetIsAdmin = $adminRole && $targetTenantUser?->tenant_role_id === $adminRole->id;

            if ($targetIsAdmin && $adminCount <= 1) {
                session()->flash('error', __('pages.users.cannot_remove_last_admin'));
                $this->showRemoveDialog = false;
                return;
            }

            $tenant->users()->detach($targetUser->id);

            if ($targetUser->default_tenant_id === $tenant->id) {
                $remainingTenant = $targetUser->tenants()->first();
                $targetUser->update(['default_tenant_id' => $remainingTenant?->id]);
            }

            $this->showRemoveDialog = false;
            $this->userToRemoveId = null;
            $this->userToRemoveName = '';
            session()->flash('success', __('pages.users.user_removed'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.users.remove_failed') . ': ' . $e->getMessage());
        } finally {
            $this->removeLoading = false;
        }
    }

    public function closeRemoveDialog(): void
    {
        $this->showRemoveDialog = false;
        $this->userToRemoveId = null;
        $this->userToRemoveName = '';
    }

    // Invitation actions
    public function resendInvitation(int $invitationId): void
    {
        if (!$this->canInviteUsers()) {
            session()->flash('error', __('pages.users.permissions.resend_invitations'));
            return;
        }

        $this->resendingId = $invitationId;

        try {
            $invitation = UserInvitation::findOrFail($invitationId);
            $service = app(UserInvitationService::class);
            $service->resend($invitation, auth()->user());
            session()->flash('success', __('pages.users.invitation_resent'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.users.resend_failed') . ': ' . $e->getMessage());
        } finally {
            $this->resendingId = null;
        }
    }

    public function cancelInvitation(int $invitationId): void
    {
        if (!$this->canInviteUsers()) {
            session()->flash('error', __('pages.users.permissions.cancel_invitations'));
            return;
        }

        $this->cancellingId = $invitationId;

        try {
            $invitation = UserInvitation::findOrFail($invitationId);
            $service = app(UserInvitationService::class);
            $service->cancel($invitation);
            session()->flash('success', __('pages.users.invitation_cancelled'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.users.cancel_failed') . ': ' . $e->getMessage());
        } finally {
            $this->cancellingId = null;
        }
    }

    // ─── Super Admin Methods ─────────────────────────────────────────────

    // Create User
    public function createUser(): void
    {
        if (!$this->isSuperAdmin) return;

        $this->validate([
            'newUserName' => 'required|string|max:255',
            'newUserEmail' => 'required|email|unique:users,email',
            'newUserPassword' => 'required|string|min:8',
        ]);

        DB::transaction(function () {
            $user = User::create([
                'name' => $this->newUserName,
                'email' => $this->newUserEmail,
                'password' => Hash::make($this->newUserPassword),
            ]);

            if ($this->newUserTenantId && $this->newUserRoleId) {
                $role = TenantRole::where('id', $this->newUserRoleId)
                    ->where('tenant_id', $this->newUserTenantId)
                    ->first();

                if ($role) {
                    $user->tenants()->attach($this->newUserTenantId, [
                        'role' => $role->is_system ? $role->name : 'viewer',
                        'tenant_role_id' => $role->id,
                        'joined_at' => now(),
                    ]);
                    $user->update(['default_tenant_id' => $this->newUserTenantId]);
                }
            }
        });

        $this->resetCreateForm();
        $this->showCreateModal = false;
        session()->flash('success', __('pages.admin_users.user_created'));
    }

    public function resetCreateForm(): void
    {
        $this->newUserName = '';
        $this->newUserEmail = '';
        $this->newUserPassword = '';
        $this->newUserTenantId = null;
        $this->newUserRoleId = null;
    }

    // Edit User
    public function editUser(int $userId): void
    {
        if (!$this->isSuperAdmin) return;

        $user = User::with('tenants')->find($userId);
        if ($user) {
            $this->editUserId = $user->id;
            $this->editUserName = $user->name;
            $this->editUserEmail = $user->email;
            $this->editUserPassword = '';
            $this->editUserTenantRoles = $user->tenants->mapWithKeys(function ($tenant) {
                $roleId = $tenant->pivot->tenant_role_id;

                if (!$roleId && $tenant->pivot->role) {
                    $roleId = TenantRole::where('tenant_id', $tenant->id)
                        ->where('name', $tenant->pivot->role)
                        ->value('id');
                }

                return [$tenant->id => $roleId];
            })->toArray();
            $this->showEditModal = true;
        }
    }

    public function updateUser(): void
    {
        if (!$this->isSuperAdmin) return;

        $rules = [
            'editUserName' => 'required|string|max:255',
            'editUserEmail' => 'required|email|unique:users,email,' . $this->editUserId,
        ];

        if ($this->editUserPassword) {
            $rules['editUserPassword'] = 'string|min:8';
        }

        $this->validate($rules);

        $user = User::find($this->editUserId);
        if ($user) {
            $data = [
                'name' => $this->editUserName,
                'email' => $this->editUserEmail,
            ];
            if ($this->editUserPassword) {
                $data['password'] = Hash::make($this->editUserPassword);
            }
            $user->update($data);

            foreach ($this->editUserTenantRoles as $tenantId => $roleId) {
                $role = TenantRole::where('id', $roleId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($role) {
                    $user->tenants()->updateExistingPivot($tenantId, [
                        'role' => $role->is_system ? $role->name : 'viewer',
                        'tenant_role_id' => $role->id,
                    ]);
                }
            }
        }

        $this->showEditModal = false;
        session()->flash('success', __('pages.admin_users.user_updated'));
    }

    // Remove tenant inline from Edit User modal
    public function removeTenantFromEditUser(int $tenantId): void
    {
        if (!$this->isSuperAdmin || !$this->editUserId) return;

        $user = User::find($this->editUserId);
        if ($user) {
            $user->tenants()->detach($tenantId);

            if ($user->default_tenant_id === $tenantId) {
                $remainingTenant = $user->tenants()->first();
                $user->update(['default_tenant_id' => $remainingTenant?->id]);
            }

            unset($this->editUserTenantRoles[$tenantId]);
        }
    }

    // Assign Tenant
    public function showAssignTenant(int $userId): void
    {
        if (!$this->isSuperAdmin) return;

        $this->assignUserId = $userId;
        $this->assignTenantId = null;
        $this->assignRoleId = null;
        $this->showAssignModal = true;
    }

    public function assignTenant(): void
    {
        if (!$this->isSuperAdmin) return;

        $this->validate([
            'assignTenantId' => 'required|exists:tenants,id',
            'assignRoleId' => 'required|exists:tenant_roles,id',
        ]);

        $role = TenantRole::where('id', $this->assignRoleId)
            ->where('tenant_id', $this->assignTenantId)
            ->first();

        if (!$role) {
            session()->flash('error', __('pages.admin_users.invalid_role_for_organization'));
            return;
        }

        $user = User::find($this->assignUserId);
        if ($user) {
            if ($user->tenants()->where('tenants.id', $this->assignTenantId)->exists()) {
                $user->tenants()->updateExistingPivot($this->assignTenantId, [
                    'role' => $role->is_system ? $role->name : 'viewer',
                    'tenant_role_id' => $role->id,
                ]);
            } else {
                $user->tenants()->attach($this->assignTenantId, [
                    'role' => $role->is_system ? $role->name : 'viewer',
                    'tenant_role_id' => $role->id,
                    'joined_at' => now(),
                ]);
            }

            // Set default tenant if user doesn't have one
            if (!$user->default_tenant_id) {
                $user->update(['default_tenant_id' => $this->assignTenantId]);
            }

            // If edit modal is open for this user, sync its tenant roles
            if ($this->showEditModal && $this->editUserId === $user->id) {
                $this->editUserTenantRoles[$this->assignTenantId] = $role->id;
            }
        }

        $this->showAssignModal = false;
        $this->resetAssignForm();
        session()->flash('success', __('pages.admin_users.tenant_assigned'));
    }

    private function resetAssignForm(): void
    {
        $this->assignUserId = null;
        $this->assignTenantId = null;
        $this->assignRoleId = null;
    }

    // Delete User
    public function confirmDeleteUser(int $userId): void
    {
        if (!$this->isSuperAdmin) return;

        $user = User::find($userId);
        if ($user) {
            $this->deleteUserId = $user->id;
            $this->deleteUserName = $user->name;
            $this->showDeleteDialog = true;
        }
    }

    public function deleteUser(): void
    {
        if (!$this->isSuperAdmin) return;

        $user = User::find($this->deleteUserId);
        if ($user) {
            if ($user->isSuperAdmin()) {
                session()->flash('error', __('pages.admin_users.cannot_delete_super_admin'));
                $this->showDeleteDialog = false;
                return;
            }

            $user->tenants()->detach();
            $user->delete();
            session()->flash('success', __('pages.admin_users.user_deleted'));
        }

        $this->showDeleteDialog = false;
        $this->deleteUserId = null;
        $this->deleteUserName = '';
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    public function getInitials(string $name): string
    {
        return strtoupper(
            implode('', array_map(fn($w) => $w[0] ?? '', array_slice(explode(' ', $name), 0, 2)))
        );
    }

    /**
     * Get a tenant role by ID with in-memory caching for the current request.
     */
    public function getTenantRoleById(?int $roleId): ?TenantRole
    {
        if (!$roleId) return null;

        if (!isset($this->roleCache[$roleId])) {
            $this->roleCache[$roleId] = TenantRole::find($roleId);
        }

        return $this->roleCache[$roleId];
    }

    /**
     * Get the display name for a tenant role ID.
     */
    public function getRoleDisplayName(?int $roleId): string
    {
        return $this->getTenantRoleById($roleId)?->display_name ?? __('pages.roles.no_role_assigned');
    }

    public function render()
    {
        $title = $this->isSuperAdmin ? __('pages.admin_users.title') : __('pages.users.title');

        return view('livewire.pages.users', [
            'usersPaginated' => $this->users,
            'invitationsList' => $this->invitations,
            'tenants' => $this->isSuperAdmin ? $this->tenants : collect(),
            'roles' => $this->roles,
            'roleOptions' => $this->roleOptions,
        ])->layout('layouts.app', ['title' => $title]);
    }
}
