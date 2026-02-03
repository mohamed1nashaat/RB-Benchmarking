<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\FacebookMetricsSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillFacebookMetrics extends Command
{
    protected $signature = 'facebook:backfill-metrics
                            {integration_id? : Specific integration ID to backfill}
                            {--start-date= : Start date (Y-m-d format, default: 24 months ago)}
                            {--end-date= : End date (Y-m-d format, default: today)}
                            {--account-id= : Specific ad account ID to backfill}
                            {--full-history : Backfill all available historical data (20+ years)}';

    protected $description = 'Backfill historical Facebook metrics data (supports 20+ years of data)';

    public function handle(FacebookMetricsSyncService $syncService)
    {
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

        $this->info("=== Facebook Historical Backfill ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("=====================================\n");

        // Get integrations
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

        $this->info("Found {$integrations->count()} Facebook integration(s) to backfill\n");

        $totalAccounts = 0;
        $totalCampaigns = 0;
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

                try {
                    $result = $syncService->syncMetricsForAccount(
                        $adAccount,
                        $accessToken,
                        $startDate,
                        $endDate
                    );

                    if ($result['success']) {
                        $this->info("    ✓ Campaigns Processed: {$result['campaigns_processed']}");
                        $this->info("    ✓ Metrics Synced: {$result['metrics_synced']}");

                        $totalCampaigns += $result['campaigns_processed'];
                        $totalMetrics += $result['metrics_synced'];
                    } else {
                        $error = "Failed to sync account {$adAccount->id}";
                        $this->error("    ✗ {$error}");
                        $errors[] = $error;
                    }

                } catch (\Exception $e) {
                    $error = "Error syncing account {$adAccount->account_name}: " . $e->getMessage();
                    $this->error("    ✗ {$error}");
                    $errors[] = $error;

                    Log::error('Facebook backfill failed for account', [
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
        $this->info("Total Campaigns Processed: {$totalCampaigns}");
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
}
