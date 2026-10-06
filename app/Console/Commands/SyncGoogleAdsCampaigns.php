<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Services\GoogleAdsService;
use App\Services\GoogleAdsCampaignSyncService;
use App\Services\GoogleAdsMetricsSyncService;
use App\Services\SyncMonitoringService;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncGoogleAdsCampaigns extends Command
{
    use TracksSyncProgress;
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
    private SyncMonitoringService $monitoringService;

    public function __construct(
        GoogleAdsService $googleAdsService,
        GoogleAdsCampaignSyncService $campaignSyncService,
        GoogleAdsMetricsSyncService $metricsSyncService,
        SyncMonitoringService $monitoringService
    ) {
        parent::__construct();
        $this->googleAdsService = $googleAdsService;
        $this->campaignSyncService = $campaignSyncService;
        $this->metricsSyncService = $metricsSyncService;
        $this->monitoringService = $monitoringService;
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
            $this->info("Processing integration ID: {$integration->id} (" . ($integration->tenant?->name ?? 'No Tenant') . ")");
            $this->syncForIntegration($integration, $syncCampaigns, $syncMetrics);
        }

        $this->info('Sync completed successfully!');
        return 0;
    }

    private function syncForIntegration(Integration $integration, bool $syncCampaigns, bool $syncMetrics): void
    {
        try {
            $this->initSyncProgress($integration->id);

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

            $this->reportStep("Found {$adAccounts->count()} ad account(s) to sync");

            $campaignsSynced = 0;
            $metricsSynced = 0;

            // Sync campaigns
            if ($syncCampaigns) {
                $this->reportCurrentStep('Syncing campaigns...');
                $campaignsSynced = $this->syncCampaigns($adAccounts, $accessToken, $refreshToken, $integration);
                $this->reportStep('Campaign sync complete', 'success');
            }

            // Sync metrics
            if ($syncMetrics) {
                $this->reportCurrentStep('Syncing metrics...');
                $metricsSynced = $this->syncMetrics($adAccounts, $accessToken, $refreshToken, $integration);

                // Update last metrics sync timestamp only on accounts that were actually processed
                foreach ($adAccounts as $adAccount) {
                    if ($adAccount->status !== 'permission_denied') {
                        $adAccount->update(['last_metrics_sync_at' => now()]);
                    }
                }
                $this->reportStep('Metrics sync complete', 'success');
            }

            // Count permission-denied accounts for reporting
            $permissionDeniedCount = $adAccounts->where('status', 'permission_denied')->count();
            $activeCount = $adAccounts->count() - $permissionDeniedCount;

            $stats = [
                'accounts_processed' => $activeCount,
                'accounts_skipped_permission_denied' => $permissionDeniedCount,
                'campaigns_synced' => $campaignsSynced,
                'metrics_synced' => $metricsSynced,
            ];

            // Warn if >50% of accounts are permission-denied
            if ($adAccounts->count() > 0 && $permissionDeniedCount / $adAccounts->count() > 0.5) {
                $this->monitoringService->recordPartialSuccess('google', $integration->id, $stats, "{$permissionDeniedCount}/{$adAccounts->count()} accounts have permission_denied status");
            } else {
                $this->monitoringService->recordSuccess('google', $integration->id, $stats);
            }
            $integration->update(['last_sync_at' => now()]);
            $this->completeSyncProgress($stats);

        } catch (\Exception $e) {
            $this->error("Error syncing integration {$integration->id}: " . $e->getMessage());
            Log::error('Google Ads sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),]);

            // Detect auth/token errors and mark integration for re-authorization
            $msg = $e->getMessage();
            if (
                str_contains($msg, 'PERMISSION_DENIED') ||
                str_contains($msg, 'AUTHENTICATION_ERROR') ||
                str_contains($msg, 'AuthenticationError') ||
                str_contains($msg, 'invalid_grant') ||
                str_contains($msg, 'Token has been expired or revoked') ||
                str_contains($msg, 'AUTH_FAILED')
            ) {
                $integration->update(['status' => 'needs_reauth']);
                $this->warn("Marked integration {$integration->id} as needs_reauth");
            }

            $this->monitoringService->recordFailure('google', $integration->id, $e->getMessage());
            $this->failSyncProgress($e->getMessage());
        }
    }

    private function syncCampaigns($adAccounts, string $accessToken, ?string $refreshToken = null, ?Integration $integration = null): int
    {
        $accountIndex = 0;
        $accountTotal = $adAccounts->count();
        $totalCampaigns = 0;

        foreach ($adAccounts as $adAccount) {
            $accountIndex++;

            // Skip accounts flagged as permission_denied
            if ($adAccount->status === 'permission_denied') {
                $this->warn("  Skipping {$adAccount->account_name} — flagged as permission_denied");
                continue;
            }

            try {
                $this->reportCurrentStep("Syncing campaigns {$accountIndex}/{$accountTotal}: {$adAccount->account_name}");

                $result = $this->campaignSyncService->syncCampaignsForAccount($adAccount, $accessToken, $refreshToken);

                $this->info("  Created: {$result['created']}");
                $this->info("  Updated: {$result['updated']}");
                $this->info("  Total: {$result['total']}");

                $totalCampaigns += (int) ($result['total'] ?? 0);

                // Auto-reset permission_denied if sync succeeds
                if ($adAccount->status === 'permission_denied') {
                    $adAccount->update(['status' => 'active']);
                    $this->info("  ✓ Auto-reset status to active (was permission_denied)");
                }

            } catch (\Exception $e) {
                $this->error("  Failed: " . $e->getMessage());
                $this->flagPermissionDenied($adAccount, $e, $integration);
            }
        }

        return $totalCampaigns;
    }

    private function syncMetrics($adAccounts, string $accessToken, ?string $refreshToken = null, ?Integration $integration = null): int
    {
        // Determine date range
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $allTime = $this->option('all');
        $days = $this->option('days') ?? 30;

        if ($allTime) {
            // Process in yearly chunks to avoid memory issues
            $this->info("Syncing ALL TIME metrics in yearly chunks...");
            return $this->syncMetricsInChunks($adAccounts, $accessToken, $refreshToken);
        }

        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
        }

        $this->info("Date range: {$startDate} to {$endDate}");

        $accountIndex = 0;
        $accountTotal = $adAccounts->count();
        $totalMetrics = 0;

        foreach ($adAccounts as $adAccount) {
            $accountIndex++;

            // Skip accounts flagged as permission_denied
            if ($adAccount->status === 'permission_denied') {
                $this->warn("  Skipping {$adAccount->account_name} — flagged as permission_denied");
                continue;
            }

            try {
                $this->reportCurrentStep("Syncing metrics {$accountIndex}/{$accountTotal}: {$adAccount->account_name}");

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

                $totalMetrics += (int) ($result['total'] ?? 0);

                // Auto-reset permission_denied if sync succeeds
                if ($adAccount->status === 'permission_denied') {
                    $adAccount->update(['status' => 'active']);
                    $this->info("  ✓ Auto-reset status to active (was permission_denied)");
                }

            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
                $this->flagPermissionDenied($adAccount, $e, $integration);
            }
        }

        return $totalMetrics;
    }

    /**
     * Flag an ad account as permission_denied if the error indicates PERMISSION_DENIED
     * or if the error indicates the integration token is invalid/expired.
     */
    private function flagPermissionDenied(AdAccount $adAccount, \Exception $e, ?Integration $integration = null): void
    {
        $message = $e->getMessage();
        $lowerMessage = strtolower($message);

        $isPermissionError =
            str_contains($message, 'PERMISSION_DENIED') ||
            str_contains($message, 'CUSTOMER_NOT_ENABLED') ||
            str_contains($message, 'The caller does not have permission') ||
            str_contains($message, 'permission');

        $isAuthError =
            str_contains($message, 'invalid_grant') ||
            str_contains($message, 'Token has been expired or revoked') ||
            str_contains($message, 'AUTHENTICATION_ERROR') ||
            str_contains($message, 'UNAUTHENTICATED') ||
            str_contains($message, 'Account has been deleted') ||
            str_contains($message, 'The user must have accepted');

        if ($isPermissionError) {
            $adAccount->update(['status' => 'permission_denied']);
            $this->warn("  Flagged {$adAccount->account_name} as permission_denied — will be skipped on future syncs");
            Log::warning('Google Ads account flagged as permission_denied', [
                'account_id' => $adAccount->id,
                'account_name' => $adAccount->account_name,
                'error' => $message,
            ]);
        }

        if ($isAuthError && $integration) {
            $integration->update(['status' => 'needs_reauth']);
            $this->warn("  Marked Google Ads integration {$integration->id} as needs_reauth due to auth error");
            Log::warning('Google Ads integration marked as needs_reauth', [
                'integration_id' => $integration->id,
                'account_id' => $adAccount->id,
                'error' => $message,
            ]);
        }
    }

    /**
     * Sync metrics in quarterly chunks to avoid memory exhaustion while being fast
     * (monthly was too slow, yearly caused memory issues)
     */
    private function syncMetricsInChunks($adAccounts, string $accessToken, ?string $refreshToken): int
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
                        $this->info("  ✓ {$adAccount->account_name}: +{$result['created']}, ~{$result['updated']}");
                    }

                    // Force garbage collection after each account
                    gc_collect_cycles();

                } catch (\Exception $e) {
                    $this->error("  ✗ {$adAccount->account_name}: " . $e->getMessage());
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

        return $totalCreated + $totalUpdated;
    }
}