<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdCampaign;
use App\Services\SnapchatAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSnapchatCampaigns extends Command
{
    protected $signature = 'snapchat:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from Snapchat Ads for all or specific integration';

    protected SnapchatAdsService $snapchatAdsService;

    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');

        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'snapchat')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'snapchat')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->error('No Snapchat Ads integrations found.');
            return 1;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing campaigns for integration ID: {$integration->id}");
            $this->syncCampaignsForIntegration($integration);
        }

        return 0;
    }

    private function syncCampaignsForIntegration(Integration $integration)
    {
        try {
            $config = $integration->app_config;

            // Ensure config is an array
            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("Invalid app config for integration {$integration->id}");
                return;
            }

            $accessToken = $config['access_token'];

            $this->info("Access token: " . substr($accessToken, 0, 20) . "...");

            $adAccounts = $integration->adAccounts;
            $this->info("Found {$adAccounts->count()} ad accounts to sync");

            $totalCreated = 0;
            $totalUpdated = 0;

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing campaigns for ad account: {$adAccount->account_name} ({$adAccount->external_account_id})");

                // Use the service method for comprehensive sync with category detection
                $result = $this->snapchatAdsService->syncCampaignsToDatabase($adAccount, $accessToken);

                $this->info("✓ Synced {$result['total']} campaigns (Created: {$result['created']}, Updated: {$result['updated']})");

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
            }

            $this->info("=== Summary ===");
            $this->info("Total campaigns created: $totalCreated");
            $this->info("Total campaigns updated: $totalUpdated");
            $this->info("Successfully completed sync for integration {$integration->id}");

        } catch (\Exception $e) {
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('Snapchat Ads campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}