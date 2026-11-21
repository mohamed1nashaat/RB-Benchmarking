<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTrackingProfile extends Model
{
    protected $fillable = ['tenant_id', 'domain', 'aliases', 'engines', 'active'];

    protected $casts = ['aliases' => 'array', 'engines' => 'array', 'active' => 'boolean'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
