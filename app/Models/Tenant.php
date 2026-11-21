<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use HasFactory, SoftDeletes, Billable;

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            app(\App\Services\RolePermissionService::class)
                ->createSystemRolesForTenant($tenant);
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'status',
        'settings',
        'logo',
        'logo_path',
        'logo_url',
        'description',
        'contact_email',
        'contact_phone',
        'contact_person',
        'address',
        'country',
        'website',
        'industry',
        'vertical',
        'billing_email',
        'contract_start_date',
        'contract_end_date',
        'subscription_tier',
        'monthly_budget',
        'notes',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'brand_color',
        'report_footer_text',
    ];

    protected $casts = [
        'settings'            => 'array',
        'contract_start_date' => 'date',
        'contract_end_date'   => 'date',
        'monthly_budget'      => 'decimal:2',
        'trial_ends_at'       => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->using(TenantUser::class)
            ->withPivot('id', 'role', 'tenant_role_id', 'invited_at', 'joined_at')
            ->withTimestamps();
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function adMetrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    /**
     * Determine if this tenant is an e-commerce business.
     */
    public function isEcommerce(): bool
    {
        return in_array($this->industry, [
            'e_commerce_retail',
            'fmcg_cpg',
            'fashion_luxury',
            'hospitality_food_services',
            'travel_destinations',
            'automotive',
        ], true);
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

    /**
     * Get the roles for this tenant.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(TenantRole::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if tenant has a logo
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo);
    }

    /**
     * Check if contract is currently active
     */
    public function isContractActive(): bool
    {
        if (!$this->contract_start_date || !$this->contract_end_date) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->contract_start_date)
            && $now->lessThanOrEqualTo($this->contract_end_date);
    }

    /**
     * Get days until contract expires
     */
    public function daysUntilContractExpires(): ?int
    {
        if (!$this->contract_end_date) {
            return null;
        }

        return now()->diffInDays($this->contract_end_date, false);
    }

    /**
     * Get total spend across all ad accounts for current month
     */
    public function getMonthlySpend(): float
    {
        return $this->adAccounts()
            ->join('ad_metrics', 'ad_accounts.id', '=', 'ad_metrics.ad_account_id')
            ->whereYear('ad_metrics.date', now()->year)
            ->whereMonth('ad_metrics.date', now()->month)
            ->sum('ad_metrics.spend');
    }

    /**
     * Get total number of active ad accounts
     */
    public function getActiveAdAccountsCount(): int
    {
        return $this->adAccounts()->where('status', 'active')->count();
    }

    /**
     * Get logo URL or default
     */
    public function getLogoUrl(): ?string
    {
        // Check for new logo_path field first
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }

        // Fall back to old logo field for backwards compatibility
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }
}
