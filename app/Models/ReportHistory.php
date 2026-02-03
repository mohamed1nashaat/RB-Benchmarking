<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ReportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_report_id',
        'tenant_id',
        'status',
        'filters_snapshot',
        'file_path',
        'format',
        'file_size',
        'error_message',
        'generated_at',
    ];

    protected $casts = [
        'filters_snapshot' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($history) {
            if (!$history->tenant_id && auth()->check()) {
                $history->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Relationships
     */
    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scopes
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'generated_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Delete associated file
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return false;
    }

    /**
     * Delete event to clean up files
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($history) {
            $history->deleteFile();
        });
    }
}
