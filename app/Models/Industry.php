<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Industry extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subIndustries(): HasMany
    {
        return $this->hasMany(SubIndustry::class);
    }

    public function campaignCategories(): HasMany
    {
        return $this->hasMany(CampaignCategory::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class, 'industry', 'name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }
}
