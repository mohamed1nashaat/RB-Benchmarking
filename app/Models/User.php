<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'default_tenant_id',
        'avatar',
        'last_login_at',
    ];

    protected $appends = ['avatar_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function defaultTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'default_tenant_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role', 'tenant_role_id', 'invited_at', 'joined_at')
            ->withTimestamps();
    }

    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    public function reportExports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function getRoleForTenant(Tenant $tenant): ?string
    {
        $tenantUser = $this->tenants()->where('tenant_id', $tenant->id)->first();
        return $tenantUser?->pivot->role;
    }

    public function hasAccessToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function isAdminForTenant(Tenant $tenant): bool
    {
        return $this->getRoleForTenant($tenant) === 'admin';
    }

    /**
     * Get the TenantUser pivot record for a tenant.
     */
    public function getTenantUser(Tenant $tenant): ?TenantUser
    {
        return TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $this->id)
            ->with('tenantRole.permissions')
            ->first();
    }

    /**
     * Get the TenantRole for a specific tenant.
     */
    public function getTenantRole(Tenant $tenant): ?TenantRole
    {
        $tenantUser = $this->getTenantUser($tenant);
        return $tenantUser?->tenantRole;
    }

    /**
     * Check if user has a specific permission for a tenant.
     */
    public function hasPermissionForTenant(Tenant $tenant, string $permission): bool
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenantUser = $this->getTenantUser($tenant);

        if (!$tenantUser) {
            return false;
        }

        // If no tenant role is assigned, fall back to legacy role check
        if (!$tenantUser->tenantRole) {
            return $tenantUser->role === 'admin';
        }

        return $tenantUser->tenantRole->hasPermission($permission);
    }

    /**
     * Check if user has any of the given permissions for a tenant.
     */
    public function hasAnyPermissionForTenant(Tenant $tenant, array $permissions): bool
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenantUser = $this->getTenantUser($tenant);

        if (!$tenantUser) {
            return false;
        }

        // If no tenant role is assigned, fall back to legacy role check
        if (!$tenantUser->tenantRole) {
            return $tenantUser->role === 'admin';
        }

        return $tenantUser->tenantRole->hasAnyPermission($permissions);
    }

    /**
     * Get all permissions for a tenant.
     */
    public function getPermissionsForTenant(Tenant $tenant): array
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        $tenantUser = $this->getTenantUser($tenant);

        if (!$tenantUser || !$tenantUser->tenantRole) {
            return [];
        }

        return $tenantUser->tenantRole->getPermissionNames();
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->id === 1 || $this->email === 'technical@redbananas.com';
    }

    /**
     * Get invitations sent by this user.
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'invited_by');
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return asset('storage/avatars/' . $this->avatar);
    }
}
