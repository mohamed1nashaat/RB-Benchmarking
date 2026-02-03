<?php

namespace App\Services;

use Illuminate\Support\Collection;

class StatisticalAnalysisService
{
    /**
     * Calculate mean (average) of a dataset
     */
    public function mean(array $data): float
    {
        if (empty($data)) {
            return 0.0;
        }

        return array_sum($data) / count($data);
    }

    /**
     * Calculate standard deviation of a dataset
     */
    public function standardDeviation(array $data): float
    {
        if (count($data) < 2) {
            return 0.0;
        }

        $mean = $this->mean($data);
        $sumSquares = 0;

        foreach ($data as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return sqrt($sumSquares / (count($data) - 1));
    }

    /**
     * Calculate Z-score for a value
     *
     * Z-score measures how many standard deviations away from the mean a value is
     * Values > 2 or < -2 are typically considered outliers
     */
    public function zScore(float $value, array $dataset): float
    {
        $mean = $this->mean($dataset);
        $stdDev = $this->standardDeviation($dataset);

        if ($stdDev == 0) {
            return 0.0;
        }

        return ($value - $mean) / $stdDev;
    }

    /**
     * Detect if a value is an outlier using Z-score method
     *
     * @param float $value The value to test
     * @param array $dataset Historical data for comparison
     * @param float $threshold Z-score threshold (default 2.0 = 95% confidence)
     * @return array ['is_outlier' => bool, 'z_score' => float, 'severity' => string]
     */
    public function isOutlier(float $value, array $dataset, float $threshold = 2.0): array
    {
        $zScore = $this->zScore($value, $dataset);
        $absZScore = abs($zScore);

        $severity = match (true) {
            $absZScore >= 3.0 => 'extreme', // 99.7% confidence
            $absZScore >= 2.5 => 'high',
            $absZScore >= 2.0 => 'moderate',
            $absZScore >= 1.5 => 'mild',
            default => 'normal',
        };

        return [
            'is_outlier' => $absZScore >= $threshold,
            'z_score' => $zScore,
            'severity' => $severity,
            'direction' => $zScore > 0 ? 'above' : 'below',
        ];
    }

    /**
     * Calculate moving average
     *
     * @param array $data Time series data
     * @param int $window Window size for moving average
     * @return array Moving averages
     */
    public function movingAverage(array $data, int $window = 7): array
    {
        if (count($data) < $window) {
            return [];
        }

        $movingAverages = [];
        $count = count($data);

        for ($i = $window - 1; $i < $count; $i++) {
            $slice = array_slice($data, $i - $window + 1, $window);
            $movingAverages[$i] = $this->mean($slice);
        }

        return $movingAverages;
    }

    /**
     * Detect trend in time series data
     *
     * @param array $data Time series data
     * @return array ['trend' => string, 'slope' => float, 'confidence' => float]
     */
    public function detectTrend(array $data): array
    {
        if (count($data) < 3) {
            return ['trend' => 'insufficient_data', 'slope' => 0, 'confidence' => 0];
        }

        // Simple linear regression
        $n = count($data);
        $x = range(0, $n - 1); // Time points
        $y = array_values($data); // Values

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        // Calculate slope (m) of best fit line: y = mx + b
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        // Calculate R-squared (coefficient of determination) for confidence
        $yMean = $this->mean($y);
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $yPredicted = $slope * $x[$i] + (($sumY - $slope * $sumX) / $n);
            $ssTotal += pow($y[$i] - $yMean, 2);
            $ssResidual += pow($y[$i] - $yPredicted, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        // Determine trend direction
        $trend = match (true) {
            abs($slope) < 0.01 => 'stable',
            $slope > 0 => 'increasing',
            default => 'decreasing',
        };

        return [
            'trend' => $trend,
            'slope' => $slope,
            'confidence' => max(0, min(1, $rSquared)), // 0-1 scale
        ];
    }

    /**
     * Calculate percentage change
     */
    public function percentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        return (($newValue - $oldValue) / abs($oldValue)) * 100;
    }

    /**
     * Detect sudden changes (spikes or drops)
     *
     * @param float $currentValue Current value
     * @param float $previousValue Previous value (or baseline)
     * @param float $threshold Percentage threshold (default 50%)
     * @return array ['is_spike' => bool, 'is_drop' => bool, 'percentage_change' => float]
     */
    public function detectSuddenChange(float $currentValue, float $previousValue, float $threshold = 50.0): array
    {
        $percentageChange = $this->percentageChange($previousValue, $currentValue);
        $absChange = abs($percentageChange);

        return [
            'is_spike' => $percentageChange > $threshold,
            'is_drop' => $percentageChange < -$threshold,
            'percentage_change' => $percentageChange,
            'severity' => match (true) {
                $absChange >= 100 => 'extreme',
                $absChange >= 75 => 'high',
                $absChange >= 50 => 'moderate',
                $absChange >= 25 => 'mild',
                default => 'normal',
            },
        ];
    }

    /**
     * Calculate confidence interval
     *
     * @param array $data Dataset
     * @param float $confidenceLevel Confidence level (default 0.95 = 95%)
     * @return array ['lower' => float, 'upper' => float, 'mean' => float]
     */
    public function confidenceInterval(array $data, float $confidenceLevel = 0.95): array
    {
        if (count($data) < 2) {
            return ['lower' => 0, 'upper' => 0, 'mean' => 0];
        }

        $mean = $this->mean($data);
        $stdDev = $this->standardDeviation($data);
        $n = count($data);

        // Use t-distribution critical value (approximation)
        // For 95% confidence and n > 30, use 1.96; for smaller n, use 2.0
        $tValue = $n > 30 ? 1.96 : 2.0;

        $marginOfError = $tValue * ($stdDev / sqrt($n));

        return [
            'lower' => $mean - $marginOfError,
            'upper' => $mean + $marginOfError,
            'mean' => $mean,
            'margin_of_error' => $marginOfError,
        ];
    }

    /**
     * Detect seasonality pattern (day of week effect)
     *
     * @param array $dataByDayOfWeek Array where keys are day numbers (0-6) and values are arrays of metrics
     * @param int $currentDayOfWeek Current day of week (0-6)
     * @param float $currentValue Current value
     * @return array ['is_anomaly' => bool, 'expected_range' => array, 'actual_value' => float]
     */
    public function detectSeasonalAnomaly(array $dataByDayOfWeek, int $currentDayOfWeek, float $currentValue): array
    {
        if (!isset($dataByDayOfWeek[$currentDayOfWeek]) || count($dataByDayOfWeek[$currentDayOfWeek]) < 2) {
            return [
                'is_anomaly' => false,
                'expected_range' => ['lower' => 0, 'upper' => 0],
                'actual_value' => $currentValue,
                'reason' => 'insufficient_data',
            ];
        }

        $historicalData = $dataByDayOfWeek[$currentDayOfWeek];
        $interval = $this->confidenceInterval($historicalData);

        $isAnomaly = $currentValue < $interval['lower'] || $currentValue > $interval['upper'];

        return [
            'is_anomaly' => $isAnomaly,
            'expected_range' => [
                'lower' => $interval['lower'],
                'upper' => $interval['upper'],
                'mean' => $interval['mean'],
            ],
            'actual_value' => $currentValue,
            'deviation' => $currentValue - $interval['mean'],
            'deviation_percentage' => $this->percentageChange($interval['mean'], $currentValue),
        ];
    }

    /**
     * Calculate median
     */
    public function median(array $data): float
    {
        if (empty($data)) {
            return 0.0;
        }

        sort($data);
        $count = count($data);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($data[$middle - 1] + $data[$middle]) / 2;
        }

        return $data[$middle];
    }

