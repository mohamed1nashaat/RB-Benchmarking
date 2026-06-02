<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoTechnicalIssue extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'issue_type',
        'severity',
        'page_url',
        'description_en',
        'description_ar',
        'current_status',
        'recommended_fix',
        'assigned_to',
        'date_found',
        'date_fixed',
        'notes',
    ];

    protected $casts = [
        'date_found' => 'date',
        'date_fixed' => 'date',
    ];

    protected $appends = ['readable_type'];

    public function getReadableTypeAttribute(): string
    {
        $type = $this->issue_type ?? '';

        if (str_starts_with($type, 'lighthouse:')) {
            return 'PageSpeed: ' . substr($type, strlen('lighthouse:'));
        }

        if (str_starts_with($type, 'sc:')) {
            return 'Search Console: ' . substr($type, strlen('sc:'));
        }

        return $type;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
