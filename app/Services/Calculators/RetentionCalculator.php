<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

class RetentionCalculator extends BaseObjectiveCalculator
{
    public function getPrimaryKpis(): array
    {
        return ['spend', 'cpa', 'retention_rate', 'ltv'];
    }

    public function getSecondaryKpis(): array
    {
        return ['ctr', 'cpc', 'cpa'];
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
        $kpis['retention_rate'] = $this->calculateRetentionRate($aggregated);
        $kpis['ltv'] = $this->calculateLtv($aggregated);

        // Secondary KPIs
        $kpis['ctr'] = $this->calculateCtr($aggregated);
        $kpis['cpc'] = $this->calculateCpc($aggregated);

        return array_filter($kpis, fn($value) => $value !== null);
    }

    protected function calculateRetentionRate(array $aggregated): ?float
    {
        $actions = $aggregated['conversions'] ?? 0;
        $users = $aggregated['reach'] ?? 0;
        
        return $users > 0 ? round(($actions / $users) * 100, 2) : null;
    }

    protected function calculateLtv(array $aggregated): ?float
    {
        $revenue = $aggregated['revenue'] ?? 0;
        $users = $aggregated['reach'] ?? 0;
        
        return $users > 0 ? round($revenue / $users, 2) : null;
    }

    public function canCalculate(array $aggregatedMetrics): bool
    {
        return isset($aggregatedMetrics['spend']) && 
               isset($aggregatedMetrics['impressions']) &&
               $aggregatedMetrics['impressions'] > 0;
    }
}