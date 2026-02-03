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

class LinkedInAdsService
{
    protected CurrencyConversionService $currencyService;
    protected IndustryDetector $industryDetector;

    private const API_BASE_URL = 'https://api.linkedin.com/v2';
    private const API_ANALYTICS_URL = 'https://api.linkedin.com/rest';
    private const OAUTH_URL = 'https://www.linkedin.com/oauth/v2/authorization';
    private const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
    private const REDIRECT_URI = 'https://rb-benchmarks.redbananas.com/api/linkedin/oauth/callback';

    public function __construct(CurrencyConversionService $currencyService, IndustryDetector $industryDetector)
    {
        $this->currencyService = $currencyService;
        $this->industryDetector = $industryDetector;
    }

    /**
     * Generate LinkedIn OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $clientId = config('services.linkedin.client_id');
        if (empty($clientId)) {
            throw new \Exception('LinkedIn Client ID not configured. Please set LINKEDIN_CLIENT_ID in your environment.');
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT_URI,
            'state' => $state,
            'scope' => 'r_ads,r_ads_reporting,r_organization_social'
        ]);

        return self::OAUTH_URL . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $clientId = config('services.linkedin.client_id');
        $clientSecret = config('services.linkedin.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('LinkedIn credentials not configured.');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::REDIRECT_URI,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$response->successful()) {
            Log::error('LinkedIn token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Verify that the Marketing Developer Platform has been approved
     * and that required ad scopes are accessible
     */
    public function verifyMarketingAccess(string $accessToken): array
    {
        try {
            // Make a lightweight API call to check if Marketing Developer Platform is approved
            // This endpoint requires Marketing Platform approval
            // Note: Using simple search without status filter to avoid parameter errors
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get(self::API_BASE_URL . '/adAccountsV2', [
                'q' => 'search'
            ]);

            $status = $response->status();
            $body = $response->body();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Marketing Developer Platform access verified successfully.',
                    'accounts_accessible' => true
                ];
            }

            // Handle specific error cases
            if ($status === 403) {
                Log::warning('LinkedIn Marketing Developer Platform not approved', [
                    'status' => $status,
                    'body' => $body
                ]);

                return [
                    'success' => false,
                    'error' => 'marketing_platform_not_approved',
                    'message' => 'LinkedIn Marketing Developer Platform approval required. Please apply for Marketing Developer Platform access at https://www.linkedin.com/developers/apps and ensure the r_ads, r_ads_reporting, and r_organization_social scopes are approved.',
                    'accounts_accessible' => false
                ];
            }

            if ($status === 401) {
                Log::warning('LinkedIn access token expired or invalid', [
                    'status' => $status,
                    'body' => $body
                ]);

                return [
                    'success' => false,
                    'error' => 'token_expired',
                    'message' => 'LinkedIn access token has expired or is invalid. Please reconnect your LinkedIn integration.',
                    'accounts_accessible' => false
                ];
            }

            // Generic error
            Log::error('LinkedIn verification failed with unexpected status', [
                'status' => $status,
                'body' => $body
            ]);

