<?php

namespace App\Console\Commands;

use App\Models\AdMetric;
use App\Models\Integration;
use App\Services\SyncMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSyncHealth extends Command
{
    protected $signature = 'sync:check-health';
    protected $description = 'Check health status of all platform syncs';

    protected SyncMonitoringService $monitoringService;

    public function __construct(SyncMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $this->info("=== Sync Health Check ===\n");

        // Get health report
        $report = $this->monitoringService->getHealthReport();

        // Display overall health
        $healthColor = match($report['overall_health']) {
            'good' => '<fg=green>GOOD</>',
            'warning' => '<fg=yellow>WARNING</>',
            'critical' => '<fg=red>CRITICAL</>',
            default => 'UNKNOWN'
        };

        $this->line("Overall Health: {$healthColor}\n");

        // Display platform details
        if (!empty($report['platforms'])) {
            $headers = ['Platform', 'Tenant', 'Health', 'Success Rate', 'Consec. Failures', 'Last Success'];
            $rows = [];

            foreach ($report['platforms'] as $platform) {
                $healthEmoji = match($platform['health']) {
                    'good' => '✓',
                    'warning' => '⚠',
                    'critical' => '✗',
                    default => '?'
                };

                $lastSuccess = $platform['last_success']
                    ? \Carbon\Carbon::parse($platform['last_success'])->diffForHumans()
                    : 'Never';

                $rows[] = [
                    "{$healthEmoji} {$platform['platform']}",
                    $platform['tenant'],
                    $platform['health'],
                    $platform['success_rate'] . '%',
                    $platform['consecutive_failures'],
                    $lastSuccess
                ];
            }

            $this->table($headers, $rows);
        }

        // Display issues
        if (!empty($report['issues'])) {
            $this->newLine();
            $this->error("Issues Found: " . count($report['issues']));

            foreach ($report['issues'] as $issue) {
                $detail = $issue['consecutive_failures'] > 0
                    ? "{$issue['consecutive_failures']} consecutive failures"
                    : ($issue['last_warning_message'] ?? 'degraded');
                $this->warn("  • {$issue['platform']} ({$issue['tenant']}): {$issue['health']} - {$detail}");
            }
        } else {
            $this->newLine();
            $this->info("No issues found - all syncs are healthy!");
        }

        // Data freshness check — query actual latest metric dates per platform
        $this->newLine();
        $this->info("=== Data Freshness Check ===\n");
        $this->checkDataFreshness();

        // Check for stale syncs
        $this->newLine();
        $this->info("Checking for stale syncs...");
        $staleSyncs = $this->monitoringService->checkForStaleSyncs();

        if (!empty($staleSyncs)) {
            $this->error("Found " . count($staleSyncs) . " stale sync(s):");
            foreach ($staleSyncs as $stale) {
                $this->warn("  • {$stale['platform']} ({$stale['tenant']}): {$stale['issue']}");
            }
        } else {
            $this->info("No stale syncs found.");
        }

        // Check error rates
        $this->newLine();
        $this->info("Checking error rates...");
        $highErrorRates = $this->monitoringService->checkErrorRates();

        if (!empty($highErrorRates)) {
            $this->error("Found " . count($highErrorRates) . " integration(s) with high error rates:");
            foreach ($highErrorRates as $errorRate) {
                $this->warn("  • {$errorRate['platform']} ({$errorRate['tenant']}): {$errorRate['error_rate']} error rate");
            }
        } else {
            $this->info("No high error rates found.");
        }

        // Upgrade overall health based on data freshness
        // Skip platforms with recent successful syncs (campaigns inactive, not broken)
        if ($report['overall_health'] !== 'critical') {
            $freshness = $this->getDataFreshness();
            $recentSyncPlatforms = $this->getPlatformsWithRecentSuccessfulSync();
            foreach ($freshness as $row) {
                $daysStale = $row->days_stale;
                if ($daysStale > 3 && $row->total_metrics > 0 && !in_array($row->platform, $recentSyncPlatforms)) {
                    $report['overall_health'] = $daysStale > 7 ? 'critical' : 'warning';
                }
            }
        }

        return $report['overall_health'] === 'critical' ? 1 : 0;
    }

    private function checkDataFreshness(): void
    {
        $freshness = $this->getDataFreshness();
        $recentSyncPlatforms = $this->getPlatformsWithRecentSuccessfulSync();

        $headers = ['Platform', 'Latest Data', 'Days Stale', 'Accounts', 'Total Metrics', 'Status'];
        $rows = [];

        foreach ($freshness as $row) {
            $daysStale = $row->days_stale;

            if ($row->total_metrics === 0) {
                $status = 'NO DATA';
            } elseif ($daysStale <= 1) {
                $status = 'OK';
            } elseif ($daysStale <= 3) {
                $status = 'OK';
            } elseif (in_array($row->platform, $recentSyncPlatforms)) {
                // Sync ran recently and succeeded but no new data — campaigns are inactive
                $status = 'INACTIVE';
            } elseif ($daysStale <= 7) {
                $status = 'STALE';
            } else {
                $status = 'CRITICAL';
            }

            $rows[] = [
                $row->platform,
                $row->latest_date ?? 'null',
                $daysStale ?? 'N/A',
                $row->account_count,
                number_format($row->total_metrics),
                $status,
            ];
        }

        $this->table($headers, $rows);

        // Check for needs_reauth integrations
        $needsReauth = Integration::where('status', 'needs_reauth')->get();
        if ($needsReauth->isNotEmpty()) {
            $this->newLine();
            $this->error("Integrations requiring re-authorization:");
            foreach ($needsReauth as $integration) {
                $this->warn("  • {$integration->platform} (ID: {$integration->id}, Tenant: " . ($integration->tenant?->name ?? 'N/A') . ") — needs re-auth at /integrations");
            }
        }
    }

    private function getDataFreshness()
    {
        return DB::table('ad_metrics')
            ->select(
                'platform',
                DB::raw('MAX(date) as latest_date'),
                DB::raw('DATEDIFF(CURDATE(), MAX(date)) as days_stale'),
                DB::raw('COUNT(DISTINCT ad_account_id) as account_count'),
                DB::raw('COUNT(*) as total_metrics')
            )
            ->groupBy('platform')
            ->orderBy('platform')
            ->get();
    }

    /**
     * Get platforms that had a successful sync within the last 24 hours.
     * Used to distinguish "sync broken" from "campaigns inactive" when data is stale.
     */
    private function getPlatformsWithRecentSuccessfulSync(): array
    {
        $platforms = [];
        $threshold = now()->subHours(24);

        $integrations = Integration::where('status', 'active')
            ->whereIn('platform', ['facebook', 'google', 'linkedin', 'snapchat', 'tiktok', 'twitter'])
            ->get();

        foreach ($integrations as $integration) {
            $key = 'sync_status:' . "{$integration->platform}:{$integration->id}";
            $data = \Illuminate\Support\Facades\Cache::get($key);

            if ($data && isset($data['last_success'])) {
                $lastSuccess = \Carbon\Carbon::parse($data['last_success']);
                if ($lastSuccess->gte($threshold) && ($data['consecutive_failures'] ?? 0) === 0) {
                    $platforms[] = $integration->platform;
                }
            }
        }

        return array_unique($platforms);
    }
}
