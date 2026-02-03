<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class CallsCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cost_per_call'];
    }

    public function getSecondaryKpis(): array
    {
        return ['ctr', 'call_conversion_rate'];
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
        $kpis['cost_per_call'] = $this->calculateCostPerCall($aggregated);

        // Secondary KPIs
        $kpis['ctr'] = $this->calculateCtr($aggregated);
        $kpis['call_conversion_rate'] = $this->safePercentage($aggregated['calls'], $aggregated['clicks']);

        // Health metrics
        $kpis['calls'] = $aggregated['calls'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['calls']) &&
               isset($aggregatedMetrics['clicks']) &&
               ($aggregatedMetrics['calls'] > 0 || $aggregatedMetrics['clicks'] > 0);
    }
}
