<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'stripe_price_id', 'price_sar', 'price_usd',
        'interval', 'features', 'limits', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'  => 'array',
        'limits'    => 'array',
        'is_active' => 'boolean',
        'price_sar' => 'decimal:2',
        'price_usd' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function limitLabel(string $key): string
    {
        $val = $this->limits[$key] ?? null;
        if ($val === null) return '—';
        return $val === -1 ? 'Unlimited' : (string) $val;
    }
}
