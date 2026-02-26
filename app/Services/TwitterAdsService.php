<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Services\CurrencyConversionService;
use App\Services\IndustryDetector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwitterAdsService
{
    protected CurrencyConversionService $currencyService;
    protected IndustryDetector $industryDetector;

    private const API_BASE_URL = 'https://ads-api.x.com/12';
    private const OAUTH_URL = 'https://api.x.com/oauth/authorize';
    private const TOKEN_URL = 'https://api.x.com/oauth/access_token';
    private const REDIRECT_URI = 'https://rb-benchmarks.redbananas.com/api/twitter/oauth/callback';

    public function __construct(CurrencyConversionService $currencyService, IndustryDetector $industryDetector)
    {
        $this->currencyService = $currencyService;
        $this->industryDetector = $industryDetector;
    }

    /**
     * Generate X/Twitter OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $clientId = config('services.twitter.client_id');
        if (empty($clientId)) {
            throw new \Exception('Twitter Client ID not configured. Please set TWITTER_CLIENT_ID in your environment.');
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT_URI,
            'state' => $state,
            'scope' => 'tweet.read users.read offline.access',
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain'
        ]);

        return self::OAUTH_URL . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $clientId = config('services.twitter.client_id');
        $clientSecret = config('services.twitter.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Twitter credentials not configured.');
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => self::REDIRECT_URI,
                'code_verifier' => 'challenge'
            ]);

        if (!$response->successful()) {
            Log::error('Twitter token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get ad accounts from X Ads API
     */
    public function getAdAccounts(Integration $integration): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for Twitter integration');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get(self::API_BASE_URL . '/accounts');

            if (!$response->successful()) {
                Log::error('Twitter ad accounts fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch Twitter ad accounts');
            }

            $data = $response->json();
            return $data['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Twitter ad accounts error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync Twitter ad accounts to database
     */
    public function syncAdAccounts(Integration $integration): array
    {
        $twitterAccounts = $this->getAdAccounts($integration);
        $syncedAccounts = [];

        foreach ($twitterAccounts as $account) {
            // Check if account already exists to preserve manually set industry
            $existingAccount = AdAccount::where([
                'tenant_id' => $integration->tenant_id,
                'integration_id' => $integration->id,
                'external_account_id' => (string) $account['id']
            ])->first();

            $accountData = [
                'tenant_id' => $integration->tenant_id,
                'integration_id' => $integration->id,
                'external_account_id' => (string) $account['id'],
                'account_name' => $account['name'] ?? 'Twitter Account',
                'currency' => $account['currency'] ?? 'USD',
                'status' => 'active',
                'account_config' => [
                    'platform' => 'twitter',
                    'account_type' => $account['business_type'] ?? 'BUSINESS',
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone'] ?? 'UTC',
                ]
            ];

            // Only set industry on NEW accounts, don't overwrite existing values
            if (!$existingAccount) {
                $accountData['industry'] = $this->industryDetector->detectIndustry($account['name'] ?? '');
            }

            $adAccount = AdAccount::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'integration_id' => $integration->id,
                    'external_account_id' => (string) $account['id']
                ],
                $accountData
            );

            $syncedAccounts[] = $adAccount;
        }

        return $syncedAccounts;
    }

    /**
     * Get campaigns for a Twitter ad account
     */
    public function getCampaigns(Integration $integration, AdAccount $adAccount): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for Twitter integration');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get(self::API_BASE_URL . '/accounts/' . $adAccount->external_account_id . '/campaigns', [
                'entity_status' => 'ACTIVE,PAUSED'
            ]);

            if (!$response->successful()) {
                Log::error('Twitter campaigns fetch failed', [
                    'account_id' => $adAccount->external_account_id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch Twitter campaigns');
            }

            $data = $response->json();
            return $data['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Twitter campaigns error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync Twitter campaigns to database
     */
    public function syncCampaigns(Integration $integration, AdAccount $adAccount): array
    {
        $twitterCampaigns = $this->getCampaigns($integration, $adAccount);
        $syncedCampaigns = [];
        $categoryMapper = app(CategoryMapper::class);

        foreach ($twitterCampaigns as $campaign) {
            $objective = $this->mapTwitterObjective($campaign['objective'] ?? 'WEBSITE_CLICKS');

            // Auto-detect category from campaign name and account industry
            $campaignName = $campaign['name'] ?? 'Twitter Campaign';
            $accountIndustry = $adAccount->industry;
            $detectedCategory = $categoryMapper->detectCategory($campaignName, $accountIndustry);

            $campaignData = [
                'tenant_id' => $integration->tenant_id,
                'ad_account_id' => $adAccount->id,
                'external_campaign_id' => (string) $campaign['id'],
                'name' => $campaignName,
                'objective' => $objective,
                'status' => strtolower($campaign['entity_status'] ?? 'active'),
                'channel_type' => 'Twitter',
                'sub_industry' => $detectedCategory,
                'category' => $detectedCategory,
                'inherit_category_from_account' => $detectedCategory ? false : true,
                'funnel_stage' => AdCampaign::funnelStageForObjective($objective),
                'user_journey' => 'landing_page',
                'has_pixel_data' => false,
            ];

            $adCampaign = AdCampaign::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'ad_account_id' => $adAccount->id,
                    'external_campaign_id' => (string) $campaign['id']
                ],
                $campaignData
            );

            $syncedCampaigns[] = $adCampaign;
        }

        return $syncedCampaigns;
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(Integration $integration, AdCampaign $campaign, string $startDate, string $endDate): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for Twitter integration');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get(self::API_BASE_URL . '/stats/campaigns/' . $campaign->external_campaign_id, [
                'granularity' => 'DAY',
                'start_time' => $startDate . 'T00:00:00Z',
                'end_time' => $endDate . 'T23:59:59Z',
                'metric_groups' => 'ENGAGEMENT,BILLING,VIDEO,WEB_CONVERSION'
            ]);

            if (!$response->successful()) {
                Log::error('Twitter metrics fetch failed', [
                    'campaign_id' => $campaign->external_campaign_id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            return $data['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Twitter metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync campaign metrics to database
     */
    public function syncCampaignMetrics(Integration $integration, AdCampaign $campaign, string $date): void
    {
        $startDate = $date;
        $endDate = $date;

        $metricsData = $this->getCampaignMetrics($integration, $campaign, $startDate, $endDate);

        if (empty($metricsData)) {
            return;
        }

        foreach ($metricsData as $metrics) {
            // Convert cost from micro-currency to regular currency
            $spendUSD = ($metrics['billed_charge_local_micro'] ?? 0) / 1000000;
            $spendSAR = $this->currencyService->convertToSAR($spendUSD, 'USD');

            $metricData = [
                'tenant_id' => $integration->tenant_id,
                'date' => $date,
                'platform' => 'twitter',
                'ad_account_id' => $campaign->ad_account_id,
                'ad_campaign_id' => $campaign->id,
                'objective' => $campaign->objective,
                'funnel_stage' => $campaign->funnel_stage,
                'user_journey' => $campaign->user_journey,
                'has_pixel_data' => $campaign->has_pixel_data,
                'spend' => $spendSAR,
                'impressions' => $metrics['impressions'] ?? 0,
                'clicks' => $metrics['url_clicks'] ?? 0,
                'conversions' => $metrics['conversion_purchases'] ?? 0,
                'leads' => $metrics['conversion_sign_ups'] ?? 0,
                'revenue' => 0, // Twitter doesn't provide revenue data directly
                'purchases' => $metrics['conversion_purchases'] ?? 0,
                'calls' => 0,
                'sessions' => $metrics['url_clicks'] ?? 0, // Approximate sessions as clicks
                'atc' => $metrics['conversion_add_to_carts'] ?? 0,
                'reach' => $metrics['reach'] ?? 0,
                'video_views' => $metrics['video_views_25'] ?? 0,
            ];

            AdMetric::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'date' => $date,
                    'platform' => 'twitter',
                    'ad_account_id' => $campaign->ad_account_id,
                    'ad_campaign_id' => $campaign->id,
                ],
                $metricData
            );
        }
    }

    /**
     * Map Twitter objective to our standard objectives
     */
    private function mapTwitterObjective(string $twitterObjective): string
    {
        $objectiveMap = [
            'WEBSITE_CLICKS' => 'traffic',
            'ENGAGEMENT' => 'engagement',
            'VIDEO_VIEWS' => 'awareness',
            'FOLLOWERS' => 'engagement',
            'TWEET_ENGAGEMENTS' => 'engagement',
            'WEBSITE_CONVERSIONS' => 'website_sales',
            'LEAD_GENERATION' => 'leads',
            'APP_INSTALLS' => 'app_installs',
            'APP_RE_ENGAGEMENTS' => 'retention',
            'REACH' => 'awareness'
        ];

        return $objectiveMap[$twitterObjective] ?? 'traffic';
    }

    /**
     * Test Twitter API connection
     */
    public function testConnection(Integration $integration): bool
    {
        try {
            $this->getAdAccounts($integration);
            return true;
        } catch (\Exception $e) {
            Log::error('Twitter connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}