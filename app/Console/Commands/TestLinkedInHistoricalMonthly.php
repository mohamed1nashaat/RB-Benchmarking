<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestLinkedInHistoricalMonthly extends Command
{
    protected $signature = 'linkedin:test-historical-monthly
                            {--account-id= : Specific ad account ID to test}
                            {--start-date=2015-01-01 : Start date}
                            {--end-date= : End date (default: today)}';

    protected $description = 'Test LinkedIn API with monthly and total granularity to retrieve historical data';

    private const API_ANALYTICS_URL = 'https://api.linkedin.com/rest';

    public function handle()
    {
        $this->info('=== Testing LinkedIn Historical Data with Monthly/Total Granularity ===');
        $this->newLine();

        // Get integration
        $integration = Integration::where('platform', 'linkedin')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            $this->error('No active LinkedIn integration found');
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
            $this->testCampaignMultipleGranularities($campaign, $accessToken, $startDate, $endDate);
        }
    }

    private function testCampaignMultipleGranularities(AdCampaign $campaign, string $accessToken, string $startDate, string $endDate): void
    {
        $this->line("Campaign: {$campaign->name}");

        // Method 1: MONTHLY granularity
        $this->line('  Method 1: MONTHLY granularity...');
        $monthlyResults = $this->fetchWithGranularity($campaign, $accessToken, 'MONTHLY', $startDate, $endDate);
        $this->displayResults('    Monthly', $monthlyResults);

        // Method 2: TOTAL granularity (lifetime aggregate)
        $this->line('  Method 2: TOTAL granularity (lifetime)...');
        $totalResults = $this->fetchWithGranularity($campaign, $accessToken, 'TOTAL', $startDate, $endDate);
        $this->displayResults('    Total', $totalResults);

        // Method 3: DAILY granularity (current method - for comparison)
        $this->line('  Method 3: DAILY granularity (current method)...');
        $dailyResults = $this->fetchWithGranularity($campaign, $accessToken, 'DAILY', $startDate, $endDate);
        $this->displayResults('    Daily', $dailyResults);

        $this->newLine();
    }

    private function fetchWithGranularity(AdCampaign $campaign, string $accessToken, string $granularity, string $startDate, string $endDate): array
    {
        try {
            // Build URL with specified granularity
            $url = self::API_ANALYTICS_URL . '/adAnalytics?q=analytics&pivot=CAMPAIGN&timeGranularity=' . $granularity;

            // Build date range
            $startYear = (int) Carbon::parse($startDate)->format('Y');
            $startMonth = (int) Carbon::parse($startDate)->format('n');
            $startDay = (int) Carbon::parse($startDate)->format('j');

            $endYear = (int) Carbon::parse($endDate)->format('Y');
            $endMonth = (int) Carbon::parse($endDate)->format('n');
            $endDay = (int) Carbon::parse($endDate)->format('j');

            $dateRangeParam = sprintf(
                'dateRange=(start:(year:%d,month:%d,day:%d),end:(year:%d,month:%d,day:%d))',
                $startYear,
                $startMonth,
                $startDay,
                $endYear,
                $endMonth,
                $endDay
            );

            // Campaign filter
            $campaignUrn = $this->formatCampaignUrn($campaign->external_campaign_id);
            $campaignsParam = 'campaigns=List(' . $campaignUrn . ')';

            $fullUrl = $url . '&' . $dateRangeParam . '&' . $campaignsParam;

            Log::info('LinkedIn API test request', [
                'url' => $fullUrl,
                'granularity' => $granularity
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'LinkedIn-Version' => '202510', // LinkedIn API version
                'X-RestLi-Protocol-Version' => '2.0.0'
            ])->timeout(60)->get($fullUrl);

            if (!$response->successful()) {
                $error = $response->json();
                Log::warning('LinkedIn API test request failed', [
                    'status' => $response->status(),
                    'error' => $error
                ]);
                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'Unknown error',
                    'status' => $response->status()
                ];
            }

            $data = $response->json();
            $elements = $data['elements'] ?? [];

            return [
                'success' => true,
                'elements' => $elements,
                'count' => count($elements)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formatCampaignUrn(string $campaignId): string
    {
        // LinkedIn campaign URN format: urn:li:sponsoredCampaign:123456
        if (str_starts_with($campaignId, 'urn:')) {
            return $campaignId;
        }
        return 'urn:li:sponsoredCampaign:' . $campaignId;
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
        $totalConversions = 0;
        $dateRanges = [];

        foreach ($results['elements'] as $element) {
            // LinkedIn returns cost in local currency (often USD cents)
            $cost = isset($element['costInLocalCurrency']) ? (float) $element['costInLocalCurrency'] : 0;
            $totalSpend += $cost;

            $totalImpressions += (int) ($element['impressions'] ?? 0);
            $totalClicks += (int) ($element['clicks'] ?? 0);
            $totalConversions += (int) ($element['externalWebsiteConversions'] ?? 0) +
                                  (int) ($element['oneClickLeads'] ?? 0);

            if (isset($element['dateRange'])) {
                $dateRanges[] = $this->formatDateRange($element['dateRange']);
            }
        }

        $dateRangeStr = !empty($dateRanges) ? (count($dateRanges) > 1 ? min($dateRanges) . ' to ' . max($dateRanges) : $dateRanges[0]) : 'N/A';

        $this->line("<fg=green>{$label}: {$count} records found</>");
        $this->line("      Spend: $" . number_format($totalSpend, 2));
        $this->line("      Impressions: " . number_format($totalImpressions));
        $this->line("      Clicks: " . number_format($totalClicks));
        $this->line("      Conversions: " . number_format($totalConversions));
        $this->line("      Date range: {$dateRangeStr}");

        if ($totalSpend > 0) {
            $this->line("<fg=cyan>      ✓ Found historical spend data!</>");
        }
    }

    private function formatDateRange($dateRange): string
    {
        if (isset($dateRange['start'])) {
            $start = $dateRange['start'];
            return sprintf('%04d-%02d-%02d', $start['year'], $start['month'], $start['day'] ?? 1);
        }
        return 'Unknown';
    }
}
