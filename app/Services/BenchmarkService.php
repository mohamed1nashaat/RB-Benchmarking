<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdMetric;
use Illuminate\Support\Collection;

class BenchmarkService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    /**
     * Calculate dynamic industry benchmarks based on actual data
     * This replaces the static benchmark data with dynamic calculations
     */
    private function calculateDynamicIndustryBenchmarks(
        string $dateFrom, 
        string $dateTo, 
        array $filters = []
    ): array
    {
        $benchmarks = [];
        
        // Build query for industries with accounts that have metrics data
        $accountsQuery = AdAccount::select('industry')
            ->whereNotNull('industry')
            ->whereHas('adMetrics', function($query) use ($dateFrom, $dateTo, $filters) {
                $query->whereBetween('date', [$dateFrom, $dateTo]);

                // Apply filters to metrics
                if (!empty($filters['platform'])) {
                    $query->where('platform', $filters['platform']);
                }
                if (!empty($filters['objective'])) {
                    $query->where('objective', $filters['objective']);
                }
                if (!empty($filters['funnel_stage'])) {
                    $query->where('funnel_stage', $filters['funnel_stage']);
                }
                if (!empty($filters['user_journey'])) {
                    $query->where('user_journey', $filters['user_journey']);
                }
                if (isset($filters['has_pixel_data'])) {
                    $query->where('has_pixel_data', $filters['has_pixel_data']);
                }
            });

        // Apply account-level filters
        if (!empty($filters['platform'])) {
            $accountsQuery->whereHas('integration', function($query) use ($filters) {
                $query->where('platform', $filters['platform']);
            });
        }
        
        $industriesWithData = $accountsQuery->groupBy('industry')->pluck('industry');

        foreach ($industriesWithData as $industry) {
            // Get all accounts in this industry with their metrics
            $accountsBuilder = AdAccount::with(['adMetrics' => function($query) use ($dateFrom, $dateTo, $filters) {
                $query->whereBetween('date', [$dateFrom, $dateTo]);
                
                // Apply same filters to metrics relation
                if (!empty($filters['platform'])) {
                    $query->where('platform', $filters['platform']);
                }
                if (!empty($filters['objective'])) {
                    $query->where('objective', $filters['objective']);
                }
                if (!empty($filters['funnel_stage'])) {
                    $query->where('funnel_stage', $filters['funnel_stage']);
                }
                if (!empty($filters['user_journey'])) {
                    $query->where('user_journey', $filters['user_journey']);
                }
                if (isset($filters['has_pixel_data'])) {
                    $query->where('has_pixel_data', $filters['has_pixel_data']);
                }
            }])
            ->where('industry', $industry);
            
            // Apply account-level filters
            if (!empty($filters['platform'])) {
                $accountsBuilder->whereHas('integration', function($query) use ($filters) {
                    $query->where('platform', $filters['platform']);
                });
            }
            
            $accounts = $accountsBuilder->get();

            $allMetrics = [];
            
            // Calculate metrics for each account and collect them
            foreach ($accounts as $account) {
                $accountMetrics = $this->calculateAccountActualMetrics($account->adMetrics, $account);

                // Only include accounts with meaningful data (spend > 0)
                if ($accountMetrics['total_spend'] > 0) {
                    $allMetrics[] = $accountMetrics;
                }
            }

            if (count($allMetrics) >= 2) { // Need at least 2 accounts for meaningful benchmarks
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
        $metrics = ['ctr', 'cpc', 'cpm', 'cvr', 'cpl'];

        foreach ($metrics as $metric) {
            $values = [];

            // Collect all non-zero values for this metric
            foreach ($allMetrics as $accountMetric) {
                $value = $accountMetric[$metric];
                if ($value > 0) {
                    $values[] = $value;
                }
            }

            if (count($values) >= 2) {
                sort($values);

                // Remove statistical outliers using IQR method with domain-specific thresholds
                $originalCount = count($values);
                $filteredValues = $this->removeOutliers($values, $metric);
                $outlierCount = $originalCount - count($filteredValues);

                // Use filtered values for benchmark calculation
                $values = $filteredValues;
                $count = count($values);

                if ($count >= 2) {
                    // Calculate percentiles for more realistic benchmarks
                    $p25Index = max(0, floor($count * 0.25) - 1);
                    $p50Index = max(0, floor($count * 0.50) - 1);
                    $p75Index = max(0, floor($count * 0.75) - 1);

                    $benchmarks[$metric] = [
                        'min' => round($values[$p25Index], 2), // 25th percentile as "good" minimum
                        'max' => round($values[$p75Index], 2), // 75th percentile as "excellent" threshold
                        'avg' => round($values[$p50Index], 2), // Median as average
                        'data_points' => $count,
                        'outliers_removed' => $outlierCount,
                        'range' => [
                            'lowest' => round(min($values), 2),
                            'highest' => round(max($values), 2),
                        ],
                        'calculation' => [
                            'method' => 'percentile_with_outlier_filtering',
                            'percentiles' => [
                                'p25' => round($values[$p25Index], 2),
                                'p50' => round($values[$p50Index], 2),
                                'p75' => round($values[$p75Index], 2),
                            ],
                            'sample_values' => array_slice($values, 0, min(5, $count)), // Show first 5 sample values
                            'formula' => [
                                'min' => '25th percentile (good performance threshold)',
                                'avg' => '50th percentile (median - typical performance)',
                                'max' => '75th percentile (excellent performance threshold)'
                            ]
                        ]
                    ];
                }
            }
        }

        return $benchmarks;
    }

    /**
     * Get benchmark data for all accounts grouped by industry
     */
    public function getIndustryBenchmarks(string $dateFrom, string $dateTo, array $filters = []): array
    {
        // Calculate dynamic benchmarks from actual data
        $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo, $filters);
        
        // Get all accounts with their actual performance data
        $accountsQuery = AdAccount::with(['adMetrics' => function($query) use ($dateFrom, $dateTo, $filters) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
            
            // Apply same filters to metrics relation
            if (!empty($filters['platform'])) {
                $query->where('platform', $filters['platform']);
            }
            if (!empty($filters['objective'])) {
                $query->where('objective', $filters['objective']);
            }
            if (!empty($filters['funnel_stage'])) {
                $query->where('funnel_stage', $filters['funnel_stage']);
            }
            if (!empty($filters['user_journey'])) {
                $query->where('user_journey', $filters['user_journey']);
            }
            if (isset($filters['has_pixel_data'])) {
                $query->where('has_pixel_data', $filters['has_pixel_data']);
            }
        }])
        ->whereNotNull('industry');
        
        // Apply account-level filters
        if (!empty($filters['platform'])) {
            $accountsQuery->whereHas('integration', function($query) use ($filters) {
                $query->where('platform', $filters['platform']);
            });
        }

        $accounts = $accountsQuery->get();

        $results = [];

        foreach ($industryBenchmarks as $industry => $benchmarks) {
            $industryAccounts = $accounts->where('industry', $industry);
            
            if ($industryAccounts->isEmpty()) {
                continue;
            }

            // Calculate actual performance for this industry
            $actualMetrics = $this->calculateIndustryActualMetrics($industryAccounts);
            
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

            $results[$industry] = [
                'industry' => $industry,
                'accounts_count' => $industryAccounts->count(),
                'account_names' => $industryAccounts->pluck('account_name')->toArray(),
                'total_spend' => $actualMetrics['total_spend'] ?? 0,
                'total_impressions' => $actualMetrics['total_impressions'] ?? 0,
                'total_clicks' => $actualMetrics['total_clicks'] ?? 0,
                'total_leads' => $actualMetrics['total_leads'] ?? 0,
                'metrics' => $comparison,
                'benchmark_info' => [
                    'calculation_method' => 'dynamic',
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                ]
            ];
        }

        return $results;
    }

    /**
     * Get benchmark comparison for a specific account
     */
    public function getAccountBenchmark(int $accountId, string $dateFrom, string $dateTo): array
    {
        $account = AdAccount::with(['adMetrics' => function($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
        }])->findOrFail($accountId);

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
     * Get overall performance summary across all industries
     */
    public function getOverallBenchmarkSummary(string $dateFrom, string $dateTo): array
    {
        $industryBenchmarks = $this->getIndustryBenchmarks($dateFrom, $dateTo);
        
        $summary = [
            'total_industries' => count($industryBenchmarks),
            'total_accounts' => 0,
            'total_spend' => 0,
            'best_performing' => [],
            'needs_improvement' => [],
            'industry_breakdown' => $industryBenchmarks,
        ];

        $performanceScores = [];

        foreach ($industryBenchmarks as $industry => $data) {
            $summary['total_accounts'] += $data['accounts_count'];
            $summary['total_spend'] += $data['total_spend'];

            // Calculate average performance score for this industry
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

        // Sort by performance score
        uasort($performanceScores, fn($a, $b) => $b['score'] <=> $a['score']);

        $summary['best_performing'] = array_slice($performanceScores, 0, 3);
        $summary['needs_improvement'] = array_slice(array_reverse($performanceScores), 0, 3);

        return $summary;
    }

    /**
     * Calculate actual metrics for a collection of accounts in an industry
     */
    private function calculateIndustryActualMetrics(Collection $accounts): array
    {
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalLeads = 0;

        foreach ($accounts as $account) {
            $metrics = $account->adMetrics;

            // Spend is already in SAR in the database - no conversion needed
            $accountSpend = $metrics->sum('spend');
            $totalSpend += $accountSpend;

            $totalImpressions += $metrics->sum('impressions');
            $totalClicks += $metrics->sum('clicks');
            $totalLeads += $metrics->sum('leads');
        }

        return [
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'cvr' => $totalClicks > 0 ? ($totalLeads / $totalClicks) * 100 : 0,
            'cpl' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
        ];
    }

    /**
     * Calculate actual metrics for a single account
     */
    private function calculateAccountActualMetrics(Collection $metrics, ?AdAccount $account = null): array
    {
        // Spend is already in SAR in the database - no conversion needed
        $totalSpend = $metrics->sum('spend');

        $totalImpressions = $metrics->sum('impressions');
        $totalClicks = $metrics->sum('clicks');
        $totalLeads = $metrics->sum('leads');

        return [
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'cvr' => $totalClicks > 0 ? ($totalLeads / $totalClicks) * 100 : 0,
            'cpl' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
        ];
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

        // For metrics where lower is better (CPC, CPM, CPL)
        $lowerIsBetterMetrics = ['cpc', 'cpm', 'cpl'];
        
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
        return AdAccount::select('industry')
            ->whereNotNull('industry')
            ->whereHas('adMetrics')
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
        
        $lowerIsBetterMetrics = ['cpc', 'cpm', 'cpl'];
        $isLowerBetter = in_array($metric, $lowerIsBetterMetrics);
        
        $unit = '';
        if (in_array($metric, ['cpc', 'cpl'])) {
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
     * Calculate expected results based on spend and industry benchmarks
     */
    public function calculateExpectedResults(float $spend, string $industry, string $objective = 'leads'): array
    {
        // Get industry benchmarks (use broader date range to ensure data)
        $dateTo = now()->toDateString();
        $dateFrom = now()->subYear()->toDateString();
        $industryBenchmarks = $this->calculateDynamicIndustryBenchmarks($dateFrom, $dateTo);
        
        if (!isset($industryBenchmarks[$industry])) {
            throw new \Exception("Not enough data to calculate predictions for {$industry} industry");
        }

        $benchmarks = $industryBenchmarks[$industry];
        
        // Calculate predictions for different performance scenarios
        $predictions = [
            'poor' => [],
            'average' => [],
            'good' => [],
            'excellent' => []
        ];

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

        return [
            'input' => [
                'spend' => $spend,
                'industry' => $industry,
                'objective' => $objective,
            ],
            'predictions' => $predictions,
            'benchmark_info' => [
                'calculation_method' => 'dynamic',
                'data_points' => array_sum(array_column($benchmarks, 'data_points')),
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
        $cpl = isset($benchmarks['cpl']) ? $benchmarks['cpl']['avg'] * $multipliers['cpl'] : 30.0;

        // Calculate basic metrics
        $expectedImpressions = $cpm > 0 ? ($spend / $cpm) * 1000 : 0;
        $expectedClicks = max($expectedImpressions * ($ctr / 100), $spend / $cpc);
        
        // Calculate objective-specific results
        $objectiveResults = $this->calculateObjectiveSpecificResults(
            $spend, $expectedImpressions, $expectedClicks, $cvr, $cpl, $objective
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
            ],
            'cost_per_result' => [
                'cost_per_impression' => 'SAR ' . round($spend / max($expectedImpressions, 1), 4),
                'cost_per_click' => 'SAR ' . round($spend / max($expectedClicks, 1), 2),
                'cost_per_conversion' => 'SAR ' . round($spend / max($objectiveResults['primary']['value'], 1), 2),
            ],
            // Keep legacy fields for backward compatibility
            'leads' => $objectiveResults['primary'],
        ];
    }

    /**
     * Calculate results specific to the campaign objective
     */
    private function calculateObjectiveSpecificResults(float $spend, float $impressions, float $clicks, float $cvr, float $cpl, string $objective): array
    {
        switch ($objective) {
            case 'leads':
            case 'messages':
            case 'calls':
                $primaryValue = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($primaryValue),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% CVR = " . number_format(round($primaryValue)),
                        'label' => $this->getObjectiveLabel($objective)
                    ],
                    'secondary' => []
                ];

            case 'sales':
            case 'conversions':
            case 'catalog_sales':
                $primaryValue = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($primaryValue),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% conversion rate = " . number_format(round($primaryValue)),
                        'label' => $this->getObjectiveLabel($objective)
                    ],
                    'secondary' => []
                ];

            case 'traffic':
            case 'link_clicks':
                return [
                    'primary' => [
                        'value' => round($clicks),
                        'calculation' => number_format(round($impressions)) . " impressions × " . round(($clicks / $impressions) * 100, 2) . "% CTR = " . number_format(round($clicks)),
                        'label' => 'Website Visits'
                    ],
                    'secondary' => []
                ];

            case 'video_views':
                $videoViews = $clicks * 2.5; // Video views typically higher than clicks
                return [
                    'primary' => [
                        'value' => round($videoViews),
                        'calculation' => number_format(round($clicks)) . " engagements × 2.5 view rate = " . number_format(round($videoViews)),
                        'label' => 'Video Views'
                    ],
                    'secondary' => []
                ];

            case 'engagement':
            case 'page_likes':
                $engagements = $impressions * 0.02; // 2% engagement rate
                return [
                    'primary' => [
                        'value' => round($engagements),
                        'calculation' => number_format(round($impressions)) . " impressions × 2% engagement rate = " . number_format(round($engagements)),
                        'label' => $this->getObjectiveLabel($objective)
                    ],
                    'secondary' => []
                ];

            case 'awareness':
            case 'reach':
            case 'impressions':
                $reach = $impressions * 0.7; // Reach is typically 70% of impressions
                return [
                    'primary' => [
                        'value' => round($reach),
                        'calculation' => number_format(round($impressions)) . " impressions × 70% reach rate = " . number_format(round($reach)),
                        'label' => 'People Reached'
                    ],
                    'secondary' => []
                ];

            case 'app_installs':
            case 'app_events':
                $appActions = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($appActions),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% install rate = " . number_format(round($appActions)),
                        'label' => $this->getObjectiveLabel($objective)
                    ],
                    'secondary' => []
                ];

            default:
                // Default to leads calculation
                $primaryValue = $clicks * ($cvr / 100);
                return [
                    'primary' => [
                        'value' => round($primaryValue),
                        'calculation' => number_format(round($clicks)) . " clicks × " . round($cvr, 2) . "% CVR = " . number_format(round($primaryValue)),
                        'label' => 'Conversions'
                    ],
                    'secondary' => []
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
}