<?php

namespace App\Services;

use App\Models\AdMetric;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnomalyDetectionService
{
    protected StatisticalAnalysisService $statsService;
    protected CurrencyConversionService $currencyService;

    public function __construct(
        StatisticalAnalysisService $statsService,
        CurrencyConversionService $currencyService
    ) {
        $this->statsService = $statsService;
        $this->currencyService = $currencyService;
    }

    /**
     * Detect anomalies for a campaign or account
     *
     * @param array $conditions Alert conditions
     * @return array ['detected' => bool, 'anomalies' => array, 'analysis' => array]
     */
    public function detectAnomalies(array $conditions): array
    {
        $scope = $conditions['scope'] ?? 'all';
        $scopeId = $conditions['scope_id'] ?? null;
        $metric = $conditions['metric'] ?? 'spend';
        $method = $conditions['detection_method'] ?? 'zscore'; // zscore, iqr, percentage_change, seasonal
        $lookbackDays = $conditions['lookback_days'] ?? 30;
        $sensitivity = $conditions['sensitivity'] ?? 'moderate'; // low, moderate, high

        // Get threshold based on sensitivity
        $threshold = $this->getThresholdBySensitivity($sensitivity);

        // Build query based on scope
        $query = $this->buildQuery($scope, $scopeId, $lookbackDays);

        if (!$query) {
            return [
                'detected' => false,
                'anomalies' => [],
                'analysis' => ['error' => 'Invalid scope configuration'],
            ];
        }

        // Get historical data
        $historicalData = $this->getHistoricalData($query, $metric);

        if (empty($historicalData)) {
            return [
                'detected' => false,
                'anomalies' => [],
                'analysis' => ['error' => 'Insufficient historical data'],
            ];
        }

        // Get today's value
        $todayValue = $this->getTodayValue($query, $metric);

        if ($todayValue === null) {
            return [
                'detected' => false,
                'anomalies' => [],
                'analysis' => ['error' => 'No data for today'],
            ];
        }

        // Detect anomalies based on method
        $result = match ($method) {
            'zscore' => $this->detectUsingZScore($todayValue, $historicalData, $threshold),
            'iqr' => $this->detectUsingIQR($todayValue, $historicalData),
            'percentage_change' => $this->detectUsingPercentageChange($todayValue, $historicalData, $threshold),
            'seasonal' => $this->detectUsingSeasonal($query, $metric, $todayValue, $lookbackDays),
            'combined' => $this->detectUsingCombinedMethods($todayValue, $historicalData, $query, $metric, $lookbackDays),
            default => ['detected' => false, 'analysis' => ['error' => 'Unknown detection method']],
        };

        // Add metadata
        $result['metadata'] = [
            'metric' => $metric,
            'method' => $method,
            'sensitivity' => $sensitivity,
            'lookback_days' => $lookbackDays,
            'historical_data_points' => count($historicalData),
            'current_value' => $todayValue,
        ];

        return $result;
    }

    /**
     * Detect using Z-Score method
     */
    protected function detectUsingZScore(float $currentValue, array $historicalData, float $threshold): array
    {
        $outlier = $this->statsService->isOutlier($currentValue, $historicalData, $threshold);

        $mean = $this->statsService->mean($historicalData);
        $stdDev = $this->statsService->standardDeviation($historicalData);

        return [
            'detected' => $outlier['is_outlier'],
            'anomalies' => $outlier['is_outlier'] ? [
                [
                    'type' => 'statistical_outlier',
                    'severity' => $outlier['severity'],
                    'direction' => $outlier['direction'],
                    'z_score' => $outlier['z_score'],
                    'description' => $this->generateDescription($outlier, $currentValue, $mean),
                ]
            ] : [],
            'analysis' => [
                'method' => 'z_score',
                'mean' => $mean,
                'std_dev' => $stdDev,
                'z_score' => $outlier['z_score'],
                'threshold' => $threshold,
            ],
        ];
    }

    /**
     * Detect using IQR method
     */
    protected function detectUsingIQR(float $currentValue, array $historicalData): array
    {
        $outlier = $this->statsService->isOutlierIQR($currentValue, $historicalData);

        return [
            'detected' => $outlier['is_outlier'],
            'anomalies' => $outlier['is_outlier'] ? [
                [
                    'type' => 'iqr_outlier',
                    'severity' => 'moderate',
                    'direction' => $outlier['direction'],
                    'description' => sprintf(
                        'Value %.2f is %s the normal range (%.2f - %.2f)',
                        $currentValue,
                        $outlier['direction'],
                        $outlier['lower_fence'],
                        $outlier['upper_fence']
                    ),
                ]
            ] : [],
            'analysis' => [
                'method' => 'iqr',
                'lower_fence' => $outlier['lower_fence'],
                'upper_fence' => $outlier['upper_fence'],
            ],
        ];
    }

    /**
     * Detect using percentage change
     */
    protected function detectUsingPercentageChange(float $currentValue, array $historicalData, float $threshold): array
    {
        // Compare to yesterday's value
        $yesterdayValue = end($historicalData);
        $change = $this->statsService->detectSuddenChange($currentValue, $yesterdayValue, $threshold);

        $detected = $change['is_spike'] || $change['is_drop'];

        return [
            'detected' => $detected,
            'anomalies' => $detected ? [
                [
                    'type' => $change['is_spike'] ? 'sudden_spike' : 'sudden_drop',
                    'severity' => $change['severity'],
                    'percentage_change' => $change['percentage_change'],
                    'description' => sprintf(
                        '%s of %.1f%% detected (%.2f → %.2f)',
                        $change['is_spike'] ? 'Spike' : 'Drop',
                        abs($change['percentage_change']),
                        $yesterdayValue,
                        $currentValue
                    ),
                ]
            ] : [],
            'analysis' => [
                'method' => 'percentage_change',
                'previous_value' => $yesterdayValue,
                'current_value' => $currentValue,
                'percentage_change' => $change['percentage_change'],
            ],
        ];
    }

    /**
     * Detect using seasonal patterns (day of week)
     */
    protected function detectUsingSeasonal($query, string $metric, float $currentValue, int $lookbackDays): array
    {
        $today = Carbon::now();
        $currentDayOfWeek = $today->dayOfWeek;

        // Get data grouped by day of week
        $dataByDayOfWeek = $this->getDataByDayOfWeek($query, $metric, $lookbackDays);

        $seasonal = $this->statsService->detectSeasonalAnomaly(
            $dataByDayOfWeek,
            $currentDayOfWeek,
            $currentValue
        );

        return [
            'detected' => $seasonal['is_anomaly'],
            'anomalies' => $seasonal['is_anomaly'] ? [
                [
                    'type' => 'seasonal_anomaly',
                    'severity' => abs($seasonal['deviation_percentage']) > 50 ? 'high' : 'moderate',
                    'day_of_week' => $currentDayOfWeek,
                    'description' => sprintf(
                        'Unusual for %s: %.2f (expected: %.2f - %.2f)',
                        $today->format('l'),
                        $currentValue,
                        $seasonal['expected_range']['lower'],
                        $seasonal['expected_range']['upper']
                    ),
                ]
            ] : [],
            'analysis' => [
                'method' => 'seasonal',
                'day_of_week' => $currentDayOfWeek,
                'expected_range' => $seasonal['expected_range'],
                'deviation_percentage' => $seasonal['deviation_percentage'] ?? 0,
            ],
        ];
    }

    /**
     * Detect using combined methods (most comprehensive)
     */
    protected function detectUsingCombinedMethods(float $currentValue, array $historicalData, $query, string $metric, int $lookbackDays): array
    {
        $anomalies = [];
        $analysis = [];

        // Method 1: Z-Score
        $zScore = $this->detectUsingZScore($currentValue, $historicalData, 2.0);
        if ($zScore['detected']) {
            $anomalies = array_merge($anomalies, $zScore['anomalies']);
        }
        $analysis['z_score'] = $zScore['analysis'];

        // Method 2: Percentage Change
        $percentChange = $this->detectUsingPercentageChange($currentValue, $historicalData, 50.0);
        if ($percentChange['detected']) {
            $anomalies = array_merge($anomalies, $percentChange['anomalies']);
        }
        $analysis['percentage_change'] = $percentChange['analysis'];

        // Method 3: Trend Analysis
        $trend = $this->statsService->detectTrend($historicalData);
        $analysis['trend'] = $trend;

        // Method 4: Seasonal
        $seasonal = $this->detectUsingSeasonal($query, $metric, $currentValue, $lookbackDays);
        if ($seasonal['detected']) {
            $anomalies = array_merge($anomalies, $seasonal['anomalies']);
        }
        $analysis['seasonal'] = $seasonal['analysis'];

        return [
            'detected' => !empty($anomalies),
            'anomalies' => $anomalies,
            'analysis' => $analysis,
        ];
    }

    /**
     * Build query based on scope
     */
    protected function buildQuery(string $scope, ?int $scopeId, int $lookbackDays)
    {
        $fromDate = Carbon::now()->subDays($lookbackDays)->format('Y-m-d');
        $toDate = Carbon::now()->subDay()->format('Y-m-d'); // Exclude today for historical comparison

        $query = AdMetric::whereBetween('date', [$fromDate, $toDate]);

        if ($scope === 'account' && $scopeId) {
            $query->where('ad_account_id', $scopeId);
        } elseif ($scope === 'campaign' && $scopeId) {
            $query->where('ad_campaign_id', $scopeId);
        }

        return $query;
    }

    /**
     * Get historical data for a metric
     */
    protected function getHistoricalData($query, string $metric): array
    {
        // Check if this is a calculated metric
        if ($this->isCalculatedMetric($metric)) {
            return $this->getCalculatedHistoricalData($query, $metric);
        }

        // Direct metric - can use SUM
        $data = $query->select('date', DB::raw("SUM({$metric}) as value"))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('value')
            ->map(fn($v) => (float) $v)
            ->toArray();

        return array_values($data);
    }

    /**
     * Get historical data for calculated metrics
     */
    protected function getCalculatedHistoricalData($query, string $metric): array
    {
        // Get aggregated raw data by date
        $data = $query->select(
            'date',
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(conversions) as total_conversions'),
            DB::raw('SUM(leads) as total_leads'),
            DB::raw('SUM(calls) as total_calls'),
            DB::raw('SUM(revenue) as total_revenue')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $values = [];
        foreach ($data as $row) {
            $values[] = $this->calculateMetricValue($row, $metric);
        }

        return $values;
    }

    /**
     * Calculate metric value from aggregated row
     */
    protected function calculateMetricValue($row, string $metric): float
    {
        return match ($metric) {
            'cpc' => $row->total_clicks > 0 ? $row->total_spend / $row->total_clicks : 0,
            'cpm' => $row->total_impressions > 0 ? ($row->total_spend / $row->total_impressions) * 1000 : 0,
            'cpl' => $row->total_leads > 0 ? $row->total_spend / $row->total_leads : 0,
            'cpa' => $row->total_conversions > 0 ? $row->total_spend / $row->total_conversions : 0,
            'roas' => $row->total_spend > 0 ? $row->total_revenue / $row->total_spend : 0,
            'ctr' => $row->total_impressions > 0 ? ($row->total_clicks / $row->total_impressions) * 100 : 0,
            'cvr' => $row->total_clicks > 0 ? ($row->total_conversions / $row->total_clicks) * 100 : 0,
            default => 0.0,
        };
    }

    /**
     * Check if a metric is calculated
     */
    protected function isCalculatedMetric(string $metric): bool
    {
        return in_array($metric, ['cpc', 'cpm', 'cpl', 'cpa', 'roas', 'ctr', 'cvr']);
    }

    /**
     * Get today's value
     */
    protected function getTodayValue($query, string $metric): ?float
    {
        $today = Carbon::now()->format('Y-m-d');

        // Build today's query with same filters
        $todayQuery = AdMetric::where('date', $today);

        // Apply same filters as historical query
        if ($query->getQuery()->wheres) {
            foreach ($query->getQuery()->wheres as $where) {
                if (isset($where['column']) && $where['column'] !== 'date') {
                    $todayQuery->where($where['column'], $where['operator'] ?? '=', $where['value'] ?? null);
                }
            }
        }

        // Handle calculated metrics
        if ($this->isCalculatedMetric($metric)) {
            $row = $todayQuery->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads,
                SUM(calls) as total_calls,
                SUM(revenue) as total_revenue
            ')->first();

            if (!$row) {
                return null;
            }

            return $this->calculateMetricValue($row, $metric);
        }

        // Direct metric
        $value = $todayQuery->sum($metric);
        return $value ? (float) $value : null;
    }

    /**
     * Get data grouped by day of week
     */
    protected function getDataByDayOfWeek($query, string $metric, int $lookbackDays): array
    {
        // Handle calculated metrics
        if ($this->isCalculatedMetric($metric)) {
            $data = $query->select(
                'date',
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(leads) as total_leads'),
                DB::raw('SUM(calls) as total_calls'),
                DB::raw('SUM(revenue) as total_revenue')
            )
                ->groupBy('date')
                ->get();

            $byDayOfWeek = [];

            foreach ($data as $row) {
                $dayOfWeek = Carbon::parse($row->date)->dayOfWeek;
                if (!isset($byDayOfWeek[$dayOfWeek])) {
                    $byDayOfWeek[$dayOfWeek] = [];
                }
                $byDayOfWeek[$dayOfWeek][] = $this->calculateMetricValue($row, $metric);
            }

            return $byDayOfWeek;
        }

        // Direct metric
        $data = $query->select('date', DB::raw("SUM({$metric}) as value"))
            ->groupBy('date')
            ->get();

        $byDayOfWeek = [];

        foreach ($data as $row) {
            $dayOfWeek = Carbon::parse($row->date)->dayOfWeek;
            if (!isset($byDayOfWeek[$dayOfWeek])) {
                $byDayOfWeek[$dayOfWeek] = [];
            }
            $byDayOfWeek[$dayOfWeek][] = (float) $row->value;
        }

        return $byDayOfWeek;
    }

    /**
     * Get threshold based on sensitivity setting
     */
    protected function getThresholdBySensitivity(string $sensitivity): float
    {
        return match ($sensitivity) {
            'low' => 3.0,      // Only extreme outliers
            'moderate' => 2.0, // Standard outliers (95% confidence)
            'high' => 1.5,     // More sensitive detection
            default => 2.0,
        };
    }

    /**
     * Generate human-readable description
     */
    protected function generateDescription(array $outlier, float $currentValue, float $mean): string
    {
        $direction = $outlier['direction'] === 'above' ? 'higher' : 'lower';
        $percentage = abs($this->statsService->percentageChange($mean, $currentValue));

        return sprintf(
            'Value is %.1f%% %s than the historical average (%.2f vs %.2f)',
            $percentage,
            $direction,
            $currentValue,
            $mean
        );
    }

    /**
     * Get anomaly suggestions based on detected anomalies
     */
    public function getAnomalySuggestions(array $anomalies, string $metric): array
    {
        $suggestions = [];

        foreach ($anomalies as $anomaly) {
            $suggestions[] = match ($anomaly['type']) {
                'sudden_spike' => $this->getSpikesSuggestions($metric),
                'sudden_drop' => $this->getDropSuggestions($metric),
                'statistical_outlier' => $this->getOutlierSuggestions($metric, $anomaly['direction']),
                'seasonal_anomaly' => 'Check if there are any unusual events or campaigns running today',
                default => 'Monitor the situation and investigate if the pattern continues',
            };
        }

        return array_unique($suggestions);
    }

    protected function getSpikesSuggestions(string $metric): string
    {
        return match ($metric) {
            'spend' => 'Check for budget overruns or bid increases',
            'cpc', 'cpm', 'cpl' => 'Review ad placement and targeting - costs have increased',
            'clicks' => 'Unusual traffic detected - verify ad placement quality',
            default => 'Investigate cause of sudden increase',
        };
    }

    protected function getDropSuggestions(string $metric): string
    {
        return match ($metric) {
            'impressions' => 'Check ad approval status and budget availability',
            'clicks' => 'Review ad creative performance and relevance',
            'conversions', 'leads' => 'Check landing page and pixel tracking',
            'revenue' => 'Investigate conversion tracking and attribution',
            default => 'Investigate cause of sudden decrease',
        };
    }

    protected function getOutlierSuggestions(string $metric, string $direction): string
    {
        if ($direction === 'above') {
            return $this->getSpikesSuggestions($metric);
        }
        return $this->getDropSuggestions($metric);
    }
}
