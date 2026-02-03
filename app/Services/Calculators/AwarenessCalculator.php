<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class AwarenessCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpm'];
    }

    public function getSecondaryKpis(): array
    {
        return ['reach', 'frequency', 'vtr', 'ctr'];
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
        $kpis['cpm'] = $this->calculateCpm($aggregated);

        // Secondary KPIs
        $kpis['reach'] = $aggregated['reach'] ?? 0;
        $kpis['frequency'] = $this->calculateFrequency($aggregated);
        $kpis['vtr'] = $this->calculateVtr($aggregated);
        $kpis['ctr'] = $this->calculateCtr($aggregated);

        // Health metrics
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['impressions']) &&
               $aggregatedMetrics['impressions'] > 0;
    }
}
