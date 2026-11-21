<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemrushReport extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_client' => 'boolean', 'metrics' => 'array', 'topics' => 'array', 'prompts' => 'array', 'source_files' => 'array'];
}
