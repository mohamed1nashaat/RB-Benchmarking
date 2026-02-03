<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class EngagementCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'ctr', 'frequency'];
    }

    public function getSecondaryKpis(): array
    {
        return ['reach', 'frequency', 'vtr'];
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
        $kpis['ctr'] = $this->calculateCtr($aggregated);
        $kpis['frequency'] = $this->calculateFrequency($aggregated);

        // Secondary KPIs
        $kpis['reach'] = $aggregated['reach'] ?? 0;
        $kpis['vtr'] = $this->calculateVtr($aggregated);

        // Health metrics
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['impressions']) &&
               isset($aggregatedMetrics['clicks']) &&
               ($aggregatedMetrics['impressions'] > 0 || $aggregatedMetrics['clicks'] > 0);
    }
}