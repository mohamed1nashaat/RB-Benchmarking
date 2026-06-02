<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\TwitterAdsService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTwitterCampaigns extends Command
{
    use TracksSyncProgress;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:twitter-campaigns {--tenant-id=} {--date=} {--days=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync X/Twitter campaigns and metrics data';

    protected TwitterAdsService $twitterService;

    public function __construct(TwitterAdsService $twitterService)
    {
        parent::__construct();
        $this->twitterService = $twitterService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant-id');
        $specificDate = $this->option('date');
        $days = (int) $this->option('days');

        if ($specificDate) {
            $dates = [$specificDate];
        } else {
            $dates = [];
            for ($i = $days; $i >= 1; $i--) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }
        }

        $this->info("Starting X/Twitter sync for dates: " . implode(', ', $dates));

        // Get active Twitter integrations
        $query = Integration::where('platform', 'twitter')
            ->where('status', 'active')
            ->with('adAccounts.adCampaigns');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->info("Filtering by tenant ID: {$tenantId}");
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active X/Twitter integrations found — nothing to sync');
            return 0;
        }

        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($integrations as $integration) {
            $this->initSyncProgress($integration->id);
            $this->info("Processing X/Twitter integration for tenant {$integration->tenant_id}");

            // Auto-refresh token if needed before syncing
            $accessToken = $this->twitterService->ensureValidToken($integration);
            if (!$accessToken) {
                $integration->update(['status' => 'needs_reauth']);
                $this->error("No valid access token for Twitter integration {$integration->id} — marked as needs_reauth");
                $totalErrors++;
                continue;
            }

            try {
                // First sync accounts and campaigns
                $accounts = $this->twitterService->syncAdAccounts($integration);
                $this->reportStep("Found " . count($accounts) . " X/Twitter ad accounts");

                // Sync campaigns for each account
                foreach ($accounts as $account) {
                    $this->reportCurrentStep("Syncing: {$account->account_name}");
                    $campaigns = $this->twitterService->syncCampaigns($integration, $account);
                    $this->reportStep("Synced " . count($campaigns) . " campaigns for {$account->account_name}");

                    // Sync metrics for each campaign for each date
                    foreach ($dates as $date) {
                        $this->info("  Syncing metrics for date: {$date}");
                        foreach ($campaigns as $campaign) {
                            try {
                                $this->twitterService->syncCampaignMetrics($integration, $campaign, $date);
                                $totalSynced++;
                            } catch (\Exception $e) {
                            $this->error("Failed to sync metrics for campaign {$campaign->name}: " . $e->getMessage());
                            Log::error('Twitter campaign metrics sync failed', [
                                'campaign_id' => $campaign->id,
                                'campaign_name' => $campaign->name,
                                'error' => $e->getMessage()
                            ]);
                            $totalErrors++;
                        }
                    }
                }
                }

                // Update last sync time
                $integration->update(['last_sync_at' => now()]);

                $this->completeSyncProgress(['accounts_processed' => count($accounts), 'campaigns_synced' => $totalSynced]);

            } catch (\Exception $e) {
                $this->failSyncProgress($e->getMessage());
                $this->error("Failed to sync X/Twitter integration {$integration->id}: " . $e->getMessage());
                Log::error('Twitter integration sync failed', [
                    'integration_id' => $integration->id,
                    'tenant_id' => $integration->tenant_id,
                    'error' => $e->getMessage()
                ]);

                // Detect auth/token errors and mark integration for re-authorization
                $msg = $e->getMessage();
                if (
                    str_contains($msg, 'AUTH_FAILED') ||
                    str_contains($msg, '401') ||
                    str_contains($msg, 'unauthorized')
                ) {
                    $integration->update(['status' => 'needs_reauth']);
                    $this->warn("Marked integration {$integration->id} as needs_reauth");
                }

                $totalErrors++;
            }
        }

        $this->info("X/Twitter sync completed:");
        $this->info("- Campaigns synced: {$totalSynced}");
        $this->info("- Errors: {$totalErrors}");

        return $totalErrors > 0 ? 1 : 0;
    }
}
