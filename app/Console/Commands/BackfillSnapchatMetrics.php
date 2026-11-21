<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\SnapchatAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillSnapchatMetrics extends Command
{
    protected $signature = 'snapchat:backfill-metrics
                            {integration_id? : Specific integration ID to backfill}
                            {--start-date= : Start date (Y-m-d format, default: 24 months ago)}
                            {--end-date= : End date (Y-m-d format, default: today)}
                            {--account-id= : Specific ad account ID to backfill}
                            {--full-history : Backfill all available historical data (20+ years)}
                            {--quick : Use TOTAL granularity for fast verification (no daily breakdown)}';

    protected $description = 'Backfill historical Snapchat Ads metrics data (supports 20+ years of data)';

    private SnapchatAdsService $snapchatAdsService;

    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
    }

    public function handle(): int
    {
        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');
        $fullHistory = $this->option('full-history');
        $quickMode = $this->option('quick');

        if ($quickMode) {
            $this->warn("Quick mode enabled - using TOTAL granularity (no daily breakdown)");
        }

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

        $this->info("=== Snapchat Ads Historical Backfill ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("=========================================\n");

        // Get integrations
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
            return Command::FAILURE;
        }

        $this->info("Found {$integrations->count()} Snapchat Ads integration(s) to backfill\n");

        $totalAccounts = 0;
        $totalCampaigns = 0;
        $totalMetrics = 0;
        $errors = [];

        foreach ($integrations as $integration) {
            $this->info("Processing Integration ID: {$integration->id}");
            $this->info("Tenant: " . ($integration->tenant?->name ?? 'No Tenant'));

            // Get valid access token (with auto-refresh)
            $accessToken = $this->snapchatAdsService->ensureValidToken($integration);

            if (!$accessToken) {
                $error = "No valid access token for integration {$integration->id}";
                $this->error($error);
                $errors[] = $error;
                continue;
            }

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
                $totalCampaigns += $campaigns->count();

                $timezone = $adAccount->account_config['time_zone'] ?? 'UTC';

                foreach ($campaigns as $campaign) {
                    try {
                        $synced = $this->syncCampaignMetrics(
                            $adAccount,
                            $campaign,
                            $accessToken,
                            $startDate,
                            $endDate,
                            $quickMode,
                            $timezone
                        );

                        $this->info("      ✓ {$campaign->name}: {$synced} metrics synced");
                        $totalMetrics += $synced;

                    } catch (\Exception $e) {
                        $error = "Error syncing campaign {$campaign->name}: " . $e->getMessage();
                        $this->error("      ✗ {$error}");
                        $errors[] = $error;

                        Log::error('Snapchat backfill failed for campaign', [
                            'campaign_id' => $campaign->id,
                            'account_id' => $adAccount->id,
                            'integration_id' => $integration->id,
                            'error' => $e->getMessage(),]);
                    }
                }

                $this->newLine();
            }
        }

        // Summary
        $this->info("=== Backfill Summary ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("Total Integrations: {$integrations->count()}");
        $this->info("Total Ad Accounts: {$totalAccounts}");
        $this->info("Total Campaigns: {$totalCampaigns}");
        $this->info("Total Metrics Synced: {$totalMetrics}");

        if (!empty($errors)) {
            $this->error("\nErrors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            return Command::FAILURE;
        }

        $this->info("\n✓ Backfill completed successfully!");
        return Command::SUCCESS;
    }

    private function syncCampaignMetrics(
        AdAccount $adAccount,
        AdCampaign $campaign,
        string $accessToken,
        string $startDate,
        string $endDate,
        bool $quickMode = false,
        string $timezone = 'UTC'
    ): int {
        $totalSynced = 0;

        // Quick mode: use TOTAL granularity (single API call for entire date range)
        if ($quickMode) {
            try {
                $stats = $this->snapchatAdsService->getCampaignTotalStats(
                    $adAccount->external_account_id,
                    $accessToken,
                    [$campaign->external_campaign_id],
                    $startDate,
                    $endDate
                );

                foreach ($stats as $stat) {
                    if (($stat['campaign_id'] ?? '') !== $campaign->external_campaign_id) {
                        continue;
                    }

                    // For TOTAL granularity, use end date as the metric date
                    $metricDate = $stat['end_date'] ?? $endDate;
                    $checksum = $this->generateChecksum($adAccount->id, $campaign->id, 'total_' . $metricDate);

                    $signUps = $stat['sign_ups'] ?? 0;
                    $swipes = $stat['swipes'] ?? 0;
                    $purchases = $stat['purchases'] ?? 0;

                    AdMetric::updateOrCreate(
                        ['checksum' => $checksum],
                        [
                            'tenant_id' => $adAccount->tenant_id,
                            'date' => $metricDate,
                            'platform' => 'snapchat',
                            'ad_account_id' => $adAccount->id,
                            'ad_campaign_id' => $campaign->id,
                            'objective' => $campaign->objective,
                            'funnel_stage' => $campaign->funnel_stage,
                            'user_journey' => $campaign->user_journey,
                            'has_pixel_data' => $campaign->has_pixel_data,
                            'spend' => $stat['spend'] ?? 0,
                            'impressions' => $stat['impressions'] ?? 0,
                            'clicks' => $swipes,
                            'link_clicks' => $swipes,
                            'swipes' => $swipes,
                            'video_views' => $stat['video_views'] ?? 0,
                            'conversions' => $purchases + $signUps,
                            'leads' => $signUps,
                            'native_leads' => $signUps,
                            'blended_leads' => $signUps,
                            'sign_ups' => $signUps,
                            'revenue' => $stat['purchase_value'] ?? 0,
                            'purchases' => $purchases,
                            'calls' => 0,
                            'sessions' => 0,
                            'atc' => $stat['add_to_cart'] ?? 0,
                            'reach' => 0,
                        ]
                    );
                    $totalSynced++;
                }
            } catch (\Exception $e) {
                Log::warning('Snapchat quick sync failed', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $totalSynced;
        }

        // Normal mode: pass full date range — service handles chunking internally
        $stats = $this->snapchatAdsService->getCampaignStats(
            $adAccount->external_account_id,
            $accessToken,
            [$campaign->external_campaign_id],
            $startDate,
            $endDate,
            $timezone
        );

        $metricsToUpsert = [];
        $now = now();

        foreach ($stats as $stat) {
            if (($stat['campaign_id'] ?? '') !== $campaign->external_campaign_id) {
                continue;
            }

            $metricDate = \Carbon\Carbon::parse($stat['date_range']['start'])->format('Y-m-d');
            $checksum = $this->generateChecksum($adAccount->id, $campaign->id, $metricDate);

            $signUps = $stat['sign_ups'] ?? 0;
            $swipes = $stat['swipes'] ?? 0;
            $purchases = $stat['purchases'] ?? 0;

            $metricsToUpsert[] = [
                'checksum' => $checksum,
                'tenant_id' => $adAccount->tenant_id,
                'date' => $metricDate,
                'platform' => 'snapchat',
                'ad_account_id' => $adAccount->id,
                'ad_campaign_id' => $campaign->id,
                'objective' => $campaign->objective,
                'funnel_stage' => $campaign->funnel_stage,
                'user_journey' => $campaign->user_journey,
                'has_pixel_data' => $campaign->has_pixel_data ?? false,
                'spend' => $stat['spend'] ?? 0,
                'impressions' => $stat['impressions'] ?? 0,
                'clicks' => $swipes,
                'link_clicks' => $swipes,
                'swipes' => $swipes,
                'video_views' => $stat['video_views'] ?? 0,
                'conversions' => $purchases + $signUps,
                'leads' => $signUps,
                'native_leads' => $signUps,
                'blended_leads' => $signUps,
                'sign_ups' => $signUps,
                'revenue' => $stat['purchase_value'] ?? 0,
                'purchases' => $purchases,
                'calls' => 0,
                'sessions' => 0,
                'atc' => $stat['add_to_cart'] ?? 0,
                'reach' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Batch upsert in chunks of 500
        if (!empty($metricsToUpsert)) {
            foreach (array_chunk($metricsToUpsert, 500) as $chunk) {
                AdMetric::upsert(
                    $chunk,
                    ['checksum'],
                    [
                        'tenant_id', 'date', 'platform', 'ad_account_id', 'ad_campaign_id',
                        'objective', 'funnel_stage', 'user_journey', 'has_pixel_data',
                        'spend', 'impressions', 'clicks', 'link_clicks', 'swipes', 'video_views', 'conversions',
                        'leads', 'native_leads', 'blended_leads', 'sign_ups',
                        'revenue', 'purchases', 'calls', 'sessions', 'atc', 'reach',
                        'updated_at'
                    ]
                );
            }
            $totalSynced = count($metricsToUpsert);
        }

        return $totalSynced;
    }

    /**
     * Generate unique checksum for metric deduplication
     */
    private function generateChecksum(int $accountId, int $campaignId, string $date): string
    {
        return hash('sha256', "snapchat_{$accountId}_{$campaignId}_{$date}");
    }

}
