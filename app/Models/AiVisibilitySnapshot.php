<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVisibilitySnapshot extends Model
{
    protected $fillable = [
        'tenant_id', 'snapshot_date', 'report_month',
        'ai_platforms', 'top_ai_pages', 'top_ai_countries',
        'ai_funnel', 'clarity_citations', 'clarity_data',
        'semrush_score', 'semrush_topics_count', 'semrush_prompts_count',
        'semrush_top_topics', 'report_tables', 'filename',
    ];

    protected $casts = [
        'ai_platforms' => 'array',
        'top_ai_pages' => 'array',
        'top_ai_countries' => 'array',
        'ai_funnel' => 'array',
        'clarity_data' => 'array',
        'semrush_top_topics' => 'array',
        'report_tables' => 'array',
        'clarity_citations' => 'integer',
        'semrush_score' => 'integer',
        'semrush_topics_count' => 'integer',
        'semrush_prompts_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
