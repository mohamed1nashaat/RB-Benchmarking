<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class TrafficCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpc', 'ctr'];
    }

    public function getSecondaryKpis(): array
    {
        return ['impressions', 'clicks', 'cpm'];
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
        $kpis['cpc'] = $this->calculateCpc($aggregated);
        $kpis['ctr'] = $this->calculateCtr($aggregated);

        // Secondary KPIs
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
        $kpis['cpm'] = $this->calculateCpm($aggregated);

        // Health metrics
        $kpis['sessions'] = $aggregated['sessions'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['clicks']) &&
               isset($aggregatedMetrics['impressions']) &&
               ($aggregatedMetrics['clicks'] > 0 || $aggregatedMetrics['impressions'] > 0);
    }
}