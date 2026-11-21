<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorVisibility extends Model
{
    protected $table = 'competitor_visibility';

    protected $fillable = [
        'competitor_id', 'report_month',
        'ai_impressions', 'ai_sessions',
        'semrush_score', 'semrush_topics_count',
        'clarity_citations', 'notes',
    ];

    protected $casts = [
        'ai_impressions'      => 'integer',
        'ai_sessions'         => 'integer',
        'semrush_score'       => 'integer',
        'semrush_topics_count'=> 'integer',
        'clarity_citations'   => 'integer',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }
}
