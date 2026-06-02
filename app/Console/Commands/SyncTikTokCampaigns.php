<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\AdCampaign;
use App\Services\IndustryDetectionService;
use App\Services\TikTokAdsService;
use App\Services\SyncMonitoringService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTikTokCampaigns extends Command
{
    use TracksSyncProgress;
    protected $signature = 'tiktok:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from TikTok Ads for all or specific integration';

    protected TikTokAdsService $tiktokAdsService;
    private SyncMonitoringService $monitoringService;
    private IndustryDetectionService $industryDetectionService;

    public function __construct(TikTokAdsService $tiktokAdsService, SyncMonitoringService $monitoringService, IndustryDetectionService $industryDetectionService)
    {
        parent::__construct();
        $this->tiktokAdsService = $tiktokAdsService;
        $this->monitoringService = $monitoringService;
        $this->industryDetectionService = $industryDetectionService;
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
            $this->info('No active TikTok Ads integrations found — nothing to sync');
            return 0;
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
            $this->initSyncProgress($integration->id);

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

            // Discover new ad accounts from TikTok API
            $this->discoverTikTokAccounts($integration, $accessToken);

            $adAccounts = $integration->adAccounts()->get();
            $this->reportStep("Found {$adAccounts->count()} ad accounts to sync");

            $totalSynced = 0;
            foreach ($adAccounts as $adAccount) {
                $this->reportCurrentStep("Syncing: {$adAccount->account_name}");

                $advertiserId = $adAccount->external_account_id;

                if (!$advertiserId) {
                    $this->error("No advertiser ID found for account {$adAccount->id}");
                    continue;
                }

                // Fetch campaigns for this ad account
                $campaigns = $this->tiktokAdsService->getCampaigns($accessToken, $advertiserId);
                $this->reportStep("Found " . count($campaigns) . " campaigns for {$adAccount->account_name}");

                foreach ($campaigns as $campaign) {
                    $this->info("Processing campaign: {$campaign['name']} ({$campaign['id']})");

                    $objective = $this->mapTikTokObjective($campaign['objective'] ?? 'unknown');

                    AdCampaign::updateOrCreate(
                        [
                            'ad_account_id' => $adAccount->id,
                            'external_campaign_id' => $campaign['id'],
                        ],
                        [
                            'tenant_id' => $adAccount->tenant_id,
                            'name' => $campaign['name'],
                            'objective' => $objective,
                            'status' => $campaign['status'] === 'active' ? 'active' : 'paused',
                            'channel_type' => 'TikTok',
                            'funnel_stage' => AdCampaign::funnelStageForObjective($objective),
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

            $this->reportStep("Sync complete: {$totalSynced} campaigns", 'success');

            $this->monitoringService->recordSuccess('tiktok', $integration->id, [
                'campaigns_synced' => $totalSynced,
            ]);

            $this->completeSyncProgress(['campaigns_synced' => $totalSynced, 'accounts_processed' => $adAccounts->count()]);

        } catch (\Exception $e) {
            $this->failSyncProgress($e->getMessage());
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('TikTok Ads campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            // Detect auth/token errors and mark integration for re-authorization
            $msg = $e->getMessage();
            if (
                str_contains($msg, '40001') ||
                str_contains($msg, 'access_token') ||
                str_contains($msg, 'unauthorized') ||
                str_contains($msg, 'AUTH_FAILED')
            ) {
                $integration->update(['status' => 'needs_reauth']);
                $this->warn("Marked integration {$integration->id} as needs_reauth");
            }

            $this->monitoringService->recordFailure('tiktok', $integration->id, $e->getMessage());
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

    private function discoverTikTokAccounts(Integration $integration, string $accessToken): void
    {
        try {
            $apiAccounts = $this->tiktokAdsService->getAdAccounts($accessToken);
            $newCount = 0;

            foreach ($apiAccounts as $account) {
                $existing = AdAccount::withoutGlobalScopes()
                    ->where('integration_id', $integration->id)
                    ->where('external_account_id', $account['id'])
                    ->exists();

                if (!$existing) {
                    AdAccount::create([
                        'integration_id' => $integration->id,
                        'external_account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => strtolower($account['status'] ?? '') === 'enable' ? 'active' : 'inactive',
                        'industry' => $this->industryDetectionService->detectIndustry($account['name'] ?? ''),
                        'tenant_id' => $integration->tenant_id,
                        'account_config' => [
                            'time_zone' => $account['time_zone'] ?? null,
                            'company' => $account['company'] ?? null,
                        ],
                    ]);
                    $newCount++;
                }
            }

            if ($newCount > 0) {
                $this->info("Discovered {$newCount} new TikTok ad account(s)");
            }
        } catch (\Exception $e) {
            $this->warn('Account discovery failed: ' . $e->getMessage());
        }
    }
}