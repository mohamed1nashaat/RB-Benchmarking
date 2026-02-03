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
        ];
    }

    public function defaultTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'default_tenant_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role', 'invited_at', 'joined_at')
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

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return asset('storage/avatars/' . $this->avatar);
    }
}
