<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'ad_account_id',
        'name',
        'status',
        'period_start',
        'period_end',
        'platform',
        'industry',
        'executive_summary',
        'performance_data',
        'recommendations',
        'sections',
        'metadata',
        'created_by',
        'sent_at',
        'language',
        'schedule_frequency',
        'delivery_emails',
        'next_run_at',
        'last_auto_run_at',
        'layout',
        'client_name',
        'share_token',
    ];

    protected $casts = [
        'performance_data'  => 'array',
        'recommendations'   => 'array',
        'sections'          => 'array',
        'metadata'          => 'array',
        'delivery_emails'   => 'array',
        'layout'            => 'array',
        'period_start'      => 'date',
        'period_end'        => 'date',
        'sent_at'           => 'datetime',
        'next_run_at'       => 'datetime',
        'last_auto_run_at'  => 'datetime',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    public function periodLabel(): string
    {
        return $this->period_start->format('d M') . ' – ' . $this->period_end->format('d M Y');
    }

    public function isScheduled(): bool
    {
        return !empty($this->schedule_frequency) && $this->schedule_frequency !== 'none';
    }

    public function scheduleLabel(): string
    {
        return match ($this->schedule_frequency) {
            'weekly'  => 'Weekly',
            'monthly' => 'Monthly',
            default   => 'One-time',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'ready'      => 'text-green-700 bg-green-50 border-green-200',
            'generating' => 'text-amber-700 bg-amber-50 border-amber-200',
            'failed'     => 'text-red-700 bg-red-50 border-red-200',
            default      => 'text-neutral-600 bg-neutral-100 border-neutral-200',
        };
    }
}
