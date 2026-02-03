<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CampaignCategory extends Model
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
    ];

    /**
     * Get the industry that owns this campaign category
     */
    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Scope to filter only active categories
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }
}
