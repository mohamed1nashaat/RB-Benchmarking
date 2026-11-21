<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\Permission;
use App\Services\RolePermissionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class SuperAdminUsers extends Component
{
    use WithPagination;

    // Tab
    public string $activeTab = 'users';

    // Filters
    public string $search = '';
    public ?int $tenantFilter = null;
    public string $roleFilter = '';
    public int $perPage = 10;
    public string $sortBy = 'created_at';
    public string $sortOrder = 'desc';

    // Create User Modal
    public bool $showCreateModal = false;
    public string $newUserName = '';
    public string $newUserEmail = '';
    public string $newUserPassword = '';
    public ?int $newUserTenantId = null;
    public string $newUserRole = 'viewer';

    // Edit User Modal
    public bool $showEditModal = false;
    public ?int $editUserId = null;
    public string $editUserName = '';
    public string $editUserEmail = '';
    public string $editUserPassword = '';

    // Assign Tenant Modal
    public bool $showAssignModal = false;
    public ?int $assignUserId = null;
    public ?int $assignTenantId = null;
    public string $assignRole = 'viewer';

    // Delete User
    public bool $showDeleteDialog = false;
    public ?int $deleteUserId = null;
    public string $deleteUserName = '';

    // Roles tab
    public array $expandedGlobalRoles = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'tenantFilter' => ['except' => null, 'as' => 'tenant'],
        'activeTab' => ['except' => 'users', 'as' => 'tab'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTenantFilter()
    {
        $this->resetPage();
    }

    public function handleSort(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortOrder = $column === 'name' ? 'asc' : 'desc';
        }
    }

    public function handlePerPageChange()
    {
        $this->resetPage();
    }

    // Create User
    public function createUser()
    {
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

            if ($this->newUserTenantId) {
                $user->tenants()->attach($this->newUserTenantId, [
                    'role' => $this->newUserRole,
                    'joined_at' => now(),
                ]);
                $user->update(['default_tenant_id' => $this->newUserTenantId]);
            }
        });

        $this->resetCreateForm();
        $this->showCreateModal = false;
        session()->flash('message', __('pages.admin_users.user_created'));
    }

    public function resetCreateForm()
    {
        $this->newUserName = '';
        $this->newUserEmail = '';
        $this->newUserPassword = '';
        $this->newUserTenantId = null;
        $this->newUserRole = 'viewer';
    }

    // Edit User
    public function editUser(int $userId)
    {
        $user = User::find($userId);
        if ($user) {
            $this->editUserId = $user->id;
            $this->editUserName = $user->name;
            $this->editUserEmail = $user->email;
            $this->editUserPassword = '';
            $this->showEditModal = true;
        }
    }

    public function updateUser()
    {
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
        }

        $this->showEditModal = false;
        session()->flash('message', __('pages.admin_users.user_updated'));
    }

    // Assign Tenant
    public function showAssignTenant(int $userId)
    {
        $this->assignUserId = $userId;
        $this->assignTenantId = null;
        $this->assignRole = 'viewer';
        $this->showAssignModal = true;
    }

    public function assignTenant()
    {
        $this->validate([
            'assignTenantId' => 'required|exists:tenants,id',
            'assignRole' => 'required|in:admin,viewer',
        ]);

        $user = User::find($this->assignUserId);
        if ($user) {
            // Check if already assigned
            if ($user->tenants()->where('tenants.id', $this->assignTenantId)->exists()) {
                $user->tenants()->updateExistingPivot($this->assignTenantId, [
                    'role' => $this->assignRole,
                ]);
            } else {
                $user->tenants()->attach($this->assignTenantId, [
                    'role' => $this->assignRole,
                    'joined_at' => now(),
                ]);
            }
        }

        $this->showAssignModal = false;
        session()->flash('message', __('pages.admin_users.tenant_assigned'));
    }

    // Delete User
    public function confirmDeleteUser(int $userId)
    {
        $user = User::find($userId);
        if ($user) {
            $this->deleteUserId = $user->id;
            $this->deleteUserName = $user->name;
            $this->showDeleteDialog = true;
        }
    }

    public function deleteUser()
    {
        $user = User::find($this->deleteUserId);
        if ($user) {
            if (!$user->isSuperAdmin()) {
                $user->tenants()->detach();
                $user->delete();
            }
        }

        $this->showDeleteDialog = false;
        $this->deleteUserId = null;
        session()->flash('message', __('pages.admin_users.user_deleted'));
    }

    public function toggleGlobalRoleExpand(string $roleName)
    {
        $key = array_search($roleName, $this->expandedGlobalRoles);
        if ($key !== false) {
            unset($this->expandedGlobalRoles[$key]);
            $this->expandedGlobalRoles = array_values($this->expandedGlobalRoles);
        } else {
            $this->expandedGlobalRoles[] = $roleName;
        }
    }

    public function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }

    public function render()
    {
        // Get users with pagination
        $usersQuery = User::with(['tenants'])
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
                    $q2->where('tenant_users.role', $this->roleFilter);
                });
            })
            ->orderBy($this->sortBy, $this->sortOrder);

        $users = $usersQuery->paginate($this->perPage);

        // Get tenants for filters/modals
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        // Get global roles for roles tab
        $globalRoles = [];
        if ($this->activeTab === 'roles') {
            $roles = ['admin', 'viewer'];
            foreach ($roles as $roleName) {
                $usersCount = DB::table('tenant_users')->where('role', $roleName)->distinct('user_id')->count('user_id');
                $globalRoles[] = [
                    'name' => $roleName,
                    'display_name' => ucfirst($roleName),
                    'description' => $roleName === 'admin' ? __('Full access to manage accounts, integrations, and settings') : __('Read-only access to view reports and dashboards'),
                    'users_count' => $usersCount,
                    'permissions' => [],
                ];
            }
        }

        return view('livewire.admin.super-admin-users', [
            'users' => $users,
            'tenants' => $tenants,
            'globalRoles' => $globalRoles,
        ])->layout('layouts.app');
    }
}
