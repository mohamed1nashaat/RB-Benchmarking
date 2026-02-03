<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackfillTikTokMetrics extends Command
{
    protected $signature = 'tiktok:backfill-metrics
                            {integration_id? : Specific integration ID to backfill}
                            {--start-date= : Start date (Y-m-d format, default: 24 months ago)}
                            {--end-date= : End date (Y-m-d format, default: today)}
                            {--account-id= : Specific ad account ID to backfill}
                            {--full-history : Backfill all available historical data (20+ years)}';

    protected $description = 'Backfill historical TikTok Ads metrics data (supports 20+ years of data)';

    private const API_BASE_URL = 'https://business-api.tiktok.com/open_api/v1.3';

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');
        $fullHistory = $this->option('full-history');

        // Parse date range
        if ($fullHistory) {
            $startDate = Carbon::now()->subYears(20)->format('Y-m-d');
            $this->warn("Full history mode enabled - fetching 20+ years of data. This may take a while...");
        } else {
            $startDate = $this->option('start-date')
                ? Carbon::parse($this->option('start-date'))->format('Y-m-d')
                : Carbon::now()->subMonths(24)->format('Y-m-d');
        }

        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');

        $this->info("=== TikTok Ads Historical Backfill ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("=========================================\n");

        // Get integrations
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

        $this->info("Found {$integrations->count()} TikTok Ads integration(s) to backfill\n");

        $totalAccounts = 0;
        $totalMetrics = 0;
        $errors = [];

        foreach ($integrations as $integration) {
            $this->info("Processing Integration ID: {$integration->id}");
            $this->info("Tenant: {$integration->tenant->name}");

            $config = $integration->app_config;
            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $error = "Invalid app config for integration {$integration->id}";
                $this->error($error);
                $errors[] = $error;
                continue;
            }

            $accessToken = $config['access_token'];

            // Get ad accounts
            $query = $integration->adAccounts()->with('adCampaigns');
            if ($accountId) {
                $query->where('id', $accountId);
            }
            $adAccounts = $query->get();

            if ($adAccounts->isEmpty()) {
                $this->warn("No ad accounts found for integration {$integration->id}");
                continue;
            }

            $this->info("Found {$adAccounts->count()} ad account(s)\n");
            $totalAccounts += $adAccounts->count();

            foreach ($adAccounts as $adAccount) {
                $this->info("  Account: {$adAccount->account_name} (ID: {$adAccount->id})");

                $campaigns = $adAccount->adCampaigns;
                if ($campaigns->isEmpty()) {
                    $this->warn("    No campaigns found for account");
                    continue;
                }

                $this->info("    Found {$campaigns->count()} campaign(s)");

                try {
                    // Sync at account level with campaign_id in dimensions to avoid duplication
                    $synced = $this->syncAccountMetrics(
                        $adAccount,
                        $campaigns,
                        $accessToken,
                        $startDate,
                        $endDate
                    );

                    $this->info("    ✓ Synced {$synced} metrics");
                    $totalMetrics += $synced;

                } catch (\Exception $e) {
                    $error = "Error syncing account {$adAccount->account_name}: " . $e->getMessage();
                    $this->error("    ✗ {$error}");
                    $errors[] = $error;

                    Log::error('TikTok backfill failed for account', [
                        'account_id' => $adAccount->id,
                        'integration_id' => $integration->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $this->newLine();
            }
        }

        // Summary
        $this->info("=== Backfill Summary ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("Total Integrations: {$integrations->count()}");
        $this->info("Total Ad Accounts: {$totalAccounts}");
        $this->info("Total Metrics Synced: {$totalMetrics}");

        if (!empty($errors)) {
            $this->error("\nErrors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            return 1;
        }

        $this->info("\n✓ Backfill completed successfully!");
        return 0;
    }

    /**
     * Sync metrics at account level with campaign_id in dimensions
     * This prevents duplicate data across campaigns
     */
    private function syncAccountMetrics(
        AdAccount $adAccount,
        $campaigns,
        string $accessToken,
        string $startDate,
        string $endDate
    ): int {
        $url = self::API_BASE_URL . '/report/integrated/get/';
        $totalSynced = 0;

        // Build a lookup map of external_campaign_id => campaign model
        $campaignMap = [];
        foreach ($campaigns as $campaign) {
            $campaignMap[$campaign->external_campaign_id] = $campaign;
        }

        if (empty($campaignMap)) {
            return 0;
        }

        // TikTok API has 30-day max limit, so chunk the date range
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($start->lt($end)) {
            $chunkEnd = $start->copy()->addDays(29);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunkStartStr = $start->format('Y-m-d');
            $chunkEndStr = $chunkEnd->format('Y-m-d');

            $this->info("      Fetching {$chunkStartStr} to {$chunkEndStr}...");

            // Fetch ALL campaign metrics in one call with campaign_id in dimensions
            $params = [
                'advertiser_id' => $adAccount->external_account_id,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_CAMPAIGN',
                'dimensions' => '["stat_time_day", "campaign_id"]',  // Include campaign_id to get per-campaign data
                'metrics' => '["spend","impressions","clicks","reach","cpc","cpm","ctr","frequency"]',
                'start_date' => $chunkStartStr,
                'end_date' => $chunkEndStr,
                'page_size' => 1000,
            ];

            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get($url, $params);

            if (!$response->successful()) {
                $start = $chunkEnd->copy()->addDay();
                continue;
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== 0) {
                $start = $chunkEnd->copy()->addDay();
                continue;
            }

            $insights = $data['data']['list'] ?? [];
            $chunkSynced = 0;

            foreach ($insights as $insight) {
                $metrics = $insight['metrics'] ?? [];
                $dimensions = $insight['dimensions'] ?? [];

                // Get campaign_id from dimensions
                $campaignExternalId = $dimensions['campaign_id'] ?? null;
                $date = $dimensions['stat_time_day'] ?? $chunkStartStr;

                // Clean up date format if needed
                if (str_contains($date, ' ')) {
                    $date = explode(' ', $date)[0];
                }

                // Find the matching campaign
                $campaign = $campaignMap[$campaignExternalId] ?? null;
                if (!$campaign) {
                    // Skip metrics for unknown campaigns
                    continue;
                }

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

            $this->info("        Synced {$chunkSynced} metrics");
            $totalSynced += $chunkSynced;

            // Move to next 30-day chunk
            $start = $chunkEnd->copy()->addDay();
        }

        return $totalSynced;
    }
}
