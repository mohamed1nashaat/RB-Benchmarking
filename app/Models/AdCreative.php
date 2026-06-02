<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCreative extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'ad_creatives';

    protected $fillable = [
        'tenant_id',
        'ad_account_id',
        'ad_campaign_id',
        'platform',
        'external_creative_id',
        'name',
        'thumbnail_url',
        'image_url',
        'video_url',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adCampaign()
    {
        return $this->belongsTo(AdCampaign::class);
    }
}
