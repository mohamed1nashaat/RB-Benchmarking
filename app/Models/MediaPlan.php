<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaPlan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'total_budget', 'currency', 'period_start', 'period_end',
        'objective', 'industry', 'platforms', 'platform_allocations', 'funnel_split',
        'scenarios', 'calendar', 'status', 'selected_scenario', 'notes', 'metadata',
        'created_by', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'platforms'            => 'array',
        'platform_allocations' => 'array',
        'funnel_split'         => 'array',
        'scenarios'            => 'array',
        'calendar'             => 'array',
        'metadata'             => 'array',
        'period_start'         => 'date',
        'period_end'           => 'date',
        'approved_at'          => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function periodLabel(): string
    {
        return $this->period_start->format('d M') . ' – ' . $this->period_end->format('d M Y');
    }

    public function durationWeeks(): int
    {
        return (int) ceil($this->period_start->diffInDays($this->period_end) / 7);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'approved' => 'text-green-700 bg-green-50 border-green-200',
            'archived' => 'text-neutral-500 bg-neutral-100 border-neutral-200',
            default    => 'text-amber-700 bg-amber-50 border-amber-200',
        };
    }

    public function formattedBudget(): string
    {
        return number_format($this->total_budget, 0) . ' ' . $this->currency;
    }
}
