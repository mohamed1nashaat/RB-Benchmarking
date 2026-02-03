<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class WebsiteSalesCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'roas', 'cpa', 'revenue'];
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
        $kpis['revenue'] = $aggregated['revenue'] ?? 0;

        // Secondary KPIs
        $kpis['aov'] = $this->calculateAov($aggregated);
        $kpis['cvr'] = $this->calculateCvr($aggregated);
        $kpis['cpc'] = $this->calculateCpc($aggregated);

        return array_filter($kpis, fn($value) => $value !== null);
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['impressions']) &&
               $aggregatedMetrics['impressions'] > 0;
    }
}