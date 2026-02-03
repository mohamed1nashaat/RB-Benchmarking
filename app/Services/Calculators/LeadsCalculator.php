<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class LeadsCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpl', 'cvr'];
    }

    public function getSecondaryKpis(): array
    {
        return ['ctr', 'cpc'];
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
        $kpis['cpl'] = $this->calculateCpl($aggregated);
        $kpis['cvr'] = $this->safePercentage($aggregated['leads'], $aggregated['clicks']);

        // Secondary KPIs
        $kpis['ctr'] = $this->calculateCtr($aggregated);
        $kpis['cpc'] = $this->calculateCpc($aggregated);

        // Health metrics
        $kpis['leads'] = $aggregated['leads'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['leads']) &&
               isset($aggregatedMetrics['clicks']) &&
               ($aggregatedMetrics['leads'] > 0 || $aggregatedMetrics['clicks'] > 0);
    }
}
