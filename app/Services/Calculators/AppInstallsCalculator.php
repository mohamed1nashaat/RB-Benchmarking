<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class AppInstallsCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpa', 'ctr'];
    }

    public function getSecondaryKpis(): array
    {
        return ['cpc', 'cvr', 'cpm'];
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
        $kpis['cpa'] = $this->calculateCpa($aggregated); // Cost per app install
        $kpis['ctr'] = $this->calculateCtr($aggregated);

        // Secondary KPIs
        $kpis['cpc'] = $this->calculateCpc($aggregated);
        $kpis['cvr'] = $this->safePercentage($aggregated['purchases'], $aggregated['clicks']); // Install conversion rate
        $kpis['cpm'] = $this->calculateCpm($aggregated);

        // Health metrics
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
        $kpis['purchases'] = $aggregated['purchases'] ?? 0; // App installs

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['purchases']) &&
               isset($aggregatedMetrics['clicks']) &&
               ($aggregatedMetrics['purchases'] > 0 || $aggregatedMetrics['clicks'] > 0);
    }
}