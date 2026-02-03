<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AdMetric;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Notifications\PerformanceAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlertService
{
    protected AnomalyDetectionService $anomalyDetectionService;

    public function __construct(AnomalyDetectionService $anomalyDetectionService)
    {
        $this->anomalyDetectionService = $anomalyDetectionService;
    }

    /**
     * Evaluate all active alerts for a tenant
     */
    public function evaluateAlerts(int $tenantId): array
    {
        $results = [
            'evaluated' => 0,
            'triggered' => 0,
            'errors' => 0,
        ];

        $alerts = Alert::where('tenant_id', $tenantId)
            ->active()
            ->get();

        foreach ($alerts as $alert) {
            try {
                // Check cooldown period (default 60 minutes)
                if (!$alert->shouldEvaluate(60)) {
                    continue;
                }

                $results['evaluated']++;

                // Evaluate based on alert type
                $triggered = match ($alert->type) {
                    'threshold' => $this->evaluateThresholdAlert($alert),
                    'anomaly' => $this->evaluateAnomalyAlert($alert),
                    'budget' => $this->evaluateBudgetAlert($alert),
                    default => false,
                };

                if ($triggered) {
                    $this->triggerAlert($alert);
                    $results['triggered']++;
                }
            } catch (\Exception $e) {
                Log::error('Alert evaluation failed', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Evaluate threshold alert
     */
    private function evaluateThresholdAlert(Alert $alert): bool
    {
        $conditions = $alert->conditions;

        // Required fields
        if (!isset($conditions['metric'], $conditions['operator'], $conditions['value'])) {
            return false;
        }

        $metric = $conditions['metric']; // spend, cpl, cpc, roas, etc.
        $operator = $conditions['operator']; // >, <, >=, <=, =
        $thresholdValue = $conditions['value'];
        $period = $conditions['period'] ?? 'today'; // today, yesterday, last_7_days, last_30_days
        $scope = $conditions['scope'] ?? 'all'; // all, account, campaign
        $scopeId = $conditions['scope_id'] ?? null;

        // Get date range
        [$fromDate, $toDate] = $this->getDateRange($period);

        // Build query based on scope
        $query = AdMetric::whereBetween('date', [$fromDate, $toDate]);

        if ($scope === 'account' && $scopeId) {
            $query->where('ad_account_id', $scopeId);
        } elseif ($scope === 'campaign' && $scopeId) {
            $query->where('ad_campaign_id', $scopeId);
        }

        // Filter by objective if specified
        if ($alert->objective) {
            $query->where('objective', $alert->objective);
        }

        // Calculate the metric value
        $actualValue = $this->calculateMetricValue($query, $metric);

        // Evaluate condition
        return $this->evaluateCondition($actualValue, $operator, $thresholdValue);
    }

    /**
     * Evaluate anomaly alert using statistical analysis
     */
    private function evaluateAnomalyAlert(Alert $alert): bool
    {
        $conditions = $alert->conditions;

        // Required fields
        if (!isset($conditions['metric'])) {
            return false;
        }

        // Set defaults
        $conditions['scope'] = $conditions['scope'] ?? 'all';
        $conditions['scope_id'] = $conditions['scope_id'] ?? null;
        $conditions['detection_method'] = $conditions['detection_method'] ?? 'combined';
        $conditions['lookback_days'] = $conditions['lookback_days'] ?? 30;
        $conditions['sensitivity'] = $conditions['sensitivity'] ?? 'moderate';

        try {
            $result = $this->anomalyDetectionService->detectAnomalies($conditions);

            if ($result['detected'] && !empty($result['anomalies'])) {
                // Store anomaly details in alert for notification
                $alert->anomaly_details = $result;

                Log::info('Anomaly detected', [
                    'alert_id' => $alert->id,
                    'alert_name' => $alert->name,
                    'anomalies' => $result['anomalies'],
                    'metadata' => $result['metadata'] ?? [],
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Anomaly evaluation failed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Evaluate budget alert
     */
    private function evaluateBudgetAlert(Alert $alert): bool
    {
        $conditions = $alert->conditions;

        if (!isset($conditions['budget'], $conditions['period'])) {
            return false;
        }

        $budget = $conditions['budget'];
        $period = $conditions['period']; // daily, weekly, monthly
        $threshold = $conditions['threshold'] ?? 90; // Alert at 90% by default
        $scope = $conditions['scope'] ?? 'all';
        $scopeId = $conditions['scope_id'] ?? null;

        // Get date range for the period
        [$fromDate, $toDate] = $this->getBudgetPeriodRange($period);

        // Build query
        $query = AdMetric::whereBetween('date', [$fromDate, $toDate]);

        if ($scope === 'account' && $scopeId) {
            $query->where('ad_account_id', $scopeId);
        } elseif ($scope === 'campaign' && $scopeId) {
            $query->where('ad_campaign_id', $scopeId);
        }

        // Calculate total spend
        $totalSpend = $query->sum('spend');

        // Calculate percentage of budget used
        $percentageUsed = ($totalSpend / $budget) * 100;

        // Trigger if percentage exceeds threshold
        return $percentageUsed >= $threshold;
    }

    /**
     * Trigger alert and send notifications
     */
    private function triggerAlert(Alert $alert): void
    {
        // Mark as triggered
        $alert->markAsTriggered();

        // Send notifications based on configured channels
        $channels = $alert->notification_channels;

        if (in_array('email', $channels)) {
            $alert->user->notify(new PerformanceAlertNotification($alert));
        }

        // Log alert trigger
        Log::info('Alert triggered', [
            'alert_id' => $alert->id,
            'alert_name' => $alert->name,
            'alert_type' => $alert->type,
            'user_id' => $alert->user_id,
        ]);
    }

    /**
     * Calculate metric value from query
     */
    private function calculateMetricValue($query, string $metric): float
    {
        return match ($metric) {
            'spend' => (float) $query->sum('spend'),
            'impressions' => (float) $query->sum('impressions'),
            'clicks' => (float) $query->sum('clicks'),
            'conversions' => (float) $query->sum('conversions'),
            'leads' => (float) $query->sum('leads'),
            'calls' => (float) $query->sum('calls'),
            'purchases' => (float) $query->sum('purchases'),
            'revenue' => (float) $query->sum('revenue'),
            'cpc' => $this->calculateCPC($query),
            'cpm' => $this->calculateCPM($query),
            'cpl' => $this->calculateCPL($query),
            'cpa' => $this->calculateCPA($query),
            'roas' => $this->calculateROAS($query),
            'ctr' => $this->calculateCTR($query),
            'cvr' => $this->calculateCVR($query),
            default => 0.0,
        };
    }

    /**
     * Calculate CPC (Cost Per Click)
     */
    private function calculateCPC($query): float
    {
        $metrics = $query->selectRaw('SUM(spend) as total_spend, SUM(clicks) as total_clicks')->first();

        if (!$metrics || $metrics->total_clicks == 0) {
            return 0.0;
        }

        return $metrics->total_spend / $metrics->total_clicks;
    }

    /**
     * Calculate CPM (Cost Per Mille/Thousand Impressions)
     */
    private function calculateCPM($query): float
    {
        $metrics = $query->selectRaw('SUM(spend) as total_spend, SUM(impressions) as total_impressions')->first();

        if (!$metrics || $metrics->total_impressions == 0) {
            return 0.0;
        }

        return ($metrics->total_spend / $metrics->total_impressions) * 1000;
    }

    /**
     * Calculate CPL (Cost Per Lead)
     */
    private function calculateCPL($query): float
    {
        $metrics = $query->selectRaw('SUM(spend) as total_spend, SUM(leads) as total_leads')->first();

        if (!$metrics || $metrics->total_leads == 0) {
            return 0.0;
        }

        return $metrics->total_spend / $metrics->total_leads;
    }

    /**
     * Calculate CPA (Cost Per Acquisition/Conversion)
     */
    private function calculateCPA($query): float
    {
        $metrics = $query->selectRaw('SUM(spend) as total_spend, SUM(conversions) as total_conversions')->first();

        if (!$metrics || $metrics->total_conversions == 0) {
            return 0.0;
        }

        return $metrics->total_spend / $metrics->total_conversions;
    }

    /**
     * Calculate ROAS (Return on Ad Spend)
     */
    private function calculateROAS($query): float
    {
        $metrics = $query->selectRaw('SUM(spend) as total_spend, SUM(revenue) as total_revenue')->first();

        if (!$metrics || $metrics->total_spend == 0) {
            return 0.0;
        }

        return $metrics->total_revenue / $metrics->total_spend;
    }

    /**
     * Calculate CTR (Click-Through Rate)
     */
    private function calculateCTR($query): float
    {
        $metrics = $query->selectRaw('SUM(impressions) as total_impressions, SUM(clicks) as total_clicks')->first();

        if (!$metrics || $metrics->total_impressions == 0) {
            return 0.0;
        }

        return ($metrics->total_clicks / $metrics->total_impressions) * 100;
    }

    /**
     * Calculate CVR (Conversion Rate)
     */
    private function calculateCVR($query): float
    {
        $metrics = $query->selectRaw('SUM(clicks) as total_clicks, SUM(conversions) as total_conversions')->first();

        if (!$metrics || $metrics->total_clicks == 0) {
            return 0.0;
        }

        return ($metrics->total_conversions / $metrics->total_clicks) * 100;
    }

    /**
     * Evaluate condition
     */
    private function evaluateCondition(float $actualValue, string $operator, float $thresholdValue): bool
    {
        return match ($operator) {
            '>' => $actualValue > $thresholdValue,
            '<' => $actualValue < $thresholdValue,
            '>=' => $actualValue >= $thresholdValue,
            '<=' => $actualValue <= $thresholdValue,
            '=' => abs($actualValue - $thresholdValue) < 0.01, // Float comparison
            '!=' => abs($actualValue - $thresholdValue) >= 0.01,
            default => false,
        };
    }

    /**
     * Get date range for period
     */
    private function getDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->startOfDay()->format('Y-m-d'), $now->endOfDay()->format('Y-m-d')],
            'yesterday' => [
                $now->subDay()->startOfDay()->format('Y-m-d'),
                $now->subDay()->endOfDay()->format('Y-m-d')
            ],
            'last_7_days' => [$now->subDays(7)->format('Y-m-d'), $now->format('Y-m-d')],
            'last_30_days' => [$now->subDays(30)->format('Y-m-d'), $now->format('Y-m-d')],
            'this_week' => [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')],
            'this_month' => [$now->startOfMonth()->format('Y-m-d'), $now->endOfMonth()->format('Y-m-d')],
            default => [$now->startOfDay()->format('Y-m-d'), $now->endOfDay()->format('Y-m-d')],
        };
    }

    /**
     * Get date range for budget period
     */
    private function getBudgetPeriodRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => [$now->startOfDay()->format('Y-m-d'), $now->endOfDay()->format('Y-m-d')],
            'weekly' => [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')],
            'monthly' => [$now->startOfMonth()->format('Y-m-d'), $now->endOfMonth()->format('Y-m-d')],
            default => [$now->startOfDay()->format('Y-m-d'), $now->endOfDay()->format('Y-m-d')],
        };
    }

    /**
     * Create a new alert
     */
    public function createAlert(array $data): Alert
    {
        return Alert::create($data);
    }

    /**
     * Update an alert
     */
    public function updateAlert(Alert $alert, array $data): Alert
    {
        $alert->update($data);
        return $alert->fresh();
    }

    /**
     * Delete an alert
     */
    public function deleteAlert(Alert $alert): bool
    {
        return $alert->delete();
    }

    /**
     * Get alerts for a tenant
     */
    public function getAlertsForTenant(int $tenantId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Alert::where('tenant_id', $tenantId);

        if (isset($filters['type'])) {
            $query->forType($filters['type']);
        }

        if (isset($filters['objective'])) {
            $query->forObjective($filters['objective']);
        }

        if (isset($filters['is_active'])) {
            $filters['is_active'] ? $query->active() : $query->inactive();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
