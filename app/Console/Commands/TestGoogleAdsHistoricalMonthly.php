<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestGoogleAdsHistoricalMonthly extends Command
{
    protected $signature = 'google-ads:test-historical-monthly
                            {--account-id= : Specific ad account ID to test}
                            {--customer-id= : Google Ads Customer ID (e.g., 819-554-9637)}
                            {--start-year=2015 : Start year}
                            {--end-year= : End year (default: current year)}';

    protected $description = 'Test Google Ads API with monthly/yearly segments to retrieve historical data';

    private const API_BASE_URL = 'https://googleads.googleapis.com/v16';

    public function handle()
    {
        $this->info('=== Testing Google Ads Historical Data with Monthly/Yearly Segments ===');
        $this->newLine();

        // Get integration
        $integration = Integration::where('platform', 'google')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            $this->error('No active Google Ads integration found');
            return 1;
        }

        // Check both app_config and credentials for token
        $credentials = $integration->app_config ?? $integration->credentials ?? [];
        $accessToken = $credentials['access_token'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;

        if (!$accessToken) {
            $this->error('No access token found in integration');
            return 1;
        }

        $developerToken = config('services.google_ads.developer_token') ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', '');
        if (empty($developerToken)) {
            $this->error('Google Ads developer token not configured');
            return 1;
        }

        // Get account to test
        $accountId = $this->option('account-id');
        $customerId = $this->option('customer-id');

        if ($customerId) {
            $customerId = str_replace('-', '', $customerId);
        } elseif ($accountId) {
            $account = AdAccount::find($accountId);
            if (!$account) {
                $this->error("Account ID {$accountId} not found");
                return 1;
            }
            $customerId = str_replace('-', '', $account->external_account_id);
        } else {
            // Test with Mancini account (819-554-9637)
            $account = AdAccount::where('integration_id', $integration->id)
                ->where('account_name', 'LIKE', '%Mancini%')
                ->first();

            if (!$account) {
                $this->warn('Mancini account not found, using first available account');
                $account = AdAccount::where('integration_id', $integration->id)->first();
            }

            if (!$account) {
                $this->error('No accounts found to test');
                return 1;
            }

            $customerId = str_replace('-', '', $account->external_account_id);
        }

        $startYear = (int) $this->option('start-year');
        $endYear = (int) ($this->option('end-year') ?? date('Y'));

        $this->info("Testing Customer ID: {$customerId}");
        $this->info("Year range: {$startYear} to {$endYear}");
        $this->newLine();

        // Test different query methods
        $this->testYearlySegments($customerId, $accessToken, $developerToken, $startYear, $endYear);
        $this->newLine();

        $this->testMonthlySegments($customerId, $accessToken, $developerToken, $startYear, $endYear);
        $this->newLine();

        $this->testDailyForComparison($customerId, $accessToken, $developerToken, $startYear, $endYear);

        return 0;
    }

    private function testYearlySegments(string $customerId, string $accessToken, string $developerToken, int $startYear, int $endYear): void
    {
        $this->info('Method 1: Yearly Aggregation (segments.year)');
        $this->line('------------------------------------------------');

        $query =
            'SELECT ' .
            'campaign.id, ' .
            'campaign.name, ' .
            'segments.year, ' .
            'metrics.impressions, ' .
            'metrics.clicks, ' .
            'metrics.cost_micros, ' .
            'metrics.conversions, ' .
            'metrics.conversions_value ' .
            'FROM campaign ' .
            'WHERE segments.year >= ' . $startYear . ' AND segments.year <= ' . $endYear . ' ' .
            'AND campaign.status != "REMOVED" ' .
            'ORDER BY segments.year DESC, campaign.id';

        $results = $this->executeQuery($customerId, $accessToken, $developerToken, $query);
        $this->displayResults($results, 'year');
    }

    private function testMonthlySegments(string $customerId, string $accessToken, string $developerToken, int $startYear, int $endYear): void
    {
        $this->info('Method 2: Monthly Aggregation (segments.month)');
        $this->line('------------------------------------------------');

        $query =
            'SELECT ' .
            'campaign.id, ' .
            'campaign.name, ' .
            'segments.month, ' .
            'metrics.impressions, ' .
            'metrics.clicks, ' .
            'metrics.cost_micros, ' .
            'metrics.conversions, ' .
            'metrics.conversions_value ' .
            'FROM campaign ' .
            'WHERE segments.year >= ' . $startYear . ' AND segments.year <= ' . $endYear . ' ' .
            'AND campaign.status != "REMOVED" ' .
            'ORDER BY segments.month DESC';

        $results = $this->executeQuery($customerId, $accessToken, $developerToken, $query);
        $this->displayResults($results, 'month');
    }

    private function testDailyForComparison(string $customerId, string $accessToken, string $developerToken, int $startYear, int $endYear): void
    {
        $this->info('Method 3: Daily Segments (current method - for comparison)');
        $this->line('------------------------------------------------');

        // Just test a sample period (first month of start year)
        $startDate = str_pad($startYear, 4, '0', STR_PAD_LEFT) . '0101';
        $endDate = str_pad($startYear, 4, '0', STR_PAD_LEFT) . '0131';

        $query =
            'SELECT ' .
            'campaign.id, ' .
            'campaign.name, ' .
            'segments.date, ' .
            'metrics.impressions, ' .
            'metrics.clicks, ' .
            'metrics.cost_micros, ' .
            'metrics.conversions, ' .
            'metrics.conversions_value ' .
            'FROM campaign ' .
            'WHERE segments.date BETWEEN "' . $startDate . '" AND "' . $endDate . '" ' .
            'AND campaign.status != "REMOVED" ' .
            'ORDER BY segments.date DESC';

        $results = $this->executeQuery($customerId, $accessToken, $developerToken, $query);
        $this->displayResults($results, 'date');
    }

    private function executeQuery(string $customerId, string $accessToken, string $developerToken, string $query): array
    {
        try {
            $requestBody = [
                'query' => $query,
                'pageSize' => 500
            ];

            $headers = [
                'Authorization' => "Bearer {$accessToken}",
                'developer-token' => $developerToken,
            ];

            Log::info('Executing Google Ads query', [
                'customer_id' => $customerId,
                'query' => $query
            ]);

            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post(self::API_BASE_URL . "/customers/{$customerId}/googleAds:search", $requestBody);

            if (!$response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->body();

                Log::error('Google Ads API request failed', [
                    'status' => $statusCode,
                    'body' => $responseBody
                ]);

                return [
                    'success' => false,
                    'error' => "API Error: {$statusCode}",
                    'message' => $responseBody
                ];
            }

            $data = $response->json();
            return [
                'success' => true,
                'results' => $data['results'] ?? [],
                'count' => count($data['results'] ?? [])
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function displayResults(array $results, string $timeSegment): void
    {
        if (!$results['success']) {
            $this->line("<fg=red>ERROR: {$results['error']}</>");
            if (isset($results['message'])) {
                $this->line("<fg=yellow>Details: {$results['message']}</>");
            }
            return;
        }

        $count = $results['count'];
        if ($count === 0) {
            $this->line("<fg=yellow>0 records returned</>");
            return;
        }

        // Aggregate data
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;
        $totalRevenue = 0;
        $campaigns = [];
        $timeValues = [];

        foreach ($results['results'] as $result) {
            $campaign = $result['campaign'] ?? [];
            $segments = $result['segments'] ?? [];
            $metrics = $result['metrics'] ?? [];

            $campaignId = $campaign['id'] ?? null;
            if ($campaignId) {
                $campaigns[$campaignId] = $campaign['name'] ?? 'Unknown';
            }

            if (isset($segments[$timeSegment])) {
                $timeValues[] = $segments[$timeSegment];
            }

            $costMicros = (int) ($metrics['costMicros'] ?? 0);
            $totalSpend += $costMicros / 1_000_000;
            $totalImpressions += (int) ($metrics['impressions'] ?? 0);
            $totalClicks += (int) ($metrics['clicks'] ?? 0);
            $totalConversions += (float) ($metrics['conversions'] ?? 0);

            $revenueMicros = (int) ($metrics['conversionsValue'] ?? 0);
            $totalRevenue += $revenueMicros / 1_000_000;
        }

        $earliestTime = !empty($timeValues) ? min($timeValues) : 'N/A';
        $latestTime = !empty($timeValues) ? max($timeValues) : 'N/A';

        $this->line("<fg=green>{$count} records found across " . count($campaigns) . " campaigns</>");
        $this->line("<fg=cyan>Spend: $" . number_format($totalSpend, 2) . "</>");
        $this->line("Impressions: " . number_format($totalImpressions));
        $this->line("Clicks: " . number_format($totalClicks));
        $this->line("Conversions: " . number_format($totalConversions, 2));
        $this->line("Revenue: $" . number_format($totalRevenue, 2));
        $this->line("Time range: {$earliestTime} to {$latestTime}");

        if ($totalSpend > 0) {
            $this->newLine();
            $this->line("<fg=green>✓ SUCCESS: Found historical spend data!</>");
            $this->line("Sample campaigns:");
            $campaignSample = array_slice($campaigns, 0, 5, true);
            foreach ($campaignSample as $id => $name) {
                $this->line("  - {$name} (ID: {$id})");
            }
        }
    }
}
