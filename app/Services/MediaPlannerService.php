<?php

namespace App\Services;

use App\Models\IndustryBenchmark;
use App\Models\MediaPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MediaPlannerService
{
    // Ramadan dates (Hijri → Gregorian approximations)
    private const RAMADAN_WINDOWS = [
        ['start' => '2025-03-01', 'end' => '2025-03-29'],
        ['start' => '2026-02-18', 'end' => '2026-03-19'],
        ['start' => '2027-02-08', 'end' => '2027-03-09'],
    ];

    // Platform objective affinity (how well each platform serves each objective)
    private const PLATFORM_AFFINITY = [
        'awareness'   => ['facebook' => 0.25, 'google' => 0.20, 'tiktok' => 0.25, 'snapchat' => 0.20, 'linkedin' => 0.10],
        'traffic'     => ['facebook' => 0.30, 'google' => 0.35, 'tiktok' => 0.15, 'snapchat' => 0.10, 'linkedin' => 0.10],
        'leads'       => ['facebook' => 0.40, 'google' => 0.30, 'tiktok' => 0.10, 'snapchat' => 0.10, 'linkedin' => 0.10],
        'conversions' => ['facebook' => 0.30, 'google' => 0.40, 'tiktok' => 0.10, 'snapchat' => 0.10, 'linkedin' => 0.10],
    ];

    // Ramadan multipliers per platform (social spikes during Ramadan)
    private const RAMADAN_BOOST = [
        'facebook' => 1.20,
        'snapchat' => 1.35,
        'tiktok'   => 1.25,
        'google'   => 0.95,
        'linkedin' => 0.85,
    ];

    // Funnel defaults by objective
    private const FUNNEL_DEFAULTS = [
        'awareness'   => ['tof' => 70, 'mof' => 20, 'bof' => 10],
        'traffic'     => ['tof' => 50, 'mof' => 35, 'bof' => 15],
        'leads'       => ['tof' => 40, 'mof' => 35, 'bof' => 25],
        'conversions' => ['tof' => 25, 'mof' => 35, 'bof' => 40],
    ];

    public function allocate(MediaPlan $plan): MediaPlan
    {
        try {
            $benchmarks   = $this->getBenchmarks($plan);
            $ramadanFactor = $this->ramadanOverlapFactor($plan);
            $scenarios    = $this->buildScenarios($plan, $benchmarks, $ramadanFactor);
            $calendar     = $this->buildCalendar($plan, $scenarios['recommended']);
            $funnelSplit  = self::FUNNEL_DEFAULTS[$plan->objective];

            $plan->update([
                'platform_allocations' => $scenarios['recommended']['allocations'],
                'funnel_split'         => $funnelSplit,
                'scenarios'            => $scenarios,
                'calendar'             => $calendar,
                'selected_scenario'    => 'recommended',
                'metadata'             => array_merge($plan->metadata ?? [], [
                    'ramadan_overlap'  => $ramadanFactor > 0,
                    'ramadan_factor'   => $ramadanFactor,
                    'allocated_at'     => now()->toIso8601String(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('MediaPlannerService::allocate failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
        }

        return $plan->fresh();
    }

    private function buildScenarios(MediaPlan $plan, array $benchmarks, float $ramadanFactor): array
    {
        $scenarios = [];

        foreach (['conservative' => 0.80, 'recommended' => 1.00, 'aggressive' => 1.25] as $key => $budgetMultiplier) {
            $scenarioBudget = $plan->total_budget * $budgetMultiplier;
            $allocations    = $this->computeAllocations($plan, $benchmarks, $scenarioBudget, $ramadanFactor);
            $projections    = $this->projectOutcomes($allocations, $benchmarks);

            $scenarios[$key] = [
                'budget'      => round($scenarioBudget, 0),
                'allocations' => $allocations,
                'projections' => $projections,
                'label'       => match($key) {
                    'conservative' => 'Conservative (-20%)',
                    'recommended'  => 'Recommended',
                    'aggressive'   => 'Aggressive (+25%)',
                },
            ];
        }

        return $scenarios;
    }

    private function computeAllocations(MediaPlan $plan, array $benchmarks, float $budget, float $ramadanFactor): array
    {
        $objective  = $plan->objective;
        $platforms  = $plan->platforms;
        $affinity   = self::PLATFORM_AFFINITY[$objective];

        // Filter affinity to selected platforms only
        $weights = array_intersect_key($affinity, array_flip($platforms));

        // Apply Ramadan boost if applicable
        if ($ramadanFactor > 0) {
            foreach ($weights as $platform => &$weight) {
                $boost = self::RAMADAN_BOOST[$platform] ?? 1.0;
                $weight *= $boost;
            }
        }

        // Normalize weights to sum to 1
        $total = array_sum($weights);
        if ($total === 0.0) {
            $equalShare = 1 / count($weights);
            $weights = array_fill_keys(array_keys($weights), $equalShare);
        } else {
            foreach ($weights as &$w) {
                $w /= $total;
            }
        }

        $allocations = [];
        foreach ($weights as $platform => $pct) {
            $platformBudget = round($budget * $pct, 0);
            $benchmark      = $benchmarks[$platform] ?? null;

            $allocations[$platform] = [
                'budget'     => $platformBudget,
                'pct'        => round($pct * 100, 1),
                'cpl'        => $benchmark['cpl'] ?? null,
                'cpc'        => $benchmark['cpc'] ?? null,
                'cpm'        => $benchmark['cpm'] ?? null,
            ];
        }

        return $allocations;
    }

    private function projectOutcomes(array $allocations, array $benchmarks): array
    {
        $totals = ['impressions' => 0, 'clicks' => 0, 'leads' => 0, 'spend' => 0];

        foreach ($allocations as $platform => $alloc) {
            $bm     = $benchmarks[$platform] ?? [];
            $budget = $alloc['budget'];

            $cpm  = $bm['cpm'] ?? 30;
            $cpc  = $bm['cpc'] ?? 3;
            $cpl  = $bm['cpl'] ?? 25;

            $impressions = $cpm > 0 ? ($budget / $cpm) * 1000 : 0;
            $clicks      = $cpc > 0 ? $budget / $cpc : 0;
            $leads       = $cpl > 0 ? $budget / $cpl : 0;

            $totals['impressions'] += round($impressions);
            $totals['clicks']      += round($clicks);
            $totals['leads']       += round($leads);
            $totals['spend']       += $budget;
        }

        return $totals;
    }

    private function buildCalendar(MediaPlan $plan, array $scenario): array
    {
        $start   = $plan->period_start;
        $end     = $plan->period_end;
        $months  = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->copy()->max($start);
            $monthEnd   = $current->copy()->endOfMonth()->min($end);
            $daysInRange = $monthStart->diffInDays($monthEnd) + 1;
            $totalDays   = $start->diffInDays($end) + 1;
            $weight      = $totalDays > 0 ? $daysInRange / $totalDays : 0;

            $isRamadan = $this->isRamadanMonth($monthStart, $monthEnd);

            $monthBudget = round($scenario['budget'] * $weight, 0);

            $months[] = [
                'month'       => $current->format('Y-m'),
                'label'       => $current->format('M Y'),
                'budget'      => $monthBudget,
                'weight'      => round($weight * 100, 1),
                'is_ramadan'  => $isRamadan,
                'days'        => $daysInRange,
            ];

            $current->addMonth();
        }

        return $months;
    }

    private function getBenchmarks(MediaPlan $plan): array
    {
        $result = [];
        $industry = $plan->industry;

        foreach ($plan->platforms as $platform) {
            $bm = IndustryBenchmark::where('industry', $industry)
                ->where('platform', $platform)
                ->first();

            if ($bm) {
                $result[$platform] = [
                    'cpl' => $bm->p50_cpl,
                    'cpc' => $bm->p50_cpc,
                    'cpm' => $bm->p50_cpm,
                    'ctr' => $bm->p50_ctr,
                ];
            } else {
                // fallback defaults
                $result[$platform] = [
                    'cpl' => 25.0,
                    'cpc' => 3.0,
                    'cpm' => 28.0,
                    'ctr' => 1.2,
                ];
            }
        }

        return $result;
    }

    private function ramadanOverlapFactor(MediaPlan $plan): float
    {
        $start = $plan->period_start;
        $end   = $plan->period_end;
        $totalDays = $start->diffInDays($end) + 1;

        $overlapDays = 0;

        foreach (self::RAMADAN_WINDOWS as $window) {
            $rStart = Carbon::parse($window['start']);
            $rEnd   = Carbon::parse($window['end']);

            $overlapStart = $start->max($rStart);
            $overlapEnd   = $end->min($rEnd);

            if ($overlapStart->lte($overlapEnd)) {
                $overlapDays += $overlapStart->diffInDays($overlapEnd) + 1;
            }
        }

        return $totalDays > 0 ? min(1.0, $overlapDays / $totalDays) : 0.0;
    }

    private function isRamadanMonth(Carbon $monthStart, Carbon $monthEnd): bool
    {
        foreach (self::RAMADAN_WINDOWS as $window) {
            $rStart = Carbon::parse($window['start']);
            $rEnd   = Carbon::parse($window['end']);
            if ($monthStart->lte($rEnd) && $monthEnd->gte($rStart)) {
                return true;
            }
        }
        return false;
    }
}
