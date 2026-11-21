<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AdMetric extends Model
{
    use HasFactory, BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'date',
        'platform',
        'ad_account_id',
        'ad_campaign_id',
        'objective',
        'funnel_stage',
        'user_journey',
        'has_pixel_data',
        'spend',
        'impressions',
        'reach',
        'clicks',
        'link_clicks',
        'swipes',
        'video_views',
        'conversions',
        'revenue',
        'purchases',
        'leads',
        'instant_leads',
        'native_leads',
        'blended_leads',
        'sign_ups',
        'website_leads',
        'on_facebook_leads',
        'calls',
        'sessions',
        'atc',
        'app_installs',
        'page_views',
        'cost_per_result',
        'checksum',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'impressions' => 'integer',
        'reach' => 'integer',
        'clicks' => 'integer',
        'link_clicks' => 'integer',
        'swipes' => 'integer',
        'video_views' => 'integer',
        'conversions' => 'integer',
        'purchases' => 'integer',
        'leads' => 'integer',
        'instant_leads' => 'integer',
        'native_leads' => 'integer',
        'blended_leads' => 'integer',
        'sign_ups' => 'integer',
        'website_leads' => 'integer',
        'on_facebook_leads' => 'integer',
        'calls' => 'integer',
        'sessions' => 'integer',
        'atc' => 'integer',
        'app_installs' => 'integer',
        'has_pixel_data' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adCampaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    // Objective-specific KPI calculations
    public function getCpmAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return ($this->spend / ($this->impressions / 1000));
    }

    public function getCpcAttribute(): ?float
    {
        if ($this->clicks == 0) return null;
        return ($this->spend / $this->clicks);
    }

    public function getCtrAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return (($this->clicks / $this->impressions) * 100);
    }

    public function getCplAttribute(): ?float
    {
        if ($this->leads == 0) return null;
        return ($this->spend / $this->leads);
    }

    public function getCvr(): ?float
    {
        if ($this->clicks == 0) return null;
        return (($this->conversions / $this->clicks) * 100);
    }

    public function getRoasAttribute(): ?float
    {
        if ($this->spend == 0) return null;
        return ($this->revenue / $this->spend);
    }

    public function getCpaAttribute(): ?float
    {
        if ($this->purchases == 0) return null;
        return ($this->spend / $this->purchases);
    }

    public function getAovAttribute(): ?float
    {
        if ($this->purchases == 0) return null;
        return ($this->revenue / $this->purchases);
    }

    public function getCostPerCallAttribute(): ?float
    {
        if ($this->calls == 0) return null;
        return ($this->spend / $this->calls);
    }

    public function getVtrAttribute(): ?float
    {
        if ($this->impressions == 0) return null;
        return (($this->video_views / $this->impressions) * 100);
    }

    public function getFrequencyAttribute(): ?float
    {
        if ($this->reach == 0) return null;
        return ($this->impressions / $this->reach);
    }

    // Scopes for filtering
    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('ad_account_id', $accountId);
    }

    public function scopeForCampaign(Builder $query, int $campaignId): Builder
    {
        return $query->where('ad_campaign_id', $campaignId);
    }
    
    public function scopeForFunnelStage(Builder $query, string $funnelStage): Builder
    {
        return $query->where('funnel_stage', $funnelStage);
    }
    
    public function scopeForUserJourney(Builder $query, string $userJourney): Builder
    {
        return $query->where('user_journey', $userJourney);
    }
    
    public function scopeWithPixelData(Builder $query): Builder
    {
        return $query->where('has_pixel_data', true);
    }
    
    public function scopeWithoutPixelData(Builder $query): Builder
    {
        return $query->where('has_pixel_data', false);
    }
}
