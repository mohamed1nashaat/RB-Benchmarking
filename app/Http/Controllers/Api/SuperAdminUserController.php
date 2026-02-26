<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class SuperAdminUserController extends Controller
{
    /**
     * List all users across all tenants.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('tenants')
            ->with(['tenants' => function ($q) {
                $q->select('tenants.id', 'tenants.name')
                  ->withPivot('role');
            }]);

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by tenant
        if ($request->tenant_id) {
            $query->whereHas('tenants', function ($q) use ($request) {
                $q->where('tenant_id', $request->tenant_id);
            });
        }

        // Filter by role in any tenant
        if ($request->role) {
            $query->whereHas('tenants', function ($q) use ($request) {
                $q->wherePivot('role', $request->role);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['name', 'email', 'created_at', 'last_login_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } elseif ($sortBy === 'tenants_count') {
            $query->orderBy('tenants_count', $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        // Transform users
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'is_super_admin' => $user->isSuperAdmin(),
                'tenants_count' => $user->tenants_count,
                'tenants' => $user->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'role' => $tenant->pivot->role,
                    ];
                }),
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json($users);
    }

    /**
     * Get a single user with all tenant memberships.
     */
    public function show(User $user)
    {
        $user->load(['tenants' => function ($q) {
            $q->select('tenants.id', 'tenants.name', 'tenants.slug')
              ->withPivot('role', 'joined_at', 'invited_at');
        }]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'is_super_admin' => $user->isSuperAdmin(),
                'default_tenant_id' => $user->default_tenant_id,
                'tenants' => $user->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'role' => $tenant->pivot->role,
                        'joined_at' => $tenant->pivot->joined_at,
                        'invited_at' => $tenant->pivot->invited_at,
                    ];
                }),
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Create a new user directly (no invitation needed).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::defaults()],
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'nullable|in:admin,viewer',
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'default_tenant_id' => $validated['tenant_id'] ?? null,
                    'email_verified_at' => now(), // Auto-verify admin-created users
                ]);

                // If tenant specified, add user to tenant
                if (!empty($validated['tenant_id'])) {
                    $user->tenants()->attach($validated['tenant_id'], [
                        'role' => $validated['role'] ?? 'viewer',
                        'joined_at' => now(),
                    ]);
                }

                return $user;
            });

            Log::info('Super admin created user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'email' => $validated['email'],
            ]);

            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }

    /**
     * Update a user's basic information.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', Password::defaults()],
            'default_tenant_id' => 'nullable|exists:tenants,id',
        ]);

        try {
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            Log::info('Super admin updated user', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    /**
     * Delete a user entirely.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        // Prevent deleting super admins
        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Cannot delete a super admin user'], 403);
        }

        try {
            $email = $user->email;
            $user->delete();

            Log::info('Super admin deleted user', [
                'deleted_user_email' => $email,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

    /**
     * Add a user to a tenant.
     */
    public function addToTenant(Request $request, User $user)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'role' => 'required|in:admin,viewer',
        ]);

        $tenant = Tenant::find($validated['tenant_id']);

        if ($user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User is already a member of this tenant'], 422);
        }

        try {
            $user->tenants()->attach($validated['tenant_id'], [
                'role' => $validated['role'],
                'joined_at' => now(),
            ]);

            // If user has no default tenant, set this one
            if (!$user->default_tenant_id) {
                $user->update(['default_tenant_id' => $validated['tenant_id']]);
            }

            Log::info('Super admin added user to tenant', [
                'user_id' => $user->id,
                'tenant_id' => $validated['tenant_id'],
                'role' => $validated['role'],
                'added_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'User added to tenant successfully',
                'data' => [
                    'tenant_id' => $validated['tenant_id'],
                    'tenant_name' => $tenant->name,
                    'role' => $validated['role'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add user to tenant', [
                'user_id' => $user->id,
                'tenant_id' => $validated['tenant_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to add user to tenant'], 500);
        }
    }

    /**
     * Remove a user from a tenant.
     */
    public function removeFromTenant(User $user, Tenant $tenant)
    {
        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User is not a member of this tenant'], 404);
        }

        // Prevent removing yourself from a tenant
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot remove yourself from a tenant'], 403);
        }

        try {
            $user->tenants()->detach($tenant->id);

            // If this was the user's default tenant, set to their first remaining tenant
            if ($user->default_tenant_id === $tenant->id) {
                $remainingTenant = $user->tenants()->first();
                $user->update(['default_tenant_id' => $remainingTenant?->id]);
            }

            Log::info('Super admin removed user from tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'removed_by' => auth()->id(),
            ]);

            return response()->json(['message' => 'User removed from tenant successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to remove user from tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to remove user from tenant'], 500);
        }
    }

    /**
     * Update a user's role in a specific tenant.
     */
    public function updateTenantRole(Request $request, User $user, Tenant $tenant)
    {
        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User is not a member of this tenant'], 404);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,viewer',
        ]);

        try {
            $tenant->users()->updateExistingPivot($user->id, [
                'role' => $validated['role'],
            ]);

            Log::info('Super admin updated user role in tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'new_role' => $validated['role'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'User role updated successfully',
                'data' => [
                    'role' => $validated['role'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user role', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to update user role'], 500);
        }
    }

    /**
     * Get all tenants (for assigning users to tenants).
     */
    public function tenants()
    {
        $tenants = Tenant::select('id', 'name', 'slug', 'status')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $tenants]);
    }

    /**
     * Get all roles across all tenants.
     */
    public function roles(Request $request)
    {
        $query = \App\Models\TenantRole::query()
            ->with(['tenant:id,name', 'permissions:id,name,display_name'])
            ->withCount('tenantUsers as users_count');

        // Filter by tenant if specified
        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('tenant_id')
            ->orderBy('display_name')
            ->get();

        return response()->json(['data' => $roles]);
    }

    /**
     * Get users for a specific role.
     */
    public function roleUsers(\App\Models\TenantRole $role)
    {
        $users = $role->tenantUsers()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($tenantUser) {
                return [
                    'id' => $tenantUser->user->id,
                    'name' => $tenantUser->user->name,
                    'email' => $tenantUser->user->email,
                ];
            });

        return response()->json(['data' => $users]);
    }

    /**
     * Create a new role for a tenant.
     */
    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = \App\Models\TenantRole::create([
            'tenant_id' => $validated['tenant_id'],
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        $role->load(['tenant:id,name', 'permissions:id,name,display_name']);
        $role->loadCount('tenantUsers as users_count');

        return response()->json(['data' => $role], 201);
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, \App\Models\TenantRole $role)
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if (isset($validated['display_name'])) {
            $role->display_name = $validated['display_name'];
        }
        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }
        $role->save();

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        $role->load(['tenant:id,name', 'permissions:id,name,display_name']);
        $role->loadCount('tenantUsers as users_count');

        return response()->json(['data' => $role]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole(\App\Models\TenantRole $role)
    {
        if ($role->is_system) {
            return response()->json(['message' => 'Cannot delete system roles'], 403);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    /**
     * Get global roles aggregated across all tenants.
     * Returns Admin and Viewer roles with permissions and total user counts.
     */
    public function globalRoles()
    {
        // Get all system roles grouped by name (admin, viewer)
        $roleTypes = ['admin', 'viewer'];
        $globalRoles = [];

        foreach ($roleTypes as $roleName) {
            // Get first role of this type to get permissions (all same)
            $sampleRole = \App\Models\TenantRole::where('name', $roleName)
                ->where('is_system', true)
                ->with('permissions:id,name,display_name')
                ->first();

            if (!$sampleRole) {
                continue;
            }

            // Count total users across all tenants with this role type
            $totalUsers = DB::table('tenant_users')
                ->join('tenant_roles', 'tenant_users.tenant_role_id', '=', 'tenant_roles.id')
                ->where('tenant_roles.name', $roleName)
                ->where('tenant_roles.is_system', true)
                ->count();

            // Get list of tenants using this role
            $tenantsWithRole = \App\Models\TenantRole::where('name', $roleName)
                ->where('is_system', true)
                ->with('tenant:id,name')
                ->get()
                ->pluck('tenant')
                ->filter()
                ->values();

            $globalRoles[] = [
                'name' => $roleName,
                'display_name' => $sampleRole->display_name,
                'description' => $sampleRole->description,
                'is_system' => true,
                'permissions' => $sampleRole->permissions,
                'users_count' => $totalUsers,
                'tenants_count' => $tenantsWithRole->count(),
                'tenants' => $tenantsWithRole,
            ];
        }

        return response()->json(['data' => $globalRoles]);
    }

    /**
     * Get all users with a specific role type across all tenants.
     */
    public function globalRoleUsers(string $roleName)
    {
        if (!in_array($roleName, ['admin', 'viewer'])) {
            return response()->json(['message' => 'Invalid role name'], 400);
        }

        // Get all users with this role type across all tenants
        $users = DB::table('tenant_users')
            ->join('tenant_roles', 'tenant_users.tenant_role_id', '=', 'tenant_roles.id')
            ->join('users', 'tenant_users.user_id', '=', 'users.id')
            ->join('tenants', 'tenant_users.tenant_id', '=', 'tenants.id')
            ->where('tenant_roles.name', $roleName)
            ->where('tenant_roles.is_system', true)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.avatar_url',
                'tenants.id as tenant_id',
                'tenants.name as tenant_name'
            )
            ->orderBy('users.name')
            ->get();

        // Group by user, collecting their tenants
        $groupedUsers = $users->groupBy('id')->map(function ($userTenants) {
            $first = $userTenants->first();
            return [
                'id' => $first->id,
                'name' => $first->name,
                'email' => $first->email,
                'avatar_url' => $first->avatar_url,
                'tenants' => $userTenants->map(function ($ut) {
                    return [
                        'id' => $ut->tenant_id,
                        'name' => $ut->tenant_name,
                    ];
                })->values()->toArray(),
            ];
        })->values();

        return response()->json(['data' => $groupedUsers]);
    }

    /**
     * Update permissions for a global role type (updates all tenants).
     */
    public function updateGlobalRole(Request $request, string $roleName)
    {
        if (!in_array($roleName, ['admin', 'viewer'])) {
            return response()->json(['message' => 'Invalid role name'], 400);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::transaction(function () use ($roleName, $validated) {
                // Get all system roles of this type across all tenants
                $roles = \App\Models\TenantRole::where('name', $roleName)
                    ->where('is_system', true)
                    ->get();

                // Update permissions for each role
                foreach ($roles as $role) {
                    $role->permissions()->sync($validated['permissions']);
                }
            });

            Log::info('Super admin updated global role permissions', [
                'role_name' => $roleName,
                'updated_by' => auth()->id(),
                'permissions_count' => count($validated['permissions']),
            ]);

            return response()->json([
                'message' => 'Global role updated successfully',
                'data' => [
                    'name' => $roleName,
                    'permissions_count' => count($validated['permissions']),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update global role', [
                'role_name' => $roleName,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to update global role'], 500);
        }
    }

    /**
     * Get all available permissions grouped.
     */
    public function permissions(RolePermissionService $roleService)
    {
        return response()->json([
            'data' => $roleService->getGroupedPermissions(),
        ]);
    }
}
