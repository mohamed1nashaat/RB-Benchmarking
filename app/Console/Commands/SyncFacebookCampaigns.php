<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\CategoryMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFacebookCampaigns extends Command
{
    protected $signature = 'facebook:sync-campaigns {integration_id?}';
    protected $description = 'Sync campaigns from Facebook for all or specific integration';

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
            $this->error('No Facebook integrations found.');
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
            $totalCreated = 0;
            $totalUpdated = 0;
            $categoryMapper = app(CategoryMapper::class);

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing campaigns for ad account: {$adAccount->account_name} ({$adAccount->external_account_id})");

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
                        $this->error("API call failed: " . $campaignsResponse->body());
                        break;
                    }
                } while ($nextUrl);

                $this->info("Total campaigns fetched: " . count($allCampaigns) . " across {$pageCount} pages");

                if (!empty($allCampaigns)) {
                    foreach ($allCampaigns as $campaign) {
                        $this->info("Processing campaign: {$campaign['name']} ({$campaign['id']})");

                        // Map objective and derive funnel stage
                        $objective = $this->mapFacebookObjective($campaign['objective'] ?? 'unknown');
                        $funnelStage = $this->determineFunnelStage($objective);

                        // Auto-detect category from campaign name and account industry
                        $accountIndustry = $adAccount->industry;
                        $detectedCategory = $categoryMapper->detectCategory($campaign['name'], $accountIndustry);

                        $campaignModel = \App\Models\AdCampaign::updateOrCreate(
                            [
                                'ad_account_id' => $adAccount->id,
                                'external_campaign_id' => $campaign['id'],
                            ],
                            [
                                'tenant_id' => $integration->tenant_id,
                                'name' => $campaign['name'],
                                'objective' => $objective,
                                'status' => $campaign['status'] === 'ACTIVE' ? 'active' : 'paused',
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

            $this->info("=== Summary ===");
            $this->info("Total campaigns synced: $totalSynced");
            $this->info("Total campaigns created: $totalCreated");
            $this->info("Total campaigns updated: $totalUpdated");
            $this->info("Successfully completed sync for integration {$integration->id}");

        } catch (\Exception $e) {
            $this->error("Error syncing campaigns: " . $e->getMessage());
            Log::error('Facebook campaigns sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
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

    private function determineFunnelStage(?string $objective): ?string
    {
        return match ($objective) {
            'awareness' => 'TOF',
            'leads' => 'MOF',
            'sales', 'calls' => 'BOF',
            default => null,
        };
    }
}