<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestSnapchatHistoricalLifetime extends Command
{
    protected $signature = 'snapchat:test-historical-lifetime
                            {--account-id= : Specific ad account ID to test}
                            {--start-date=2015-01-01 : Start date}
                            {--end-date= : End date (default: today)}';

    protected $description = 'Test Snapchat API with account-level stats and different granularities';

    private const BASE_URL = 'https://adsapi.snapchat.com';
    private const SNAPCHAT_API_VERSION = 'v1';

    public function handle()
    {
        $this->info('=== Testing Snapchat Historical Data with Account-Level Stats ===');
        $this->newLine();

        // Get integration
        $integration = Integration::where('platform', 'snapchat')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            $this->error('No active Snapchat integration found');
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
            // Test with first 2 accounts
            $accounts = AdAccount::where('integration_id', $integration->id)
                ->limit(2)
                ->get();
        }

        if ($accounts->isEmpty()) {
            $this->error('No accounts found to test');
            return 1;
        }

        $startDate = $this->option('start-date');
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

        // Method 1: Account-level stats with LIFETIME granularity
        $this->line('Method 1: Account-level LIFETIME stats...');
        $accountLifetimeResults = $this->fetchAccountStats($account->external_account_id, $accessToken, 'LIFETIME', $startDate, $endDate);
        $this->displayResults('  Account LIFETIME', $accountLifetimeResults);

        // Method 2: Account-level stats with TOTAL granularity (current method)
        $this->line('Method 2: Account-level TOTAL stats (current method)...');
        $accountTotalResults = $this->fetchAccountStats($account->external_account_id, $accessToken, 'TOTAL', $startDate, $endDate);
        $this->displayResults('  Account TOTAL', $accountTotalResults);

        // Method 3: Account-level stats with DAY granularity
        $this->line('Method 3: Account-level DAILY breakdown...');
        $accountDailyResults = $this->fetchAccountStats($account->external_account_id, $accessToken, 'DAY', $startDate, $endDate);
        $this->displayResults('  Account DAILY', $accountDailyResults);

        $this->newLine();

        // Test campaign-level for comparison
        $campaigns = AdCampaign::where('ad_account_id', $account->id)
            ->limit(2)
            ->get();

        if (!$campaigns->isEmpty()) {
            $this->line('Method 4: Campaign-level stats (for comparison)...');
            foreach ($campaigns as $campaign) {
                $campaignResults = $this->fetchCampaignStats($campaign->external_campaign_id, $accessToken, 'TOTAL', $startDate, $endDate);
                $this->displayResults("  Campaign: {$campaign->name}", $campaignResults);
            }
        }

        $this->newLine();
    }

    private function fetchAccountStats(string $adAccountId, string $accessToken, string $granularity, string $startDate, string $endDate): array
    {
        try {
            $url = self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/adaccounts/{$adAccountId}/stats";

            $params = [
                'granularity' => $granularity,
                'fields' => 'impressions,swipes,spend,quartile_1,quartile_2,quartile_3,view_completion,saves,shares,story_opens,story_completes',
                'start_time' => $startDate,
                'end_time' => $endDate
            ];

            Log::info('Snapchat account stats test request', [
                'url' => $url,
                'granularity' => $granularity
            ]);

            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->get($url, $params);

            if (!$response->successful()) {
                $error = $response->json();
                Log::warning('Snapchat API test request failed', [
                    'status' => $response->status(),
                    'error' => $error
                ]);
                return [
                    'success' => false,
                    'error' => $error['request_status'] ?? 'Unknown error',
                    'status' => $response->status()
                ];
            }

            $data = $response->json();

            // Snapchat returns different structures based on granularity
            $stats = [];
            if ($granularity === 'TOTAL' || $granularity === 'LIFETIME') {
                $stats = $data['total_stats'] ?? [];
            } elseif ($granularity === 'DAY') {
                $stats = $data['timeseries_stats'] ?? [];
            }

            return [
                'success' => true,
                'stats' => $stats,
                'count' => count($stats)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function fetchCampaignStats(string $campaignId, string $accessToken, string $granularity, string $startDate, string $endDate): array
    {
        try {
            $url = self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$campaignId}/stats";

            $params = [
                'granularity' => $granularity,
                'fields' => 'impressions,swipes,spend,quartile_1,quartile_2,quartile_3,view_completion,saves,shares,story_opens,story_completes',
                'start_time' => $startDate,
                'end_time' => $endDate
            ];

            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->get($url, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => $response->json()['request_status'] ?? 'Unknown error'
                ];
            }

            $data = $response->json();
            $stats = $data['total_stats'] ?? [];

            return [
                'success' => true,
                'stats' => $stats,
                'count' => count($stats)
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

        // Calculate totals from stats
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalSwipes = 0;
        $totalVideoViews = 0;

        foreach ($results['stats'] as $stat) {
            // Snapchat stats can be in 'total_stat' or 'timeseries_stat' or directly
            $statData = $stat['total_stat'] ?? $stat['timeseries_stat'] ?? $stat;

            // Spend is in micros (millionths)
            $spendMicros = (int) ($statData['spend'] ?? 0);
            $totalSpend += $spendMicros / 1_000_000;

            $totalImpressions += (int) ($statData['impressions'] ?? 0);
            $totalSwipes += (int) ($statData['swipes'] ?? 0);
            $totalVideoViews += (int) ($statData['view_completion'] ?? 0);
        }

        $this->line("<fg=green>{$label}: {$count} records found</>");
        $this->line("    Spend: $" . number_format($totalSpend, 2));
        $this->line("    Impressions: " . number_format($totalImpressions));
        $this->line("    Swipes: " . number_format($totalSwipes));
        $this->line("    Video Completions: " . number_format($totalVideoViews));

        if ($totalSpend > 0) {
            $this->line("<fg=cyan>    ✓ Found historical spend data!</>");
        }
    }
}
