<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Google\Ads\GoogleAds\Lib\V22\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V22\Services\SearchGoogleAdsRequest;

class GoogleAdsCampaignSyncService
{
    private const API_BASE_URL = 'https://googleads.googleapis.com/v16';
    private const PAGE_SIZE = 100;

    private GoogleAdsService $googleAdsService;

    public function __construct(GoogleAdsService $googleAdsService)
    {
        $this->googleAdsService = $googleAdsService;
    }

    /**
     * Sync campaigns for a specific ad account
     */
    public function syncCampaignsForAccount(AdAccount $account, string $accessToken, ?string $refreshToken = null): array
    {
        try {
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

            Log::info('Starting campaign sync for account', [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'customer_id' => $customerId,
                'login_customer_id' => $loginCustomerId
            ]);

            // Get developer token from service
            $developerToken = config('services.google_ads.developer_token') ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', '');

            if (empty($developerToken)) {
                throw new \Exception('Google Ads developer token not configured');
            }

            // Use SDK if refresh token is available, otherwise fallback to HTTP
            if ($refreshToken) {
                $campaigns = $this->fetchCampaignsFromSDK($customerId, $refreshToken, $developerToken, $loginCustomerId);
            } else {
                $campaigns = $this->fetchCampaignsFromAPI($customerId, $accessToken, $developerToken, $loginCustomerId);
            }

            Log::info('Fetched campaigns from Google Ads API', [
                'account_id' => $account->id,
                'campaigns_count' => count($campaigns)
            ]);

            // Sync campaigns to database
            $syncResults = $this->syncCampaignsToDatabase($account, $campaigns);

            Log::info('Campaign sync completed', [
                'account_id' => $account->id,
                'created' => $syncResults['created'],
                'updated' => $syncResults['updated'],
                'total' => count($campaigns)
            ]);

            return $syncResults;

        } catch (\Exception $e) {
            Log::error('Campaign sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch campaigns from Google Ads API using GAQL
     */
    private function fetchCampaignsFromAPI(string $customerId, string $accessToken, string $developerToken, ?string $loginCustomerId = null): array
    {
        $campaigns = [];
        $nextPageToken = null;

        do {
            // Build GAQL query for campaigns (include ALL campaigns including archived/removed for historical data)
            $query = 'SELECT ' .
                'campaign.id, ' .
                'campaign.name, ' .
                'campaign.status, ' .
                'campaign.advertising_channel_type, ' .
                'campaign.bidding_strategy_type, ' .
                'campaign.start_date, ' .
                'campaign.end_date, ' .
                'campaign.optimization_goal_setting.optimization_goal_types ' .
                'FROM campaign ' .
                'ORDER BY campaign.id';

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

                Log::error('Failed to fetch campaigns from Google Ads API', [
                    'customer_id' => $customerId,
                    'status' => $statusCode,
                    'body' => $responseBody
                ]);

                throw new \Exception("Google Ads API error: {$statusCode} - {$responseBody}");
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            // Parse campaign data
            foreach ($results as $result) {
                $campaign = $result['campaign'] ?? [];

                if (!empty($campaign['id'])) {
                    $campaigns[] = [
                        'external_id' => (string)$campaign['id'],
                        'name' => $campaign['name'] ?? 'Unnamed Campaign',
                        'status' => $this->mapGoogleAdsStatus($campaign['status'] ?? 'UNKNOWN'),
                        'channel_type' => $campaign['advertisingChannelType'] ?? null,
                        'bidding_strategy' => $campaign['biddingStrategyType'] ?? null,
                        'start_date' => $campaign['startDate'] ?? null,
                        'end_date' => $campaign['endDate'] ?? null,
                        'optimization_goals' => $campaign['optimizationGoalSetting']['optimizationGoalTypes'] ?? [],
                        'raw_data' => $campaign,
                    ];
                }
            }

            // Handle pagination
            $nextPageToken = $data['nextPageToken'] ?? null;

        } while ($nextPageToken);

        return $campaigns;
    }

    /**
     * Fetch campaigns from Google Ads API using official SDK
     */
    private function fetchCampaignsFromSDK(string $customerId, string $refreshToken, string $developerToken, ?string $loginCustomerId = null): array
    {
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

            // Build GAQL query for campaigns (include ALL campaigns including archived/removed for historical data)
            $query = 'SELECT ' .
                'campaign.id, ' .
                'campaign.name, ' .
                'campaign.status, ' .
                'campaign.advertising_channel_type, ' .
                'campaign.bidding_strategy_type, ' .
                'campaign.start_date, ' .
                'campaign.end_date, ' .
                'campaign.optimization_goal_setting.optimization_goal_types ' .
                'FROM campaign ' .
                'ORDER BY campaign.id';

            $searchRequest = new SearchGoogleAdsRequest([
                'customer_id' => $customerId,
                'query' => $query
                // Note: page_size is not supported by the API, it uses fixed page size of 10000
            ]);

            $response = $googleAdsServiceClient->search($searchRequest);

            $campaigns = [];

            // Parse campaign data from SDK response
            foreach ($response->getPage()->getIterator() as $googleAdsRow) {
                $campaign = $googleAdsRow->getCampaign();

                if ($campaign) {
                    $campaigns[] = [
                        'external_id' => (string)$campaign->getId(),
                        'name' => $campaign->getName(),
                        'status' => $this->mapGoogleAdsStatus($campaign->getStatus()),
                        'channel_type' => $campaign->getAdvertisingChannelType(),
                        'bidding_strategy' => $campaign->getBiddingStrategyType(),
                        'start_date' => $campaign->getStartDate(),
                        'end_date' => $campaign->getEndDate() ?: null,
                        'optimization_goals' => [], // SDK returns this differently
                    ];
                }
            }

            Log::info('Fetched campaigns using SDK', [
                'customer_id' => $customerId,
                'campaigns_count' => count($campaigns)
            ]);

            return $campaigns;

        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns using SDK', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync campaigns to database
     */
    private function syncCampaignsToDatabase(AdAccount $account, array $campaigns): array
    {
        $created = 0;
        $updated = 0;
        $categoryMapper = app(CategoryMapper::class);

        DB::beginTransaction();

        try {
            foreach ($campaigns as $campaignData) {
                $objective = $this->determineObjective($campaignData);
                $funnelStage = AdCampaign::funnelStageForObjective($objective);
                $channelType = $campaignData['channel_type'];

                // Try to auto-detect category from campaign name and account industry
                $accountIndustry = $account->industry;
                $detectedCategory = $categoryMapper->detectCategory($campaignData['name'], $accountIndustry);

                $campaign = AdCampaign::updateOrCreate(
                    [
                        'ad_account_id' => $account->id,
                        'external_campaign_id' => $campaignData['external_id']
                    ],
                    [
                        'tenant_id' => $account->tenant_id,
                        'name' => $campaignData['name'],
                        'status' => $campaignData['status'],
                        'objective' => $objective,
                        'channel_type' => $channelType,
                        'funnel_stage' => $funnelStage,
                        'sub_industry' => $detectedCategory,
                        'inherit_category_from_account' => $detectedCategory ? false : true,
                        'category' => $detectedCategory,
                        'user_journey' => 'landing_page', // Default for Google Ads
                        'campaign_config' => [
                            'channel_type' => $channelType,
                            'bidding_strategy' => $campaignData['bidding_strategy'],
                            'start_date' => $campaignData['start_date'],
                            'end_date' => $campaignData['end_date'],
                            'optimization_goals' => $campaignData['optimization_goals'],
                            'last_synced_at' => now()->toIso8601String(),
                        ]
                    ]
                );

                if ($campaign->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            DB::commit();

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($campaigns)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Map Google Ads campaign status to local status
     */
    private function mapGoogleAdsStatus(string $googleStatus): string
    {
        return match ($googleStatus) {
            'ENABLED' => 'active',
            'PAUSED' => 'paused',
            'REMOVED' => 'archived',
            default => 'archived',
        };
    }

    /**
     * Determine objective based on campaign data
     */
    private function determineObjective(array $campaignData): ?string
    {
        $channelType = $campaignData['channel_type'] ?? '';
        $optimizationGoals = $campaignData['optimization_goals'] ?? [];

        // Check optimization goals first
        foreach ($optimizationGoals as $goal) {
            if (str_contains(strtolower($goal), 'lead')) {
                return 'leads';
            }
            if (str_contains(strtolower($goal), 'sale') || str_contains(strtolower($goal), 'purchase')) {
                return 'sales';
            }
            if (str_contains(strtolower($goal), 'call')) {
                return 'calls';
            }
            if (str_contains(strtolower($goal), 'awareness') || str_contains(strtolower($goal), 'reach')) {
                return 'awareness';
            }
        }

        // Fallback to channel type inference
        if (str_contains(strtolower($channelType), 'search')) {
            return 'leads'; // Search campaigns typically for lead generation
        }

        if (str_contains(strtolower($channelType), 'shopping')) {
            return 'sales';
        }

        if (str_contains(strtolower($channelType), 'display') || str_contains(strtolower($channelType), 'video')) {
            return 'awareness';
        }

        // Default to null if can't determine
        return null;
    }

    /**
     * Sync campaigns for multiple accounts
     */
    public function syncCampaignsForAccounts(array $accounts, string $accessToken): array
    {
        $results = [];

        foreach ($accounts as $account) {
            try {
                $syncResult = $this->syncCampaignsForAccount($account, $accessToken);
                $results[$account->id] = [
                    'success' => true,
                    'account_name' => $account->name,
                    'created' => $syncResult['created'],
                    'updated' => $syncResult['updated'],
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
}
