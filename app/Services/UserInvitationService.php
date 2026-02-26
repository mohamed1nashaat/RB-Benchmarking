<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\TenantInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserInvitationService
{
    /**
     * Create and send an invitation.
     */
    public function invite(Tenant $tenant, string $email, string $role, User $inviter): UserInvitation
    {
        // Check if user already exists in this tenant
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->hasAccessToTenant($tenant)) {
            throw new \InvalidArgumentException('User is already a member of this tenant.');
        }

        // Cancel any existing pending invitations for this email in this tenant
        UserInvitation::forTenant($tenant->id)
            ->where('email', $email)
            ->pending()
            ->update(['accepted_at' => now()]); // Mark as cancelled by setting accepted

        // Create new invitation
        $invitation = UserInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $role,
            'token' => UserInvitation::generateToken(),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addHours(48),
        ]);

        // Send notification email
        $invitation->notify(new TenantInvitationNotification($invitation));

        return $invitation;
    }

    /**
     * Verify an invitation token.
     */
    public function verify(string $token): ?array
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (!$invitation) {
            return null;
        }

        $existingUser = User::where('email', $invitation->email)->first();

        return [
            'valid' => $invitation->isValid(),
            'expired' => $invitation->isExpired(),
            'accepted' => $invitation->isAccepted(),
            'email' => $invitation->email,
            'role' => $invitation->role,
            'tenant' => [
                'id' => $invitation->tenant->id,
                'name' => $invitation->tenant->name,
            ],
            'inviter' => [
                'name' => $invitation->inviter->name,
            ],
            'existing_user' => $existingUser !== null,
            'expires_at' => $invitation->expires_at->toISOString(),
        ];
    }

    /**
     * Accept an invitation.
     */
    public function accept(string $token, ?string $name = null, ?string $password = null): array
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (!$invitation) {
            throw new \InvalidArgumentException('Invalid invitation token.');
        }

        if (!$invitation->isValid()) {
            if ($invitation->isExpired()) {
                throw new \InvalidArgumentException('This invitation has expired.');
            }
            if ($invitation->isAccepted()) {
                throw new \InvalidArgumentException('This invitation has already been used.');
            }
        }

        return DB::transaction(function () use ($invitation, $name, $password) {
            $existingUser = User::where('email', $invitation->email)->first();

            if ($existingUser) {
                // Existing user - just add to tenant
                $user = $existingUser;
            } else {
                // New user - create account
                if (!$name || !$password) {
                    throw new \InvalidArgumentException('Name and password are required for new users.');
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $invitation->email,
                    'password' => Hash::make($password),
                    'default_tenant_id' => $invitation->tenant_id,
                    'email_verified_at' => now(), // Auto-verify since invitation was sent to their email
                ]);
            }

            // Add user to tenant if not already a member
            if (!$user->hasAccessToTenant($invitation->tenant)) {
                $user->tenants()->attach($invitation->tenant_id, [
                    'role' => $invitation->role,
                    'invited_at' => $invitation->created_at,
                    'joined_at' => now(),
                    'invited_by' => $invitation->invited_by,
                ]);
            }

            // Mark invitation as accepted
            $invitation->markAsAccepted();

            return [
                'user' => $user,
                'tenant' => $invitation->tenant,
                'is_new_user' => !$existingUser,
            ];
        });
    }

    /**
     * Cancel/delete a pending invitation.
     */
    public function cancel(UserInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw new \InvalidArgumentException('Cannot cancel an accepted invitation.');
        }

        $invitation->delete();
    }

    /**
     * Resend an invitation (creates new token and extends expiry).
     */
    public function resend(UserInvitation $invitation, User $inviter): UserInvitation
    {
        if ($invitation->isAccepted()) {
            throw new \InvalidArgumentException('Cannot resend an accepted invitation.');
        }

        // Update token and expiry
        $invitation->update([
            'token' => UserInvitation::generateToken(),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addHours(48),
        ]);

        // Send notification email
        $invitation->notify(new TenantInvitationNotification($invitation));

        return $invitation->fresh();
    }

    /**
     * Get pending invitations for a tenant.
     */
    public function getPendingInvitations(Tenant $tenant)
    {
        return UserInvitation::forTenant($tenant->id)
            ->pending()
            ->with('inviter:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up expired invitations (can be run via scheduler).
     */
    public function cleanupExpired(): int
    {
        return UserInvitation::where('expires_at', '<', now()->subDays(7))
            ->whereNull('accepted_at')
            ->delete();
    }
}
