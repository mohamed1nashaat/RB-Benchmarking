<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SubIndustry extends Model
{
    protected $fillable = [
        'industry_id',
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

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'sub_industry', 'name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    public function scopeForIndustry(Builder $query, int $industryId): Builder
    {
        return $query->where('industry_id', $industryId);
    }
}
