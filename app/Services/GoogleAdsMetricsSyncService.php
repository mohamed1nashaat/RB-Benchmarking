<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Google\Ads\GoogleAds\Lib\V22\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V22\Services\SearchGoogleAdsRequest;

class GoogleAdsMetricsSyncService
{
    private const API_BASE_URL = 'https://googleads.googleapis.com/v16';
    private const PAGE_SIZE = 100;

    private GoogleAdsService $googleAdsService;

    public function __construct(GoogleAdsService $googleAdsService)
    {
        $this->googleAdsService = $googleAdsService;
    }

    /**
     * Sync metrics for a specific ad account
     *
     * @param AdAccount $account
     * @param string $accessToken
     * @param string|null $startDate YYYY-MM-DD format
     * @param string|null $endDate YYYY-MM-DD format
     * @param string|null $refreshToken OAuth refresh token for SDK authentication
     */
    public function syncMetricsForAccount(
        AdAccount $account,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $refreshToken = null
    ): array {
        try {
            // Default to last 30 days if not specified
            $startDate = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
            $endDate = $endDate ?? Carbon::now()->format('Y-m-d');

            // Extract customer ID from account config or external_account_id
            $customerId = $account->account_config['customer_id'] ?? $account->external_account_id;

            // Remove dashes if present
            $customerId = str_replace('-', '', $customerId);

            if (empty($customerId)) {
                throw new \Exception("No customer ID found for account {$account->id}");
            }

            // Extract login customer ID (manager account ID) if this is a client account
            $loginCustomerId = $account->account_config['parent_manager_id'] ?? null;
            if ($loginCustomerId) {
                $loginCustomerId = str_replace('-', '', $loginCustomerId);
            }

            Log::info('Starting metrics sync for account', [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'customer_id' => $customerId,
                'login_customer_id' => $loginCustomerId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Get developer token
            $developerToken = config('services.google_ads.developer_token') ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', '');

            if (empty($developerToken)) {
                throw new \Exception('Google Ads developer token not configured');
            }

            // Use SDK if refresh token is available, otherwise fallback to HTTP
            if ($refreshToken) {
                $metricsData = $this->fetchMetricsFromSDK($customerId, $refreshToken, $developerToken, $startDate, $endDate, $loginCustomerId);
            } else {
                $metricsData = $this->fetchMetricsFromAPI(
                    $customerId,
                    $accessToken,
                    $developerToken,
                    $startDate,
                    $endDate,
                    $loginCustomerId
                );
            }

            Log::info('Fetched metrics from Google Ads API', [
                'account_id' => $account->id,
                'metrics_count' => count($metricsData)
            ]);

            // Sync metrics to database
            $syncResults = $this->syncMetricsToDatabase($account, $metricsData);

            Log::info('Metrics sync completed', [
                'account_id' => $account->id,
                'created' => $syncResults['created'],
                'updated' => $syncResults['updated'],
                'skipped' => $syncResults['skipped'],
                'total' => count($metricsData)
            ]);

            return $syncResults;

        } catch (\Exception $e) {
            Log::error('Metrics sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch metrics from Google Ads API using GAQL
     */
    private function fetchMetricsFromAPI(
        string $customerId,
        string $accessToken,
        string $developerToken,
        string $startDate,
        string $endDate,
        ?string $loginCustomerId = null
    ): array {
        $metrics = [];
        $nextPageToken = null;

        // Convert dates to Google Ads format (YYYYMMDD)
        $startDateFormatted = str_replace('-', '', $startDate);
        $endDateFormatted = str_replace('-', '', $endDate);

        do {
            // Build GAQL query for campaign performance (include ALL campaigns including archived/removed for historical data)
            // Note: Only include universally available metrics
            $query = 'SELECT ' .
                'campaign.id, ' .
                'campaign.name, ' .
                'segments.date, ' .
                'metrics.impressions, ' .
                'metrics.clicks, ' .
                'metrics.cost_micros, ' .
                'metrics.conversions, ' .
                'metrics.conversions_value, ' .
                'metrics.interactions, ' .
                'campaign.advertising_channel_type ' .
                'FROM campaign ' .
                'WHERE segments.date BETWEEN "' . $startDateFormatted . '" AND "' . $endDateFormatted . '" ' .
                'ORDER BY segments.date DESC, campaign.id';

            $requestBody = [
                'query' => $query,
                'pageSize' => self::PAGE_SIZE
            ];

            if ($nextPageToken) {
                $requestBody['pageToken'] = $nextPageToken;
            }

            // Build headers
            $headers = [
                'Authorization' => "Bearer {$accessToken}",
                'developer-token' => $developerToken,
            ];

            // Add login-customer-id header if this is a client account under a manager
            if ($loginCustomerId) {
                $headers['login-customer-id'] = $loginCustomerId;
            }

            // Make API request
            $response = Http::withHeaders($headers)->post(self::API_BASE_URL . "/customers/{$customerId}/googleAds:search", $requestBody);

            if (!$response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->body();

                Log::error('Failed to fetch metrics from Google Ads API', [
                    'customer_id' => $customerId,
                    'status' => $statusCode,
                    'body' => $responseBody
                ]);

                throw new \Exception("Google Ads API error: {$statusCode} - {$responseBody}");
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            // Parse metrics data
            foreach ($results as $result) {
                $campaign = $result['campaign'] ?? [];
                $segments = $result['segments'] ?? [];
                $metricsRow = $result['metrics'] ?? [];

                if (!empty($campaign['id']) && !empty($segments['date'])) {
                    $metrics[] = [
                        'campaign_id' => (string)$campaign['id'],
                        'campaign_name' => $campaign['name'] ?? 'Unknown',
                        'date' => $this->formatGoogleAdsDate($segments['date']),
                        'impressions' => (int)($metricsRow['impressions'] ?? 0),
                        'clicks' => (int)($metricsRow['clicks'] ?? 0),
                        'spend' => $this->convertMicrosToDecimal($metricsRow['costMicros'] ?? 0),
                        'conversions' => (int)($metricsRow['conversions'] ?? 0),
                        'revenue' => $this->convertMicrosToDecimal($metricsRow['conversionsValue'] ?? 0),
                        'video_views' => 0, // Not available for all campaign types
                        'interactions' => (int)($metricsRow['interactions'] ?? 0),
                        'view_through_conversions' => 0, // Not available for all campaign types
                        'channel_type' => $campaign['advertisingChannelType'] ?? null,
                    ];
                }
            }

            // Handle pagination
            $nextPageToken = $data['nextPageToken'] ?? null;

        } while ($nextPageToken);

        return $metrics;
    }

    /**
     * Fetch metrics from Google Ads API using official SDK
     */
    private function fetchMetricsFromSDK(
        string $customerId,
        string $refreshToken,
        string $developerToken,
        string $startDate,
        string $endDate,
        ?string $loginCustomerId = null
    ): array {
        try {
            // Build Google Ads client using official SDK
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId(config('services.google_ads.client_id') ?: env('GOOGLE_ADS_CLIENT_ID', ''))
                ->withClientSecret(config('services.google_ads.client_secret') ?: env('GOOGLE_ADS_CLIENT_SECRET', ''))
                ->withRefreshToken($refreshToken)
                ->build();

            $clientBuilder = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($developerToken);

            // Set login customer ID if this is a client account under a manager
            if ($loginCustomerId) {
                $clientBuilder->withLoginCustomerId($loginCustomerId);
            }

            $googleAdsClient = $clientBuilder->build();

            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

            // Convert dates to Google Ads format (YYYYMMDD)
            $startDateFormatted = str_replace('-', '', $startDate);
            $endDateFormatted = str_replace('-', '', $endDate);

            // Build GAQL query for campaign performance (include ALL campaigns including archived/removed for historical data)
            // Note: Only include universally available metrics
            $query = 'SELECT ' .
                'campaign.id, ' .
                'campaign.name, ' .
                'segments.date, ' .
                'metrics.impressions, ' .
                'metrics.clicks, ' .
                'metrics.cost_micros, ' .
                'metrics.conversions, ' .
                'metrics.conversions_value, ' .
                'metrics.interactions, ' .
                'campaign.advertising_channel_type ' .
                'FROM campaign ' .
                'WHERE segments.date BETWEEN "' . $startDateFormatted . '" AND "' . $endDateFormatted . '" ' .
                'ORDER BY segments.date DESC, campaign.id';

            $searchRequest = new SearchGoogleAdsRequest([
                'customer_id' => $customerId,
                'query' => $query
                // Note: page_size is not supported by the API
            ]);

            $response = $googleAdsServiceClient->search($searchRequest);

            $metrics = [];

            // Parse metrics data from SDK response
            foreach ($response->getPage()->getIterator() as $googleAdsRow) {
                $campaign = $googleAdsRow->getCampaign();
                $segments = $googleAdsRow->getSegments();
                $metricsRow = $googleAdsRow->getMetrics();

                if ($campaign && $segments && $metricsRow) {
                    $metrics[] = [
                        'campaign_id' => (string)$campaign->getId(),
                        'campaign_name' => $campaign->getName(),
                        'date' => $segments->getDate(),
                        'impressions' => (int)$metricsRow->getImpressions(),
                        'clicks' => (int)$metricsRow->getClicks(),
                        'spend' => $this->convertMicrosToDecimal($metricsRow->getCostMicros()),
                        'conversions' => (int)$metricsRow->getConversions(),
                        'revenue' => $this->convertMicrosToDecimal($metricsRow->getConversionsValue()),
                        'video_views' => 0, // Not available for all campaign types
                        'interactions' => (int)$metricsRow->getInteractions(),
                        'view_through_conversions' => 0, // Not available for all campaign types
                        'channel_type' => $campaign->getAdvertisingChannelType(),
                    ];
                }
            }

            Log::info('Fetched metrics using SDK', [
                'customer_id' => $customerId,
                'metrics_count' => count($metrics),
                'date_range' => "{$startDate} to {$endDate}"
            ]);

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Failed to fetch metrics using SDK', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync metrics to database
     */
    private function syncMetricsToDatabase(AdAccount $account, array $metricsData): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($metricsData as $metricRow) {
                // Find the corresponding campaign in our database
                $campaign = AdCampaign::where('ad_account_id', $account->id)
                    ->where('external_campaign_id', $metricRow['campaign_id'])
                    ->first();

                if (!$campaign) {
                    // Auto-create placeholder campaign for deleted/removed campaigns
                    // so we can still track their historical metrics
                    $campaign = AdCampaign::create([
                        'tenant_id' => $account->tenant_id,
                        'ad_account_id' => $account->id,
                        'external_campaign_id' => $metricRow['campaign_id'],
                        'name' => $metricRow['campaign_name'] ?? 'Deleted Campaign ' . $metricRow['campaign_id'],
                        'status' => 'archived',
                        'objective' => null,
                    ]);

                    Log::info('Auto-created placeholder campaign for metrics', [
                        'campaign_id' => $campaign->id,
                        'external_id' => $metricRow['campaign_id'],
                        'campaign_name' => $campaign->name
                    ]);
                }

                // Generate checksum for deduplication
                $checksum = $this->generateChecksum(
                    $account->id,
                    $campaign->id,
                    $metricRow['date']
                );

                // Check if metric already exists
                $existingMetric = AdMetric::where('checksum', $checksum)->first();

                if ($existingMetric) {
                    // Update existing metric
                    $existingMetric->update([
                        'spend' => $metricRow['spend'],
                        'impressions' => $metricRow['impressions'],
                        'clicks' => $metricRow['clicks'],
                        'conversions' => $metricRow['conversions'],
                        'revenue' => $metricRow['revenue'],
                        'video_views' => $metricRow['video_views'],
                        'reach' => $metricRow['interactions'], // Use interactions as proxy for reach
                    ]);
                    $updated++;
                } else {
                    // Create new metric
                    AdMetric::create([
                        'tenant_id' => $account->tenant_id,
                        'date' => $metricRow['date'],
                        'platform' => 'google',
                        'ad_account_id' => $account->id,
                        'ad_campaign_id' => $campaign->id,
                        'objective' => $campaign->objective,
                        'spend' => $metricRow['spend'],
                        'impressions' => $metricRow['impressions'],
                        'clicks' => $metricRow['clicks'],
                        'conversions' => $metricRow['conversions'],
                        'revenue' => $metricRow['revenue'],
                        'video_views' => $metricRow['video_views'],
                        'reach' => $metricRow['interactions'], // Use interactions as proxy for reach
                        'checksum' => $checksum,
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => count($metricsData)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync metrics for multiple accounts
     */
    public function syncMetricsForAccounts(
        array $accounts,
        string $accessToken,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $refreshToken = null
    ): array {
        $results = [];

        foreach ($accounts as $account) {
            try {
                $syncResult = $this->syncMetricsForAccount($account, $accessToken, $startDate, $endDate, $refreshToken);
                $results[$account->id] = [
                    'success' => true,
                    'account_name' => $account->name,
                    'created' => $syncResult['created'],
                    'updated' => $syncResult['updated'],
                    'skipped' => $syncResult['skipped'],
                    'total' => $syncResult['total']
                ];
            } catch (\Exception $e) {
                $results[$account->id] = [
                    'success' => false,
                    'account_name' => $account->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Convert Google Ads date format to YYYY-MM-DD
     */
    private function formatGoogleAdsDate(string $googleDate): string
    {
        // Google Ads returns dates as YYYY-MM-DD, so just return as is
        return $googleDate;
    }

    /**
     * Convert micros (1/1,000,000) to decimal
     */
    private function convertMicrosToDecimal($micros): float
    {
        return round((float)$micros / 1000000, 2);
    }

    /**
     * Generate unique checksum for metric
     */
    private function generateChecksum(int $accountId, int $campaignId, string $date): string
    {
        return hash('sha256', "google_{$accountId}_{$campaignId}_{$date}");
    }
}
