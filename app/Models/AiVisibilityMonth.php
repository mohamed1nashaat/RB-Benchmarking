<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVisibilityMonth extends Model
{
    protected $fillable = [
        'tenant_id', 'month', 'website',
        'ai_impressions', 'ai_impressions_per_day', 'total_impressions', 'ai_impression_share',
        'gsc_clicks', 'gsc_ctr',
        'ai_sessions', 'ai_key_events', 'ai_revenue', 'ai_key_event_rate',
        'ai_revenue_per_session', 'ai_session_share',
        'total_sessions', 'total_key_events', 'total_revenue',
    ];

    protected $casts = [
        'ai_impressions' => 'integer',
        'ai_impressions_per_day' => 'integer',
        'total_impressions' => 'integer',
        'ai_impression_share' => 'float',
        'gsc_clicks' => 'integer',
        'gsc_ctr' => 'float',
        'ai_sessions' => 'integer',
        'ai_key_events' => 'integer',
        'ai_revenue' => 'float',
        'ai_key_event_rate' => 'float',
        'ai_revenue_per_session' => 'float',
        'ai_session_share' => 'float',
        'total_sessions' => 'integer',
        'total_key_events' => 'integer',
        'total_revenue' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
