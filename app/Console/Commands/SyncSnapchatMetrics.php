<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\SnapchatAdsService;
use App\Services\SyncMonitoringService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncSnapchatMetrics extends Command
{
    use TracksSyncProgress;

    protected $signature = 'snapchat:sync-metrics {--days=7 : Number of days to sync} {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)} {--all : Sync all available historical data} {--account-id= : Specific ad account ID to sync} {--integration-id= : Specific integration ID to sync}';
    protected $description = 'Sync Snapchat ad metrics for all connected accounts';

    private SnapchatAdsService $snapchatAdsService;
    private SyncMonitoringService $monitoringService;

    public function __construct(SnapchatAdsService $snapchatAdsService, SyncMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Snapchat metrics sync...');

        try {
            $days = (int) $this->option('days');
            $startDateOption = $this->option('start-date');
            $endDateOption = $this->option('end-date');
            $allTime = $this->option('all');
            $accountId = $this->option('account-id');
            $integrationId = $this->option('integration-id');

            // Use explicit dates if provided, otherwise use days
            $endDate = Carbon::now()->format('Y-m-d');

            if ($allTime) {
                // Will be determined dynamically per-account based on earliest campaign
                $startDate = null;
                $this->warn("All-time sync enabled - will detect earliest campaign date from API");
            } elseif ($startDateOption && $endDateOption) {
                $startDate = Carbon::parse($startDateOption)->format('Y-m-d');
                $endDate = Carbon::parse($endDateOption)->format('Y-m-d');
            } else {
                $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            }

            if ($startDate) {
                $this->info("Syncing metrics from {$startDate} to {$endDate}");
            }

            // Get active Snapchat integration (filter by ID if provided)
            $query = Integration::where('platform', 'snapchat')
                ->where('status', 'active');

            if ($integrationId) {
                $query->where('id', $integrationId);
            }

            $integration = $query->first();

            if (!$integration) {
                $this->error('No active Snapchat integration found');
                return Command::FAILURE;
            }

            $this->initSyncProgress($integration->id);

            // Auto-refresh token if needed before syncing
            $accessToken = $this->snapchatAdsService->ensureValidToken($integration);

            if (!$accessToken) {
                $integration->update(['status' => 'needs_reauth']);
                $this->error('No valid access token available for Snapchat integration — marked as needs_reauth');
                $this->failSyncProgress('No valid access token available');
                return Command::FAILURE;
            }

            // Get Snapchat ad accounts (filter by specific ID if provided)
            $query = $integration->adAccounts();
            if ($accountId) {
                $query->where('id', $accountId);
                $this->info("Filtering to specific account ID: {$accountId}");
            }
            $adAccounts = $query->get();
            $this->reportStep("Found {$adAccounts->count()} ad accounts");

            $totalMetricsSynced = 0;
            $totalErrors = 0;
            $accountIndex = 0;
            $accountTotal = $adAccounts->count();

            foreach ($adAccounts as $adAccount) {
                $accountIndex++;
                $this->reportCurrentStep("Syncing account {$accountIndex}/{$accountTotal}: {$adAccount->account_name}");

                try {
                    // For all-time sync, dynamically detect earliest campaign start date
                    $accountStartDate = $startDate;
                    if ($allTime) {
                        $accountStartDate = $this->getEarliestCampaignDate($adAccount, $accessToken);
                        $this->info("  Syncing from {$accountStartDate} to {$endDate} (earliest campaign date)");
                    }

                    $result = $this->syncMetricsForAccount($adAccount, $accessToken, $accountStartDate, $endDate);
                    $totalMetricsSynced += $result['synced'];
                    $totalErrors += $result['errors'];

                    // Update last metrics sync timestamp
                    $adAccount->update(['last_metrics_sync_at' => now()]);

                    $this->reportStep("Account {$adAccount->account_name}: {$result['synced']} metrics synced", 'success');
                } catch (\Exception $e) {
                    $this->error("  Error syncing account {$adAccount->external_account_id}: {$e->getMessage()}");
                    $totalErrors++;
                }
            }

            $this->info("\n=== All Time Sync Complete ===");
            $this->info("Total metrics synced: {$totalMetricsSynced}");
            $this->info("Total errors: {$totalErrors}");

            $stats = [
                'accounts_processed' => $adAccounts->count(),
                'metrics_synced' => $totalMetricsSynced,
                'errors' => $totalErrors,
            ];

            $this->monitoringService->recordSuccess('snapchat', $integration->id, $stats);
            $this->completeSyncProgress($stats);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error('Snapchat metrics sync failed', ['error' => $e->getMessage()]);
            $this->monitoringService->recordFailure('snapchat', $integration->id ?? 0, $e->getMessage());
            $this->failSyncProgress($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Sync metrics for a specific ad account
     */
    private function syncMetricsForAccount(AdAccount $adAccount, string $accessToken, string $startDate, string $endDate): array
    {
        $synced = 0;
        $errors = 0;

        // Get all campaigns for this account
        $campaigns = $adAccount->adCampaigns;

        if ($campaigns->isEmpty()) {
            $this->warn("  No campaigns found for account {$adAccount->account_name}");
            return ['synced' => 0, 'errors' => 0];
        }

        $this->info("  Found {$campaigns->count()} campaigns");

        // Create campaign lookup map for O(1) access instead of O(N) firstWhere()
        $campaignMap = $campaigns->keyBy('external_campaign_id');

        // Get campaign IDs
        $campaignIds = $campaigns->pluck('external_campaign_id')->toArray();

        // Get account timezone (required for Snapchat API)
        $timezone = $adAccount->account_config['time_zone'] ?? $this->fetchAccountTimezone($adAccount, $accessToken);

        // Output progress markers for date range (for SyncProgressService cache tracking)
        $progressStart = Carbon::parse($startDate);
        $progressEnd = Carbon::parse($endDate);
        $totalMonths = (int) $progressStart->diffInMonths($progressEnd) + 1;
        $currentMonth = $progressStart->copy();

        $this->info("  Syncing {$totalMonths} months of data...");

        // Output each month marker for progress tracking
        while ($currentMonth->lte($progressEnd)) {
            $monthLabel = $currentMonth->format('F Y');
            $this->info("=== Processing {$monthLabel} ===");
            $currentMonth->addMonth();
        }

        // Fetch stats from Snapchat API
        try {
            $stats = $this->snapchatAdsService->getCampaignStats(
                $adAccount->external_account_id,
                $accessToken,
                $campaignIds,
                $startDate,
                $endDate,
                $timezone
            );

            // Batch upsert - collect all metrics first
            $metricsToUpsert = [];
            $now = now();

            foreach ($stats as $stat) {
                // O(1) lookup instead of O(N) firstWhere()
                $campaign = $campaignMap[$stat['campaign_id']] ?? null;

                if (!$campaign) {
                    $this->warn("    Campaign {$stat['campaign_id']} not found in database, skipping");
                    Log::warning('Snapchat campaign not found in database during metrics sync', [
                        'external_campaign_id' => $stat['campaign_id'],
                        'ad_account_id' => $adAccount->id,
                        'ad_account_name' => $adAccount->account_name,
                    ]);
                    $errors++;
                    continue;
                }

                // Parse the date from the stat (Snapchat returns date range)
                $metricDate = Carbon::parse($stat['date_range']['start'])->format('Y-m-d');

                // Generate checksum for deduplication
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

            // Batch upsert in chunks of 500 for optimal performance
            if (!empty($metricsToUpsert)) {
                $chunks = array_chunk($metricsToUpsert, 500);
                foreach ($chunks as $chunk) {
                    AdMetric::upsert(
                        $chunk,
                        ['checksum'], // Unique key for matching
                        [ // Columns to update on conflict
                            'tenant_id', 'date', 'platform', 'ad_account_id', 'ad_campaign_id',
                            'objective', 'funnel_stage', 'user_journey', 'has_pixel_data',
                            'spend', 'impressions', 'clicks', 'link_clicks', 'swipes', 'video_views', 'conversions',
                            'leads', 'native_leads', 'blended_leads', 'sign_ups',
                            'revenue', 'purchases', 'calls', 'sessions', 'atc', 'reach',
                            'updated_at'
                        ]
                    );
                }
                $synced = count($metricsToUpsert);
                $this->info("    Batch upserted {$synced} metrics");
            }

        } catch (\Exception $e) {
            $this->error("  Error fetching stats from Snapchat API: {$e->getMessage()}");
            Log::error('Snapchat API error', [
                'ad_account_id' => $adAccount->external_account_id,
                'error' => $e->getMessage()
            ]);
            $errors++;
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Generate unique checksum for metric deduplication
     */
    private function generateChecksum(int $accountId, int $campaignId, string $date): string
    {
        return hash('sha256', "snapchat_{$accountId}_{$campaignId}_{$date}");
    }

    /**
     * Fetch account timezone from Snapchat API and cache it
     */
    private function fetchAccountTimezone(AdAccount $adAccount, string $accessToken): string
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get("https://adsapi.snapchat.com/v1/adaccounts/{$adAccount->external_account_id}");

            if ($response->successful()) {
                $data = $response->json();
                $accountData = $data['adaccounts'][0]['adaccount'] ?? [];
                $timezone = $accountData['timezone'] ?? 'UTC';

                // Cache the timezone in account_config
                $config = $adAccount->account_config ?? [];
                $config['time_zone'] = $timezone;
                $adAccount->update(['account_config' => $config]);

                return $timezone;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Snapchat account timezone', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage()
            ]);
        }

        return 'UTC'; // Fallback
    }

    /**
     * Get earliest campaign start date from Snapchat API
     */
    private function getEarliestCampaignDate(AdAccount $adAccount, string $accessToken): string
    {
        try {
            // Fetch campaigns from Snapchat API to get start times
            $campaigns = $this->snapchatAdsService->getCampaigns(
                $adAccount->external_account_id,
                $accessToken
            );

            if ($campaigns->isEmpty()) {
                // Fallback to 1 year if no campaigns
                return Carbon::now()->subYear()->format('Y-m-d');
            }

            // Find earliest start_time
            $earliestDate = null;
            foreach ($campaigns as $campaign) {
                $startTime = $campaign['start_time'] ?? null;
                if ($startTime) {
                    $campaignStart = Carbon::parse($startTime);
                    if (!$earliestDate || $campaignStart->lt($earliestDate)) {
                        $earliestDate = $campaignStart;
                    }
                }
            }

            if ($earliestDate) {
                return $earliestDate->format('Y-m-d');
            }

            // Fallback to 1 year if no start times found
            return Carbon::now()->subYear()->format('Y-m-d');

        } catch (\Exception $e) {
            Log::warning('Failed to get earliest campaign date', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage()
            ]);
            // Fallback to 1 year
            return Carbon::now()->subYear()->format('Y-m-d');
        }
    }
}
