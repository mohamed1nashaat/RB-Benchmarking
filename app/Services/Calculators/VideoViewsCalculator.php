<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class VideoViewsCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'vtr', 'cpm'];
    }

    public function getSecondaryKpis(): array
    {
        return ['reach', 'frequency', 'ctr'];
    }

    public function calculateKpis(Collection $metrics): array
    {
        $aggregated = $this->aggregateMetrics($metrics);
        
        if (empty($aggregated)) {
            return [];
        }

        $kpis = [];

        // Primary KPIs
        $kpis['spend'] = $aggregated['spend'] ?? 0;
        $kpis['vtr'] = $this->calculateVtr($aggregated);
        $kpis['cpm'] = $this->calculateCpm($aggregated);

        // Secondary KPIs
        $kpis['reach'] = $aggregated['reach'] ?? 0;
        $kpis['frequency'] = $this->calculateFrequency($aggregated);
        $kpis['ctr'] = $this->calculateCtr($aggregated);

        // Health metrics
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;
        $kpis['video_views'] = $aggregated['video_views'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['video_views']) &&
               isset($aggregatedMetrics['impressions']) &&
               ($aggregatedMetrics['video_views'] > 0 || $aggregatedMetrics['impressions'] > 0);
    }
}