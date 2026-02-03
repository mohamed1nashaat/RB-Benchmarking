<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class InAppActionsCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpa', 'ctr', 'atc'];
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
        $kpis['cpa'] = $this->calculateCpa($aggregated);
        $kpis['ctr'] = $this->calculateCtr($aggregated);
        $kpis['atc'] = $aggregated['atc'] ?? 0;

        // Secondary KPIs
        $kpis['cpc'] = $this->calculateCpc($aggregated);
        $kpis['cvr'] = $this->calculateCvr($aggregated);
        $kpis['cpm'] = $this->calculateCpm($aggregated);

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['impressions']) &&
               $aggregatedMetrics['impressions'] > 0;
    }
}