<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdCampaign;
use App\Services\TikTokAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTikTokCampaigns extends Command
{
    protected $signature = 'tiktok:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from TikTok Ads for all or specific integration';

    protected TikTokAdsService $tiktokAdsService;

    public function __construct(TikTokAdsService $tiktokAdsService)
    {
        parent::__construct();
        $this->tiktokAdsService = $tiktokAdsService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');

        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'tiktok')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'tiktok')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->error('No TikTok Ads integrations found.');
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

            $totalSynced = 0;
            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing campaigns for ad account: {$adAccount->account_name} ({$adAccount->external_account_id})");

                $advertiserId = $adAccount->external_account_id;

                if (!$advertiserId) {
                    $this->error("No advertiser ID found for account {$adAccount->id}");
                    continue;
                }

                // Fetch campaigns for this ad account
                $campaigns = $this->tiktokAdsService->getCampaigns($accessToken, $advertiserId);
                $this->info("Found " . count($campaigns) . " campaigns for this account");

                foreach ($campaigns as $campaign) {
                    $this->info("Processing campaign: {$campaign['name']} ({$campaign['id']})");

                    AdCampaign::updateOrCreate(
                        [
                            'ad_account_id' => $adAccount->id,
                            'external_campaign_id' => $campaign['id'],
                        ],
                        [
                            'tenant_id' => $integration->tenant_id,
                            'name' => $campaign['name'],
                            'objective' => $this->mapTikTokObjective($campaign['objective'] ?? 'unknown'),
                            'status' => $campaign['status'] === 'active' ? 'active' : 'paused',
                            'campaign_config' => [
                                'tiktok_objective' => $campaign['objective'] ?? null,
                                'budget' => $campaign['budget'] ?? null,
                                'budget_mode' => $campaign['budget_mode'] ?? null,
                            ],
                        ]
                    );
                    $totalSynced++;
                }
            }

            $this->info("Successfully synced $totalSynced campaigns for integration {$integration->id}");

        } catch (\Exception $e) {
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('TikTok Ads campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function mapTikTokObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'TRAFFIC', 'REACH', 'VIDEO_VIEWS' => 'awareness',
            'CONVERSIONS', 'APP_INSTALL', 'LEAD_GENERATION' => 'leads',
            'CATALOG_SALES', 'WEB_CONVERSIONS' => 'sales',
            'PHONE_CALLS' => 'calls',
            default => 'awareness',
        };
    }
}