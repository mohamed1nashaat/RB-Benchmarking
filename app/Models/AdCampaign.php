<?php

namespace App\Models;

use App\Events\CampaignCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AdCampaign extends Model
{
    public const OBJECTIVE_FUNNEL_MAP = [
        'awareness' => 'TOF',
        'leads'     => 'BOF',
        'sales'     => 'BOF',
        'calls'     => 'BOF',
    ];

    public const NAME_FUNNEL_KEYWORDS = [
        'BOF' => [
            'leads', 'lead gen', 'lead generation', 'leadgen', 'signups', 'signup',
            'sales', 'conversions', 'conversion', 'install', 'download',
            'action', 'cpa', 'bof', 'purchase', 'checkout', 'calls',
        ],
        'MOF' => [
            'traffic', 'visits', 'content views', 'product views',
            'landing page views', 'clicks', 'cpc', 'mof',
        ],
        'TOF' => [
            'awareness', 'reach', 'impressions', 'brand', 'branding',
            'tof', 'video views',
        ],
    ];

    public static function funnelStageForObjective(?string $objective): ?string
    {
        if ($objective === null) {
            return null;
        }
        return self::OBJECTIVE_FUNNEL_MAP[$objective] ?? null;
    }

    public static function funnelStageFromName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $nameLower = strtolower($name);
        $scores = [];

        foreach (self::NAME_FUNNEL_KEYWORDS as $stage => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $nameLower)) {
                    $score += 10;
                }
            }
            if ($score > 0) {
                $scores[$stage] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);
        return array_key_first($scores);
    }

    protected $fillable = [
        'tenant_id',
        'ad_account_id',
        'external_campaign_id',
        'name',
        'objective',
        'sub_industry',
        'category',
        'inherit_category_from_account',
        'channel_type',
        'linkedin_level',
        'google_level',
        'funnel_stage',
        'user_journey',
        'has_pixel_data',
        'target_segment',
        'age_group',
        'geo_targeting',
        'messaging_tone',
        'status',
        'google_sheet_id',
        'google_sheet_url',
        'sheet_mapping',
        'sheets_integration_enabled',
        'last_sheet_sync',
        'pixel_config',
        'conversion_tracking_enabled',
        'conversion_pixel_id',
    ];

    protected $casts = [
        'has_pixel_data' => 'boolean',
        'inherit_category_from_account' => 'boolean',
        'sheets_integration_enabled' => 'boolean',
        'conversion_tracking_enabled' => 'boolean',
        'sheet_mapping' => 'array',
        'pixel_config' => 'array',
        'last_sheet_sync' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // Robust super admin detection
                $isSuperAdmin = (app()->bound('is_super_admin') && app('is_super_admin'))
                               || $user->id === 1
                               || $user->email === 'technical@redbananas.com';

                if ($isSuperAdmin) {
                    // Super admins can optionally filter to specific tenant
                    $tenantId = session('current_tenant_id')
                               ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);
                    if ($tenantId) {
                        $builder->where('tenant_id', $tenantId);
                    }
                    // Otherwise show all data without filtering
                    return;
                }

                // Regular users see only their tenant's data
                $tenantId = session('current_tenant_id')
                           ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);
                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                }
            }
        });

        // Fire event when a new campaign is created
        static::created(function (AdCampaign $campaign) {
            // Load relationships needed for sheet creation
            $campaign->load('adAccount.integration');
            event(new CampaignCreated($campaign));
        });

        // Auto-derive funnel_stage: objective → name keywords → keep existing
        static::saving(function (AdCampaign $campaign) {
            if ($campaign->isDirty('objective') || $campaign->isDirty('name') || !$campaign->funnel_stage) {
                $campaign->funnel_stage =
                    self::funnelStageForObjective($campaign->objective)
                    ?? self::funnelStageFromName($campaign->name)
                    ?? $campaign->funnel_stage;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adMetrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('ad_account_id', $accountId);
    }
    
    public function scopeForFunnelStage(Builder $query, string $funnelStage): Builder
    {
        return $query->where('funnel_stage', $funnelStage);
    }
    
    public function scopeForUserJourney(Builder $query, string $userJourney): Builder
    {
        return $query->where('user_journey', $userJourney);
    }
    
    public function scopeForSubIndustry(Builder $query, string $subIndustry): Builder
    {
        return $query->where('sub_industry', $subIndustry);
    }
    
    public function scopeWithPixelData(Builder $query): Builder
    {
        return $query->where('has_pixel_data', true);
    }
    
    public function scopeWithoutPixelData(Builder $query): Builder
    {
        return $query->where('has_pixel_data', false);
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForChannelType(Builder $query, string $channelType): Builder
    {
        return $query->where('channel_type', $channelType);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the effective category for this campaign.
     * Returns the campaign's category if set and not inheriting from account,
     * otherwise returns the account's category.
     *
     * @return string|null
     */
    public function getEffectiveCategory(): ?string
    {
        if (!$this->inherit_category_from_account && $this->category) {
            return $this->category;
        }

        return $this->adAccount?->category;
    }

    /**
     * Get the effective industry for this campaign from the ad account.
     *
     * @return string|null
     */
    public function getEffectiveIndustry(): ?string
    {
        return $this->adAccount?->industry;
    }

    public function getPlatform(): string
    {
        return $this->adAccount->integration->platform;
    }

    public function getMetricsForDateRange(string $startDate, string $endDate)
    {
        return $this->adMetrics()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get aggregated metrics for a date range
     * Note: Values are in the account's currency (not converted)
     *
     * @param string $startDate
     * @param string $endDate
     * @param bool $convertToSAR Whether to convert spend/revenue to SAR
     * @return array
     */
    public function getAggregatedMetrics(string $startDate, string $endDate, bool $convertToSAR = false): array
    {
        $metrics = $this->adMetrics()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(reach) as total_reach,
                SUM(clicks) as total_clicks,
                SUM(video_views) as total_video_views,
                SUM(conversions) as total_conversions,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases,
                SUM(leads) as total_leads,
                SUM(calls) as total_calls,
                SUM(sessions) as total_sessions,
                SUM(atc) as total_atc
            ')
            ->first();

        if (!$metrics) {
            return [];
        }

        $result = $metrics->toArray();

        // Optionally convert to SAR
        if ($convertToSAR && $this->adAccount) {
            $currencyService = app(\App\Services\CurrencyConversionService::class);
            $currency = $this->adAccount->currency ?? 'USD';

            $result['total_spend'] = $currencyService->convertToSAR((float) $result['total_spend'], $currency);
            $result['total_revenue'] = $currencyService->convertToSAR((float) $result['total_revenue'], $currency);
            $result['currency'] = 'SAR';
        } else {
            $result['currency'] = $this->adAccount->currency ?? 'USD';
        }

        return $result;
    }
}
