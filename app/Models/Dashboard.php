<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Dashboard extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'objective',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
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

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('position');
    }

    public function scopeForObjective(Builder $query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function setAsDefault(): void
    {
        // Remove default from other dashboards for this user and objective
        static::where('user_id', $this->user_id)
            ->where('objective', $this->objective)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function addWidget(string $type, array $config = [], int $position = null): DashboardWidget
    {
        if ($position === null) {
            $position = $this->widgets()->max('position') + 1;
        }

        return $this->widgets()->create([
            'type' => $type,
            'position' => $position,
            'config' => $config,
        ]);
    }

    public function getObjectiveKpis(): array
    {
        return match ($this->objective) {
            'awareness' => ['spend', 'cpm', 'reach', 'vtr', 'ctr'],
            'engagement' => ['spend', 'ctr', 'frequency', 'reach', 'vtr'],
            'traffic' => ['spend', 'cpc', 'ctr', 'impressions', 'clicks', 'cpm'],
            'messages' => ['spend', 'cpc', 'ctr', 'conversations', 'impressions', 'clicks'],
            'app_installs' => ['spend', 'cpa', 'ctr', 'cpc', 'cvr', 'cpm'],
            'in_app_actions' => ['spend', 'cpa', 'ctr', 'atc', 'cpc', 'cvr'],
            'leads' => ['spend', 'cpl', 'cvr', 'ctr', 'cpc'],
            'website_sales' => ['spend', 'roas', 'cpa', 'revenue', 'aov', 'cvr'],
            'retention' => ['spend', 'cpa', 'retention_rate', 'ltv', 'ctr', 'cpc'],
            default => [],
        };
    }
}