            return [
                'success' => false,
                'error' => 'verification_failed',
                'message' => "LinkedIn API returned status {$status}. Please check your integration settings.",
                'accounts_accessible' => false
            ];

        } catch (\Exception $e) {
            Log::error('LinkedIn Marketing Platform verification error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'exception',
                'message' => 'Failed to verify Marketing Developer Platform access: ' . $e->getMessage(),
                'accounts_accessible' => false
            ];
        }
    }

    /**
     * Get ad accounts from LinkedIn Ads API
     */
    public function getAdAccounts(Integration $integration): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for LinkedIn integration');
        }

        try {
            // Get ad accounts (LinkedIn calls them "accounts")
            // Note: Using finder method instead of search to avoid parameter errors
            // The 'search' finder with status was deprecated - now using basic query
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get(self::API_BASE_URL . '/adAccountsV2', [
                // Use account owner finder instead of search
                'q' => 'search',
                'start' => 0,
                'count' => 100
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                $body = $response->body();

                Log::error('LinkedIn ad accounts fetch failed', [
                    'status' => $status,
                    'body' => $body
                ]);

                // Provide specific error messages based on status code
                if ($status === 403) {
                    throw new \Exception('LinkedIn Marketing Developer Platform approval required. Please ensure your LinkedIn app has Marketing Platform access and the required scopes (r_ads, r_ads_reporting, r_organization_social) are approved. Apply at: https://www.linkedin.com/developers/apps');
                }

                if ($status === 401) {
                    throw new \Exception('LinkedIn access token has expired or is invalid. Please reconnect your LinkedIn integration.');
                }

                throw new \Exception("Failed to fetch LinkedIn ad accounts: HTTP {$status}");
            }

            $data = $response->json();
            return $data['elements'] ?? [];

        } catch (\Exception $e) {
            Log::error('LinkedIn ad accounts error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync LinkedIn ad accounts to database
     */
    public function syncAdAccounts(Integration $integration): array
    {
        $linkedinAccounts = $this->getAdAccounts($integration);
        $syncedAccounts = [];

        foreach ($linkedinAccounts as $account) {
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
                'account_name' => $account['name'] ?? 'LinkedIn Account',
                'currency' => $account['currency'] ?? 'USD',
                'status' => 'active',
                'account_config' => [
                    'platform' => 'linkedin',
                    'account_type' => $account['type'] ?? 'BUSINESS',
                    'currency' => $account['currency'] ?? 'USD',
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
     * Get Ad Sets (LinkedIn Campaigns) for a LinkedIn ad account
     * LinkedIn hierarchy: Campaign Groups > Campaigns (ad sets) > Creatives (ads)
     */
    public function getAdSets(Integration $integration, AdAccount $adAccount): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for LinkedIn integration');
        }

        try {
            $url = self::API_BASE_URL . '/adCampaignsV2';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get($url, [
                'q' => 'search',
                'start' => 0,
                'count' => 500
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch LinkedIn ad sets: HTTP {$response->status()}");
            }

            $data = $response->json();
            $allCampaigns = $data['elements'] ?? [];

            // Filter by account
            $accountUrn = 'urn:li:sponsoredAccount:' . $adAccount->external_account_id;
            $filtered = array_filter($allCampaigns, fn($c) => ($c['account'] ?? '') === $accountUrn);

            Log::info('LinkedIn ad sets fetched', [
                'account_id' => $adAccount->external_account_id,
                'count' => count($filtered)
            ]);

            return array_values($filtered);
        } catch (\Exception $e) {
            Log::error('LinkedIn ad sets error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Campaign Groups for a LinkedIn ad account
     */
    public function getCampaignGroupsData(Integration $integration, AdAccount $adAccount): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for LinkedIn integration');
        }

        try {
            $url = self::API_ANALYTICS_URL . '/adAccounts/' . $adAccount->external_account_id . '/adCampaignGroups';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202501',
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get($url, [
                'q' => 'search',
                'start' => 0,
                'count' => 500
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch LinkedIn campaign groups: HTTP {$response->status()}");
            }

            $data = $response->json();
            $groups = $data['elements'] ?? [];

            Log::info('LinkedIn campaign groups fetched', [
                'account_id' => $adAccount->external_account_id,
                'count' => count($groups)
            ]);

            return $groups;
        } catch (\Exception $e) {
            Log::error('LinkedIn campaign groups error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Creatives (ads) for a LinkedIn ad account
     */
    public function getCreativesData(Integration $integration, AdAccount $adAccount): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for LinkedIn integration');
        }

        try {
            // First get ad sets for this account
            $adSets = $this->getAdSets($integration, $adAccount);
            $adSetUrns = array_map(fn($c) => 'urn:li:sponsoredCampaign:' . $c['id'], $adSets);

            // Fetch all creatives
            $url = self::API_BASE_URL . '/adCreativesV2';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get($url, [
                'q' => 'search',
                'start' => 0,
                'count' => 500
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch LinkedIn creatives: HTTP {$response->status()}");
            }

            $data = $response->json();
            $allCreatives = $data['elements'] ?? [];

            // Filter by ad sets belonging to this account
            $filtered = array_filter($allCreatives, fn($c) => in_array($c['campaign'] ?? '', $adSetUrns));

            Log::info('LinkedIn creatives fetched', [
                'account_id' => $adAccount->external_account_id,
                'total' => count($allCreatives),
                'filtered' => count($filtered)
            ]);

            return array_values($filtered);
        } catch (\Exception $e) {
            Log::error('LinkedIn creatives error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Legacy method - now syncs Ad Sets (LinkedIn Campaigns)
     */
    public function getCampaigns(Integration $integration, AdAccount $adAccount): array
    {
        return $this->getAdSets($integration, $adAccount);
    }

    /**
     * Sync LinkedIn Ad Sets (Campaigns) to database - DEFAULT
     */
    public function syncCampaigns(Integration $integration, AdAccount $adAccount): array
    {
        $adSets = $this->getAdSets($integration, $adAccount);
        $synced = [];
        $categoryMapper = app(CategoryMapper::class);

        foreach ($adSets as $adSet) {
            $objective = $this->mapLinkedInObjective($adSet['objective'] ?? 'WEBSITE_VISITS');
            $status = $this->mapLinkedInStatus($adSet['status'] ?? 'ACTIVE');
            $name = $adSet['name'] ?? 'LinkedIn Ad Set';
            $detectedCategory = $categoryMapper->detectCategory($name, $adAccount->industry);

            $data = [
                'tenant_id' => $integration->tenant_id,
                'ad_account_id' => $adAccount->id,
                'external_campaign_id' => (string) $adSet['id'],
                'name' => $name,
                'objective' => $objective,
                'status' => $status,
                'channel_type' => 'LinkedIn',
                'sub_industry' => $detectedCategory,
                'category' => $detectedCategory,
                'inherit_category_from_account' => $detectedCategory ? false : true,
                'funnel_stage' => $this->mapObjectiveToFunnelStage($objective),
                'user_journey' => 'landing_page',
                'has_pixel_data' => false,
                'linkedin_level' => 'ad_set',
            ];

            $synced[] = AdCampaign::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'ad_account_id' => $adAccount->id,
                    'external_campaign_id' => (string) $adSet['id']
                ],
                $data
            );
        }

        return $synced;
    }

    /**
     * Sync LinkedIn Campaign Groups to database
     */
    public function syncCampaignGroups(Integration $integration, AdAccount $adAccount): array
    {
        $groups = $this->getCampaignGroupsData($integration, $adAccount);
        $synced = [];
        $categoryMapper = app(CategoryMapper::class);

        foreach ($groups as $group) {
            $status = $this->mapLinkedInStatus($group['status'] ?? 'ACTIVE');
            $name = $group['name'] ?? 'LinkedIn Campaign Group';
            $detectedCategory = $categoryMapper->detectCategory($name, $adAccount->industry);

            $data = [
                'tenant_id' => $integration->tenant_id,
                'ad_account_id' => $adAccount->id,
                'external_campaign_id' => (string) $group['id'],
                'name' => $name,
                'objective' => 'awareness',
                'status' => $status,
                'channel_type' => 'LinkedIn',
                'sub_industry' => $detectedCategory,
                'category' => $detectedCategory,
                'inherit_category_from_account' => $detectedCategory ? false : true,
                'funnel_stage' => 'TOF',
                'user_journey' => 'landing_page',
                'has_pixel_data' => false,
                'linkedin_level' => 'campaign_group',
            ];

            $synced[] = AdCampaign::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'ad_account_id' => $adAccount->id,
                    'external_campaign_id' => (string) $group['id']
                ],
                $data
            );
        }

        return $synced;
    }

    /**
     * Sync LinkedIn Creatives (ads) to database
     */
    public function syncCreatives(Integration $integration, AdAccount $adAccount): array
    {
        $creatives = $this->getCreativesData($integration, $adAccount);
        $synced = [];
        $categoryMapper = app(CategoryMapper::class);

        foreach ($creatives as $creative) {
            $status = $this->mapLinkedInStatus($creative['intendedStatus'] ?? $creative['status'] ?? 'ACTIVE');
            $name = $creative['name'] ?? ('LinkedIn Ad #' . ($creative['id'] ?? 'unknown'));
            $detectedCategory = $categoryMapper->detectCategory($name, $adAccount->industry);

            $data = [
                'tenant_id' => $integration->tenant_id,
                'ad_account_id' => $adAccount->id,
                'external_campaign_id' => (string) $creative['id'],
                'name' => $name,
                'objective' => 'awareness',
                'status' => $status,
                'channel_type' => 'LinkedIn',
                'sub_industry' => $detectedCategory,
                'category' => $detectedCategory,
                'inherit_category_from_account' => $detectedCategory ? false : true,
                'funnel_stage' => 'TOF',
                'user_journey' => 'landing_page',
                'has_pixel_data' => false,
                'linkedin_level' => 'creative',
            ];

            $synced[] = AdCampaign::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'ad_account_id' => $adAccount->id,
                    'external_campaign_id' => (string) $creative['id']
                ],
                $data
            );
        }

        return $synced;
    }

    /**
     * Get campaign performance metrics
     * @param string $granularity 'DAILY' for daily breakdown, 'ALL' for total (faster)
     */
    public function getCampaignMetrics(Integration $integration, AdCampaign $campaign, string $startDate, string $endDate, string $granularity = 'DAILY'): array
    {
        $credentials = $integration->getCredentials();
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No access token available for LinkedIn integration');
        }

        try {
            // Determine pivot based on linkedin_level
            $linkedinLevel = $campaign->linkedin_level ?? 'ad_set';
            switch ($linkedinLevel) {
                case 'campaign_group':
                    $pivot = 'CAMPAIGN_GROUP';
                    $filter = 'campaignGroups=List(urn%3Ali%3AsponsoredCampaignGroup%3A' . $campaign->external_campaign_id . ')';
                    break;
                case 'creative':
                    $pivot = 'CREATIVE';
                    $filter = 'creatives=List(urn%3Ali%3AsponsoredCreative%3A' . $campaign->external_campaign_id . ')';
                    break;
                case 'ad_set':
                default:
                    $pivot = 'CAMPAIGN';
                    $filter = 'campaigns=List(urn%3Ali%3AsponsoredCampaign%3A' . $campaign->external_campaign_id . ')';
                    break;
            }

            // Build URL with proper LinkedIn API v2025 format
            $url = self::API_ANALYTICS_URL . '/adAnalytics?q=analytics&pivot=' . $pivot . '&timeGranularity=' . $granularity;

            // Build date range parameter
            $startYear = (int) Carbon::parse($startDate)->format('Y');
            $startMonth = (int) Carbon::parse($startDate)->format('n');
            $startDay = (int) Carbon::parse($startDate)->format('j');
            $endYear = (int) Carbon::parse($endDate)->format('Y');
            $endMonth = (int) Carbon::parse($endDate)->format('n');
            $endDay = (int) Carbon::parse($endDate)->format('j');

            $url .= '&dateRange=(start:(year:' . $startYear . ',month:' . $startMonth . ',day:' . $startDay . ')';
            $url .= ',end:(year:' . $endYear . ',month:' . $endMonth . ',day:' . $endDay . '))';

            // Add filter based on level
            $url .= '&' . $filter;

            // Add metrics fields (dateRange is required for daily granularity to get the date of each metric)
            $url .= '&fields=impressions,clicks,costInLocalCurrency,externalWebsiteConversions,oneClickLeads,dateRange';

            Log::info('LinkedIn API Request', ['url' => $url, 'campaign_id' => $campaign->external_campaign_id]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202510',
                'X-Restli-Protocol-Version' => '2.0.0'
            ])->get($url);

            if (!$response->successful()) {
                Log::error('LinkedIn metrics fetch failed', [
                    'campaign_id' => $campaign->external_campaign_id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            $elements = $data['elements'] ?? [];

            if (empty($elements)) {
                Log::info('LinkedIn API returned empty elements', ['campaign_id' => $campaign->external_campaign_id, 'date' => $startDate]);
            } else {
                Log::info('LinkedIn API returned metrics', ['campaign_id' => $campaign->external_campaign_id, 'count' => count($elements)]);
            }

            return $elements;

        } catch (\Exception $e) {
            Log::error('LinkedIn metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get campaign TOTAL metrics (ALL granularity) - much faster for verification
     * Returns a single aggregated metric for the entire date range
     */
    public function getCampaignTotalMetrics(Integration $integration, AdCampaign $campaign, string $startDate, string $endDate): array
    {
        return $this->getCampaignMetrics($integration, $campaign, $startDate, $endDate, 'ALL');
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

        Log::info('Processing LinkedIn metrics', ['campaign_id' => $campaign->id, 'metrics_count' => count($metricsData)]);

        foreach ($metricsData as $metrics) {
            // LinkedIn returns cost in local currency (USD for most accounts)
            // Store in ORIGINAL currency - conversion to SAR happens at display layer
            $spendOriginal = $metrics['costInLocalCurrency'] ?? 0;

            // Generate checksum for deduplication
            $checksum = hash('sha256', "linkedin_{$campaign->ad_account_id}_{$campaign->id}_{$date}");

            $metricData = [
                'tenant_id' => $integration->tenant_id,
                'date' => $date,
                'platform' => 'linkedin',
                'ad_account_id' => $campaign->ad_account_id,
                'ad_campaign_id' => $campaign->id,
                'objective' => $campaign->objective,
                'funnel_stage' => $campaign->funnel_stage,
                'user_journey' => $campaign->user_journey,
                'has_pixel_data' => $campaign->has_pixel_data,
                'spend' => $spendOriginal,
                'impressions' => $metrics['impressions'] ?? 0,
                'clicks' => $metrics['clicks'] ?? 0,
                'conversions' => ($metrics['externalWebsiteConversions'] ?? 0) + ($metrics['oneClickLeads'] ?? 0),
                'leads' => $metrics['oneClickLeads'] ?? 0,
                'revenue' => 0, // LinkedIn doesn't provide revenue data directly
                'purchases' => 0,
                'calls' => 0,
                'sessions' => $metrics['clicks'] ?? 0, // Approximate sessions as clicks
                'atc' => 0,
                'reach' => 0, // LinkedIn doesn't provide reach in basic metrics
                'video_views' => 0,
                'checksum' => $checksum,
            ];

            Log::info('Saving LinkedIn metric', ['campaign_id' => $campaign->id, 'spend' => $spendOriginal, 'impressions' => $metrics['impressions'] ?? 0]);

            $metric = AdMetric::updateOrCreate(
                [
                    'tenant_id' => $integration->tenant_id,
                    'date' => $date,
                    'platform' => 'linkedin',
                    'ad_account_id' => $campaign->ad_account_id,
                    'ad_campaign_id' => $campaign->id,
                ],
                $metricData
            );

            Log::info('LinkedIn metric saved', ['metric_id' => $metric->id, 'campaign_id' => $campaign->id]);
        }

        Log::info('LinkedIn metrics sync completed for campaign', ['campaign_id' => $campaign->id, 'total_metrics' => count($metricsData)]);
    }

    /**
     * Save metrics directly from API response (used by backfill to avoid redundant API calls)
     */
    public function saveMetricsFromApiResponse(Integration $integration, AdCampaign $campaign, array $metricsData, string $date): void
    {
        if (empty($metricsData)) {
            return;
        }

        // LinkedIn returns cost in local currency (USD for most accounts)
        // Store in ORIGINAL currency - conversion to SAR happens at display layer
        $spendOriginal = $metricsData['costInLocalCurrency'] ?? 0;

        // Generate checksum for deduplication
        $checksum = hash('sha256', "linkedin_{$campaign->ad_account_id}_{$campaign->id}_{$date}");

        $metricData = [
            'tenant_id' => $integration->tenant_id,
            'date' => $date,
            'platform' => 'linkedin',
            'ad_account_id' => $campaign->ad_account_id,
            'ad_campaign_id' => $campaign->id,
            'objective' => $campaign->objective,
            'funnel_stage' => $campaign->funnel_stage,
            'user_journey' => $campaign->user_journey,
            'has_pixel_data' => $campaign->has_pixel_data,
            'spend' => $spendOriginal,
            'impressions' => $metricsData['impressions'] ?? 0,
            'clicks' => $metricsData['clicks'] ?? 0,
            'conversions' => ($metricsData['externalWebsiteConversions'] ?? 0) + ($metricsData['oneClickLeads'] ?? 0),
            'leads' => $metricsData['oneClickLeads'] ?? 0,
            'revenue' => 0, // LinkedIn doesn't provide revenue data directly
            'purchases' => 0,
            'calls' => 0,
            'sessions' => $metricsData['clicks'] ?? 0, // Approximate sessions as clicks
            'atc' => 0,
            'reach' => 0, // LinkedIn doesn't provide reach in basic metrics
            'video_views' => 0,
            'checksum' => $checksum,
        ];

        $metric = AdMetric::updateOrCreate(
            [
                'tenant_id' => $integration->tenant_id,
                'date' => $date,
                'platform' => 'linkedin',
                'ad_account_id' => $campaign->ad_account_id,
                'ad_campaign_id' => $campaign->id,
            ],
            $metricData
        );

        Log::info('LinkedIn metric saved from backfill', [
            'metric_id' => $metric->id,
            'campaign_id' => $campaign->id,
            'date' => $date,
            'spend' => $spendOriginal,
            'impressions' => $metricsData['impressions'] ?? 0
        ]);
    }

    /**
     * Map LinkedIn objective to our standard objectives
     * Valid values: 'awareness', 'leads', 'sales', 'calls', 'engagement'
     */
    private function mapLinkedInObjective(string $linkedinObjective): string
    {
        $objectiveMap = [
            'WEBSITE_VISITS' => 'leads',
            'ENGAGEMENT' => 'awareness', // Map engagement to awareness (valid enum value)
            'VIDEO_VIEWS' => 'awareness',
            'LEAD_GENERATION' => 'leads',
            'WEBSITE_CONVERSIONS' => 'sales',
            'JOB_APPLICANTS' => 'leads',
            'BRAND_AWARENESS' => 'awareness'
        ];

        return $objectiveMap[$linkedinObjective] ?? 'awareness';
    }

    /**
     * Map objective to funnel stage
     */
    private function mapObjectiveToFunnelStage(string $objective): string
    {
        $funnelMap = [
            'awareness' => 'TOF',
            'engagement' => 'MOF',
            'leads' => 'MOF',
            'sales' => 'BOF',
            'calls' => 'BOF',
        ];

        return $funnelMap[$objective] ?? 'MOF';
    }

    /**
     * Map LinkedIn campaign status to our standard status values
     * Valid values: 'active', 'paused', 'archived'
     */
    private function mapLinkedInStatus(string $linkedinStatus): string
    {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'ARCHIVED' => 'archived',
            'COMPLETED' => 'archived',
            'CANCELED' => 'archived',
            'DRAFT' => 'paused',
            'REMOVED' => 'archived',
        ];

        $normalizedStatus = strtoupper($linkedinStatus);
        return $statusMap[$normalizedStatus] ?? 'active';
    }

    /**
     * Test LinkedIn API connection
     */
    public function testConnection(Integration $integration): bool
    {
        try {
            $this->getAdAccounts($integration);
            return true;
        } catch (\Exception $e) {
            Log::error('LinkedIn connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}