<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientHealthService
{
    /**
     * Calculate health score for a client (0-100)
     *
     * Health score factors:
     * - Active ad accounts (20 points)
     * - Recent spend activity (30 points)
     * - Contract status (20 points)
     * - Account growth (15 points)
     * - Performance trends (15 points)
     */
    public function calculateHealthScore(Tenant $client): array
    {
        $score = 0;
        $factors = [];
        $alerts = [];

        // Factor 1: Active Ad Accounts (20 points)
        $accountsScore = $this->calculateAccountsScore($client);
        $score += $accountsScore['score'];
        $factors['accounts'] = $accountsScore;
        if ($accountsScore['alert']) {
            $alerts[] = $accountsScore['alert'];
        }

        // Factor 2: Recent Spend Activity (30 points)
        $spendScore = $this->calculateSpendScore($client);
        $score += $spendScore['score'];
        $factors['spend'] = $spendScore;
        if ($spendScore['alert']) {
            $alerts[] = $spendScore['alert'];
        }

        // Factor 3: Contract Status (20 points)
        $contractScore = $this->calculateContractScore($client);
        $score += $contractScore['score'];
        $factors['contract'] = $contractScore;
        if ($contractScore['alert']) {
            $alerts[] = $contractScore['alert'];
        }

        // Factor 4: Account Growth (15 points)
        $growthScore = $this->calculateGrowthScore($client);
        $score += $growthScore['score'];
        $factors['growth'] = $growthScore;

        // Factor 5: Performance Trends (15 points)
        $performanceScore = $this->calculatePerformanceScore($client);
        $score += $performanceScore['score'];
        $factors['performance'] = $performanceScore;

        // Determine overall health status
        $healthStatus = $this->getHealthStatus($score);

        return [
            'score' => round($score, 1),
            'status' => $healthStatus,
            'factors' => $factors,
            'alerts' => $alerts,
        ];
    }

    /**
     * Calculate score based on active ad accounts
     */
    private function calculateAccountsScore(Tenant $client): array
    {
        $accountCount = $client->adAccounts()->count();

        if ($accountCount === 0) {
            return [
                'score' => 0,
                'value' => 0,
                'max' => 20,
                'alert' => [
                    'type' => 'critical',
                    'message' => 'No ad accounts connected',
                ],
            ];
        }

        if ($accountCount === 1) {
            return [
                'score' => 10,
                'value' => 1,
                'max' => 20,
                'alert' => [
                    'type' => 'warning',
                    'message' => 'Only 1 ad account - consider diversification',
                ],
            ];
        }

        // Full score for 3+ accounts
        $score = min(20, 10 + ($accountCount * 2));

        return [
            'score' => $score,
            'value' => $accountCount,
            'max' => 20,
            'alert' => null,
        ];
    }

    /**
     * Calculate score based on recent spend activity
     */
    private function calculateSpendScore(Tenant $client): array
    {
        // Get spend from last 30 days
        $recentSpend = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->where('a.tenant_id', $client->id)
            ->where('m.date', '>=', now()->subDays(30))
            ->sum('m.spend');

        // Get spend from 30-60 days ago for comparison
        $previousSpend = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->where('a.tenant_id', $client->id)
            ->whereBetween('m.date', [now()->subDays(60), now()->subDays(30)])
            ->sum('m.spend');

        if ($recentSpend == 0) {
            return [
                'score' => 0,
                'value' => 0,
                'max' => 30,
                'alert' => [
                    'type' => 'critical',
                    'message' => 'No spend in last 30 days',
                ],
            ];
        }

        // Calculate score based on spend level
        $score = 15; // Base score for any spend

        // Bonus for healthy spend levels
        if ($recentSpend >= 50000) {
            $score += 15; // High spender
        } elseif ($recentSpend >= 10000) {
            $score += 10; // Medium spender
        } elseif ($recentSpend >= 1000) {
            $score += 5; // Low spender
        }

        // Check for declining spend
        if ($previousSpend > 0 && $recentSpend < ($previousSpend * 0.5)) {
            return [
                'score' => max(5, $score - 10),
                'value' => $recentSpend,
                'max' => 30,
                'alert' => [
                    'type' => 'warning',
                    'message' => 'Spend declined by more than 50%',
                ],
            ];
        }

        return [
            'score' => $score,
            'value' => $recentSpend,
            'max' => 30,
            'alert' => null,
        ];
    }

    /**
     * Calculate score based on contract status
     */
    private function calculateContractScore(Tenant $client): array
    {
        if (!$client->contract_end_date) {
            return [
                'score' => 10,
                'value' => 'No contract',
                'max' => 20,
                'alert' => null,
            ];
        }

        $daysUntilExpiry = now()->diffInDays($client->contract_end_date, false);

        // Expired
        if ($daysUntilExpiry < 0) {
            return [
                'score' => 0,
                'value' => 'Expired',
                'max' => 20,
                'alert' => [
                    'type' => 'critical',
                    'message' => 'Contract expired',
                ],
            ];
        }

        // Expiring soon (within 30 days)
        if ($daysUntilExpiry <= 30) {
            return [
                'score' => 10,
                'value' => "{$daysUntilExpiry} days remaining",
                'max' => 20,
                'alert' => [
                    'type' => 'warning',
                    'message' => "Contract expires in {$daysUntilExpiry} days",
                ],
            ];
        }

        // Active and healthy
        return [
            'score' => 20,
            'value' => "{$daysUntilExpiry} days remaining",
            'max' => 20,
            'alert' => null,
        ];
    }

    /**
     * Calculate score based on account growth
     */
    private function calculateGrowthScore(Tenant $client): array
    {
        // This is a simplified version - in production, track historical account counts
        $currentAccounts = $client->adAccounts()->count();

        // For now, give points based on total accounts
        $score = min(15, $currentAccounts * 3);

        return [
            'score' => $score,
            'value' => $currentAccounts,
            'max' => 15,
            'alert' => null,
        ];
    }

    /**
     * Calculate score based on performance trends
     */
    private function calculatePerformanceScore(Tenant $client): array
    {
        // Get average CTR and conversion metrics
        $metrics = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->where('a.tenant_id', $client->id)
            ->where('m.date', '>=', now()->subDays(30))
            ->select(
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(conversions) as total_conversions')
            )
            ->first();

        if (!$metrics || $metrics->total_impressions == 0) {
            return [
                'score' => 7,
                'value' => 'Insufficient data',
                'max' => 15,
                'alert' => null,
            ];
        }

        $ctr = ($metrics->total_clicks / $metrics->total_impressions) * 100;
        $score = 7; // Base score

        // Bonus for good CTR
        if ($ctr >= 2) {
            $score += 8; // Excellent CTR
        } elseif ($ctr >= 1) {
            $score += 5; // Good CTR
        } elseif ($ctr >= 0.5) {
            $score += 3; // Average CTR
        }

        return [
            'score' => $score,
            'value' => round($ctr, 2) . '% CTR',
            'max' => 15,
            'alert' => null,
        ];
    }

    /**
     * Get health status based on score
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 80) {
            return 'excellent';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'fair';
        } elseif ($score >= 20) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Get health color for UI display
     */
    public function getHealthColor(string $status): string
    {
        return match($status) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'poor' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }
}
