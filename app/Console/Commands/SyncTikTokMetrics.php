<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\TikTokAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncTikTokMetrics extends Command
{
    protected $signature = 'tiktok:sync-metrics {integration_id?} {--days=30} {--all : Sync all available historical data} {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)} {--account-id= : Specific ad account ID to sync}';
    protected $description = 'Sync metrics from TikTok Ads for campaigns';

    private const API_BASE_URL = 'https://business-api.tiktok.com/open_api/v1.3';

    private TikTokAdsService $tikTokAdsService;

    public function __construct(TikTokAdsService $tikTokAdsService)
    {
        parent::__construct();
        $this->tikTokAdsService = $tikTokAdsService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        $days = $this->option('days');
        $allTime = $this->option('all');
        $startDateOption = $this->option('start-date');
        $endDateOption = $this->option('end-date');
        $accountId = $this->option('account-id');

        // If account-id is provided, find the integration from the account
        if ($accountId && !$integrationId) {
            $adAccount = AdAccount::find($accountId);
            if ($adAccount) {
                $integrationId = $adAccount->integration_id;
            }
        }

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
            $this->info("Syncing metrics for integration ID: {$integration->id}");
            $this->syncMetricsForIntegration($integration, $days, $allTime, $startDateOption, $endDateOption, $accountId);
        }

        return 0;
    }

    private function syncMetricsForIntegration(Integration $integration, int $days, bool $allTime, ?string $startDateOption, ?string $endDateOption, ?string $accountId)
    {
        try {
            $config = $integration->app_config;

            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("Invalid app config for integration {$integration->id}");
                return;
            }

            $accessToken = $config['access_token'];

            // Get date range
            $endDate = $endDateOption ? Carbon::parse($endDateOption) : Carbon::now();

            if ($startDateOption) {
                $startDate = Carbon::parse($startDateOption);
            } elseif (!$allTime) {
                $startDate = $endDate->copy()->subDays($days);
            } else {
                // Will be set per account for all-time sync
                $startDate = null;
            }

            // Get ad accounts
            $adAccountsQuery = $integration->adAccounts()->with('adCampaigns');
            if ($accountId) {
                $adAccountsQuery->where('id', $accountId);
            }
            $adAccounts = $adAccountsQuery->get();

            $this->info("Found {$adAccounts->count()} ad accounts to sync");

            $totalSynced = 0;
            $totalErrors = 0;

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing metrics for ad account: {$adAccount->account_name}");

                try {
                    // For all-time sync, dynamically detect earliest campaign start date
                    $accountStartDate = $startDate;
                    if ($allTime && !$startDate) {
                        $accountStartDate = Carbon::parse($this->getEarliestCampaignDate($adAccount, $accessToken));
                        $this->info("  Syncing from {$accountStartDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} (earliest campaign date)");
                    } else {
                        $this->info("  Syncing from {$accountStartDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
                    }

                    $campaigns = $adAccount->adCampaigns;
                    $this->info("  Found {$campaigns->count()} campaigns");

                    // Sync metrics at account level (not per-campaign) to avoid duplication
                    $synced = $this->syncAccountMetrics(
                        $adAccount,
                        $campaigns,
                        $accessToken,
                        $accountStartDate,
                        $endDate
                    );
                    $totalSynced += $synced;

                } catch (\Exception $e) {
                    $this->error("  Error syncing account {$adAccount->external_account_id}: {$e->getMessage()}");
                    $totalErrors++;
                }
            }

            $this->info("\n=== Sync Complete ===");
            $this->info("Total metrics synced: {$totalSynced}");
            $this->info("Total errors: {$totalErrors}");

        } catch (\Exception $e) {
            $this->error("Error syncing metrics: " . $e->getMessage());
            Log::error('TikTok metrics sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync metrics at account level with campaign_id in dimensions
     * This prevents duplicate data across campaigns
     */
    private function syncAccountMetrics(
        AdAccount $adAccount,
        $campaigns,
        string $accessToken,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $url = self::API_BASE_URL . '/report/integrated/get/';
        $totalSynced = 0;

        // Build a lookup map of external_campaign_id => campaign model
        $campaignMap = [];
        foreach ($campaigns as $campaign) {
            $campaignMap[$campaign->external_campaign_id] = $campaign;
        }

        if (empty($campaignMap)) {
            $this->info("  No campaigns to sync");
            return 0;
        }

        // TikTok API has 30-day max limit, so chunk the date range
        $currentStart = $startDate->copy();

        while ($currentStart->lt($endDate)) {
            $chunkEnd = $currentStart->copy()->addDays(29);
            if ($chunkEnd->gt($endDate)) {
                $chunkEnd = $endDate->copy();
            }

            $this->info("  Fetching {$currentStart->format('Y-m-d')} to {$chunkEnd->format('Y-m-d')}...");

            // Fetch ALL campaign metrics in one call with campaign_id in dimensions
            $params = [
                'advertiser_id' => $adAccount->external_account_id,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_CAMPAIGN',
                'dimensions' => '["stat_time_day", "campaign_id"]',  // Include campaign_id to get per-campaign data
                'metrics' => '["spend","impressions","clicks","reach","cpc","cpm","ctr","frequency"]',
                'start_date' => $currentStart->format('Y-m-d'),
                'end_date' => $chunkEnd->format('Y-m-d'),
                'page_size' => 1000,
            ];

            try {
                $response = Http::withHeaders([
                    'Access-Token' => $accessToken,
                ])->get($url, $params);

                if (!$response->successful()) {
                    $this->error("  API call failed: " . $response->body());
                    $currentStart = $chunkEnd->copy()->addDay();
                    continue;
                }

                $data = $response->json();

                if (!isset($data['code']) || $data['code'] !== 0) {
                    $this->error("  TikTok API error: " . ($data['message'] ?? 'Unknown error'));
                    $currentStart = $chunkEnd->copy()->addDay();
                    continue;
                }

                $insights = $data['data']['list'] ?? [];
                $this->info("    Found " . count($insights) . " metric rows");

                $chunkSynced = 0;
                foreach ($insights as $insight) {
                    $metrics = $insight['metrics'] ?? [];
                    $dimensions = $insight['dimensions'] ?? [];

                    // Get campaign_id from dimensions
                    $campaignExternalId = $dimensions['campaign_id'] ?? null;
                    $date = $dimensions['stat_time_day'] ?? $currentStart->format('Y-m-d');

                    // Clean up date format if needed
                    if (str_contains($date, ' ')) {
                        $date = explode(' ', $date)[0];
                    }

                    // Find the matching campaign
                    $campaign = $campaignMap[$campaignExternalId] ?? null;
                    if (!$campaign) {
                        // Skip metrics for unknown campaigns (not in our DB)
                        continue;
                    }

                    // Generate checksum for this metric record
                    $checksum = md5("{$campaign->tenant_id}:{$campaign->ad_account_id}:{$campaign->id}:tiktok:{$date}");

                    AdMetric::updateOrCreate(
                        [
                            'tenant_id' => $campaign->tenant_id,
                            'ad_account_id' => $campaign->ad_account_id,
                            'ad_campaign_id' => $campaign->id,
                            'platform' => 'tiktok',
                            'date' => $date,
                        ],
                        [
                            'spend' => floatval($metrics['spend'] ?? 0),
                            'impressions' => intval($metrics['impressions'] ?? 0),
                            'clicks' => intval($metrics['clicks'] ?? 0),
                            'conversions' => 0,
                            'revenue' => 0,
                            'reach' => intval($metrics['reach'] ?? 0),
                            'video_views' => 0,
                            'leads' => 0,
                            'checksum' => $checksum,
                        ]
                    );

                    $chunkSynced++;
                }

                $this->info("    Synced {$chunkSynced} metrics");
                $totalSynced += $chunkSynced;

            } catch (\Exception $e) {
                $this->error("  Error fetching metrics: " . $e->getMessage());
                Log::error('TikTok metrics chunk sync failed', [
                    'account_id' => $adAccount->id,
                    'start' => $currentStart->format('Y-m-d'),
                    'end' => $chunkEnd->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
            }

            $currentStart = $chunkEnd->copy()->addDay();
        }

        return $totalSynced;
    }

    /**
     * Get earliest campaign start date from TikTok API
     */
    private function getEarliestCampaignDate(AdAccount $adAccount, string $accessToken): string
    {
        try {
            // Fetch campaigns from TikTok API to get creation times
            $campaigns = $this->tikTokAdsService->getCampaigns(
                $accessToken,
                $adAccount->external_account_id
            );

            if (empty($campaigns)) {
                return Carbon::now()->subYear()->format('Y-m-d');
            }

            // Find earliest create_time
            $earliestDate = null;
            foreach ($campaigns as $campaign) {
                $createTime = $campaign['create_time'] ?? null;
                if ($createTime) {
                    $campaignStart = Carbon::parse($createTime);
                    if (!$earliestDate || $campaignStart->lt($earliestDate)) {
                        $earliestDate = $campaignStart;
                    }
                }
            }

            if ($earliestDate) {
                return $earliestDate->format('Y-m-d');
            }

            return Carbon::now()->subYear()->format('Y-m-d');

        } catch (\Exception $e) {
            Log::warning('Failed to get earliest TikTok campaign date', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage()
            ]);
            return Carbon::now()->subYear()->format('Y-m-d');
        }
    }
}
