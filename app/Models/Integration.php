<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;
    protected $fillable = [
        'tenant_id',
        'user_id',
        'platform',
        'app_config',
        'created_by',
        'status',
        'last_sync_at',
    ];

    protected $casts = [
        'app_config' => 'encrypted:array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getCredentials(): array
    {
        return $this->app_config ?? [];
    }

    public function updateCredentials(array $credentials): void
    {
        $this->update(['app_config' => $credentials]);
    }
}
