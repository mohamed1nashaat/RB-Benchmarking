<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Alert extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'type',
        'objective',
        'conditions',
        'notification_channels',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $tenantId = session('current_tenant_id') ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);
                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function activate(): void
    {
        if (!$this->isActive()) {
            $this->update(['is_active' => true]);
        }
    }

    public function deactivate(): void
    {
        if ($this->isActive()) {
            $this->update(['is_active' => false]);
        }
    }

    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Check if this alert should be evaluated based on cooldown period
     */
    public function shouldEvaluate(int $cooldownMinutes = 60): bool
    {
        if (!$this->last_triggered_at) {
            return true;
        }

        return $this->last_triggered_at->addMinutes($cooldownMinutes)->isPast();
    }
}
