<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class SalesCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'roas', 'cpa'];
    }

    public function getSecondaryKpis(): array
    {
        return ['aov', 'cvr', 'cpc'];
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
        $kpis['roas'] = $this->calculateRoas($aggregated);
        $kpis['cpa'] = $this->calculateCpa($aggregated);

        // Secondary KPIs
        $kpis['aov'] = $this->calculateAov($aggregated);
        $kpis['cvr'] = $this->safePercentage($aggregated['purchases'], $aggregated['clicks']);
        $kpis['cpc'] = $this->calculateCpc($aggregated);

        // Health metrics
        $kpis['revenue'] = $aggregated['revenue'] ?? 0;
        $kpis['purchases'] = $aggregated['purchases'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['revenue']) &&
               isset($aggregatedMetrics['purchases']) &&
               ($aggregatedMetrics['revenue'] > 0 || $aggregatedMetrics['purchases'] > 0);
    }
}
