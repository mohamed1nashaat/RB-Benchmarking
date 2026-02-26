<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Policies\TenantUserPolicy;
use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantUserController extends Controller
{
    protected UserInvitationService $invitationService;
    protected TenantUserPolicy $policy;

    public function __construct(UserInvitationService $invitationService, TenantUserPolicy $policy)
    {
        $this->invitationService = $invitationService;
        $this->policy = $policy;
    }

    /**
     * List all users in a tenant.
     */
    public function index(Request $request, Tenant $tenant)
    {
        $user = auth()->user();

        if (!$this->policy->viewAny($user, $tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $query = $tenant->users()->withPivot('role', 'invited_at', 'joined_at', 'last_activity_at', 'invited_by');

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->role) {
            $query->wherePivot('role', $request->role);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['name', 'email', 'created_at', 'last_login_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } elseif ($sortBy === 'role') {
            $query->orderByPivot('role', $sortOrder);
        } elseif ($sortBy === 'joined_at') {
            $query->orderByPivot('joined_at', $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        // Transform users to include tenant-specific data
        $users->getCollection()->transform(function ($user) use ($tenant) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $user->pivot->role,
                'joined_at' => $user->pivot->joined_at,
                'invited_at' => $user->pivot->invited_at,
                'last_activity_at' => $user->pivot->last_activity_at,
                'last_login_at' => $user->last_login_at,
                'is_current_user' => $user->id === auth()->id(),
            ];
        });

        return response()->json($users);
    }

    /**
     * Get a single user in a tenant.
     */
    public function show(Tenant $tenant, User $user)
    {
        $currentUser = auth()->user();

        if (!$this->policy->view($currentUser, $tenant, $user)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User not found in this tenant'], 404);
        }

        $tenantUser = $tenant->users()->where('user_id', $user->id)->first();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $tenantUser->pivot->role,
                'joined_at' => $tenantUser->pivot->joined_at,
                'invited_at' => $tenantUser->pivot->invited_at,
                'last_activity_at' => $tenantUser->pivot->last_activity_at,
                'last_login_at' => $user->last_login_at,
                'is_current_user' => $user->id === auth()->id(),
            ],
        ]);
    }

    /**
     * Update a user's role in a tenant.
     */
    public function updateRole(Request $request, Tenant $tenant, User $targetUser)
    {
        $currentUser = auth()->user();

        if (!$this->policy->updateRole($currentUser, $tenant, $targetUser)) {
            if ($currentUser->id === $targetUser->id) {
                return response()->json(['message' => 'You cannot change your own role'], 403);
            }
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$targetUser->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User not found in this tenant'], 404);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,viewer',
            'tenant_role_id' => 'nullable|integer|exists:tenant_roles,id',
        ]);

        // Check if this would leave the tenant without an admin
        if (!$this->policy->canSafelyChangeRole($tenant, $targetUser, $validated['role'])) {
            return response()->json([
                'message' => 'Cannot change role. This would leave the tenant without any admins.',
            ], 422);
        }

        $updateData = [
            'role' => $validated['role'],
        ];

        // If tenant_role_id is provided, validate it belongs to this tenant
        if (isset($validated['tenant_role_id'])) {
            $tenantRole = \App\Models\TenantRole::where('id', $validated['tenant_role_id'])
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($tenantRole) {
                $updateData['tenant_role_id'] = $tenantRole->id;
            }
        }

        $tenant->users()->updateExistingPivot($targetUser->id, $updateData);

        Log::info('User role updated', [
            'tenant_id' => $tenant->id,
            'target_user_id' => $targetUser->id,
            'new_role' => $validated['role'],
            'tenant_role_id' => $updateData['tenant_role_id'] ?? null,
            'updated_by' => $currentUser->id,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => [
                'user_id' => $targetUser->id,
                'role' => $validated['role'],
                'tenant_role_id' => $updateData['tenant_role_id'] ?? null,
            ],
        ]);
    }

    /**
     * Remove a user from a tenant.
     */
    public function destroy(Tenant $tenant, User $targetUser)
    {
        $currentUser = auth()->user();

        if (!$this->policy->remove($currentUser, $tenant, $targetUser)) {
            if ($currentUser->id === $targetUser->id) {
                return response()->json(['message' => 'You cannot remove yourself from a tenant'], 403);
            }
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$targetUser->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'User not found in this tenant'], 404);
        }

        // Check if this would leave the tenant without an admin
        if (!$this->policy->canSafelyRemove($tenant, $targetUser)) {
            return response()->json([
                'message' => 'Cannot remove user. This would leave the tenant without any admins.',
            ], 422);
        }

        // Detach user from tenant
        $tenant->users()->detach($targetUser->id);

        // If this was the user's default tenant, clear it
        if ($targetUser->default_tenant_id === $tenant->id) {
            // Set to their first remaining tenant or null
            $remainingTenant = $targetUser->tenants()->first();
            $targetUser->update([
                'default_tenant_id' => $remainingTenant?->id,
            ]);
        }

        Log::info('User removed from tenant', [
            'tenant_id' => $tenant->id,
            'removed_user_id' => $targetUser->id,
            'removed_by' => $currentUser->id,
        ]);

        return response()->json([
            'message' => 'User removed from tenant successfully',
        ]);
    }

    /**
     * Invite a new user to the tenant.
     */
    public function invite(Request $request, Tenant $tenant)
    {
        $currentUser = auth()->user();

        if (!$this->policy->invite($currentUser, $tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,viewer',
        ]);

        try {
            $invitation = $this->invitationService->invite(
                $tenant,
                $validated['email'],
                $validated['role'],
                $currentUser
            );

            Log::info('User invited to tenant', [
                'tenant_id' => $tenant->id,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invited_by' => $currentUser->id,
            ]);

            return response()->json([
                'message' => 'Invitation sent successfully',
                'data' => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'expires_at' => $invitation->expires_at->toISOString(),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to invite user', [
                'tenant_id' => $tenant->id,
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to send invitation'], 500);
        }
    }

    /**
     * List pending invitations for a tenant.
     */
    public function invitations(Tenant $tenant)
    {
        $currentUser = auth()->user();

        if (!$this->policy->viewAny($currentUser, $tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $invitations = $this->invitationService->getPendingInvitations($tenant);

        return response()->json([
            'data' => $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'invited_by' => $invitation->inviter?->name,
                    'expires_at' => $invitation->expires_at->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvitation(Tenant $tenant, UserInvitation $invitation)
    {
        $currentUser = auth()->user();

        if (!$this->policy->invite($currentUser, $tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($invitation->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        try {
            $this->invitationService->cancel($invitation);

            Log::info('Invitation cancelled', [
                'tenant_id' => $tenant->id,
                'invitation_id' => $invitation->id,
                'cancelled_by' => $currentUser->id,
            ]);

            return response()->json(['message' => 'Invitation cancelled successfully']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Resend a pending invitation.
     */
    public function resendInvitation(Tenant $tenant, UserInvitation $invitation)
    {
        $currentUser = auth()->user();

        if (!$this->policy->invite($currentUser, $tenant)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($invitation->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        try {
            $invitation = $this->invitationService->resend($invitation, $currentUser);

            Log::info('Invitation resent', [
                'tenant_id' => $tenant->id,
                'invitation_id' => $invitation->id,
                'resent_by' => $currentUser->id,
            ]);

            return response()->json([
                'message' => 'Invitation resent successfully',
                'data' => [
                    'id' => $invitation->id,
                    'expires_at' => $invitation->expires_at->toISOString(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
