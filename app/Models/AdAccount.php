<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AdAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'integration_id',
        'external_account_id',
        'account_name',
        'currency',
        'status',
        'industry',
        'country',
        'category',
        'account_config',
        'data_verification_status',
        'verification_notes',
        'verified_at',
        'verified_by',
        'last_metrics_sync_at',
    ];

    protected $casts = [
        'account_config' => 'array',
        'last_metrics_sync_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // Super admin detection
                $isSuperAdmin = (app()->bound('is_super_admin') && app('is_super_admin'))
                               || $user->id === 1
                               || $user->email === 'technical@redbananas.com';

                // Check if user has admin role in any tenant
                $hasAdminRole = $user->tenants()
                    ->wherePivot('role', 'admin')
                    ->exists();

                // Skip tenant filtering for super admins OR users with admin role
                if ($isSuperAdmin || $hasAdminRole) {
                    \Log::info('AdAccount global scope: Admin detected - skipping tenant filter', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'is_super_admin' => $isSuperAdmin,
                        'has_admin_role' => $hasAdminRole,
                    ]);
                    return; // Skip tenant filtering entirely
                }

                // For regular users (viewers): filter by current tenant
                $tenantId = (app()->bound('current_tenant_id') ? app('current_tenant_id') : null)
                           ?? session('current_tenant_id');

                \Log::info('AdAccount global scope: Viewer tenant filtering', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                ]);

                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function adMetrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->whereHas('integration', function ($q) use ($platform) {
            $q->where('platform', $platform);
        });
    }
    
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }
    

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPlatform(): string
    {
        return $this->integration->platform;
    }
}
