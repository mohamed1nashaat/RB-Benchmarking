<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Services\GoogleAdsService;
use App\Services\GoogleAdsCampaignSyncService;
use App\Services\GoogleAdsMetricsSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncGoogleAdsCampaigns extends Command
{
    protected $signature = 'google-ads:sync
                            {integration_id? : Specific integration ID to sync}
                            {--campaigns : Sync campaigns only}
                            {--metrics : Sync metrics only}
                            {--all : Sync all time metrics (from 2015-01-01)}
                            {--start-date= : Start date for metrics sync (YYYY-MM-DD)}
                            {--end-date= : End date for metrics sync (YYYY-MM-DD)}
                            {--days= : Number of days to sync (default: 30)}';

    protected $description = 'Sync campaigns and metrics from Google Ads API';

    protected GoogleAdsService $googleAdsService;
    protected GoogleAdsCampaignSyncService $campaignSyncService;
    protected GoogleAdsMetricsSyncService $metricsSyncService;

    public function __construct(
        GoogleAdsService $googleAdsService,
        GoogleAdsCampaignSyncService $campaignSyncService,
        GoogleAdsMetricsSyncService $metricsSyncService
    ) {
        parent::__construct();
        $this->googleAdsService = $googleAdsService;
        $this->campaignSyncService = $campaignSyncService;
        $this->metricsSyncService = $metricsSyncService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        $syncCampaigns = $this->option('campaigns');
        $syncMetrics = $this->option('metrics');

        // If neither flag is set, sync both
        if (!$syncCampaigns && !$syncMetrics) {
            $syncCampaigns = true;
            $syncMetrics = true;
        }

        // Get integrations
        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'google')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'google')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->error('No Google Ads integrations found.');
            return 1;
        }

        $this->info('Starting Google Ads sync...');
        $this->info('Sync campaigns: ' . ($syncCampaigns ? 'Yes' : 'No'));
        $this->info('Sync metrics: ' . ($syncMetrics ? 'Yes' : 'No'));

        foreach ($integrations as $integration) {
            $this->info("Processing integration ID: {$integration->id} ({$integration->tenant->name})");
            $this->syncForIntegration($integration, $syncCampaigns, $syncMetrics);
        }

        $this->info('Sync completed successfully!');
        return 0;
    }

    private function syncForIntegration(Integration $integration, bool $syncCampaigns, bool $syncMetrics): void
    {
        try {
            // Get access token
            $config = $integration->app_config;

            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("Invalid app config for integration {$integration->id}");
                return;
            }

            $accessToken = $config['access_token'];
            $refreshToken = $config['refresh_token'] ?? null;
            $adAccounts = $integration->adAccounts;

            if ($adAccounts->isEmpty()) {
                $this->warn("No ad accounts found for integration {$integration->id}");
                return;
            }

            $this->info("Found {$adAccounts->count()} ad account(s) to sync");

            // Sync campaigns
            if ($syncCampaigns) {
                $this->info("\n=== Syncing Campaigns ===");
                $this->syncCampaigns($adAccounts, $accessToken, $refreshToken);
            }

            // Sync metrics
            if ($syncMetrics) {
                $this->info("\n=== Syncing Metrics ===");
                $this->syncMetrics($adAccounts, $accessToken, $refreshToken);
            }

        } catch (\Exception $e) {
            $this->error("Error syncing integration {$integration->id}: " . $e->getMessage());
            Log::error('Google Ads sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function syncCampaigns($adAccounts, string $accessToken, ?string $refreshToken = null): void
    {
        foreach ($adAccounts as $adAccount) {
            try {
                $this->info("Syncing campaigns for: {$adAccount->account_name}");

                $result = $this->campaignSyncService->syncCampaignsForAccount($adAccount, $accessToken, $refreshToken);

                $this->info("  ✓ Created: {$result['created']}");
                $this->info("  ✓ Updated: {$result['updated']}");
                $this->info("  ✓ Total: {$result['total']}");

            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
            }
        }
    }

    private function syncMetrics($adAccounts, string $accessToken, ?string $refreshToken = null): void
    {
        // Determine date range
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $allTime = $this->option('all');
        $days = $this->option('days') ?? 30;

        if ($allTime) {
            // Process in yearly chunks to avoid memory issues
            $this->info("Syncing ALL TIME metrics in yearly chunks...");
            $this->syncMetricsInChunks($adAccounts, $accessToken, $refreshToken);
            return;
        }

        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
        }

        $this->info("Date range: {$startDate} to {$endDate}");

        foreach ($adAccounts as $adAccount) {
            try {
                $this->info("Syncing metrics for: {$adAccount->name}");

                $result = $this->metricsSyncService->syncMetricsForAccount(
                    $adAccount,
                    $accessToken,
                    $startDate,
                    $endDate,
                    $refreshToken
                );

                $this->info("  ✓ Created: {$result['created']}");
                $this->info("  ✓ Updated: {$result['updated']}");
                $this->info("  ✓ Skipped: {$result['skipped']}");
                $this->info("  ✓ Total: {$result['total']}");

            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Sync metrics in quarterly chunks to avoid memory exhaustion while being fast
     * (monthly was too slow, yearly caused memory issues)
     */
    private function syncMetricsInChunks($adAccounts, string $accessToken, ?string $refreshToken): void
    {
        // Filter out manager accounts (they have no parent_manager_id and always fail)
        $clientAccounts = $adAccounts->filter(function ($account) {
            $config = $account->account_config;
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            return !empty($config['parent_manager_id']);
        });

        $skippedCount = $adAccounts->count() - $clientAccounts->count();
        if ($skippedCount > 0) {
            $this->info("Skipping {$skippedCount} manager account(s) - syncing {$clientAccounts->count()} client accounts");
        }

        // Start from 2020 - most accounts don't have data before this
        $startYear = 2020;
        $currentDate = Carbon::now();

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        // Process quarter by quarter from 2020 to now (3x faster than monthly)
        $date = Carbon::createFromDate($startYear, 1, 1);
        $quartersProcessed = 0;

        while ($date->lte($currentDate)) {
            $startDate = $date->copy()->format('Y-m-d');
            $endDate = $date->copy()->addMonths(2)->endOfMonth()->format('Y-m-d');

            // Don't go beyond today
            if (Carbon::parse($endDate)->gt($currentDate)) {
                $endDate = $currentDate->format('Y-m-d');
            }

            $quarterLabel = 'Q' . ceil($date->month / 3) . ' ' . $date->year;
            $this->info("\n=== Processing {$quarterLabel} ({$startDate} to {$endDate}) ===");

            foreach ($clientAccounts as $adAccount) {
                try {
                    $result = $this->metricsSyncService->syncMetricsForAccount(
                        $adAccount,
                        $accessToken,
                        $startDate,
                        $endDate,
                        $refreshToken
                    );

                    $totalCreated += $result['created'];
                    $totalUpdated += $result['updated'];
                    $totalSkipped += $result['skipped'];

                    // Only log when data was found
                    if ($result['created'] > 0 || $result['updated'] > 0) {
                        $this->info("  ✓ {$adAccount->name}: +{$result['created']}, ~{$result['updated']}");
                    }

                    // Force garbage collection after each account
                    gc_collect_cycles();

                } catch (\Exception $e) {
                    $this->error("  ✗ {$adAccount->name}: " . $e->getMessage());
                }
            }

            // Force garbage collection after each quarter
            gc_collect_cycles();
            $quartersProcessed++;

            // Move to next quarter (3 months)
            $date->addMonths(3);
        }

        $this->info("\n=== All Time Sync Complete ===");
        $this->info("Quarters processed: {$quartersProcessed}");
        $this->info("Total Created: {$totalCreated}");
        $this->info("Total Updated: {$totalUpdated}");
        $this->info("Total Skipped: {$totalSkipped}");
    }
}