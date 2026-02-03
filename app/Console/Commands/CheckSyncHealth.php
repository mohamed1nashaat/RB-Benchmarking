<?php

namespace App\Console\Commands;

use App\Services\SyncMonitoringService;
use Illuminate\Console\Command;

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
                $this->warn("  • {$issue['platform']} ({$issue['tenant']}): {$issue['health']} - {$issue['consecutive_failures']} consecutive failures");
            }
        } else {
            $this->newLine();
            $this->info("No issues found - all syncs are healthy!");
        }

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

        return $report['overall_health'] === 'critical' ? 1 : 0;
    }
}
