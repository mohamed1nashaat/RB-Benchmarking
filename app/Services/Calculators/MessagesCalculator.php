<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class MessagesCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpc', 'ctr', 'conversations'];
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
        $kpis['conversations'] = $aggregated['conversions'] ?? 0;

        // Secondary KPIs
        $kpis['impressions'] = $aggregated['impressions'] ?? 0;
        $kpis['clicks'] = $aggregated['clicks'] ?? 0;
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