    /**
     * Calculate Interquartile Range (IQR) - robust outlier detection
     *
     * @param array $data Dataset
     * @return array ['q1' => float, 'q3' => float, 'iqr' => float, 'lower_fence' => float, 'upper_fence' => float]
     */
    public function interquartileRange(array $data): array
    {
        if (count($data) < 4) {
            return [
                'q1' => 0,
                'q3' => 0,
                'iqr' => 0,
                'lower_fence' => 0,
                'upper_fence' => 0,
            ];
        }

        sort($data);
        $count = count($data);

        // Calculate Q1 (25th percentile)
        $q1Index = floor($count * 0.25);
        $q1 = $data[$q1Index];

        // Calculate Q3 (75th percentile)
        $q3Index = floor($count * 0.75);
        $q3 = $data[$q3Index];

        $iqr = $q3 - $q1;

        // Calculate fences (1.5 * IQR rule)
        $lowerFence = $q1 - (1.5 * $iqr);
        $upperFence = $q3 + (1.5 * $iqr);

        return [
            'q1' => $q1,
            'q3' => $q3,
            'iqr' => $iqr,
            'lower_fence' => $lowerFence,
            'upper_fence' => $upperFence,
        ];
    }

    /**
     * Detect outliers using IQR method (more robust than Z-score for skewed distributions)
     */
    public function isOutlierIQR(float $value, array $dataset): array
    {
        $iqr = $this->interquartileRange($dataset);

        $isOutlier = $value < $iqr['lower_fence'] || $value > $iqr['upper_fence'];

        return [
            'is_outlier' => $isOutlier,
            'value' => $value,
            'lower_fence' => $iqr['lower_fence'],
            'upper_fence' => $iqr['upper_fence'],
            'direction' => $value < $iqr['lower_fence'] ? 'below' : ($value > $iqr['upper_fence'] ? 'above' : 'normal'),
        ];
    }
}
