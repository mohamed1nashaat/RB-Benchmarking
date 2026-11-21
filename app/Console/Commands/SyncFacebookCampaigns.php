<?php

namespace App\Console\Commands;
use App\Models\Scopes\TenantScope;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Services\CategoryMapper;
use App\Services\IndustryDetectionService;
use App\Services\SyncMonitoringService;
use App\Traits\RefreshesFacebookToken;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFacebookCampaigns extends Command
{
    use TracksSyncProgress, RefreshesFacebookToken;
    protected $signature = 'facebook:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from Facebook for all or specific integration';

    private SyncMonitoringService $monitoringService;
    private IndustryDetectionService $industryDetectionService;

    public function __construct(SyncMonitoringService $monitoringService, IndustryDetectionService $industryDetectionService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->industryDetectionService = $industryDetectionService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        
        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'facebook')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'facebook')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->info('No active Facebook integrations found — nothing to sync');
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

            $accessToken = $this->ensureValidFacebookToken($integration);
            if (!$accessToken) {
                $this->error("No valid access token for integration {$integration->id}");
                $integration->update(['status' => 'needs_reauth']);
                $this->monitoringService->recordFailure('facebook', $integration->id, 'No valid access token — re-authorization required');
                $this->failSyncProgress('No valid access token');
                return;
            }

            $this->info("Access token: " . substr($accessToken, 0, 20) . "...");

            // Discover new ad accounts from Facebook API
            $this->discoverFacebookAccounts($integration, $accessToken);

            $adAccounts = $integration->adAccounts()->get();
            $this->reportStep("Found {$adAccounts->count()} ad accounts to sync");

            $totalSynced = 0;
            $totalCreated = 0;
            $totalUpdated = 0;
            $authFailures = 0;
            $categoryMapper = app(CategoryMapper::class);

            foreach ($adAccounts as $adAccount) {
                $this->reportCurrentStep("Syncing: {$adAccount->account_name}");

                // Fetch ALL campaigns for this ad account with pagination
                $url = "https://graph.facebook.com/v23.0/{$adAccount->external_account_id}/campaigns";
                $params = [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,objective,status,created_time,updated_time',
                    'limit' => 500, // Increased from 100 to reduce API calls
                ];

                $this->info("Making API call to: $url");

                // Pagination loop to fetch ALL campaigns
                $allCampaigns = [];
                $nextUrl = null;
                $pageCount = 0;
                $accountAuthFailed = false;

                do {
                    $campaignsResponse = Http::get($nextUrl ?? $url, $nextUrl ? [] : $params);

                    if ($campaignsResponse->successful()) {
                        $responseData = $campaignsResponse->json();
                        $campaigns = $responseData['data'] ?? [];
                        $allCampaigns = array_merge($allCampaigns, $campaigns);
                        $pageCount++;

                        $this->info("Page {$pageCount}: Found " . count($campaigns) . " campaigns");

                        // Check for next page
                        $nextUrl = $responseData['paging']['next'] ?? null;
                    } else {
                        $body = $campaignsResponse->json();
                        $errorCode = $body['error']['code'] ?? 0;

                        if ($errorCode === 190) {
                            $this->error("Auth token invalid for account {$adAccount->account_name}: " . ($body['error']['message'] ?? 'OAuthException'));
                            $authFailures++;
                            $accountAuthFailed = true;
                        } else {
                            $this->error("API call failed: " . $campaignsResponse->body());
                        }
                        break;
                    }
                } while ($nextUrl);

                // Early exit: if 3+ accounts fail with auth errors, token is dead
                if ($authFailures >= 3) {
                    $this->warn("3+ auth failures — token is invalid, stopping sync early");
                    break;
                }

                if ($accountAuthFailed) {
                    continue;
                }

                $this->reportStep("Fetched " . count($allCampaigns) . " campaigns for {$adAccount->account_name}", 'success');

                if (!empty($allCampaigns)) {
                    foreach ($allCampaigns as $campaign) {
                        $this->info("Processing campaign: {$campaign['name']} ({$campaign['id']})");

                        // Map objective and derive funnel stage
                        $objective = $this->mapFacebookObjective($campaign['objective'] ?? 'unknown');
                        $funnelStage = \App\Models\AdCampaign::funnelStageForObjective($objective);

                        // Auto-detect category from campaign name and account industry
                        $accountIndustry = $adAccount->industry;
                        $detectedCategory = $categoryMapper->detectCategory($campaign['name'], $accountIndustry);

                        $campaignModel = \App\Models\AdCampaign::updateOrCreate(
                            [
                                'ad_account_id' => $adAccount->id,
                                'external_campaign_id' => $campaign['id'],
                            ],
                            [
                                'tenant_id' => $adAccount->tenant_id,
                                'name' => $campaign['name'],
                                'objective' => $objective,
                                'status' => match ($campaign['status'] ?? 'PAUSED') {
                                    'ACTIVE' => 'active',
                                    'DELETED', 'ARCHIVED' => 'archived',
                                    default => 'paused',
                                },
                                'channel_type' => 'Facebook',
                                'funnel_stage' => $funnelStage,
                                'sub_industry' => $detectedCategory,
                                'inherit_category_from_account' => $detectedCategory ? false : true,
                                'category' => $detectedCategory,
                                'user_journey' => 'landing_page',
                                'campaign_config' => [
                                    'facebook_objective' => $campaign['objective'] ?? null,
                                    'created_time' => $campaign['created_time'] ?? null,
                                    'updated_time' => $campaign['updated_time'] ?? null,
                                    'last_synced_at' => now()->toIso8601String(),
                                ],
                            ]
                        );

                        if ($campaignModel->wasRecentlyCreated) {
                            $totalCreated++;
                        } else {
                            $totalUpdated++;
                        }
                        $totalSynced++;
                    }
                }
            }

            // If ALL accounts failed with auth errors, record as failure
            if ($authFailures > 0 && $totalSynced === 0) {
                $this->error("OAuth token invalidated — all {$authFailures} account(s) failed with auth errors");
                $this->monitoringService->recordFailure('facebook', $integration->id, 'OAuth token invalidated — re-authorization required');
                $integration->update(['status' => 'needs_reauth']);
                $this->failSyncProgress('OAuth token invalidated');
                return;
            }

            $this->reportStep("Sync complete: {$totalSynced} campaigns ({$totalCreated} new, {$totalUpdated} updated)", 'success');

            $stats = [
                'campaigns_synced' => $totalSynced,
                'campaigns_created' => $totalCreated,
                'campaigns_updated' => $totalUpdated,
                'auth_failures' => $authFailures,
            ];

            if ($authFailures > 0 && $totalSynced > 0) {
                $this->monitoringService->recordPartialSuccess('facebook', $integration->id, $stats, "{$authFailures} accounts failed with auth errors");
            } else {
                $this->monitoringService->recordSuccess('facebook', $integration->id, $stats);
            }

            $this->completeSyncProgress(['campaigns_synced' => $totalSynced, 'accounts_processed' => $adAccounts->count()]);

        } catch (\Exception $e) {
            $this->failSyncProgress($e->getMessage());
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('Facebook campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            $this->monitoringService->recordFailure('facebook', $integration->id, $e->getMessage());
        }
    }

    private function mapFacebookObjective(string $facebookObjective): string
    {
        return match (strtoupper($facebookObjective)) {
            'REACH', 'BRAND_AWARENESS', 'IMPRESSIONS' => 'awareness',
            'LEAD_GENERATION', 'MESSAGES', 'CONVERSIONS' => 'leads',
            'PURCHASES', 'CATALOG_SALES' => 'sales',
            'CALLS', 'PHONE_CALLS' => 'calls',
            default => 'awareness',
        };
    }

    private function discoverFacebookAccounts(Integration $integration, string $accessToken): void
    {
        try {
            $response = Http::get('https://graph.facebook.com/v23.0/me/adaccounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,account_status,currency',
                'limit' => 500,
            ]);

            if (!$response->successful()) {
                $this->warn('Could not fetch Facebook ad accounts for discovery: ' . $response->status());
                return;
            }

            $apiAccounts = $response->json()['data'] ?? [];
            $newCount = 0;

            foreach ($apiAccounts as $account) {
                $existing = AdAccount::withoutGlobalScope(TenantScope::class)
                    ->where('integration_id', $integration->id)
                    ->where('external_account_id', $account['id'])
                    ->exists();

                if (!$existing) {
                    AdAccount::create([
                        'integration_id' => $integration->id,
                        'external_account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'status' => ($account['account_status'] ?? 0) === 1 ? 'active' : 'inactive',
                        'industry' => $this->industryDetectionService->detectIndustry($account['name'] ?? ''),
                        'tenant_id' => $integration->tenant_id,
                        'account_config' => ['currency' => $account['currency'] ?? 'USD'],
                    ]);
                    $newCount++;
                }
            }

            if ($newCount > 0) {
                $this->info("Discovered {$newCount} new Facebook ad account(s)");
            }
        } catch (\Exception $e) {
            $this->warn('Account discovery failed: ' . $e->getMessage());
        }
    }
}