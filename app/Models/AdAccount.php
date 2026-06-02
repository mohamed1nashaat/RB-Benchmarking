<?php

namespace App\Models;
use App\Models\Scopes\TenantScope;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccount extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;
    protected $fillable = [
        'tenant_id',
        'integration_id',
        'external_account_id',
        'account_name',
        'currency',
        'status',
        'industry',
        'industry_id',
        'country',
        'category',
        'sub_industry',
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
    

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPlatform(): string
    {
        return $this->integration->platform;
    }

    /**
     * Resolve the tenant_id to use when syncing an ad account.
     *
     * ALWAYS use this in sync code instead of hardcoding $integration->tenant_id.
     * Preserves any manually-assigned client on existing accounts; falls back to
     * the integration's tenant only when the account does not exist yet.
     *
     * Usage in updateOrCreate:
     *   'tenant_id' => AdAccount::syncTenantId($integration->id, $account['id'], $integration->tenant_id),
     */
    public static function syncTenantId(int $integrationId, string $externalAccountId, ?int $fallbackTenantId): ?int
    {
        $existing = static::withoutGlobalScope(TenantScope::class)
            ->where('integration_id', $integrationId)
            ->where('external_account_id', $externalAccountId)
            ->first(['id', 'tenant_id']);

        // Existing account — preserve whatever tenant is set (including intentional null).
        if ($existing !== null) {
            return $existing->tenant_id;
        }

        // New account — default to the integration's tenant.
        return $fallbackTenantId;
    }
}
