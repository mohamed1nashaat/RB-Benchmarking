<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTrackedPrompt extends Model
{
    protected $fillable = ['tenant_id', 'prompt', 'prompt_hash', 'topic', 'volume', 'country', 'source', 'active'];

    protected $casts = ['active' => 'boolean', 'volume' => 'integer'];

    public static function hashOf(string $prompt): string
    {
        return sha1(mb_strtolower(trim(preg_replace('/\s+/u', ' ', $prompt))));
    }

    public function runs()
    {
        return $this->hasMany(AiPromptRun::class);
    }
}
