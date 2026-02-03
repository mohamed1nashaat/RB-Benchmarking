<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestFacebookHistoricalMonthly extends Command
{
    protected $signature = 'facebook:test-historical-monthly
                            {--account-id= : Specific ad account ID to test}
                            {--start-date= : Start date (default: 2015-01-01)}
                            {--end-date= : End date (default: today)}';

    protected $description = 'Test Facebook API with monthly aggregation and lifetime presets to retrieve historical data';

    private const API_BASE_URL = 'https://graph.facebook.com/v23.0';

    public function handle()
    {
        $this->info('=== Testing Facebook Historical Data with Monthly Aggregation ===');
        $this->newLine();

        // Get integration
        $integration = Integration::where('platform', 'facebook')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            $this->error('No active Facebook integration found');
            return 1;
        }

        // Check both app_config and credentials for token
        $credentials = $integration->app_config ?? $integration->credentials ?? [];
        $accessToken = $credentials['access_token'] ?? null;
        if (!$accessToken) {
            $this->error('No access token found in integration');
            return 1;
        }

        // Get account to test
        $accountId = $this->option('account-id');
        if ($accountId) {
            $account = AdAccount::find($accountId);
            if (!$account) {
                $this->error("Account ID {$accountId} not found");
                return 1;
            }
            $accounts = collect([$account]);
        } else {
            // Test with Mancini account (known to have historical data)
            $accounts = AdAccount::where('integration_id', $integration->id)
                ->where('account_name', 'LIKE', '%Mancini%')
                ->get();

            if ($accounts->isEmpty()) {
                $this->warn('Mancini account not found, using first available account');
                $accounts = AdAccount::where('integration_id', $integration->id)
                    ->limit(1)
                    ->get();
            }
        }

        if ($accounts->isEmpty()) {
            $this->error('No accounts found to test');
            return 1;
        }

        $startDate = $this->option('start-date') ?? '2015-01-01';
        $endDate = $this->option('end-date') ?? now()->format('Y-m-d');

        $this->info("Testing date range: {$startDate} to {$endDate}");
        $this->newLine();

        foreach ($accounts as $account) {
            $this->testAccount($account, $accessToken, $startDate, $endDate);
        }

        return 0;
    }

    private function testAccount(AdAccount $account, string $accessToken, string $startDate, string $endDate): void
    {
        $this->info("Testing Account: {$account->account_name} (ID: {$account->id})");
        $this->info("External ID: {$account->external_account_id}");
        $this->newLine();

        // Get campaigns
        $campaigns = AdCampaign::where('ad_account_id', $account->id)
            ->limit(3) // Test with first 3 campaigns
            ->get();

        if ($campaigns->isEmpty()) {
            $this->warn('No campaigns found for this account');
            return;
        }

        $this->info("Testing {$campaigns->count()} campaigns:");
        $this->newLine();

        foreach ($campaigns as $campaign) {
            $this->testCampaignMultipleMethods($campaign, $accessToken, $startDate, $endDate);
        }
    }

    private function testCampaignMultipleMethods(AdCampaign $campaign, string $accessToken, string $startDate, string $endDate): void
    {
        $this->line("Campaign: {$campaign->name} (ID: {$campaign->external_campaign_id})");

        // Method 1: Monthly time increment
        $this->line('  Method 1: Monthly time_increment...');
        $monthlyResults = $this->fetchWithMonthlyIncrement($campaign->external_campaign_id, $accessToken, $startDate, $endDate);
        $this->displayResults('    Monthly', $monthlyResults);

        // Method 2: Date preset = maximum
        $this->line('  Method 2: date_preset=maximum...');
        $maximumResults = $this->fetchWithDatePreset($campaign->external_campaign_id, $accessToken, 'maximum');
        $this->displayResults('    Maximum preset', $maximumResults);

        // Method 3: Date preset = lifetime
        $this->line('  Method 3: date_preset=lifetime...');
        $lifetimeResults = $this->fetchWithDatePreset($campaign->external_campaign_id, $accessToken, 'lifetime');
        $this->displayResults('    Lifetime preset', $lifetimeResults);

        // Method 4: Lifetime aggregate (no time breakdown)
        $this->line('  Method 4: Lifetime aggregate (no time increment)...');
        $aggregateResults = $this->fetchLifetimeAggregate($campaign->external_campaign_id, $accessToken, $startDate, $endDate);
        $this->displayResults('    Aggregate', $aggregateResults);

        $this->newLine();
    }

    private function fetchWithMonthlyIncrement(string $campaignId, string $accessToken, string $startDate, string $endDate): array
    {
        $url = self::API_BASE_URL . "/{$campaignId}/insights";

        $params = [
            'access_token' => $accessToken,
            'time_range' => json_encode([
                'since' => $startDate,
                'until' => $endDate
            ]),
            'time_increment' => 'monthly', // MONTHLY instead of 1 (daily)
            'level' => 'campaign',
            'fields' => 'date_start,date_stop,impressions,clicks,spend',
            'limit' => 500
        ];

        return $this->makeApiRequest($url, $params);
    }

    private function fetchWithDatePreset(string $campaignId, string $accessToken, string $preset): array
    {
        $url = self::API_BASE_URL . "/{$campaignId}/insights";

        $params = [
            'access_token' => $accessToken,
            'date_preset' => $preset, // 'maximum' or 'lifetime'
            'time_increment' => 'monthly',
            'level' => 'campaign',
            'fields' => 'date_start,date_stop,impressions,clicks,spend',
            'limit' => 500
        ];

        return $this->makeApiRequest($url, $params);
    }

    private function fetchLifetimeAggregate(string $campaignId, string $accessToken, string $startDate, string $endDate): array
    {
        $url = self::API_BASE_URL . "/{$campaignId}/insights";

        $params = [
            'access_token' => $accessToken,
            'time_range' => json_encode([
                'since' => $startDate,
                'until' => $endDate
            ]),
            // NO time_increment = returns single aggregate
            'level' => 'campaign',
            'fields' => 'impressions,clicks,spend',
            'limit' => 1
        ];

        return $this->makeApiRequest($url, $params);
    }

    private function makeApiRequest(string $url, array $params): array
    {
        try {
            $response = Http::timeout(30)->get($url, $params);

            if (!$response->successful()) {
                $error = $response->json();
                Log::warning('Facebook API test request failed', [
                    'status' => $response->status(),
                    'error' => $error
                ]);
                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'error_code' => $error['error']['code'] ?? null
                ];
            }

            $data = $response->json();
            return [
                'success' => true,
                'data' => $data['data'] ?? [],
                'count' => count($data['data'] ?? [])
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function displayResults(string $label, array $results): void
    {
        if (!$results['success']) {
            $this->line("<fg=red>{$label}: ERROR - {$results['error']}</>");
            return;
        }

        $count = $results['count'];
        if ($count === 0) {
            $this->line("<fg=yellow>{$label}: 0 records returned</>");
            return;
        }

        // Calculate totals
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $dateRange = [];

        foreach ($results['data'] as $record) {
            $totalSpend += (float) ($record['spend'] ?? 0);
            $totalImpressions += (int) ($record['impressions'] ?? 0);
            $totalClicks += (int) ($record['clicks'] ?? 0);

            if (isset($record['date_start'])) {
                $dateRange[] = $record['date_start'];
            }
        }

        $earliestDate = !empty($dateRange) ? min($dateRange) : 'N/A';
        $latestDate = !empty($dateRange) ? max($dateRange) : 'N/A';

        $this->line("<fg=green>{$label}: {$count} records found</>");
        $this->line("      Spend: $" . number_format($totalSpend, 2));
        $this->line("      Impressions: " . number_format($totalImpressions));
        $this->line("      Clicks: " . number_format($totalClicks));
        $this->line("      Date range: {$earliestDate} to {$latestDate}");
    }
}
