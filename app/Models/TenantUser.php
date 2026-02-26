<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    protected $table = 'tenant_users';

    public $incrementing = true;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'tenant_role_id',
        'invited_at',
        'joined_at',
        'last_activity_at',
        'invited_by',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant role.
     */
    public function tenantRole(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class);
    }

    /**
     * Get the user who invited this user.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the user has a specific permission through their role.
     */
    public function hasPermission(string $permissionName): bool
    {
        if (!$this->tenantRole) {
            // Fallback to legacy role check
            return $this->role === 'admin';
        }

        return $this->tenantRole->hasPermission($permissionName);
    }

    /**
     * Get all permission names for this user's role.
     */
    public function getPermissionNames(): array
    {
        if (!$this->tenantRole) {
            return [];
        }

        return $this->tenantRole->getPermissionNames();
    }

    /**
     * Check if user has admin role (system admin role).
     */
    public function isAdmin(): bool
    {
        if ($this->tenantRole) {
            return $this->tenantRole->isAdmin();
        }

        // Fallback to legacy role
        return $this->role === 'admin';
    }
}
