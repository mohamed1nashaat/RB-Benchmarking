<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Industry;
use App\Models\IndustryBenchmark;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BenchmarkService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    /**
     * Get a map of industry slug → display name from the industries table.
     */
    private function getIndustryDisplayNames(): array
    {
        return Cache::remember('industry_display_names', 3600, function () {
            return Industry::pluck('display_name', 'name')->toArray();
        });
    }

    /**
     * Resolve an industry slug to its display name.
     */
    private function resolveIndustryName(string $slug): string
    {
        $map = $this->getIndustryDisplayNames();
        return $map[$slug] ?? ucwords(str_replace(['_', '-'], ' ', $slug));
    }

    /**
     * Apply metric-level filters to a query builder
     */
    private function applyMetricFilters($query, string $dateFrom, string $dateTo, array $filters): void
    {
        $query->whereBetween('date', [$dateFrom, $dateTo]);

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->whereIn('platform', $platforms);
        }
        if (!empty($filters['objective'])) {
            $objectives = is_array($filters['objective']) ? $filters['objective'] : [$filters['objective']];
            $query->whereIn('objective', $objectives);
        }
        if (!empty($filters['funnel_stage'])) {
            $stages = is_array($filters['funnel_stage']) ? $filters['funnel_stage'] : [$filters['funnel_stage']];
            $query->whereIn('funnel_stage', $stages);
        }
        if (!empty($filters['user_journey'])) {
            $journeys = is_array($filters['user_journey']) ? $filters['user_journey'] : [$filters['user_journey']];
            $query->whereIn('user_journey', $journeys);
        }
        if (isset($filters['has_pixel_data'])) {
            $query->where('has_pixel_data', $filters['has_pixel_data']);
        }
    }

    /**
     * Apply metric-level filters to a query builder (DB facade style).
     */
    private function applyMetricFiltersToBuilder($query, string $dateFrom, string $dateTo, array $filters, string $tableAlias = 'm'): void
    {
        $query->whereBetween("{$tableAlias}.date", [$dateFrom, $dateTo]);

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->whereIn("{$tableAlias}.platform", $platforms);
        }
        if (!empty($filters['objective'])) {
            $objectives = is_array($filters['objective']) ? $filters['objective'] : [$filters['objective']];
            $query->whereIn("{$tableAlias}.objective", $objectives);
        }
        if (!empty($filters['funnel_stage'])) {
            $stages = is_array($filters['funnel_stage']) ? $filters['funnel_stage'] : [$filters['funnel_stage']];
            $query->whereIn("{$tableAlias}.funnel_stage", $stages);
        }
        if (!empty($filters['user_journey'])) {
            $journeys = is_array($filters['user_journey']) ? $filters['user_journey'] : [$filters['user_journey']];
            $query->whereIn("{$tableAlias}.user_journey", $journeys);
        }
        if (isset($filters['has_pixel_data'])) {
            $query->where("{$tableAlias}.has_pixel_data", $filters['has_pixel_data']);
        }
    }

    /**
     * Load aggregated metrics per account via SQL GROUP BY.
     * Returns a map of ad_account_id → pre-computed metrics array.
     */
    private function loadAccountAggregates(string $dateFrom, string $dateTo, array $filters = []): array
    {
        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'a.id', '=', 'm.ad_account_id')
            ->select(
                'm.ad_account_id',
                DB::raw("COALESCE(a.account_config->>'$.currency', a.currency, 'USD') as account_currency"),
                DB::raw('SUM(m.spend) as total_spend_raw'),
                DB::raw('SUM(m.impressions) as total_impressions'),
                DB::raw('SUM(m.clicks) as total_clicks'),
                DB::raw('SUM(GREATEST(COALESCE(m.blended_leads,0), COALESCE(m.leads,0) + COALESCE(m.instant_leads,0))) as total_leads'),
                DB::raw('SUM(m.conversions) as total_conversions'),
                DB::raw('SUM(m.purchases) as total_purchases'),
                DB::raw('SUM(m.app_installs) as total_installs')
            )
            ->whereBetween('m.date', [$dateFrom, $dateTo]);

        $this->applyMetricFiltersToBuilder($query, $dateFrom, $dateTo, $filters, 'm');

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->join('integrations as i', 'i.id', '=', 'a.integration_id')
                  ->whereIn('i.platform', $platforms);
        }

        if (!empty($filters['country'])) {
            $countries = is_array($filters['country']) ? $filters['country'] : [$filters['country']];
            $query->whereIn('a.country', $countries);
        }

        // Exclude short-lived campaigns (< 7 active days) — they are anomalies that skew benchmarks.
        // Metrics with no campaign_id are kept (account-level data).
        $validCampaigns = DB::table('ad_metrics')
            ->selectRaw('ad_campaign_id')
            ->whereNotNull('ad_campaign_id')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->groupBy('ad_campaign_id')
            ->havingRaw('COUNT(DISTINCT date) >= 7');
        $query->where(function ($q) use ($validCampaigns) {
            $q->whereNull('m.ad_campaign_id')
              ->orWhereIn('m.ad_campaign_id', $validCampaigns);
        });

        $rows = $query->groupBy('m.ad_account_id', 'account_currency')->get();

        $map = [];
        foreach ($rows as $row) {
            $rawSpend = (float) $row->total_spend_raw;
            $currency = $row->account_currency;
            $totalSpend = $this->currencyService->convertToSAR($rawSpend, $currency);
            $totalImpressions = (int) $row->total_impressions;
            $totalClicks = (int) $row->total_clicks;
            $totalLeads = (int) $row->total_leads;
            $totalConversions = (int) $row->total_conversions;
            $totalPurchases = (int) $row->total_purchases;
            $totalInstalls = (int) $row->total_installs;

            $map[$row->ad_account_id] = [
                'total_spend' => $totalSpend,
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'total_leads' => $totalLeads,
                'total_conversions' => $totalConversions,
                'total_purchases' => $totalPurchases,
                'total_installs' => $totalInstalls,
                'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
                'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
                'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
                'cvr' => $totalClicks > 0 ? ($totalLeads / $totalClicks) * 100 : 0,
                'cpl' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
                'cpa' => $totalPurchases > 0 ? $totalSpend / $totalPurchases : 0,
                'cpi' => $totalInstalls > 0 ? $totalSpend / $totalInstalls : 0,
            ];
        }

        return $map;
    }

    /**
     * Load aggregated sub-rows via SQL GROUP BY with campaign joins.
     * Two-step: aggregate per account per combo, then re-aggregate by group/combo in PHP.
     * Returns a map of group_key → array of sub-row aggregates.
     */
    private function loadSubRowAggregates(string $dateFrom, string $dateTo, array $filters = [], string $groupBy = 'industry'): array
    {
        $groupField = $groupBy === 'tenant_id' ? 'a.tenant_id' : 'a.industry';

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'a.id', '=', 'm.ad_account_id')
            ->leftJoin('ad_campaigns as c', 'c.id', '=', 'm.ad_campaign_id')
            ->select(
                DB::raw("{$groupField} as group_key"),
                'a.id as ad_account_id',
                DB::raw("COALESCE(a.account_config->>'$.currency', a.currency, 'USD') as account_currency"),
                DB::raw("COALESCE(
                    CASE WHEN c.inherit_category_from_account = 0 AND c.category IS NOT NULL AND c.category != '' THEN c.category ELSE a.category END,
                    a.category,
                    '-'
                ) as category"),
                DB::raw("COALESCE(c.sub_industry, '-') as sub_category"),
                DB::raw("COALESCE(
                    c.funnel_stage,
                    m.funnel_stage,
                    CASE m.objective
                        WHEN 'awareness' THEN 'TOF'
                        WHEN 'leads' THEN 'BOF'
                        WHEN 'sales' THEN 'BOF'
                        WHEN 'calls' THEN 'BOF'
                    END,
                    '-'
                ) as funnel_stage"),
                DB::raw("COALESCE(c.objective, m.objective, '-') as objective"),
                'a.country',
                DB::raw('SUM(m.spend) as total_spend_raw'),
                DB::raw('SUM(m.impressions) as total_impressions'),
                DB::raw('SUM(m.clicks) as total_clicks'),
                DB::raw('SUM(GREATEST(COALESCE(m.blended_leads,0), COALESCE(m.leads,0) + COALESCE(m.instant_leads,0))) as total_leads'),
                DB::raw('SUM(m.conversions) as total_conversions'),
                DB::raw('SUM(m.purchases) as total_purchases'),
                DB::raw('SUM(m.app_installs) as total_installs')
            )
            ->whereBetween('m.date', [$dateFrom, $dateTo]);

        $this->applyMetricFiltersToBuilder($query, $dateFrom, $dateTo, $filters, 'm');

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->join('integrations as i', 'i.id', '=', 'a.integration_id')
                  ->whereIn('i.platform', $platforms);
        }

        if (!empty($filters['country'])) {
            $countries = is_array($filters['country']) ? $filters['country'] : [$filters['country']];
            $query->whereIn('a.country', $countries);
        }

        // Exclude short-lived campaigns (< 7 active days) — anomalies that skew benchmarks.
        $validCampaigns = DB::table('ad_metrics')
            ->selectRaw('ad_campaign_id')
            ->whereNotNull('ad_campaign_id')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->groupBy('ad_campaign_id')
            ->havingRaw('COUNT(DISTINCT date) >= 7');
        $query->where(function ($q) use ($validCampaigns) {
            $q->whereNull('m.ad_campaign_id')
              ->orWhereIn('m.ad_campaign_id', $validCampaigns);
        });

        $rows = $query
            ->groupByRaw('1, 2, 3, 4, 5, 6, 7, 8')
            ->get();

        // Re-aggregate by group_key → (category, sub_category, funnel_stage)
        $buckets = [];
        foreach ($rows as $row) {
            $groupKey = $groupBy === 'tenant_id' ? $row->group_key : ($row->group_key ?? 'Uncategorized');
            $key = "{$groupKey}|{$row->category}|{$row->sub_category}|{$row->funnel_stage}|{$row->objective}";
            $spend = $this->currencyService->convertToSAR((float) $row->total_spend_raw, $row->account_currency);

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'group_key' => $groupKey,
                    'category' => $row->category,
                    'sub_category' => $row->sub_category,
                    'funnel_stage' => $row->funnel_stage,
                    'objective' => $row->objective,
                    'total_spend' => 0,
                    'total_impressions' => 0,
                    'total_clicks' => 0,
                    'total_leads' => 0,
                    'total_conversions' => 0,
                    'total_purchases' => 0,
                    'total_installs' => 0,
                    'countries' => [],
                ];
            }

            $buckets[$key]['total_spend'] += $spend;
            $buckets[$key]['total_impressions'] += (int) $row->total_impressions;
            $buckets[$key]['total_clicks'] += (int) $row->total_clicks;
            $buckets[$key]['total_leads'] += (int) $row->total_leads;
            $buckets[$key]['total_conversions'] += (int) $row->total_conversions;
            $buckets[$key]['total_purchases'] += (int) $row->total_purchases;
            $buckets[$key]['total_installs'] += (int) $row->total_installs;

            if ($row->country) {
                $buckets[$key]['countries'][$row->country] = true;
            }
        }

        // Build final sub-rows
        $grouped = [];
        foreach ($buckets as $bucket) {
            if ($bucket['total_spend'] <= 0) {
                continue;
            }

            $s = $bucket['total_spend'];
            $imp = $bucket['total_impressions'];
            $cl = $bucket['total_clicks'];
            $le = $bucket['total_leads'];
            $pu = $bucket['total_purchases'];
            $ins = $bucket['total_installs'];

            $countries = array_keys($bucket['countries']);
            $country = count($countries) === 1 ? $countries[0] : (empty($countries) ? null : __('Mixed'));

            $grouped[$bucket['group_key']][] = [
                'group_key' => $bucket['group_key'],
                'category' => $bucket['category'],
                'sub_category' => $bucket['sub_category'],
                'funnel_stage' => $bucket['funnel_stage'],
                'objective' => $bucket['objective'],
                'country' => $country,
                'total_spend' => $s,
                'total_impressions' => $imp,
                'total_clicks' => $cl,
                'total_leads' => $le,
                'total_conversions' => $bucket['total_conversions'],
                'total_purchases' => $pu,
                'total_installs' => $ins,
                'ctr' => $imp > 0 ? ($cl / $imp) * 100 : 0,
                'cpc' => $cl > 0 ? $s / $cl : 0,
                'cpm' => $imp > 0 ? ($s / $imp) * 1000 : 0,
                'cvr' => $cl > 0 ? ($le / $cl) * 100 : 0,
                'cpl' => $le > 0 ? $s / $le : 0,
                'cpa' => $pu > 0 ? $s / $pu : 0,
                'cpi' => $ins > 0 ? $s / $ins : 0,
            ];
        }

        // Sort each group's sub-rows by spend descending
        foreach ($grouped as $key => &$subRows) {
            usort($subRows, fn($a, $b) => $b['total_spend'] <=> $a['total_spend']);
        }

        return $grouped;
    }

    /**
     * Load distinct objectives per group (industry or tenant).
     */
    private function loadObjectives(string $dateFrom, string $dateTo, array $filters = [], string $groupBy = 'industry'): array
    {
        $selectGroup = $groupBy === 'tenant_id' ? 'a.tenant_id' : 'a.industry';

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'a.id', '=', 'm.ad_account_id')
            ->select($selectGroup, 'm.objective')
            ->whereBetween('m.date', [$dateFrom, $dateTo])
            ->whereNotNull('m.objective')
            ->distinct();

        $this->applyMetricFiltersToBuilder($query, $dateFrom, $dateTo, $filters, 'm');

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->join('integrations as i', 'i.id', '=', 'a.integration_id')
                  ->whereIn('i.platform', $platforms);
        }

        if (!empty($filters['country'])) {
            $countries = is_array($filters['country']) ? $filters['country'] : [$filters['country']];
            $query->whereIn('a.country', $countries);
        }

        $rows = $query->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $groupBy === 'tenant_id' ? $row->tenant_id : ($row->industry ?? 'Uncategorized');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][$row->objective] = true;
        }

        // Convert to simple arrays
        foreach ($grouped as $key => $objectives) {
            $grouped[$key] = array_keys($objectives);
        }

        return $grouped;
    }

    /**
     * Load accounts with eager-loaded campaigns (no metrics).
     * Returns accounts that have metrics in the date range.
     */
    private function loadAllAccountsWithMetrics(string $dateFrom, string $dateTo, array $filters = [], bool $withTenant = false): Collection
    {
        $campaignColumns = [
            'id', 'ad_account_id', 'category', 'inherit_category_from_account',
            'sub_industry', 'funnel_stage', 'objective',
        ];

        $eagerLoads = [
            'adCampaigns' => function ($q) use ($campaignColumns) {
                $q->withoutGlobalScope(TenantScope::class)
                  ->select($campaignColumns);
            },
        ];

        if ($withTenant) {
            $eagerLoads[] = 'tenant';
        }

        $query = AdAccount::withoutGlobalScope(TenantScope::class)
            ->with($eagerLoads)
            ->whereHas('adMetrics', function ($q) use ($dateFrom, $dateTo, $filters) {
                $q->withoutGlobalScope(TenantScope::class);
                $this->applyMetricFilters($q, $dateFrom, $dateTo, $filters);
            });

        if (!empty($filters['platform'])) {
            $platforms = is_array($filters['platform']) ? $filters['platform'] : [$filters['platform']];
            $query->whereHas('integration', function ($q) use ($platforms) {
                $q->withoutGlobalScope(TenantScope::class);
                $q->whereIn('platform', $platforms);
            });
        }

        if (!empty($filters['country'])) {
            $countries = is_array($filters['country']) ? $filters['country'] : [$filters['country']];
            $query->whereIn('country', $countries);
        }

        return $query->get();
    }

    /**
     * Calculate dynamic industry benchmarks from pre-aggregated account metrics.
     * Groups accounts by industry and computes benchmark ranges per group.
     */
    private function calculateDynamicIndustryBenchmarks(
        string $dateFrom,
        string $dateTo,
        array $filters = [],
        ?array $accountAggregates = null
    ): array
    {
        $accountAggregates = $accountAggregates ?? $this->loadAccountAggregates($dateFrom, $dateTo, $filters);

        // Group aggregates by industry (need account → industry lookup)
        $accounts = AdAccount::withoutGlobalScope(TenantScope::class)
            ->select('id', 'industry')
            ->whereIn('id', array_keys($accountAggregates))
            ->get()
            ->keyBy('id');

        $grouped = [];
        foreach ($accountAggregates as $accountId => $metrics) {
            $industry = $accounts[$accountId]->industry ?? 'Uncategorized';
            if (!isset($grouped[$industry])) {
                $grouped[$industry] = [];
            }
            if ($metrics['total_spend'] > 0) {
                $grouped[$industry][] = $metrics;
            }
        }

        $benchmarks = [];
        foreach ($grouped as $industry => $allMetrics) {
            if (count($allMetrics) >= 1) {
                $benchmarks[$industry] = $this->calculateIndustryBenchmarkRanges($allMetrics);
            }
        }

        return $benchmarks;
    }

    /**
     * Remove statistical outliers using IQR method with domain-specific thresholds
     */
    private function removeOutliers(array $values, string $metric = ''): array
    {
        if (count($values) < 4) {
            return $values; // Need at least 4 values for IQR
        }

        sort($values);
        $count = count($values);

        // Calculate Q1 and Q3
        $q1Index = floor($count * 0.25);
        $q3Index = floor($count * 0.75);
        $q1 = $values[$q1Index];
        $q3 = $values[$q3Index];

        // Calculate IQR
        $iqr = $q3 - $q1;

        // Define bounds (1.5 × IQR is standard for outlier detection)
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);

        // Apply domain-specific minimum thresholds for cost metrics
        // These prevent unrealistic values that break predictions
        $domainMinThresholds = [
            'cpc' => 0.40,  // CPC below $0.40 is typically data quality issues, brand campaigns, or incomplete data
            'cpl' => 5.00,  // CPL below $5.00 is unrealistic for lead generation in most industries
            'cpm' => 1.00,  // CPM below $1.00 is unrealistic for performance campaigns
        ];

        if (isset($domainMinThresholds[$metric])) {
            $lowerBound = max($lowerBound, $domainMinThresholds[$metric]);
        }

        // Filter outliers
        $filtered = array_filter($values, function($value) use ($lowerBound, $upperBound) {
            return $value >= $lowerBound && $value <= $upperBound;
        });

        return array_values($filtered);
    }

    /**
     * Calculate benchmark ranges from actual account metrics data
     */
    private function calculateIndustryBenchmarkRanges(array $allMetrics): array
    {
        $benchmarks = [];
        $metrics = ['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'cpi'];

        foreach ($metrics as $metric) {
            $values = [];

            // Collect all non-zero values for this metric.
            // For cost-per-conversion metrics, require minimum volume so accounts with incidental
            // conversions from awareness spend don't distort the benchmark (e.g. a furniture brand
            // spending SAR 4M on reach that incidentally gets 6 form fills).
            $minDenominator = match ($metric) {
                'cpl' => 20,
                'cpa' => 10,
                'cpi' => 10,
                default => 0,
            };
            foreach ($allMetrics as $accountMetric) {
                $value = $accountMetric[$metric];
                if ($value > 0) {
                    if ($minDenominator > 0) {
                        $denomField = match ($metric) {
                            'cpl' => 'total_leads',
                            'cpa' => 'total_purchases',
                            'cpi' => 'total_installs',
                            default => null,
                        };
                        if ($denomField && ($accountMetric[$denomField] ?? 0) < $minDenominator) {
                            continue;
                        }
                    }
                    $values[] = $value;
                }
            }

            if (count($values) >= 1) {
                // Always apply domain-specific minimums, even before IQR (catches single-account outliers)
                $domainMin = match ($metric) {
                    'cpl', 'cpa' => 5.0,    // SAR 5 minimum — sub-SAR-5 CPL/CPA are micro-conversion artefacts
                    'cpc'        => 0.40,
                    'cpm'        => 1.00,
                    default      => 0.0,
                };
                if ($domainMin > 0) {
                    $values = array_values(array_filter($values, fn ($v) => $v >= $domainMin));
                }
                if (count($values) === 0) {
                    continue;
                }

                sort($values);
                $count = count($values);

                if ($count === 1) {
                    // Single account: use its value as the benchmark
                    $val = round($values[0], 2);
                    $benchmarks[$metric] = [
                        'min' => $val,
                        'max' => $val,
                        'avg' => $val,
                        'data_points' => 1,
                        'outliers_removed' => 0,
                        'range' => ['lowest' => $val, 'highest' => $val],
                        'calculation' => [
                            'method' => 'single_account',
                            'percentiles' => ['p25' => $val, 'p50' => $val, 'p75' => $val],
                            'sample_values' => [$val],
                            'formula' => ['min' => 'single data point', 'avg' => 'single data point', 'max' => 'single data point'],
                        ],
                    ];
                } else {
                    // Remove statistical outliers using IQR method with domain-specific thresholds
                    $originalCount = count($values);
                    $filteredValues = $this->removeOutliers($values, $metric);
                    $outlierCount = $originalCount - count($filteredValues);

                    // Use filtered values for benchmark calculation
                    $values = $filteredValues;
                    $count = count($values);

                    if ($count >= 1) {
                        // For very small samples (2–3), the index-based percentile formula collapses
                        // all three percentiles to the minimum value. Use explicit min/mean/max instead.
                        if ($count <= 3) {
                            $minVal = round($values[0], 2);
                            $maxVal = round($values[$count - 1], 2);
                            $avgVal = $count === 2
                                ? round(array_sum($values) / 2, 2)
                                : round($values[1], 2); // middle of 3
                            $p25 = $minVal;
                            $p50 = $avgVal;
                            $p75 = $maxVal;
                        } else {
                            // Standard percentile calculation for 4+ data points
                            $p25Index = max(0, floor($count * 0.25) - 1);
                            $p50Index = max(0, floor($count * 0.50) - 1);
                            $p75Index = max(0, floor($count * 0.75) - 1);
                            $minVal = round($values[$p25Index], 2);
                            $avgVal = round($values[$p50Index], 2);
                            $maxVal = round($values[$p75Index], 2);
                            $p25 = $minVal;
                            $p50 = $avgVal;
                            $p75 = $maxVal;
                        }

                        $benchmarks[$metric] = [
                            'min' => $minVal,
                            'max' => $maxVal,
                            'avg' => $avgVal,
                            'data_points' => $count,
                            'outliers_removed' => $outlierCount,
                            'range' => [
                                'lowest' => round(min($values), 2),
                                'highest' => round(max($values), 2),
                            ],
                            'calculation' => [
                                'method' => $count <= 3 ? 'min_mean_max_small_sample' : 'percentile_with_outlier_filtering',
                                'percentiles' => [
                                    'p25' => $p25,
                                    'p50' => $p50,
                                    'p75' => $p75,
                                ],
                                'sample_values' => array_slice($values, 0, min(5, $count)),
                                'formula' => [
                                    'min' => $count <= 3 ? 'minimum value (small sample)' : '25th percentile (good performance threshold)',
                                    'avg' => $count <= 3 ? 'mean / middle value (small sample)' : '50th percentile (median - typical performance)',
                                    'max' => $count <= 3 ? 'maximum value (small sample)' : '75th percentile (excellent performance threshold)',
                                ],
                            ],
                        ];
                    }
                }
            }
        }

        return $benchmarks;
    }

    /**
     * Get benchmark data for all accounts grouped by industry.
     * Uses SQL aggregation + 15-minute cache to avoid loading raw metrics.
     */
    public function getIndustryBenchmarks(string $dateFrom, string $dateTo, array $filters = []): array
    {
        $cacheKey = 'benchmarks:' . md5($dateFrom . $dateTo . json_encode($filters));

        return Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo, $filters) {
            $prevLimit = ini_get('memory_limit');
            ini_set('memory_limit', '1024M');

            try {
                // Load pre-aggregated metrics (~90 rows instead of ~89K)
                $accountAggregates = $this->loadAccountAggregates($dateFrom, $dateTo, $filters);

                // Load sub-rows via SQL aggregation
                $subRowAggregates = $this->loadSubRowAggregates($dateFrom, $dateTo, $filters, 'industry');

                // Load distinct objectives per industry
                $objectivesByIndustry = $this->loadObjectives($dateFrom, $dateTo, $filters, 'industry');

                // Load accounts for dimensional fields and campaign counts
                $allAccounts = $this->loadAllAccountsWithMetrics($dateFrom, $dateTo, $filters);
                $grouped = $allAccounts->groupBy(fn ($a) => $a->industry ?? 'Uncategorized');

                // Apply category/sub-category filters
                $grouped = $this->applyCategoryFilters($accountAggregates, $subRowAggregates, $objectivesByIndustry, $allAccounts, $grouped, $filters, 'industry');

                // Calculate dynamic benchmarks from pre-aggregated data
                $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo, $filters, $accountAggregates);

                $results = [];

                foreach ($industryBenchmarks as $industry => $benchmarks) {
                    $industryAccounts = $grouped->get($industry, collect());

                    if ($industryAccounts->isEmpty()) {
                        continue;
                    }

                    // Get pre-aggregated metrics for this industry's accounts
                    $industryAccountIds = $industryAccounts->pluck('id')->toArray();
                    $industryAggregates = array_intersect_key($accountAggregates, array_flip($industryAccountIds));

                    // Calculate actual performance from pre-aggregated data
                    $actualMetrics = $this->calculateIndustryActualMetrics($industryAggregates);

                    // Compare with dynamic benchmarks
                    $comparison = [];
                    foreach ($benchmarks as $metric => $benchmark) {
                        $actualValue = $actualMetrics[$metric] ?? null;
                        $performanceScore = $this->calculatePerformanceScore($actualValue, $benchmark, $metric);
                        $status = $this->getPerformanceStatus($actualValue, $benchmark, $metric);

                        $comparison[$metric] = [
                            'actual' => $actualValue,
                            'benchmark' => $benchmark,
                            'performance' => $performanceScore,
                            'status' => $status,
                            'calculation_details' => [
                                'score_calculation' => $this->getScoreCalculationExplanation($actualValue, $benchmark, $metric, $performanceScore),
                                'status_thresholds' => [
                                    'excellent' => '80-100 points',
                                    'good' => '60-79 points',
                                    'average' => '40-59 points',
                                    'below_average' => '20-39 points',
                                    'poor' => '0-19 points'
                                ]
                            ]
                        ];
                    }

                    $objectives = $objectivesByIndustry[$industry] ?? [];
                    $objectiveValue = count($objectives) === 1 ? ucfirst($objectives[0]) : (empty($objectives) ? null : __('Mixed'));

                    $results[$industry] = [
                        'industry' => $this->resolveIndustryName($industry),
                        'industry_slug' => $industry,
                        'accounts_count' => $industryAccounts->count(),
                        'campaigns_count' => $industryAccounts->sum(fn($a) => $a->adCampaigns ? $a->adCampaigns->count() : 0),
                        'account_names' => $industryAccounts->pluck('account_name')->toArray(),
                        'total_spend' => $actualMetrics['total_spend'] ?? 0,
                        'total_impressions' => $actualMetrics['total_impressions'] ?? 0,
                        'total_clicks' => $actualMetrics['total_clicks'] ?? 0,
                        'total_leads' => $actualMetrics['total_leads'] ?? 0,
                        'total_conversions' => $actualMetrics['total_conversions'] ?? 0,
                        'total_installs' => $actualMetrics['total_installs'] ?? 0,
                        'country' => $this->aggregateDimensionalField($industryAccounts, 'country'),
                        'category' => $this->aggregateDimensionalField($industryAccounts, 'category'),
                        'objective' => $objectiveValue,
                        'metrics' => $comparison,
                        'sub_rows' => $this->buildSubRows($industry, $subRowAggregates),
                        'benchmark_info' => [
                            'calculation_method' => 'dynamic',
                            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                        ]
                    ];
                }

                return $results;
            } finally {
                ini_set('memory_limit', $prevLimit);
            }
        });
    }

    /**
     * Get global benchmarks computed across all synced accounts regardless of industry or client.
     * Uses the same dynamic percentile logic as industry benchmarks.
     */
    public function getGlobalBenchmarks(string $dateFrom, string $dateTo, array $filters = []): array
    {
        $cacheKey = 'benchmarks:global:' . md5($dateFrom . $dateTo . json_encode($filters));

        return Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo, $filters) {
            $accountAggregates = $this->loadAccountAggregates($dateFrom, $dateTo, $filters);
            return $this->calculateIndustryBenchmarkRanges(array_values($accountAggregates));
        });
    }

    /**
     * Get benchmark data for all accounts grouped by client (tenant).
     * Uses SQL aggregation + 15-minute cache.
     */
    public function getClientBenchmarks(string $dateFrom, string $dateTo, array $filters = []): array
    {
        $cacheKey = 'benchmarks:client:' . md5($dateFrom . $dateTo . json_encode($filters));

        return Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo, $filters) {
            $prevLimit = ini_get('memory_limit');
            ini_set('memory_limit', '1024M');

            try {
                // Load pre-aggregated metrics (~90 rows instead of ~89K)
                $accountAggregates = $this->loadAccountAggregates($dateFrom, $dateTo, $filters);

                // Load sub-rows via SQL aggregation (grouped by tenant_id)
                $subRowAggregates = $this->loadSubRowAggregates($dateFrom, $dateTo, $filters, 'tenant_id');

                // Load distinct objectives per tenant
                $objectivesByTenant = $this->loadObjectives($dateFrom, $dateTo, $filters, 'tenant_id');

                // Load accounts with tenant for dimensional fields and campaign counts
                $allAccounts = $this->loadAllAccountsWithMetrics($dateFrom, $dateTo, $filters, withTenant: true);
                $grouped = $allAccounts->groupBy('tenant_id');

                // Apply category/sub-category filters
                $grouped = $this->applyCategoryFilters($accountAggregates, $subRowAggregates, $objectivesByTenant, $allAccounts, $grouped, $filters, 'tenant_id');

                $results = [];

                foreach ($grouped as $tenantId => $accounts) {
                    $tenant = $accounts->first()->tenant;
                    $clientName = $tenant ? $tenant->name : 'Unassigned';

                    // Get pre-aggregated metrics for this tenant's accounts
                    $tenantAccountIds = $accounts->pluck('id')->toArray();
                    $tenantAggregates = array_intersect_key($accountAggregates, array_flip($tenantAccountIds));

                    $actualMetrics = $this->calculateIndustryActualMetrics($tenantAggregates);

                    if ($actualMetrics['total_spend'] <= 0) {
                        continue;
                    }

                    $metrics = ['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'cpi'];
                    $comparison = [];
                    foreach ($metrics as $metric) {
                        $actualValue = $actualMetrics[$metric] ?? null;
                        $comparison[$metric] = [
                            'actual' => $actualValue,
                            'benchmark' => null,
                            'performance' => null,
                            'status' => 'no_benchmark',
                        ];
                    }

                    $objectives = $objectivesByTenant[$tenantId] ?? [];
                    $objectiveValue = count($objectives) === 1 ? ucfirst($objectives[0]) : (empty($objectives) ? null : __('Mixed'));

                    $results[$clientName] = [
                        'industry' => $clientName,
                        'accounts_count' => $accounts->count(),
                        'campaigns_count' => $accounts->sum(fn($a) => $a->adCampaigns ? $a->adCampaigns->count() : 0),
                        'account_names' => $accounts->pluck('account_name')->toArray(),
                        'total_spend' => $actualMetrics['total_spend'] ?? 0,
                        'total_impressions' => $actualMetrics['total_impressions'] ?? 0,
                        'total_clicks' => $actualMetrics['total_clicks'] ?? 0,
                        'total_leads' => $actualMetrics['total_leads'] ?? 0,
                        'total_conversions' => $actualMetrics['total_conversions'] ?? 0,
                        'total_installs' => $actualMetrics['total_installs'] ?? 0,
                        'country' => $this->aggregateDimensionalField($accounts, 'country'),
                        'category' => $this->aggregateDimensionalField($accounts, 'category'),
                        'objective' => $objectiveValue,
                        'metrics' => $comparison,
                        'sub_rows' => $this->buildSubRows($tenantId, $subRowAggregates),
                        'benchmark_info' => [
                            'calculation_method' => 'client_aggregation',
                            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                        ],
                    ];
                }

                return $results;
            } finally {
                ini_set('memory_limit', $prevLimit);
            }
        });
    }

    /**
     * Get benchmark comparison for a specific account
     */
    public function getAccountBenchmark(int $accountId, string $dateFrom, string $dateTo): array
    {
        $account = AdAccount::withoutGlobalScope(TenantScope::class)
            ->with(['adMetrics' => function($query) use ($dateFrom, $dateTo) {
                $query->withoutGlobalScope(TenantScope::class)
                    ->whereBetween('date', [$dateFrom, $dateTo]);
            }])->findOrFail($accountId);

        // Enforce tenant isolation: non-super-admins can only benchmark their own accounts
        $isSuperAdmin = app()->bound('is_super_admin') && app('is_super_admin');
        $currentTenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        if (!$isSuperAdmin && $account->tenant_id !== $currentTenantId) {
            abort(403, 'Unauthorized');
        }

        if (!$account->industry) {
            throw new \Exception('Account industry not set');
        }

        // Calculate dynamic benchmarks from actual data
        $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo);
        
        if (!isset($industryBenchmarks[$account->industry])) {
            throw new \Exception('Not enough data to calculate benchmarks for ' . $account->industry . ' industry');
        }

        $benchmarks = $industryBenchmarks[$account->industry];
        $actualMetrics = $this->calculateAccountActualMetrics($account->adMetrics, $account);

        $comparison = [];
        foreach ($benchmarks as $metric => $benchmark) {
            $actualValue = $actualMetrics[$metric] ?? null;
            $performanceScore = $this->calculatePerformanceScore($actualValue, $benchmark, $metric);
            $status = $this->getPerformanceStatus($actualValue, $benchmark, $metric);
            
            $comparison[$metric] = [
                'actual' => $actualValue,
                'benchmark' => $benchmark,
                'performance' => $performanceScore,
                'status' => $status,
                'calculation_details' => [
                    'score_calculation' => $this->getScoreCalculationExplanation($actualValue, $benchmark, $metric, $performanceScore),
                    'status_thresholds' => [
                        'excellent' => '80-100 points',
                        'good' => '60-79 points', 
                        'average' => '40-59 points',
                        'below_average' => '20-39 points',
                        'poor' => '0-19 points'
                    ]
                ]
            ];
        }

        return [
            'account_id' => $account->id,
            'account_name' => $account->account_name,
            'industry' => $account->industry,
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_spend' => $actualMetrics['total_spend'] ?? 0,
            'total_impressions' => $actualMetrics['total_impressions'] ?? 0,
            'total_clicks' => $actualMetrics['total_clicks'] ?? 0,
            'total_leads' => $actualMetrics['total_leads'] ?? 0,
            'metrics' => $comparison,
            'benchmark_info' => [
                'calculation_method' => 'dynamic',
                'data_points' => array_sum(array_column($benchmarks, 'data_points')),
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            ]
        ];
    }

    /**
     * Get overall performance summary across all industries.
     * Accepts pre-computed industry data to avoid re-querying.
     */
    public function getOverallBenchmarkSummary(string $dateFrom, string $dateTo, ?array $industryBenchmarks = null): array
    {
        $industryBenchmarks = $industryBenchmarks ?? $this->getIndustryBenchmarks($dateFrom, $dateTo);

        $summary = [
            'total_industries' => count($industryBenchmarks),
            'total_accounts' => 0,
            'total_spend' => 0,
            'best_performing' => [],
            'needs_improvement' => [],
        ];

        $performanceScores = [];

        foreach ($industryBenchmarks as $industry => $data) {
            $summary['total_accounts'] += $data['accounts_count'];
            $summary['total_spend'] += $data['total_spend'];

            $scores = [];
            foreach ($data['metrics'] as $metric => $metricData) {
                if ($metricData['performance'] !== null) {
                    $scores[] = $metricData['performance'];
                }
            }

            if (!empty($scores)) {
                $avgScore = array_sum($scores) / count($scores);
                $performanceScores[$industry] = [
                    'industry' => $industry,
                    'score' => $avgScore,
                    'accounts_count' => $data['accounts_count'],
                ];
            }
        }

        uasort($performanceScores, fn($a, $b) => $b['score'] <=> $a['score']);

        $summary['best_performing'] = array_slice($performanceScores, 0, 3);
        $summary['needs_improvement'] = array_slice(array_reverse($performanceScores), 0, 3);

        return $summary;
    }

    /**
     * Calculate actual metrics for a collection of accounts or pre-aggregated metrics.
     */
    private function calculateIndustryActualMetrics(Collection|array $accountsOrAggregates): array
    {
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalLeads = 0;
        $totalConversions = 0;
        $totalPurchases = 0;
        $totalInstalls = 0;

        if (is_array($accountsOrAggregates)) {
            foreach ($accountsOrAggregates as $metrics) {
                $totalSpend += $metrics['total_spend'] ?? 0;
                $totalImpressions += $metrics['total_impressions'] ?? 0;
                $totalClicks += $metrics['total_clicks'] ?? 0;
                $totalLeads += $metrics['total_leads'] ?? 0;
                $totalConversions += $metrics['total_conversions'] ?? 0;
                $totalPurchases += $metrics['total_purchases'] ?? 0;
                $totalInstalls += $metrics['total_installs'] ?? 0;
            }
        } else {
            foreach ($accountsOrAggregates as $account) {
                $metrics = $account->adMetrics;

                $accountCurrency = $account->account_config['currency'] ?? $account->currency ?? 'USD';
                $accountSpend = $this->currencyService->convertToSAR((float) $metrics->sum('spend'), $accountCurrency);
                $totalSpend += $accountSpend;

                $totalImpressions += $metrics->sum('impressions');
                $totalClicks += $metrics->sum('clicks');
                $totalLeads += $metrics->sum('leads');
                $totalConversions += $metrics->sum('conversions');
                $totalPurchases += $metrics->sum('purchases');
                $totalInstalls += $metrics->sum('app_installs');
            }
        }

        return [
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'total_conversions' => $totalConversions,
            'total_purchases' => $totalPurchases,
            'total_installs' => $totalInstalls,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'cvr' => $totalClicks > 0 ? ($totalLeads / $totalClicks) * 100 : 0,
            'cpl' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
            'cpa' => $totalPurchases > 0 ? $totalSpend / $totalPurchases : 0,
            'cpi' => $totalInstalls > 0 ? $totalSpend / $totalInstalls : 0,
        ];
    }

    /**
     * Calculate actual metrics for a single account.
     * Accepts either a Collection of metrics (legacy) or a pre-aggregated array.
     */
    private function calculateAccountActualMetrics(Collection|array $metrics, ?AdAccount $account = null): array
    {
        if (is_array($metrics)) {
            return $metrics;
        }

        $rawSpend = $metrics->sum('spend');
        $accountCurrency = $account ? ($account->account_config['currency'] ?? $account->currency ?? 'USD') : 'USD';
        $totalSpend = $this->currencyService->convertToSAR((float) $rawSpend, $accountCurrency);

        $totalImpressions = $metrics->sum('impressions');
        $totalClicks = $metrics->sum('clicks');
        $totalLeads = $metrics->sum('leads');
        $totalConversions = $metrics->sum('conversions');
        $totalPurchases = $metrics->sum('purchases');
        $totalInstalls = $metrics->sum('app_installs');

        return [
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'total_conversions' => $totalConversions,
            'total_purchases' => $totalPurchases,
            'total_installs' => $totalInstalls,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'cvr' => $totalClicks > 0 ? ($totalLeads / $totalClicks) * 100 : 0,
            'cpl' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
            'cpa' => $totalPurchases > 0 ? $totalSpend / $totalPurchases : 0,
            'cpi' => $totalInstalls > 0 ? $totalSpend / $totalInstalls : 0,
        ];
    }

    /**
     * Aggregate a dimensional field across grouped accounts.
     * Returns the value when all accounts agree, "Mixed" when they differ, null when empty.
     */
    private function aggregateDimensionalField(Collection $accounts, string $field): ?string
    {
        $values = $accounts->pluck($field)->filter()->unique();
        if ($values->isEmpty()) return null;
        if ($values->count() === 1) return $values->first();
        return __('Mixed');
    }

    /**
     * Build sub-rows for an industry/client group.
     * Delegates to SQL-level aggregation for performance.
     */
    private function buildSubRows(string $groupKey, array $allSubRows): array
    {
        return $allSubRows[$groupKey] ?? [];
    }

    /**
     * Calculate performance score (0-100) based on how the actual compares to benchmark
     */
    private function calculatePerformanceScore(?float $actual, array $benchmark, string $metric = ''): ?float
    {
        if ($actual === null) {
            return null;
        }

        $min = $benchmark['min'];
        $max = $benchmark['max'];
        $avg = $benchmark['avg'];

        // Guard against division by zero when all benchmark values are identical
        if ($max == $min) {
            return $actual == $max ? 100 : ($actual > $max ? 100 : 0);
        }

        // For metrics where lower is better (CPC, CPM, CPL)
        $lowerIsBetterMetrics = ['cpc', 'cpm', 'cpl', 'cpa', 'cpi'];
        
        if (in_array($metric, $lowerIsBetterMetrics)) {
            if ($actual <= $min) return 100; // Excellent
            if ($actual >= $max) return 0;   // Poor
            // Linear interpolation (inverted)
            return 100 - (($actual - $min) / ($max - $min)) * 100;
        } else {
            // For metrics where higher is better (CTR, CVR)
            if ($actual >= $max) return 100; // Excellent
            if ($actual <= $min) return 0;   // Poor
            // Linear interpolation
            return (($actual - $min) / ($max - $min)) * 100;
        }
    }

    /**
     * Get performance status based on score
     */
    private function getPerformanceStatus(?float $actual, array $benchmark, string $metric = ''): string
    {
        if ($actual === null) {
            return 'no_data';
        }

        $score = $this->calculatePerformanceScore($actual, $benchmark, $metric);
        
        if ($score === null) return 'no_data';
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        if ($score >= 20) return 'below_average';
        return 'poor';
    }

    /**
     * Get available industries with benchmark data
     */
    public function getAvailableIndustries(): array
    {
        return AdAccount::withoutGlobalScope(TenantScope::class)
            ->select('industry')
            ->whereNotNull('industry')
            ->whereHas('adMetrics', function ($q) {
                $q->withoutGlobalScope(TenantScope::class);
            })
            ->groupBy('industry')
            ->pluck('industry')
            ->toArray();
    }

    /**
     * Get benchmark data for a specific industry
     */
    public function getIndustryBenchmarkData(string $industry): ?array
    {
        // For now, calculate dynamic benchmarks for the last 90 days
        $dateTo = now()->toDateString();
        $dateFrom = now()->subDays(90)->toDateString();
        
        $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo);
        
        return $industryBenchmarks[$industry] ?? null;
    }

    /**
     * Get detailed explanation of how the performance score was calculated
     */
    private function getScoreCalculationExplanation(?float $actual, array $benchmark, string $metric, ?float $score): array
    {
        if ($actual === null || $score === null) {
            return [
                'explanation' => 'No data available for calculation',
                'formula' => 'N/A'
            ];
        }

        $min = $benchmark['min'];
        $max = $benchmark['max'];
        $avg = $benchmark['avg'];
        
        $lowerIsBetterMetrics = ['cpc', 'cpm', 'cpl', 'cpa', 'cpi'];
        $isLowerBetter = in_array($metric, $lowerIsBetterMetrics);
        
        $unit = '';
        if (in_array($metric, ['cpc', 'cpl', 'cpa', 'cpi'])) {
            $unit = '$';
        } elseif (in_array($metric, ['ctr', 'cvr'])) {
            $unit = '%';
        } elseif ($metric === 'cpm') {
            $unit = '$';
        }

        if ($isLowerBetter) {
            // For CPC, CPM, CPL - lower values are better
            $explanation = "Lower is better for {$metric}. ";
            
            if ($actual <= $min) {
                $explanation .= "Your {$unit}{$actual} is at or below the 25th percentile ({$unit}{$min}), which is excellent.";
                $formula = "Score = 100 (excellent performance)";
            } elseif ($actual >= $max) {
                $explanation .= "Your {$unit}{$actual} is at or above the 75th percentile ({$unit}{$max}), which needs improvement.";
                $formula = "Score = 0 (poor performance)";
            } else {
                $explanation .= "Your {$unit}{$actual} is between the 25th percentile ({$unit}{$min}) and 75th percentile ({$unit}{$max}).";
                $formula = "Score = 100 - (({$actual} - {$min}) / ({$max} - {$min})) × 100 = " . round($score, 1);
            }
        } else {
            // For CTR, CVR - higher values are better
            $explanation = "Higher is better for {$metric}. ";
            
            if ($actual >= $max) {
                $explanation .= "Your {$unit}{$actual} is at or above the 75th percentile ({$unit}{$max}), which is excellent.";
                $formula = "Score = 100 (excellent performance)";
            } elseif ($actual <= $min) {
                $explanation .= "Your {$unit}{$actual} is at or below the 25th percentile ({$unit}{$min}), which needs improvement.";
                $formula = "Score = 0 (poor performance)";
            } else {
                $explanation .= "Your {$unit}{$actual} is between the 25th percentile ({$unit}{$min}) and 75th percentile ({$unit}{$max}).";
                $formula = "Score = (({$actual} - {$min}) / ({$max} - {$min})) × 100 = " . round($score, 1);
            }
        }

        return [
            'explanation' => $explanation,
            'formula' => $formula,
            'benchmark_context' => [
                'your_value' => $unit . $actual,
                'industry_25th_percentile' => $unit . $min,
                'industry_median' => $unit . $avg,
                'industry_75th_percentile' => $unit . $max,
                'interpretation' => $isLowerBetter ? 'Lower values are better' : 'Higher values are better'
            ]
        ];
    }

    /**
     * Calculate expected results based on spend and industry benchmarks.
     * Accepts optional filters (platform, country, funnel_stage, user_journey, etc.)
     * to narrow the benchmark dataset.
     */
    public function calculateExpectedResults(float $spend, string $industry, string $objective = 'leads', array $filters = []): array
    {
        $result = $this->buildPredictions($spend, $industry, $objective, $filters);
        $result['input']['mode'] = 'budget_to_results';

        return $result;
    }

    /**
     * Calculate the budget required to hit a target number of results.
     * Uses the average scenario yield to recommend a budget, then returns
     * predictions for that budget plus a scenario-based budget range.
     */
    public function calculateRequiredBudget(float $targetResults, string $industry, string $objective = 'leads', array $filters = []): array
    {
        // Use a reference spend to determine the yield per scenario.
        $referenceSpend = 10000;
        $referenceResult = $this->buildPredictions($referenceSpend, $industry, $objective, $filters);

        $budgets = [];
        foreach ($referenceResult['predictions'] as $scenario => $prediction) {
            $primaryValue = (float) ($prediction['primary_result']['value'] ?? 0);
            $yield = $primaryValue > 0 ? ($primaryValue / $referenceSpend) : 0;

            if ($yield <= 0) {
                $budgets[$scenario] = null;
                continue;
            }

            $budgets[$scenario] = (float) ($targetResults / $yield);
        }

        $recommendedSpend = $budgets['average'] ?? null;

        if ($recommendedSpend === null || $recommendedSpend <= 0) {
            throw new \Exception("Not enough data to calculate a budget for the target in {$industry} industry");
        }

        $result = $this->buildPredictions($recommendedSpend, $industry, $objective, $filters);
        $result['input']['mode'] = 'target_to_budget';
        $result['input']['target'] = $targetResults;
        $result['budget'] = [
            'recommended' => $recommendedSpend,
            'poor' => $budgets['poor'],
            'average' => $budgets['average'],
            'good' => $budgets['good'],
            'excellent' => $budgets['excellent'],
        ];
        $result['disclaimers'][] = 'The recommended budget is based on the average performance scenario.';

        return $result;
    }

    /**
     * Calculate predictions using global (external) benchmark data from the
     * industry_benchmarks table. Returns null when no global data is available
     * for the selected industry/platform combination.
     */
    public function calculateExpectedResultsFromGlobalBenchmarks(float $spend, string $industry, string $objective = 'leads', array $filters = []): ?array
    {
        $platform = !empty($filters['platform']) ? (is_array($filters['platform']) ? $filters['platform'][0] : $filters['platform']) : 'all';

        $industries = $this->getIndustryAliases($industry);

        $metrics = [];
        foreach ($industries as $candidate) {
            $metrics = IndustryBenchmark::forIndustry($candidate)
                ->forPlatform($platform)
                ->pluck('percentile_50', 'metric')
                ->toArray();

            if (!empty($metrics)) {
                break;
            }
        }

        if (empty($metrics)) {
            foreach ($industries as $candidate) {
                $metrics = IndustryBenchmark::forIndustry($candidate)
                    ->pluck('percentile_50', 'metric')
                    ->toArray();

                if (!empty($metrics)) {
                    break;
                }
            }
        }

        if (empty($metrics)) {
            return null;
        }

        $benchmarks = [];
        foreach (['ctr', 'cpc', 'cpm', 'cvr', 'cpl'] as $metric) {
            $benchmarks[$metric] = ['avg' => $metrics[$metric] ?? $this->getDefaultBenchmarkValue($metric)];
        }

        $scenarios = [
            'poor' => ['ctr' => 0.3, 'cpc' => 1.7, 'cpm' => 1.7, 'cvr' => 0.3, 'cpl' => 1.7],
            'average' => ['ctr' => 0.65, 'cpc' => 1.3, 'cpm' => 1.3, 'cvr' => 0.65, 'cpl' => 1.3],
            'good' => ['ctr' => 0.85, 'cpc' => 0.7, 'cpm' => 0.7, 'cvr' => 0.85, 'cpl' => 0.7],
            'excellent' => ['ctr' => 1.2, 'cpc' => 0.5, 'cpm' => 0.5, 'cvr' => 1.2, 'cpl' => 0.5],
        ];

        $predictions = [];
        foreach ($scenarios as $scenario => $multipliers) {
            $predictions[$scenario] = $this->calculateScenarioPrediction($spend, $benchmarks, $multipliers, $objective);
        }

        return [
            'source' => 'global_benchmarks',
            'input' => [
                'spend' => $spend,
                'industry' => $industry,
                'objective' => $objective,
                'filters' => $filters,
            ],
            'predictions' => $predictions,
        ];
    }

    /**
     * Fallback benchmark values when a metric is missing.
     */
    private function getDefaultBenchmarkValue(string $metric): float
    {
        return match ($metric) {
            'ctr' => 1.5,
            'cpc' => 1.0,
            'cpm' => 10.0,
            'cvr' => 3.0,
            'cpl' => 30.0,
            default => 1.0,
        };
    }

    /**
     * Get industry aliases that should be treated as the same benchmark group.
     * (e.g. retail / ecommerce / e_commerce_retail are aggregated together.)
     */
    private function getIndustryAliases(string $industry): array
    {
        return match ($industry) {
            'e_commerce_retail', 'ecommerce', 'retail' => ['e_commerce_retail', 'ecommerce', 'retail'],
            default => [$industry],
        };
    }

    /**
     * Build predictions for a given spend.
     */
    private function buildPredictions(float $spend, string $industry, string $objective, array $filters): array
    {
        // Get industry benchmarks (use broader date range to ensure data)
        $dateTo = now()->toDateString();
        $dateFrom = now()->subYear()->toDateString();

        // Use all campaign data — awareness/traffic campaigns with no leads already get CPL=0
        // which is excluded by the domain minimum (≥ SAR 10), so no objective filter needed.
        $benchmarkFilters = $filters;
        $objectiveFiltered = false;

        $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo, $benchmarkFilters);

        $aliases = $this->getIndustryAliases($industry);

        $benchmarks = null;
        $matchedIndustry = null;
        foreach ($aliases as $alias) {
            if (isset($industryBenchmarks[$alias])) {
                $benchmarks = $industryBenchmarks[$alias];
                $matchedIndustry = $alias;
                break;
            }
        }

        $source = 'dynamic';
        $dataPoints = $benchmarks ? array_sum(array_column($benchmarks, 'data_points')) : 0;
        // Track conversion-metric data points separately for quality warnings
        $cplDataPoints = $benchmarks['cpl']['data_points'] ?? 0;
        $cvrDataPoints = $benchmarks['cvr']['data_points'] ?? 0;
        $conversionDataPoints = max($cplDataPoints, $cvrDataPoints);

        // Calculate predictions for different performance scenarios
        $predictions = [
            'poor' => [],
            'average' => [],
            'good' => [],
            'excellent' => []
        ];

        if ($benchmarks !== null) {
            // Define scenario multipliers based on performance levels
            $scenarios = [
                'poor' => ['ctr' => 0.3, 'cpc' => 1.7, 'cpm' => 1.7, 'cvr' => 0.3, 'cpl' => 1.7],      // Worse than min
                'average' => ['ctr' => 0.65, 'cpc' => 1.3, 'cpm' => 1.3, 'cvr' => 0.65, 'cpl' => 1.3],   // Between min-avg
                'good' => ['ctr' => 0.85, 'cpc' => 0.7, 'cpm' => 0.7, 'cvr' => 0.85, 'cpl' => 0.7],      // Between avg-max
                'excellent' => ['ctr' => 1.2, 'cpc' => 0.5, 'cpm' => 0.5, 'cvr' => 1.2, 'cpl' => 0.5],   // Better than max
            ];

            foreach ($scenarios as $scenario => $multipliers) {
                $prediction = $this->calculateScenarioPrediction($spend, $benchmarks, $multipliers, $objective);
                $predictions[$scenario] = $prediction;
            }
        } else {
            throw new \Exception("Not enough data from your campaigns to calculate predictions for the {$industry} industry. Please run more campaigns or select a different industry.");
        }

        return [
            'input' => [
                'spend' => $spend,
                'industry' => $industry,
                'objective' => $objective,
                'filters' => $filters,
            ],
            'predictions' => $predictions,
            'benchmark_info' => [
                'calculation_method' => $source,
                'objective_filtered' => $objectiveFiltered,
                'data_points' => $dataPoints,
                'conversion_data_points' => $conversionDataPoints,
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            ],
            'disclaimers' => [
                'These are estimates based on industry averages from your actual account data',
                'Actual results may vary based on targeting, creative quality, competition, and market conditions',
                'Performance scenarios represent different levels of campaign optimization'
            ]
        ];
    }

    /**
     * Calculate prediction for a specific performance scenario
     */
    private function calculateScenarioPrediction(float $spend, array $benchmarks, array $multipliers, string $objective): array
    {
        // Get benchmark values
        $ctr = isset($benchmarks['ctr']) ? $benchmarks['ctr']['avg'] * $multipliers['ctr'] : 1.5;
        $cpc = isset($benchmarks['cpc']) ? $benchmarks['cpc']['avg'] * $multipliers['cpc'] : 1.0;
        $cpm = isset($benchmarks['cpm']) ? $benchmarks['cpm']['avg'] * $multipliers['cpm'] : 10.0;
        $cvr = isset($benchmarks['cvr']) ? $benchmarks['cvr']['avg'] * $multipliers['cvr'] : 3.0;

        // Linearly interpolate CPL/CPA across the three percentiles so all four scenarios diverge.
        // Multiplier mapping: 0.5 (excellent) → p25 (min), 1.0 (midpoint) → p50 (avg), 1.7 (poor) → p75 (max).
        $cplMult = $multipliers['cpl'];
        if (isset($benchmarks['cpl'])) {
            $cplMin = $benchmarks['cpl']['min'] ?? null;
            $cplAvg = $benchmarks['cpl']['avg'];
            $cplMax = $benchmarks['cpl']['max'] ?? null;
            if ($cplMin !== null && $cplMax !== null) {
                if ($cplMult <= 1.0) {
                    $t = max(0.0, min(1.0, ($cplMult - 0.5) / 0.5));
                    $cpl = $cplMin + ($cplAvg - $cplMin) * $t;
                } else {
                    $t = max(0.0, min(1.0, ($cplMult - 1.0) / 0.7));
                    $cpl = $cplAvg + ($cplMax - $cplAvg) * $t;
                }
            } else {
                $cpl = $cplAvg * $cplMult;
            }
        } else {
            $cpl = 30.0;
        }
        if (isset($benchmarks['cpa'])) {
            $cpaMin = $benchmarks['cpa']['min'] ?? null;
            $cpaAvg = $benchmarks['cpa']['avg'];
            $cpaMax = $benchmarks['cpa']['max'] ?? null;
            if ($cpaMin !== null && $cpaMax !== null) {
                if ($cplMult <= 1.0) {
                    $t = max(0.0, min(1.0, ($cplMult - 0.5) / 0.5));
                    $cpa = $cpaMin + ($cpaAvg - $cpaMin) * $t;
                } else {
                    $t = max(0.0, min(1.0, ($cplMult - 1.0) / 0.7));
                    $cpa = $cpaAvg + ($cpaMax - $cpaAvg) * $t;
                }
            } else {
                $cpa = $cpaAvg * $cplMult;
            }
        } else {
            $cpa = 0.0;
        }

        // Calculate basic metrics
        $expectedImpressions = $cpm > 0 ? ($spend / $cpm) * 1000 : 0;
        $expectedClicks = max($expectedImpressions * ($ctr / 100), $spend / $cpc);

        // Calculate objective-specific results
        $objectiveResults = $this->calculateObjectiveSpecificResults(
            $spend, $expectedImpressions, $expectedClicks, $cvr, $cpl, $cpa, $objective
        );

        return [
            'impressions' => [
                'value' => round($expectedImpressions),
                'calculation' => "($spend ÷ $" . round($cpm, 2) . ") × 1,000 = " . number_format(round($expectedImpressions))
            ],
            'clicks' => [
                'value' => round($expectedClicks),
                'calculation' => number_format(round($expectedImpressions)) . " impressions × " . round($ctr, 2) . "% CTR = " . number_format(round($expectedClicks))
            ],
            'primary_result' => $objectiveResults['primary'],
            'secondary_results' => $objectiveResults['secondary'],
            'metrics' => [
                'ctr' => round($ctr, 2) . '%',
                'cpc' => 'SAR ' . round($cpc, 2),
                'cpm' => 'SAR ' . round($cpm, 2),
                'cvr' => round($cvr, 2) . '%',
                'cpl' => 'SAR ' . round($cpl, 2),
                'cpa' => $cpa > 0 ? 'SAR ' . round($cpa, 2) : null,
            ],
            'cost_per_result' => [
                'cost_per_impression' => 'SAR ' . round($spend / max($expectedImpressions, 1), 4),
                'cost_per_click' => 'SAR ' . round($spend / max($expectedClicks, 1), 2),
                'cost_per_conversion' => $objectiveResults['cost_per_result'] ?? ('SAR ' . round($spend / max($objectiveResults['primary']['value'], 1), 2)),
            ],
            // Keep legacy fields for backward compatibility
            'leads' => $objectiveResults['primary'],
        ];
    }

    /**
     * Calculate results specific to the campaign objective
     */
    private function calculateObjectiveSpecificResults(float $spend, float $impressions, float $clicks, float $cvr, float $cpl, float $cpa, string $objective): array
    {
        switch ($objective) {
            case 'leads':
            case 'messages':
            case 'calls':
                // Prefer direct CPL calculation when CPL benchmark is available —
                // it's more reliable than the CPM→CTR→CVR chain for small datasets.
                if ($cpl > 0) {
                    $primaryValue = $spend / $cpl;
                    $calc = 'SAR ' . number_format($spend, 0) . ' ÷ SAR ' . round($cpl, 2) . ' CPL = ' . number_format(round($primaryValue));
                } else {
                    $primaryValue = $clicks * ($cvr / 100);
                    $calc = number_format(round($clicks)) . ' clicks × ' . round($cvr, 2) . '% CVR = ' . number_format(round($primaryValue));
                }
                return [
                    'primary' => ['value' => round($primaryValue), 'calculation' => $calc, 'label' => $this->getObjectiveLabel($objective)],
                    'secondary' => [],
                    'cost_per_result' => round($primaryValue) > 0 ? 'SAR ' . number_format(round($spend / $primaryValue, 2), 2) : null,
                ];

            case 'sales':
            case 'conversions':
            case 'catalog_sales':
                // Prefer direct CPA calculation when CPA benchmark is available.
                if ($cpa > 0) {
                    $primaryValue = $spend / $cpa;
                    $calc = 'SAR ' . number_format($spend, 0) . ' ÷ SAR ' . round($cpa, 2) . ' CPA = ' . number_format(round($primaryValue));
                } else {
                    $primaryValue = $clicks * ($cvr / 100);
                    $calc = number_format(round($clicks)) . ' clicks × ' . round($cvr, 2) . '% conversion rate = ' . number_format(round($primaryValue));
                }
                return [
                    'primary' => ['value' => round($primaryValue), 'calculation' => $calc, 'label' => $this->getObjectiveLabel($objective)],
                    'secondary' => [],
                    'cost_per_result' => round($primaryValue) > 0 ? 'SAR ' . number_format(round($spend / $primaryValue, 2), 2) : null,
                ];

            case 'traffic':
            case 'link_clicks':
                $ctrDisplay = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
                $cpc = $clicks > 0 ? $spend / $clicks : 0;
                return [
                    'primary' => [
                        'value' => round($clicks),
                        'calculation' => number_format(round($impressions)) . " impressions × " . $ctrDisplay . "% CTR = " . number_format(round($clicks)),
                        'label' => 'Website Visits',
                    ],
                    'secondary' => [],
                    'cost_per_result' => $cpc > 0 ? 'SAR ' . number_format(round($cpc, 2), 2) : null,
                ];

            case 'video_views':
                $videoViews = $clicks * 2.5; // Video views typically higher than clicks
                $cpv = $videoViews > 0 ? $spend / $videoViews : 0;
                return [
                    'primary' => [
                        'value' => round($videoViews),
                        'calculation' => number_format(round($clicks)) . " engagements × 2.5 view rate = " . number_format(round($videoViews)),
                        'label' => 'Video Views',
                    ],
                    'secondary' => [],
                    'cost_per_result' => $cpv > 0 ? 'SAR ' . number_format(round($cpv, 3), 3) : null,
                ];

            case 'engagement':
            case 'page_likes':
                $engagements = $impressions * 0.02; // 2% engagement rate
                $cpe = $engagements > 0 ? $spend / $engagements : 0;
                return [
                    'primary' => [
                        'value' => round($engagements),
                        'calculation' => number_format(round($impressions)) . " impressions × 2% engagement rate = " . number_format(round($engagements)),
                        'label' => $this->getObjectiveLabel($objective),
                    ],
                    'secondary' => [],
                    'cost_per_result' => $cpe > 0 ? 'SAR ' . number_format(round($cpe, 2), 2) : null,
                ];

            case 'awareness':
            case 'reach':
            case 'impressions':
                $reach = $impressions * 0.7; // Reach is typically 70% of impressions
                // CPM is the standard cost metric for awareness campaigns
                $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;
                return [
                    'primary' => [
                        'value' => round($reach),
                        'calculation' => number_format(round($impressions)) . " impressions × 70% reach rate = " . number_format(round($reach)),
                        'label' => 'People Reached',
                    ],
                    'secondary' => [],
                    'cost_per_result' => $cpm > 0 ? 'SAR ' . number_format(round($cpm, 2), 2) . ' CPM' : null,
                ];

            case 'app_installs':
            case 'app_events':
                $appActions = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($appActions),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% install rate = " . number_format(round($appActions)),
                        'label' => $this->getObjectiveLabel($objective),
                    ],
                    'secondary' => [],
                    'cost_per_result' => round($appActions) > 0 ? 'SAR ' . number_format(round($spend / $appActions, 2), 2) : null,
                ];

            default:
                // Default to leads calculation
                $primaryValue = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($primaryValue),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% CVR = " . number_format(round($primaryValue)),
                        'label' => 'Conversions',
                    ],
                    'secondary' => [],
                    'cost_per_result' => round($primaryValue) > 0 ? 'SAR ' . number_format(round($spend / $primaryValue, 2), 2) : null,
                ];
        }
    }

    /**
     * Get user-friendly label for campaign objective
     */
    private function getObjectiveLabel(string $objective): string
    {
        $labels = [
            'leads' => 'Leads',
            'messages' => 'Messages',
            'calls' => 'Phone Calls',
            'sales' => 'Sales',
            'conversions' => 'Conversions',
            'catalog_sales' => 'Catalog Sales',
            'store_visits' => 'Store Visits',
            'traffic' => 'Website Visits',
            'link_clicks' => 'Link Clicks',
            'engagement' => 'Engagements',
            'video_views' => 'Video Views',
            'page_likes' => 'Page Likes',
            'awareness' => 'People Reached',
            'reach' => 'People Reached',
            'impressions' => 'Impressions',
            'app_installs' => 'App Installs',
            'app_events' => 'App Events',
        ];

        return $labels[$objective] ?? 'Results';
    }

    /**
     * Apply category and sub-category filters to benchmark data structures.
     * Filters accounts, account aggregates, sub-rows, and objectives.
     */
    private function applyCategoryFilters(array &$accountAggregates, array &$subRowAggregates, array &$objectivesByGroup, Collection &$allAccounts, Collection $grouped, array $filters, string $groupBy = 'industry'): Collection
    {
        if (empty($filters['category']) && empty($filters['sub_category'])) {
            return $grouped;
        }

        $allAccounts = $allAccounts->filter(fn ($a) => $this->accountMatchesCategoryFilters($a, $filters));

        $grouped = $groupBy === 'tenant_id'
            ? $allAccounts->groupBy('tenant_id')
            : $allAccounts->groupBy(fn ($a) => $a->industry ?? 'Uncategorized');

        $filteredAccountIds = $allAccounts->pluck('id')->toArray();
        $accountAggregates = array_intersect_key($accountAggregates, array_flip($filteredAccountIds));

        $filteredKeys = array_keys($grouped);
        $objectivesByGroup = array_intersect_key($objectivesByGroup, array_flip($filteredKeys));

        foreach ($subRowAggregates as $groupKey => &$subRows) {
            $subRows = array_filter($subRows, function ($sr) use ($filters) {
                if (!empty($filters['category']) && !in_array($sr['category'], $filters['category'])) {
                    return false;
                }
                if (!empty($filters['sub_category']) && !in_array($sr['sub_category'], $filters['sub_category'])) {
                    return false;
                }
                return true;
            });
        }
        unset($subRows);

        return $grouped;
    }

    /**
     * Check if an account has at least one campaign matching the category/sub-category filters.
     */
    private function accountMatchesCategoryFilters(AdAccount $account, array $filters): bool
    {
        $categoryFilter = $filters['category'] ?? [];
        $subCategoryFilter = $filters['sub_category'] ?? [];

        if (empty($categoryFilter) && empty($subCategoryFilter)) {
            return true;
        }

        if (!$account->adCampaigns || $account->adCampaigns->isEmpty()) {
            return false;
        }

        foreach ($account->adCampaigns as $campaign) {
            $effectiveCategory = ($campaign->inherit_category_from_account == 0 && !empty($campaign->category))
                ? $campaign->category
                : $account->category;

            if (!empty($categoryFilter) && !in_array($effectiveCategory, $categoryFilter)) {
                continue;
            }

            if (!empty($subCategoryFilter) && !in_array($campaign->sub_industry, $subCategoryFilter)) {
                continue;
            }

            return true;
        }

        return false;
    }
}