<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptRun extends Model
{
    protected $fillable = ['tenant_id', 'ai_tracked_prompt_id', 'engine', 'model', 'run_on', 'status', 'answer', 'brand_mentioned', 'brand_rank', 'brand_cited', 'brands', 'sources', 'sentiment', 'error'];

    protected $casts = ['brand_mentioned' => 'boolean', 'brand_cited' => 'boolean', 'brand_rank' => 'integer', 'brands' => 'array', 'sources' => 'array'];

    public function prompt()
    {
        return $this->belongsTo(AiTrackedPrompt::class, 'ai_tracked_prompt_id');
    }
}
