<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    protected $fillable = ['tenant_id', 'name', 'website', 'industry', 'notes', 'sort_order'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function visibility(): HasMany
    {
        return $this->hasMany(CompetitorVisibility::class)->orderBy('report_month');
    }

    public function latestVisibility(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CompetitorVisibility::class)->latestOfMany('report_month');
    }
}
