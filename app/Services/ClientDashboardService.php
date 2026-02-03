<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\AdAccount;
use App\Models\AdMetric;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientDashboardService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Parse date range from filters
     */
    private function getDateRange(array $filters): array
    {
        $startDate = null;
        $endDate = null;

        // Custom date range takes priority
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $startDate = Carbon::parse($filters['from'])->startOfDay();
            $endDate = Carbon::parse($filters['to'])->endOfDay();
        } elseif (!empty($filters['period'])) {
            // Period-based date range
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays($filters['period'])->startOfDay();
        }
        // If both are null, it means all-time (no date filter)

        return [$startDate, $endDate];
    }

    /**
     * Get account IDs filtered by platform if specified
     */
    private function getFilteredAccountIds(Tenant $client, array $filters): \Illuminate\Support\Collection
    {
        $query = $client->adAccounts()->with('integration');

        if (!empty($filters['platform'])) {
            $query->whereHas('integration', function ($q) use ($filters) {
                $q->where('platform', $filters['platform']);
            });
        }

        return $query->get()->pluck('id');
    }

    /**
     * Get aggregated statistics for a client
     */
    public function getClientStatistics(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);

        if ($accountIds->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $metrics = $this->calculateMetrics($accountIds, $filters);

        return [
            'total_spend' => $metrics['total_spend'],
            'total_impressions' => $metrics['total_impressions'],
            'total_clicks' => $metrics['total_clicks'],
            'total_conversions' => $metrics['total_conversions'],
            'total_revenue' => $metrics['total_revenue'],
            'ctr' => $metrics['ctr'],
            'cvr' => $metrics['cvr'],
            'cpc' => $metrics['cpc'],
            'roas' => $metrics['roas'],
        ];
    }

    /**
     * Get performance trends over time with currency conversion
     */
    public function getPerformanceTrends(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);

        if ($accountIds->isEmpty()) {
            return [
                'spend' => [],
                'impressions' => [],
                'clicks' => [],
                'conversions' => [],
            ];
        }

        [$startDate, $endDate] = $this->getDateRange($filters);

        // Get trends with currency info for conversion
        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->selectRaw('
                DATE(m.date) as date,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.impressions) as impressions,
                SUM(m.clicks) as clicks,
                SUM(m.conversions) as conversions
            ')
            ->whereIn('m.ad_account_id', $accountIds);

        // Apply date filter if set
        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $trendsRaw = $query
            ->groupBy('date', 'a.currency')
            ->orderBy('date')
            ->get();

        // Group by date and convert currencies to SAR
        $trendsByDate = $trendsRaw->groupBy('date')->map(function ($dayMetrics) {
            $totalSpendSAR = 0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalConversions = 0;

            foreach ($dayMetrics as $metric) {
                // Convert spend to SAR based on account currency
                $currency = $metric->currency ?? 'USD';
                $totalSpendSAR += $this->currencyService->convertToSAR((float) $metric->spend, $currency);
                $totalImpressions += (int) $metric->impressions;
                $totalClicks += (int) $metric->clicks;
                $totalConversions += (int) $metric->conversions;
            }

            return [
                'date' => $dayMetrics->first()->date,
                'spend' => $totalSpendSAR,
                'impressions' => $totalImpressions,
                'clicks' => $totalClicks,
                'conversions' => $totalConversions,
            ];
        })->values();

        return [
            'spend' => $trendsByDate->map(fn($t) => ['date' => $t['date'], 'value' => (float) $t['spend']])->toArray(),
            'impressions' => $trendsByDate->map(fn($t) => ['date' => $t['date'], 'value' => (int) $t['impressions']])->toArray(),
            'clicks' => $trendsByDate->map(fn($t) => ['date' => $t['date'], 'value' => (int) $t['clicks']])->toArray(),
            'conversions' => $trendsByDate->map(fn($t) => ['date' => $t['date'], 'value' => (int) $t['conversions']])->toArray(),
        ];
    }

    /**
     * Get platform breakdown
     */
    public function getPlatformBreakdown(Tenant $client, array $filters = []): array
    {
        $adAccounts = $client->adAccounts()->with('integration')->get();

        if ($adAccounts->isEmpty()) {
            return [];
        }

        $breakdown = [];

        foreach ($adAccounts->groupBy('integration.platform') as $platform => $accounts) {
            $accountIds = $accounts->pluck('id');
            $metrics = $this->calculateMetrics($accountIds, $filters);

            $breakdown[] = [
                'platform' => $platform,
                'accounts_count' => $accounts->count(),
                'spend' => $metrics['total_spend'],
                'impressions' => $metrics['total_impressions'],
                'clicks' => $metrics['total_clicks'],
                'conversions' => $metrics['total_conversions'],
            ];
        }

        // Sort by spend descending
        usort($breakdown, fn($a, $b) => $b['spend'] <=> $a['spend']);

        return $breakdown;
    }

    /**
     * Get ad accounts with status with currency conversion
     */
    public function getAdAccountsWithStatus(Tenant $client, array $filters = []): array
    {
        $query = $client->adAccounts()->with('integration');

        // Filter by platform if specified
        if (!empty($filters['platform'])) {
            $query->whereHas('integration', function ($q) use ($filters) {
                $q->where('platform', $filters['platform']);
            });
        }

        [$startDate, $endDate] = $this->getDateRange($filters);

        return $query->get()
            ->map(function ($account) use ($startDate, $endDate) {
                $currency = $account->currency ?? 'USD';

                // Calculate spend within date range for the account
                $spendQuery = DB::table('ad_metrics')
                    ->where('ad_account_id', $account->id);

                if ($startDate && $endDate) {
                    $spendQuery->whereBetween('date', [$startDate, $endDate]);
                }

                $totalSpend = $spendQuery->sum('spend');

                // Calculate last 7 days spend for health indicator (always recent)
                $recentSpend = DB::table('ad_metrics')
                    ->where('ad_account_id', $account->id)
                    ->where('date', '>=', Carbon::now()->subDays(7))
                    ->sum('spend');

                // Convert spend to SAR based on account currency
                $recentSpendSAR = $this->currencyService->convertToSAR((float) $recentSpend, $currency);
                $totalSpendSAR = $this->currencyService->convertToSAR((float) $totalSpend, $currency);

                return [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'platform' => $account->integration->platform ?? 'Unknown',
                    'status' => $account->status,
                    'currency' => $currency,
                    'total_spend' => $totalSpendSAR,
                    'recent_spend' => $recentSpendSAR,
                    'health' => $this->determineAccountHealth($recentSpendSAR, $account->status),
                ];
            })
            ->toArray();
    }

    /**
     * Get top performing campaigns with currency conversion
     */
    public function getTopCampaigns(Tenant $client, int $limit = 5, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);

        if ($accountIds->isEmpty()) {
            return [];
        }

        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_campaigns')
            ->join('ad_accounts', 'ad_campaigns.ad_account_id', '=', 'ad_accounts.id')
            ->leftJoin('ad_metrics', 'ad_campaigns.id', '=', 'ad_metrics.ad_campaign_id')
            ->select(
                'ad_campaigns.id',
                'ad_campaigns.name',
                'ad_accounts.account_name',
                'ad_accounts.currency',
                DB::raw('SUM(ad_metrics.spend) as total_spend'),
                DB::raw('SUM(ad_metrics.impressions) as total_impressions'),
                DB::raw('SUM(ad_metrics.clicks) as total_clicks'),
                DB::raw('SUM(ad_metrics.conversions) as total_conversions'),
                DB::raw('SUM(ad_metrics.revenue) as total_revenue')
            )
            ->whereIn('ad_campaigns.ad_account_id', $accountIds);

        // Apply date filter if set
        if ($startDate && $endDate) {
            $query->whereBetween('ad_metrics.date', [$startDate, $endDate]);
        }

        return $query
            ->groupBy('ad_campaigns.id', 'ad_campaigns.name', 'ad_accounts.account_name', 'ad_accounts.currency')
            ->get()
            ->map(function ($campaign) {
                $currency = $campaign->currency ?? 'USD';
                $spend = (float) $campaign->total_spend;
                $revenue = (float) $campaign->total_revenue;

                // Convert spend and revenue to SAR based on account currency
                $spendSAR = $this->currencyService->convertToSAR($spend, $currency);
                $revenueSAR = $this->currencyService->convertToSAR($revenue, $currency);

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'account_name' => $campaign->account_name,
                    'currency' => $currency,
                    'spend' => $spendSAR,
                    'impressions' => (int) $campaign->total_impressions,
                    'clicks' => (int) $campaign->total_clicks,
                    'conversions' => (int) $campaign->total_conversions,
                    'roas' => $spendSAR > 0 ? round($revenueSAR / $spendSAR, 2) : 0,
                ];
            })
            ->sortByDesc('spend')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Calculate metrics from account IDs with currency conversion
     */
    private function calculateMetrics($accountIds, array $filters = []): array
    {
        [$startDate, $endDate] = $this->getDateRange($filters);

        // Get metrics grouped by account to apply currency conversion
        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->selectRaw('
                a.id as account_id,
                a.currency,
                SUM(m.spend) as total_spend,
                SUM(m.impressions) as total_impressions,
                SUM(m.clicks) as total_clicks,
                SUM(m.conversions) as total_conversions,
                SUM(m.revenue) as total_revenue
            ')
            ->whereIn('m.ad_account_id', $accountIds);

        // Apply date filter if set
        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $metricsByAccount = $query
            ->groupBy('a.id', 'a.currency')
            ->get();

        // Convert and aggregate
        $totalSpendSAR = 0;
        $totalRevenueSAR = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;

        foreach ($metricsByAccount as $accountMetrics) {
            // Convert spend and revenue to SAR based on account currency
            $currency = $accountMetrics->currency ?? 'USD';
            $totalSpendSAR += $this->currencyService->convertToSAR((float) $accountMetrics->total_spend, $currency);
            $totalRevenueSAR += $this->currencyService->convertToSAR((float) $accountMetrics->total_revenue, $currency);

            $totalImpressions += (int) $accountMetrics->total_impressions;
            $totalClicks += (int) $accountMetrics->total_clicks;
            $totalConversions += (int) $accountMetrics->total_conversions;
        }

        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        $cvr = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;
        $cpc = $totalClicks > 0 ? round($totalSpendSAR / $totalClicks, 2) : 0;
        $roas = $totalSpendSAR > 0 ? round($totalRevenueSAR / $totalSpendSAR, 2) : 0;

        return [
            'total_spend' => $totalSpendSAR,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenueSAR,
            'ctr' => $ctr,
            'cvr' => $cvr,
            'cpc' => $cpc,
            'roas' => $roas,
        ];
    }

    /**
     * Get empty statistics structure
     */
    private function getEmptyStatistics(): array
    {
        return [
            'total_spend' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'total_revenue' => 0,
            'ctr' => 0,
            'cvr' => 0,
            'cpc' => 0,
            'roas' => 0,
        ];
    }

    /**
     * Determine account health based on recent activity
     */
    private function determineAccountHealth(float $recentSpend, string $status): string
    {
        if ($status !== 'active') {
            return 'inactive';
        }

        if ($recentSpend >= 100) {
            return 'healthy';
        } elseif ($recentSpend > 0) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
}
