<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TikTokAdsService
{
    // Use environment variables for OAuth credentials
    private function getClientKey(): string
    {
        // TikTok uses 'app_id' - try client_key first (TikTok's naming), then client_id
        return config('services.tiktok.client_key')
            ?: config('services.tiktok.client_id')
            ?: env('TIKTOK_CLIENT_KEY')
            ?: env('TIKTOK_CLIENT_ID', '');
    }

    private function getClientSecret(): string
    {
        return config('services.tiktok.client_secret') ?: env('TIKTOK_CLIENT_SECRET', '');
    }

    private function getRedirectUri(): string
    {
        return config('services.tiktok.redirect_uri') ?: env('TIKTOK_REDIRECT_URI', 'https://rb-benchmarks.redbananas.com/api/tiktok/oauth/callback');
    }

    private const REDIRECT_URI = 'https://rb-benchmarks.redbananas.com/api/tiktok/oauth/callback';
    private const OAUTH_URL = 'https://business-api.tiktok.com/portal/auth';
    private const TOKEN_URL = 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/';
    private const API_BASE_URL = 'https://business-api.tiktok.com/open_api/v1.3';

    public function getAuthorizationUrl(?string $state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));

        $params = http_build_query([
            'app_id' => $this->getClientKey(),
            'redirect_uri' => $this->getRedirectUri(),
            'state' => $state,
        ]);

        return self::OAUTH_URL . '?' . $params;
    }

    public function exchangeCodeForTokens(string $code): ?array
    {
        try {
            $response = Http::post(self::TOKEN_URL, [
                'app_id' => $this->getClientKey(),
                'secret' => $this->getClientSecret(),
                'auth_code' => $code,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['code']) && $data['code'] === 0) {
                    $tokenData = $data['data'];

                    Log::info('TikTok Ads token exchange successful', [
                        'expires_in' => $tokenData['expires_in'] ?? null,
                        'scope' => $tokenData['scope'] ?? null
                    ]);

                    return $tokenData;
                }

                Log::error('TikTok Ads token exchange failed', [
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null
                ]);

                return null;
            }

            Log::error('TikTok Ads token exchange HTTP failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('TikTok Ads token exchange error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getLongLivedAccessToken(array $tokenData): ?array
    {
        // TikTok Ads tokens can be refreshed using refresh_token
        if (isset($tokenData['refresh_token'])) {
            return $this->refreshAccessToken($tokenData['refresh_token']);
        }

        return $tokenData;
    }

    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::post(self::API_BASE_URL . '/oauth2/refresh_token/', [
                'app_id' => $this->getClientKey(),
                'secret' => $this->getClientSecret(),
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['code']) && $data['code'] === 0) {
                    Log::info('TikTok Ads token refresh successful');
                    return $data['data'];
                }

                Log::error('TikTok Ads token refresh failed', [
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null
                ]);

                return null;
            }

            Log::error('TikTok Ads token refresh HTTP failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('TikTok Ads token refresh error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getAdAccounts(string $accessToken): array
    {
        try {
            // Step 1: Get list of advertiser IDs the user has access to
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get(self::API_BASE_URL . '/oauth2/advertiser/get/', [
                'app_id' => $this->getClientKey(),
                'secret' => $this->getClientSecret(),
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch TikTok advertiser list', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            Log::info('TikTok advertiser list response', ['data' => $data]);

            if (!isset($data['code']) || $data['code'] !== 0) {
                Log::error('TikTok advertiser list API returned error', [
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null
                ]);
                return [];
            }

            $advertiserList = $data['data']['list'] ?? [];

            if (empty($advertiserList)) {
                Log::warning('No TikTok advertiser IDs found');
                return [];
            }

            Log::info('TikTok advertiser list found', ['count' => count($advertiserList)]);

            // The list can contain either just IDs or objects with advertiser_id and advertiser_name
            // Check the structure and extract accordingly
            $accounts = [];
            $advertiserIds = [];

            foreach ($advertiserList as $item) {
                if (is_array($item) && isset($item['advertiser_id'])) {
                    // Already have full info from oauth2/advertiser/get
                    $accounts[] = [
                        'id' => $item['advertiser_id'],
                        'name' => $item['advertiser_name'] ?? "TikTok Account {$item['advertiser_id']}",
                        'currency' => 'USD',
                        'status' => 'active',
                        'time_zone' => null,
                        'company' => null,
                    ];
                    $advertiserIds[] = $item['advertiser_id'];
                } else {
                    // Just an ID
                    $advertiserIds[] = $item;
                }
            }

            // If we already have account info, try to get more details
            if (!empty($advertiserIds)) {
                $infoResponse = Http::withHeaders([
                    'Access-Token' => $accessToken,
                ])->get(self::API_BASE_URL . '/advertiser/info/', [
                    'advertiser_ids' => json_encode(array_map('strval', $advertiserIds)),
                ]);

                if ($infoResponse->successful()) {
                    $infoData = $infoResponse->json();

                    if (isset($infoData['code']) && $infoData['code'] === 0 && !empty($infoData['data']['list'])) {
                        // Update accounts with full info
                        $accounts = [];
                        foreach ($infoData['data']['list'] as $advertiser) {
                            $accounts[] = [
                                'id' => $advertiser['advertiser_id'],
                                'name' => $advertiser['advertiser_name'] ?? $advertiser['name'] ?? "TikTok Account {$advertiser['advertiser_id']}",
                                'currency' => $advertiser['currency'] ?? 'USD',
                                'status' => $this->mapTikTokStatus($advertiser['status'] ?? 'ENABLE'),
                                'time_zone' => $advertiser['timezone'] ?? null,
                                'company' => $advertiser['company'] ?? null,
                            ];
                        }
                    }
                }
            }

            Log::info('TikTok ad accounts fetched successfully', [
                'accounts_count' => count($accounts)
            ]);

            return $accounts;

        } catch (\Exception $e) {
            Log::error('TikTok ad accounts fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getCampaigns(string $accessToken, string $advertiserId): array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get(self::API_BASE_URL . '/campaign/get/', [
                'advertiser_id' => $advertiserId,
                'page' => 1,
                'page_size' => 100,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch TikTok campaigns', [
                    'advertiser_id' => $advertiserId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== 0) {
                Log::error('TikTok campaigns API returned error', [
                    'advertiser_id' => $advertiserId,
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null
                ]);
                return [];
            }

            $campaignsList = $data['data']['list'] ?? [];
            $campaigns = [];

            foreach ($campaignsList as $campaign) {
                $campaigns[] = [
                    'id' => $campaign['campaign_id'],
                    'name' => $campaign['campaign_name'] ?? 'Unnamed Campaign',
                    'status' => $this->mapTikTokCampaignStatus($campaign['status'] ?? 'ENABLE'),
                    'objective' => $campaign['objective_type'] ?? null,
                    'budget' => $campaign['budget'] ?? null,
                    'budget_mode' => $campaign['budget_mode'] ?? null,
                    'create_time' => $campaign['create_time'] ?? null,
                ];
            }

            Log::info('TikTok campaigns fetched successfully', [
                'advertiser_id' => $advertiserId,
                'campaigns_count' => count($campaigns)
            ]);

            return $campaigns;

        } catch (\Exception $e) {
            Log::error('TikTok campaigns fetch failed', [
                'advertiser_id' => $advertiserId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getFormattedLeadsData(string $accessToken, string $advertiserId, string $campaignId, string $dateRange = 'last_30_days'): array
    {
        try {
            // Calculate date range
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-30 days'));

            if ($dateRange === 'last_7_days') {
                $startDate = date('Y-m-d', strtotime('-7 days'));
            } elseif ($dateRange === 'last_90_days') {
                $startDate = date('Y-m-d', strtotime('-90 days'));
            }

            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get(self::API_BASE_URL . '/report/integrated/get/', [
                'advertiser_id' => $advertiserId,
                'report_type' => 'BASIC',
                'dimensions' => 'campaign_id',
                'metrics' => 'impressions,clicks,spend,conversions,conversion_rate',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filters' => json_encode([
                    [
                        'field_name' => 'campaign_id',
                        'filter_type' => 'IN',
                        'filter_value' => [$campaignId]
                    ]
                ])
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch TikTok metrics', [
                    'advertiser_id' => $advertiserId,
                    'campaign_id' => $campaignId,
                    'status' => $response->status()
                ]);
                return [];
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== 0) {
                Log::error('TikTok metrics API returned error', [
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null
                ]);
                return [];
            }

            $results = $data['data']['list'] ?? [];
            $formattedData = [];

            foreach ($results as $result) {
                $metrics = $result['metrics'] ?? [];

                $formattedData[] = [
                    'Date' => $result['stat_time_day'] ?? date('Y-m-d'),
                    'Campaign Name' => $this->getCampaignName($accessToken, $advertiserId, $campaignId),
                    'Impressions' => $metrics['impressions'] ?? 0,
                    'Clicks' => $metrics['clicks'] ?? 0,
                    'Spend' => $metrics['spend'] ?? 0,
                    'Conversions' => $metrics['conversions'] ?? 0,
                    'Conversion Rate' => $metrics['conversion_rate'] ?? 0,
                    'Platform' => 'TikTok Ads',
                ];
            }

            return $formattedData;

        } catch (\Exception $e) {
            Log::error('TikTok metrics fetch failed', [
                'advertiser_id' => $advertiserId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getCampaignName(string $accessToken, string $advertiserId, string $campaignId): string
    {
        $campaigns = $this->getCampaigns($accessToken, $advertiserId);

        foreach ($campaigns as $campaign) {
            if ($campaign['id'] === $campaignId) {
                return $campaign['name'];
            }
        }

        return "Campaign {$campaignId}";
    }

    private function mapTikTokStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ENABLE' => 'active',
            'DISABLE', 'PAUSE' => 'paused',
            'DELETE' => 'archived',
            default => 'active',
        };
    }

    private function mapTikTokCampaignStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ENABLE' => 'active',
            'DISABLE', 'PAUSE' => 'paused',
            'DELETE' => 'archived',
            default => 'active',
        };
    }

    private function mapTikTokObjective(string $objective): string
    {
        return match (strtoupper($objective)) {
            'TRAFFIC', 'REACH', 'VIDEO_VIEWS' => 'awareness',
            'CONVERSIONS', 'APP_INSTALL' => 'leads',
            'CATALOG_SALES' => 'sales',
            default => 'awareness',
        };
    }

    /**
     * Sync campaigns to database
     */
    public function syncCampaignsToDatabase(\App\Models\AdAccount $account, string $accessToken): array
    {
        try {
            $campaigns = $this->getCampaigns($accessToken, $account->external_account_id);

            if (empty($campaigns)) {
                return ['created' => 0, 'updated' => 0, 'total' => 0];
            }

            $created = 0;
            $updated = 0;
            $categoryMapper = app(CategoryMapper::class);

            \DB::beginTransaction();

            foreach ($campaigns as $campaignData) {
                $objective = $this->mapTikTokObjective($campaignData['objective'] ?? '');
                $funnelStage = \App\Models\AdCampaign::funnelStageForObjective($objective);

                // Auto-detect category
                $accountIndustry = $account->industry;
                $detectedCategory = $categoryMapper->detectCategory($campaignData['name'], $accountIndustry);

                $campaign = \App\Models\AdCampaign::updateOrCreate(
                    [
                        'ad_account_id' => $account->id,
                        'external_campaign_id' => $campaignData['id']
                    ],
                    [
                        'tenant_id' => $account->tenant_id,
                        'name' => $campaignData['name'],
                        'objective' => $objective,
                        'status' => $campaignData['status'],
                        'channel_type' => 'TikTok',
                        'funnel_stage' => $funnelStage,
                        'sub_industry' => $detectedCategory,
                        'inherit_category_from_account' => $detectedCategory ? false : true,
                        'category' => $detectedCategory,
                        'user_journey' => 'landing_page',
                        'campaign_config' => [
                            'objective_type' => $campaignData['objective'],
                            'budget' => $campaignData['budget'],
                            'budget_mode' => $campaignData['budget_mode'],
                            'last_synced_at' => now()->toIso8601String()
                        ]
                    ]
                );

                if ($campaign->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            \DB::commit();

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($campaigns)
            ];

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('TikTok campaigns database sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->getClientKey()) && !empty($this->getClientSecret());
    }

    public function testConnection(string $accessToken): array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get(self::API_BASE_URL . '/advertiser/info/', [
                'page' => 1,
                'page_size' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['code']) && $data['code'] === 0) {
                    return [
                        'success' => true,
                        'message' => 'TikTok Ads connection test successful'
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'TikTok Ads connection test failed',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'TikTok Ads connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }
}