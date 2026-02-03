<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\LinkedInAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLinkedInCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:linkedin-campaigns {--tenant-id=} {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync LinkedIn campaigns and metrics data';

    protected LinkedInAdsService $linkedinService;

    public function __construct(LinkedInAdsService $linkedinService)
    {
        parent::__construct();
        $this->linkedinService = $linkedinService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant-id');
        $date = $this->option('date') ?? now()->subDay()->format('Y-m-d');

        $this->info("Starting LinkedIn sync for date: {$date}");

        // Get active LinkedIn integrations
        $query = Integration::where('platform', 'linkedin')
            ->where('status', 'active')
            ->with('adAccounts.adCampaigns');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->info("Filtering by tenant ID: {$tenantId}");
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->warn('No active LinkedIn integrations found');
            return 1;
        }

        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($integrations as $integration) {
            $this->info("Processing LinkedIn integration for tenant {$integration->tenant_id}");

            try {
                // First sync accounts and campaigns
                $accounts = $this->linkedinService->syncAdAccounts($integration);
                $this->info("Synced " . count($accounts) . " LinkedIn ad accounts");

                // Sync campaigns for each account
                foreach ($accounts as $account) {
                    $campaigns = $this->linkedinService->syncCampaigns($integration, $account);
                    $this->info("Synced " . count($campaigns) . " campaigns for account: {$account->account_name}");

                    // Sync metrics for each campaign
                    foreach ($campaigns as $campaign) {
                        try {
                            $this->linkedinService->syncCampaignMetrics($integration, $campaign, $date);
                            $totalSynced++;
                        } catch (\Exception $e) {
                            $this->error("Failed to sync metrics for campaign {$campaign->name}: " . $e->getMessage());
                            Log::error('LinkedIn campaign metrics sync failed', [
                                'campaign_id' => $campaign->id,
                                'campaign_name' => $campaign->name,
                                'error' => $e->getMessage()
                            ]);
                            $totalErrors++;
                        }
                    }
                }

                // Update last sync time
                $integration->update(['last_sync_at' => now()]);

            } catch (\Exception $e) {
                $this->error("Failed to sync LinkedIn integration {$integration->id}: " . $e->getMessage());
                Log::error('LinkedIn integration sync failed', [
                    'integration_id' => $integration->id,
                    'tenant_id' => $integration->tenant_id,
                    'error' => $e->getMessage()
                ]);
                $totalErrors++;
            }
        }

        $this->info("LinkedIn sync completed:");
        $this->info("- Campaigns synced: {$totalSynced}");
        $this->info("- Errors: {$totalErrors}");

        return $totalErrors > 0 ? 1 : 0;
    }
}
