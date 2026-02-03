<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class IndustryBenchmark extends Model
{
    protected $fillable = [
        'industry',
        'platform',
        'metric',
        'percentile_10',
        'percentile_25',
        'percentile_50',
        'percentile_75',
        'percentile_90',
        'sample_size',
        'source',
        'region',
        'data_period_start',
        'data_period_end',
        'last_updated',
    ];

    protected $casts = [
        'percentile_10' => 'float',
        'percentile_25' => 'float',
        'percentile_50' => 'float',
        'percentile_75' => 'float',
        'percentile_90' => 'float',
        'sample_size' => 'integer',
        'data_period_start' => 'date',
        'data_period_end' => 'date',
        'last_updated' => 'date',
    ];

    /**
     * Scope to filter by industry
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope to filter by platform
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by metric
     */
    public function scopeForMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric', $metric);
    }

    /**
     * Scope to filter by region
     */
    public function scopeForRegion(Builder $query, string $region = 'global'): Builder
    {
        return $query->where('region', $region);
    }

    /**
     * Get benchmarks for a specific industry and platform
     */
    public static function getBenchmarks(string $industry, string $platform, string $region = 'global'): array
    {
        return static::query()
            ->forIndustry($industry)
            ->forPlatform($platform)
            ->forRegion($region)
            ->get()
            ->keyBy('metric')
            ->toArray();
    }

    /**
     * Get all available industries
     */
    public static function getAvailableIndustries(): array
    {
        return static::query()
            ->distinct()
            ->pluck('industry')
            ->toArray();
    }

    /**
     * Get all available platforms for an industry
     */
    public static function getAvailablePlatforms(string $industry): array
    {
        return static::query()
            ->forIndustry($industry)
            ->distinct()
            ->pluck('platform')
            ->toArray();
    }
}
