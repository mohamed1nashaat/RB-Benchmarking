<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SyncMonitoringService
{
    private const CACHE_PREFIX = 'sync_status:';
    private const ALERT_THRESHOLD_HOURS = 6; // Alert if sync hasn't run in 6 hours
    private const ERROR_RATE_THRESHOLD = 0.3; // Alert if >30% error rate

    /**
     * Record successful sync
     */
    public function recordSuccess(string $platform, int $integrationId, array $stats): void
    {
        $key = self::CACHE_PREFIX . "{$platform}:{$integrationId}";

        $data = [
            'platform' => $platform,
            'integration_id' => $integrationId,
            'last_success' => now()->toIso8601String(),
            'last_stats' => $stats,
            'consecutive_failures' => 0,
            'total_syncs' => $this->incrementCounter($key, 'total_syncs'),
            'total_successes' => $this->incrementCounter($key, 'total_successes'),
        ];

        Cache::put($key, $data, now()->addDays(7));

        Log::info("Sync success recorded", [
            'platform' => $platform,
            'integration_id' => $integrationId,
            'stats' => $stats
        ]);
    }

    /**
     * Record sync failure
     */
    public function recordFailure(string $platform, int $integrationId, string $error): void
    {
        $key = self::CACHE_PREFIX . "{$platform}:{$integrationId}";
        $existing = Cache::get($key, []);

        $consecutiveFailures = ($existing['consecutive_failures'] ?? 0) + 1;

        $data = [
            'platform' => $platform,
            'integration_id' => $integrationId,
            'last_failure' => now()->toIso8601String(),
            'last_error' => $error,
            'consecutive_failures' => $consecutiveFailures,
            'total_syncs' => $this->incrementCounter($key, 'total_syncs'),
            'total_failures' => $this->incrementCounter($key, 'total_failures'),
        ];

        Cache::put($key, array_merge($existing, $data), now()->addDays(7));

        Log::error("Sync failure recorded", [
            'platform' => $platform,
            'integration_id' => $integrationId,
            'error' => $error,
            'consecutive_failures' => $consecutiveFailures
        ]);

        // Send alert if consecutive failures exceed threshold
        if ($consecutiveFailures >= 3) {
            $this->sendAlert(
                "Critical: {$platform} sync failing",
                "Integration {$integrationId} has failed {$consecutiveFailures} times in a row. Last error: {$error}"
            );
        }
    }

    /**
     * Check for stale syncs (haven't run recently)
     */
    public function checkForStaleSyncs(): array
    {
        $staleIntegrations = [];
        $threshold = now()->subHours(self::ALERT_THRESHOLD_HOURS);

        $integrations = Integration::where('status', 'active')
            ->whereIn('platform', ['facebook', 'google', 'linkedin', 'snapchat', 'tiktok'])
            ->get();

        foreach ($integrations as $integration) {
            $key = self::CACHE_PREFIX . "{$integration->platform}:{$integration->id}";
            $data = Cache::get($key);

            if (!$data || !isset($data['last_success'])) {
                $staleIntegrations[] = [
                    'platform' => $integration->platform,
                    'integration_id' => $integration->id,
                    'tenant' => $integration->tenant->name,
                    'issue' => 'Never synced',
                    'severity' => 'high'
                ];
                continue;
            }

            $lastSuccess = Carbon::parse($data['last_success']);

            if ($lastSuccess->lt($threshold)) {
                $staleIntegrations[] = [
                    'platform' => $integration->platform,
                    'integration_id' => $integration->id,
                    'tenant' => $integration->tenant->name,
                    'issue' => "Last sync: {$lastSuccess->diffForHumans()}",
                    'severity' => 'medium'
                ];
            }
        }

        if (!empty($staleIntegrations)) {
            $this->sendAlert(
                "Stale syncs detected",
                "Found " . count($staleIntegrations) . " integrations with stale syncs:\n" .
                json_encode($staleIntegrations, JSON_PRETTY_PRINT)
            );
        }

        return $staleIntegrations;
    }

    /**
     * Check error rates across all platforms
     */
    public function checkErrorRates(): array
    {
        $highErrorRates = [];

        $integrations = Integration::where('status', 'active')
            ->whereIn('platform', ['facebook', 'google', 'linkedin', 'snapchat', 'tiktok'])
            ->get();

        foreach ($integrations as $integration) {
            $key = self::CACHE_PREFIX . "{$integration->platform}:{$integration->id}";
            $data = Cache::get($key);

            if (!$data) continue;

            $totalSyncs = $data['total_syncs'] ?? 0;
            $totalFailures = $data['total_failures'] ?? 0;

            if ($totalSyncs < 10) continue; // Not enough data

            $errorRate = $totalSyncs > 0 ? $totalFailures / $totalSyncs : 0;

            if ($errorRate > self::ERROR_RATE_THRESHOLD) {
                $highErrorRates[] = [
                    'platform' => $integration->platform,
                    'integration_id' => $integration->id,
                    'tenant' => $integration->tenant->name,
                    'error_rate' => round($errorRate * 100, 2) . '%',
                    'total_syncs' => $totalSyncs,
                    'total_failures' => $totalFailures,
                    'severity' => $errorRate > 0.5 ? 'critical' : 'high'
                ];
            }
        }

        if (!empty($highErrorRates)) {
            $this->sendAlert(
                "High error rates detected",
                "Found " . count($highErrorRates) . " integrations with high error rates:\n" .
                json_encode($highErrorRates, JSON_PRETTY_PRINT)
            );
        }

        return $highErrorRates;
    }

    /**
     * Get sync health report
     */
    public function getHealthReport(): array
    {
        $report = [
            'overall_health' => 'good',
            'platforms' => [],
            'issues' => []
        ];

        $integrations = Integration::where('status', 'active')
            ->whereIn('platform', ['facebook', 'google', 'linkedin', 'snapchat', 'tiktok'])
            ->get();

        foreach ($integrations as $integration) {
            $key = self::CACHE_PREFIX . "{$integration->platform}:{$integration->id}";
            $data = Cache::get($key, []);

            $totalSyncs = $data['total_syncs'] ?? 0;
            $totalSuccesses = $data['total_successes'] ?? 0;
            $totalFailures = $data['total_failures'] ?? 0;
            $consecutiveFailures = $data['consecutive_failures'] ?? 0;

            $successRate = $totalSyncs > 0 ? ($totalSuccesses / $totalSyncs) * 100 : 0;

            $platformData = [
                'platform' => $integration->platform,
                'integration_id' => $integration->id,
                'tenant' => $integration->tenant->name,
                'last_success' => $data['last_success'] ?? null,
                'last_failure' => $data['last_failure'] ?? null,
                'consecutive_failures' => $consecutiveFailures,
                'total_syncs' => $totalSyncs,
                'success_rate' => round($successRate, 2),
                'health' => $this->determineHealth($consecutiveFailures, $successRate)
            ];

            $report['platforms'][] = $platformData;

            if ($platformData['health'] !== 'good') {
                $report['issues'][] = $platformData;
                if ($platformData['health'] === 'critical') {
                    $report['overall_health'] = 'critical';
                } elseif ($platformData['health'] === 'warning' && $report['overall_health'] === 'good') {
                    $report['overall_health'] = 'warning';
                }
            }
        }

        return $report;
    }

    /**
     * Send alert (log for now, can be extended to email/Slack/etc)
     */
    private function sendAlert(string $subject, string $message): void
    {
        Log::channel('slack')->critical($subject, ['message' => $message]);

        // Can add additional notification channels here:
        // - Email
        // - Slack webhook
        // - SMS
        // - PagerDuty
    }

    /**
     * Increment counter
     */
    private function incrementCounter(string $key, string $field): int
    {
        $data = Cache::get($key, []);
        $count = ($data[$field] ?? 0) + 1;
        return $count;
    }

    /**
     * Determine health status
     */
    private function determineHealth(int $consecutiveFailures, float $successRate): string
    {
        if ($consecutiveFailures >= 5 || $successRate < 50) {
            return 'critical';
        } elseif ($consecutiveFailures >= 2 || $successRate < 80) {
            return 'warning';
        }
        return 'good';
    }
}
