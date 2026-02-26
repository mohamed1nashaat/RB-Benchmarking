<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    protected RolePermissionService $roleService;

    public function __construct(RolePermissionService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * List all roles for a tenant.
     */
    public function index(Tenant $tenant): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $roles = $this->roleService->getRolesForTenant($tenant);

        return response()->json([
            'data' => $roles->map(function ($role) {
                return $this->formatRole($role);
            }),
        ]);
    }

    /**
     * Get all available permissions grouped.
     */
    public function permissions(Tenant $tenant): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json([
            'data' => $this->roleService->getGroupedPermissions(),
        ]);
    }

    /**
     * Create a new custom role.
     */
    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isAdminForTenant($tenant)) {
            return response()->json(['message' => 'Access denied. Admin role required.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        // Generate slug from display_name if name looks like display_name
        $name = Str::slug($validated['name']);

        if (!$this->roleService->isRoleNameAvailable($tenant, $name)) {
            return response()->json([
                'message' => 'A role with this name already exists.',
                'errors' => ['name' => ['A role with this name already exists.']],
            ], 422);
        }

        $validated['name'] = $name;

        $role = $this->roleService->createRole($tenant, $validated);

        Log::info('Custom role created', [
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'role_name' => $role->name,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $this->formatRole($role),
        ], 201);
    }

    /**
     * Get a specific role.
     */
    public function show(Tenant $tenant, TenantRole $role): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($role->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json([
            'data' => $this->formatRole($this->roleService->getRole($role)),
        ]);
    }

    /**
     * Update a role.
     */
    public function update(Request $request, Tenant $tenant, TenantRole $role): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isAdminForTenant($tenant)) {
            return response()->json(['message' => 'Access denied. Admin role required.'], 403);
        }

        if ($role->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $rules = [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ];

        // Only validate name/display_name for custom roles
        if (!$role->is_system) {
            $rules['name'] = 'sometimes|string|max:50';
            $rules['display_name'] = 'sometimes|string|max:100';
            $rules['description'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // Check name uniqueness if changing
        if (!$role->is_system && isset($validated['name'])) {
            $name = Str::slug($validated['name']);
            if (!$this->roleService->isRoleNameAvailable($tenant, $name, $role->id)) {
                return response()->json([
                    'message' => 'A role with this name already exists.',
                    'errors' => ['name' => ['A role with this name already exists.']],
                ], 422);
            }
            $validated['name'] = $name;
        }

        $role = $this->roleService->updateRole($role, $validated);

        Log::info('Role updated', [
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'role_name' => $role->name,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $this->formatRole($role),
        ]);
    }

    /**
     * Get users assigned to a specific role.
     */
    public function users(Tenant $tenant, TenantRole $role): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($role->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $users = $tenant->users()
            ->wherePivot('tenant_role_id', $role->id)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            });

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * Delete a custom role.
     */
    public function destroy(Tenant $tenant, TenantRole $role): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isAdminForTenant($tenant)) {
            return response()->json(['message' => 'Access denied. Admin role required.'], 403);
        }

        if ($role->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 422);
        }

        try {
            $roleName = $role->name;
            $roleId = $role->id;
            $usersCount = $this->roleService->getUsersCount($role);

            $this->roleService->deleteRole($role);

            Log::info('Custom role deleted', [
                'tenant_id' => $tenant->id,
                'role_id' => $roleId,
                'role_name' => $roleName,
                'users_reassigned' => $usersCount,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Role deleted successfully. ' . ($usersCount > 0 ? "{$usersCount} user(s) reassigned to Viewer role." : ''),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Format role for API response.
     */
    protected function formatRole(TenantRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions' => $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'group' => $permission->group,
                ];
            }),
            'users_count' => $this->roleService->getUsersCount($role),
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
