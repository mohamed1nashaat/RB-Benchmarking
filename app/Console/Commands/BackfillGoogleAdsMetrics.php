<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\GoogleAdsMetricsSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillGoogleAdsMetrics extends Command
{
    protected $signature = 'google-ads:backfill-metrics
                            {integration_id? : Specific integration ID to backfill}
                            {--start-date= : Start date (Y-m-d format, default: 24 months ago)}
                            {--end-date= : End date (Y-m-d format, default: today)}
                            {--account-id= : Specific ad account ID to backfill}
                            {--full-history : Backfill all available historical data (20+ years)}';

    protected $description = 'Backfill historical Google Ads metrics data (supports 20+ years of data)';

    protected GoogleAdsMetricsSyncService $metricsSyncService;

    public function __construct(GoogleAdsMetricsSyncService $metricsSyncService)
    {
        parent::__construct();
        $this->metricsSyncService = $metricsSyncService;
    }

    public function handle()
    {
        // Increase memory limit for large backfills
        ini_set('memory_limit', '2048M');

        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');
        $fullHistory = $this->option('full-history');

        // Parse date range
        if ($fullHistory) {
            // Backfill all available historical data (20+ years)
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

        $this->info("=== Google Ads Historical Backfill ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("========================================\n");

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

        $this->info("Found {$integrations->count()} Google Ads integration(s) to backfill\n");

        $totalAccounts = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
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
            $refreshToken = $config['refresh_token'] ?? null;

            // Get ad accounts
            $query = $integration->adAccounts();
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

                // Process in monthly chunks to avoid memory issues
                $chunkStart = Carbon::parse($startDate);
                $chunkEnd = Carbon::parse($endDate);
                $totalChunks = 0;
                $accountCreated = 0;
                $accountUpdated = 0;
                $accountSkipped = 0;

                while ($chunkStart < $chunkEnd) {
                    $currentChunkEnd = $chunkStart->copy()->addMonth()->subDay();
                    if ($currentChunkEnd > $chunkEnd) {
                        $currentChunkEnd = $chunkEnd;
                    }

                    $totalChunks++;
                    $this->info("    Chunk {$totalChunks}: {$chunkStart->format('Y-m-d')} to {$currentChunkEnd->format('Y-m-d')}");

                    try {
                        $result = $this->metricsSyncService->syncMetricsForAccount(
                            $adAccount,
                            $accessToken,
                            $chunkStart->format('Y-m-d'),
                            $currentChunkEnd->format('Y-m-d'),
                            $refreshToken
                        );

                        $accountCreated += $result['created'];
                        $accountUpdated += $result['updated'];
                        $accountSkipped += $result['skipped'];

                        $this->info("      → Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}");

                        // Free memory
                        gc_collect_cycles();

                    } catch (\Exception $e) {
                        $error = "Error syncing chunk for {$adAccount->account_name}: " . $e->getMessage();
                        $this->error("      ✗ {$error}");
                        $errors[] = $error;

                        Log::error('Google Ads backfill chunk failed', [
                            'account_id' => $adAccount->id,
                            'integration_id' => $integration->id,
                            'chunk_start' => $chunkStart->format('Y-m-d'),
                            'chunk_end' => $currentChunkEnd->format('Y-m-d'),
                            'error' => $e->getMessage()
                        ]);
                    }

                    $chunkStart = $currentChunkEnd->copy()->addDay();
                }

                $this->info("    ✓ Total Created: {$accountCreated}");
                $this->info("    ✓ Total Updated: {$accountUpdated}");
                $this->info("    ✓ Total Skipped: {$accountSkipped}");
                $this->info("    ✓ Chunks Processed: {$totalChunks}");

                $totalCreated += $accountCreated;
                $totalUpdated += $accountUpdated;
                $totalSkipped += $accountSkipped;

                $this->newLine();
            }
        }

        // Summary
        $this->info("=== Backfill Summary ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("Total Integrations: {$integrations->count()}");
        $this->info("Total Ad Accounts: {$totalAccounts}");
        $this->info("Total Metrics Created: {$totalCreated}");
        $this->info("Total Metrics Updated: {$totalUpdated}");
        $this->info("Total Metrics Skipped: {$totalSkipped}");
        $this->info("Grand Total: " . ($totalCreated + $totalUpdated + $totalSkipped));

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
}
