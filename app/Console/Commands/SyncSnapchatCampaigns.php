<?php

namespace App\Console\Commands;
use App\Models\Scopes\TenantScope;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\AdCampaign;
use App\Services\IndustryDetectionService;
use App\Services\SnapchatAdsService;
use App\Services\SyncMonitoringService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSnapchatCampaigns extends Command
{
    use TracksSyncProgress;
    protected $signature = 'snapchat:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from Snapchat Ads for all or specific integration';

    protected SnapchatAdsService $snapchatAdsService;
    private SyncMonitoringService $monitoringService;
    private IndustryDetectionService $industryDetectionService;

    public function __construct(SnapchatAdsService $snapchatAdsService, SyncMonitoringService $monitoringService, IndustryDetectionService $industryDetectionService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
        $this->monitoringService = $monitoringService;
        $this->industryDetectionService = $industryDetectionService;
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
            $this->info('No active Snapchat Ads integrations found — nothing to sync');
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

            // Auto-refresh token if needed before syncing
            $accessToken = $this->snapchatAdsService->ensureValidToken($integration);

            if (!$accessToken) {
                $integration->update(['status' => 'needs_reauth']);
                $this->error('No valid access token available for Snapchat integration — marked as needs_reauth');
                return;
            }

            $this->info("Access token: " . substr($accessToken, 0, 20) . "...");

            // Discover new ad accounts from Snapchat API
            $this->discoverSnapchatAccounts($integration, $accessToken);

            $adAccounts = $integration->adAccounts()->get();
            $this->reportStep("Found {$adAccounts->count()} ad accounts to sync");

            $totalCreated = 0;
            $totalUpdated = 0;

            foreach ($adAccounts as $adAccount) {
                $this->reportCurrentStep("Syncing: {$adAccount->account_name}");

                // Use the service method for comprehensive sync with category detection
                $result = $this->snapchatAdsService->syncCampaignsToDatabase($adAccount, $accessToken);

                $this->reportStep("Synced {$result['total']} campaigns for {$adAccount->account_name}", 'success');

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
            }

            $this->reportStep("Sync complete: " . ($totalCreated + $totalUpdated) . " campaigns ({$totalCreated} new, {$totalUpdated} updated)", 'success');

            $this->monitoringService->recordSuccess('snapchat', $integration->id, [
                'campaigns_created' => $totalCreated,
                'campaigns_updated' => $totalUpdated,
            ]);

            $this->completeSyncProgress(['campaigns_synced' => $totalCreated + $totalUpdated, 'accounts_processed' => $adAccounts->count()]);

        } catch (\Exception $e) {
            $this->failSyncProgress($e->getMessage());
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('Snapchat Ads campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            $this->monitoringService->recordFailure('snapchat', $integration->id, $e->getMessage());
        }
    }

    private function discoverSnapchatAccounts(Integration $integration, string $accessToken): void
    {
        try {
            $apiAccounts = $this->snapchatAdsService->getAdAccounts($accessToken);
            $newCount = 0;

            foreach ($apiAccounts as $account) {
                $existing = AdAccount::withoutGlobalScope(TenantScope::class)
                    ->where('integration_id', $integration->id)
                    ->where('external_account_id', $account['id'])
                    ->exists();

                if (!$existing) {
                    $status = match (strtoupper($account['status'] ?? 'ACTIVE')) {
                        'ACTIVE' => 'active',
                        default => 'inactive',
                    };

                    AdAccount::create([
                        'integration_id' => $integration->id,
                        'external_account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $status,
                        'industry' => $this->industryDetectionService->detectIndustry($account['name'] ?? ''),
                        'tenant_id' => $integration->tenant_id,
                        'account_config' => [
                            'time_zone' => $account['time_zone'] ?? null,
                            'organization_id' => $account['organization_id'] ?? null,
                        ],
                    ]);
                    $newCount++;
                }
            }

            if ($newCount > 0) {
                $this->info("Discovered {$newCount} new Snapchat ad account(s)");
            }
        } catch (\Exception $e) {
            $this->warn('Account discovery failed: ' . $e->getMessage());
        }
    }
}