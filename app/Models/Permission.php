<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
    ];

    /**
     * Get all roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(TenantRole::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Scope to filter permissions by group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get permissions grouped by their group attribute.
     */
    public static function getGrouped(): array
    {
        return static::orderBy('group')
            ->orderBy('display_name')
            ->get()
            ->groupBy('group')
            ->toArray();
    }
}
