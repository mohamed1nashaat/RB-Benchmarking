<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ReportExport extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'format',
        'params',
        'status',
        'file_path',
    ];

    protected $casts = [
        'params' => 'array',
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

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'done');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'queued');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'queued';
    }

    public function markAsCompleted(string $filePath): void
    {
        $this->update([
            'status' => 'done',
            'file_path' => $filePath,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function getDownloadUrl(): ?string
    {
        if (!$this->isCompleted() || !$this->file_path) {
            return null;
        }

        return route('reports.download', $this->id);
    }
}
