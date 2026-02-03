<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\SnapchatAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncSnapchatMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapchat:sync-metrics {--days=7 : Number of days to sync} {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)} {--all : Sync all available historical data} {--account-id= : Specific ad account ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Snapchat ad metrics for all connected accounts';

    private SnapchatAdsService $snapchatAdsService;

    /**
     * Create a new command instance.
     */
    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
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

            // Get active Snapchat integration
            $integration = Integration::where('platform', 'snapchat')
                ->where('status', 'active')
                ->first();

            if (!$integration) {
                $this->error('No active Snapchat integration found');
                return Command::FAILURE;
            }

            // Auto-refresh token if needed before syncing
            $accessToken = $this->ensureValidToken($integration);

            if (!$accessToken) {
                $this->error('No valid access token available for Snapchat integration');
                return Command::FAILURE;
            }

            // Get Snapchat ad accounts (filter by specific ID if provided)
            $query = $integration->adAccounts();
            if ($accountId) {
                $query->where('id', $accountId);
                $this->info("Filtering to specific account ID: {$accountId}");
            }
            $adAccounts = $query->get();
            $this->info("Found {$adAccounts->count()} ad accounts");

            $totalMetricsSynced = 0;
            $totalErrors = 0;

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing metrics for ad account: {$adAccount->account_name}");

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

                    $this->info("  ✓ Synced {$result['synced']} metrics, {$result['errors']} errors");
                } catch (\Exception $e) {
                    $this->error("  ✗ Error syncing account {$adAccount->external_account_id}: {$e->getMessage()}");
                    $totalErrors++;
                }
            }

            $this->info("\n=== All Time Sync Complete ===");
            $this->info("Total metrics synced: {$totalMetricsSynced}");
            $this->info("Total errors: {$totalErrors}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error('Snapchat metrics sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

        // Output progress markers for date range (for sync-progress API to parse)
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
                    $errors++;
                    continue;
                }

                // Parse the date from the stat (Snapchat returns date range)
                $metricDate = Carbon::parse($stat['date_range']['start'])->format('Y-m-d');

                // Generate checksum for deduplication
                $checksum = $this->generateChecksum($adAccount->id, $campaign->id, $metricDate);

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
                    'clicks' => $stat['swipes'] ?? 0,
                    'video_views' => $stat['video_views'] ?? 0,
                    'conversions' => 0,
                    'leads' => 0,
                    'revenue' => 0,
                    'purchases' => 0,
                    'calls' => 0,
                    'sessions' => $stat['swipes'] ?? 0,
                    'atc' => 0,
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
                            'spend', 'impressions', 'clicks', 'video_views', 'conversions',
                            'leads', 'revenue', 'purchases', 'calls', 'sessions', 'atc', 'reach',
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
     * Ensure we have a valid access token, refreshing if needed
     */
    private function ensureValidToken(Integration $integration): ?string
    {
        $config = $integration->app_config;
        $accessToken = $config['access_token'] ?? null;
        $refreshToken = $config['refresh_token'] ?? null;
        $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;

        if (!$accessToken) {
            $this->warn('No access token found');
            return null;
        }

        // Check if token is expired or expiring soon (less than 10 minutes)
        $needsRefresh = false;
        if ($expiresAt) {
            $expiresTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
            $minutesUntilExpiry = ($expiresTimestamp - time()) / 60;

            if ($minutesUntilExpiry < 10) {
                $needsRefresh = true;
                $this->info("Token expires in {$minutesUntilExpiry} minutes, refreshing...");
            }
        }

        if ($needsRefresh && $refreshToken) {
            $this->info('Refreshing Snapchat access token...');

            $tokenResponse = $this->snapchatAdsService->refreshAccessToken($refreshToken);

            if (isset($tokenResponse['access_token'])) {
                // Update the integration with new tokens
                $config['access_token'] = $tokenResponse['access_token'];

                if (isset($tokenResponse['refresh_token'])) {
                    $config['refresh_token'] = $tokenResponse['refresh_token'];
                }

                $expiresIn = $tokenResponse['expires_in'] ?? 3600;
                $config['expires_at'] = time() + $expiresIn;
                $config['token_expires_at'] = time() + $expiresIn;
                $config['last_refreshed_at'] = now()->toISOString();

                $integration->update(['app_config' => $config]);

                $this->info('Token refreshed successfully');
                Log::info('Snapchat token auto-refreshed during sync', [
                    'integration_id' => $integration->id,
                    'expires_in' => $expiresIn
                ]);

                return $tokenResponse['access_token'];
            } else {
                $this->error('Token refresh failed: ' . ($tokenResponse['message'] ?? 'Unknown error'));
                Log::error('Snapchat token refresh failed during sync', [
                    'integration_id' => $integration->id,
                    'error' => $tokenResponse
                ]);
                // Try with existing token anyway
                return $accessToken;
            }
        }

        return $accessToken;
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
