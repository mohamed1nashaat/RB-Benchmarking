<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\LinkedInAdsService;
use App\Services\SyncMonitoringService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLinkedInCampaigns extends Command
{
    use TracksSyncProgress;

    protected $signature = 'sync:linkedin-campaigns {--tenant-id=} {--integration-id=} {--date=} {--days=2}';
    protected $description = 'Sync LinkedIn campaigns and metrics data';

    protected LinkedInAdsService $linkedinService;
    private SyncMonitoringService $monitoringService;

    public function __construct(LinkedInAdsService $linkedinService, SyncMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->linkedinService = $linkedinService;
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $tenantId = $this->option('tenant-id');
        $integrationId = $this->option('integration-id');
        $specificDate = $this->option('date');
        $days = (int) $this->option('days');

        // Support multi-day sync to avoid data loss if scheduler skips a day
        if ($specificDate) {
            $dates = [$specificDate];
        } else {
            $dates = [];
            for ($i = $days; $i >= 1; $i--) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }
        }

        $this->info("Starting LinkedIn sync for dates: " . implode(', ', $dates));

        // Get active LinkedIn integrations
        $query = Integration::where('platform', 'linkedin')
            ->where('status', 'active')
            ->with('adAccounts.adCampaigns');

        if ($integrationId) {
            $query->where('id', $integrationId);
            $this->info("Filtering by integration ID: {$integrationId}");
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->info("Filtering by tenant ID: {$tenantId}");
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active LinkedIn integrations found — nothing to sync');
            return 0;
        }

        foreach ($integrations as $integration) {
            $totalSynced = 0;
            $totalErrors = 0;

            $this->initSyncProgress($integration->id);
            $this->info("Processing LinkedIn integration for tenant {$integration->tenant_id}");

            // Auto-refresh token if needed before syncing
            $accessToken = $this->linkedinService->ensureValidToken($integration);
            if (!$accessToken) {
                $integration->update(['status' => 'needs_reauth']);
                $this->error("No valid access token for LinkedIn integration {$integration->id} — marked as needs_reauth");
                $totalErrors++;
                $this->monitoringService->recordFailure('linkedin', $integration->id, 'No valid access token — needs re-authorization');
                $this->failSyncProgress('No valid access token');
                continue;
            }

            try {
                // First sync accounts and campaigns
                $accounts = $this->linkedinService->syncAdAccounts($integration);
                $this->reportStep("Found " . count($accounts) . " LinkedIn ad accounts");

                $accountIndex = 0;
                $accountTotal = count($accounts);

                // Sync campaigns for each account
                foreach ($accounts as $account) {
                    $accountIndex++;
                    $this->reportCurrentStep("Syncing account {$accountIndex}/{$accountTotal}: {$account->account_name}");

                    $campaigns = $this->linkedinService->syncCampaigns($integration, $account);
                    $this->reportStep("Synced " . count($campaigns) . " campaigns for account: {$account->account_name}");

                    // Sync metrics for each campaign for each date
                    foreach ($dates as $date) {
                        $this->info("  Syncing metrics for date: {$date}");
                        foreach ($campaigns as $campaign) {
                            try {
                                $metricsCount = $this->linkedinService->syncCampaignMetrics($integration, $campaign, $date);
                                $totalSynced += $metricsCount;
                            } catch (\Exception $e) {
                                $this->error("Failed to sync metrics for campaign {$campaign->name} on {$date}: " . $e->getMessage());
                                Log::error('LinkedIn campaign metrics sync failed', [
                                    'campaign_id' => $campaign->id,
                                    'campaign_name' => $campaign->name,
                                    'date' => $date,
                                    'error' => $e->getMessage()
                                ]);
                                $totalErrors++;
                                // Auth errors — rethrow so the outer handler marks needs_reauth
                                $msg = $e->getMessage();
                                if (str_contains($msg, '401') || str_contains($msg, '403') || str_contains($msg, 'auth error')) {
                                    throw $e;
                                }
                            }
                        }
                    }

                    // Update last metrics sync timestamp on the ad account
                    $account->update(['last_metrics_sync_at' => now()]);
                    $this->reportStep("Account {$account->account_name} complete", 'success');
                }

                // Detect zero metrics across all campaigns — LinkedIn silently returns 200 with
                // empty data when the token is expired instead of returning 401
                if ($totalSynced === 0 && count($accounts) > 0) {
                    $this->warn("Zero metrics synced across all LinkedIn campaigns — marking as needs_reauth (LinkedIn silent token expiry)");
                    $integration->update(['status' => 'needs_reauth']);
                    $this->monitoringService->recordFailure('linkedin', $integration->id, "Zero metrics across all campaigns — token silently expired, needs re-authorization");
                    $this->failSyncProgress('Zero metrics synced — needs_reauth');
                    continue;
                }

                // Update last sync time
                $integration->update(['last_sync_at' => now()]);

                $stats = [
                    'accounts_processed' => count($accounts),
                    'campaigns_synced' => $totalSynced,
                    'dates_synced' => count($dates),
                ];

                $this->monitoringService->recordSuccess('linkedin', $integration->id, $stats);
                $this->completeSyncProgress($stats);

            } catch (\Exception $e) {
                $this->error("Failed to sync LinkedIn integration {$integration->id}: " . $e->getMessage());
                Log::error('LinkedIn integration sync failed', [
                    'integration_id' => $integration->id,
                    'tenant_id' => $integration->tenant_id,
                    'error' => $e->getMessage()
                ]);

                // Detect auth/token errors and mark integration for re-authorization
                $msg = $e->getMessage();
                if (
                    str_contains($msg, 'Token expired') ||
                    str_contains($msg, '401') ||
                    str_contains($msg, 'unauthorized') ||
                    str_contains($msg, 'AUTH_FAILED')
                ) {
                    $integration->update(['status' => 'needs_reauth']);
                    $this->warn("Marked integration {$integration->id} as needs_reauth");
                }

                $totalErrors++;
                $this->monitoringService->recordFailure('linkedin', $integration->id, $e->getMessage());
                $this->failSyncProgress($e->getMessage());
            }
        }

        $this->info("LinkedIn sync completed:");
        $this->info("- Campaigns synced: {$totalSynced}");
        $this->info("- Errors: {$totalErrors}");

        return $totalErrors > 0 ? 1 : 0;
    }
}
