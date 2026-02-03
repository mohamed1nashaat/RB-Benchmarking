<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'report_type',
        'metrics',
        'filters',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'export_formats',
        'recipients',
        'is_active',
        'last_generated_at',
        'next_generation_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'filters' => 'array',
        'export_formats' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
    ];

    protected $attributes = [
        'export_formats' => '["pdf"]',
        'frequency' => 'weekly',
        'time_of_day' => '09:00:00',
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

        static::creating(function ($report) {
            if (!$report->tenant_id && auth()->check()) {
                $report->tenant_id = auth()->user()->tenant_id;
            }
            if (!$report->user_id && auth()->check()) {
                $report->user_id = auth()->id();
            }

            // Calculate next generation time
            $report->next_generation_at = $report->calculateNextGeneration();
        });

        static::updating(function ($report) {
            // Recalculate next generation time if schedule changed
            if ($report->isDirty(['frequency', 'day_of_week', 'day_of_month', 'time_of_day'])) {
                $report->next_generation_at = $report->calculateNextGeneration();
            }
        });
    }

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReportHistory::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForGeneration(Builder $query): Builder
    {
        return $query->active()
            ->where('next_generation_at', '<=', Carbon::now());
    }

    public function scopeForReportType(Builder $query, string $type): Builder
    {
        return $query->where('report_type', $type);
    }

    /**
     * Calculate next generation time based on frequency
     */
    public function calculateNextGeneration(): Carbon
    {
        $now = Carbon::now();
        $time = Carbon::parse($this->time_of_day);

        return match ($this->frequency) {
            'daily' => $this->calculateNextDaily($now, $time),
            'weekly' => $this->calculateNextWeekly($now, $time),
            'monthly' => $this->calculateNextMonthly($now, $time),
            default => $now->addDay(),
        };
    }

    /**
     * Calculate next daily generation
     */
    protected function calculateNextDaily(Carbon $now, Carbon $time): Carbon
    {
        $next = $now->copy()->setTime($time->hour, $time->minute, 0);

        // If time has already passed today, schedule for tomorrow
        if ($next->lte($now)) {
            $next->addDay();
        }

        return $next;
    }

    /**
     * Calculate next weekly generation
     */
    protected function calculateNextWeekly(Carbon $now, Carbon $time): Carbon
    {
        if (!$this->day_of_week) {
            return $this->calculateNextDaily($now, $time);
        }

        // Map day name to Carbon constant
        $dayMap = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        $targetDay = $dayMap[$this->day_of_week];
        $next = $now->copy()->next($targetDay)->setTime($time->hour, $time->minute, 0);

        // If it's the same day but time hasn't passed, use today
        if ($now->dayOfWeek === $targetDay) {
            $today = $now->copy()->setTime($time->hour, $time->minute, 0);
            if ($today->gt($now)) {
                return $today;
            }
        }

        return $next;
    }

    /**
     * Calculate next monthly generation
     */
    protected function calculateNextMonthly(Carbon $now, Carbon $time): Carbon
    {
        $day = $this->day_of_month ?? 1;

        // Ensure day is valid for the month
        $day = min($day, 31);

        $next = $now->copy()->setTime($time->hour, $time->minute, 0);

        // Try current month first
        try {
            $next->setDay($day);
            if ($next->lte($now)) {
                // Already passed this month, go to next month
                $next->addMonth()->setDay($day);
            }
        } catch (\Exception $e) {
            // Day doesn't exist in current month, go to next month
            $next->addMonth()->setDay($day);
        }

        return $next;
    }

    /**
     * Mark as generated
     */
    public function markAsGenerated(): void
    {
        $this->update([
            'last_generated_at' => Carbon::now(),
            'next_generation_at' => $this->calculateNextGeneration(),
        ]);
    }

    /**
     * Check if report should be generated now
     */
    public function shouldGenerate(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->next_generation_at) {
            return true;
        }

        return $this->next_generation_at->lte(Carbon::now());
    }

    /**
     * Get human-readable frequency
     */
    public function getFrequencyTextAttribute(): string
    {
        return match ($this->frequency) {
            'daily' => 'Daily at ' . Carbon::parse($this->time_of_day)->format('g:i A'),
            'weekly' => ucfirst($this->day_of_week) . 's at ' . Carbon::parse($this->time_of_day)->format('g:i A'),
            'monthly' => 'Monthly on day ' . $this->day_of_month . ' at ' . Carbon::parse($this->time_of_day)->format('g:i A'),
            default => $this->frequency,
        };
    }
}
