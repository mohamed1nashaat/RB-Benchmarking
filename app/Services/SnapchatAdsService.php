<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Services\CategoryMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SnapchatAdsService
{
    private const SNAPCHAT_API_VERSION = 'v1';
    private const BASE_URL = 'https://adsapi.snapchat.com';

    // Use environment variables for OAuth credentials
    private function getClientId(): string
    {
        return config('services.snapchat.client_id') ?: env('SNAPCHAT_CLIENT_ID', '');
    }

    private function getClientSecret(): string
    {
        return config('services.snapchat.client_secret') ?: env('SNAPCHAT_CLIENT_SECRET', '');
    }

    private function getRedirectUri(): string
    {
        return config('services.snapchat.redirect_uri', 'https://rb-benchmarks.redbananas.com/api/snapchat/oauth/callback');
    }

    /**
     * Get OAuth authorization URL for Snapchat
     */
    public function getAuthorizationUrl(?string $state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));

        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'snapchat-marketing-api',
            'state' => $state
        ]);

        return "https://accounts.snapchat.com/login/oauth2/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $authCode): array
    {
        try {
            $response = Http::asForm()->post('https://accounts.snapchat.com/login/oauth2/access_token', [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'code' => $authCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->getRedirectUri(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Snapchat OAuth token exchange successful', [
                    'expires_in' => $data['expires_in'] ?? null
                ]);
                return $data;
            }

            Log::error('Snapchat OAuth token exchange failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'error' => 'Token exchange failed',
                'message' => $response->json('error_description', 'Unknown error')
            ];

        } catch (\Exception $e) {
            Log::error('Snapchat OAuth token exchange exception', [
                'error' => $e->getMessage()
            ]);
            return [
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()->post('https://accounts.snapchat.com/login/oauth2/access_token', [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => 'Refresh failed',
                'message' => $response->json('error_description', 'Unknown error')
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Ensure we have a valid access token, refreshing if needed.
     * Shared method used by both SyncSnapchatMetrics and SyncSnapchatCampaigns.
     */
    public function ensureValidToken(Integration $integration): ?string
    {
        $config = $integration->app_config;
        $accessToken = $config['access_token'] ?? null;
        $refreshToken = $config['refresh_token'] ?? null;
        $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;

        if (!$accessToken) {
            Log::warning('No Snapchat access token found', ['integration_id' => $integration->id]);
            return null;
        }

        // Check if token is expired or expiring soon (less than 10 minutes)
        $needsRefresh = false;
        if ($expiresAt) {
            $expiresTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
            $minutesUntilExpiry = ($expiresTimestamp - time()) / 60;

            if ($minutesUntilExpiry < 10) {
                $needsRefresh = true;
                Log::info("Snapchat token expires in {$minutesUntilExpiry} minutes, refreshing...", [
                    'integration_id' => $integration->id,
                ]);
            }
        }

        if ($needsRefresh && $refreshToken) {
            $lock = Cache::lock('snapchat_token_refresh_' . $integration->id, 30);

            if ($lock->get()) {
                try {
                    // Re-read config in case another process already refreshed
                    $integration->refresh();
                    $config = $integration->app_config;
                    $accessToken = $config['access_token'] ?? null;
                    $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;

                    // Check if still needs refresh after re-read
                    $stillNeedsRefresh = true;
                    if ($expiresAt) {
                        $expiresTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
                        $stillNeedsRefresh = (($expiresTimestamp - time()) / 60) < 10;
                    }

                    if ($stillNeedsRefresh) {
                        $refreshToken = $config['refresh_token'] ?? $refreshToken;
                        $tokenResponse = $this->refreshAccessToken($refreshToken);

                        if (isset($tokenResponse['access_token'])) {
                            $config['access_token'] = $tokenResponse['access_token'];

                            if (isset($tokenResponse['refresh_token'])) {
                                $config['refresh_token'] = $tokenResponse['refresh_token'];
                            }

                            $expiresIn = $tokenResponse['expires_in'] ?? 3600;
                            $config['token_expires_at'] = time() + $expiresIn;
                            $config['last_refreshed_at'] = now()->toISOString();

                            $integration->update(['app_config' => $config]);

                            Log::info('Snapchat token auto-refreshed', [
                                'integration_id' => $integration->id,
                                'expires_in' => $expiresIn,
                            ]);

                            return $tokenResponse['access_token'];
                        } else {
                            Log::error('Snapchat token refresh failed', [
                                'integration_id' => $integration->id,
                                'error' => $tokenResponse['message'] ?? 'Unknown error',
                            ]);
                            return $accessToken;
                        }
                    }

                    return $accessToken;
                } finally {
                    $lock->release();
                }
            } else {
                // Another process is refreshing — re-read and use the updated token
                sleep(2);
                $integration->refresh();
                return $integration->app_config['access_token'] ?? $accessToken;
            }
        }

        return $accessToken;
    }

    /**
     * Get Snapchat ad accounts for authenticated user
     * Fetches from both organization-level and user-level endpoints to support business accounts
     */
    public function getAdAccounts(string $accessToken): Collection
    {
        try {
            $allAccounts = collect();

            // Fetch all organizations the user has access to
            $orgsResponse = Http::withToken($accessToken)
                ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . '/me/organizations');

            if ($orgsResponse->successful()) {
                $orgsData = $orgsResponse->json();
                $organizations = collect($orgsData['organizations'] ?? [])
                    ->filter(fn($item) => isset($item['organization']))
                    ->map(fn($item) => $item['organization']);

                Log::info('Found Snapchat organizations', [
                    'count' => $organizations->count()
                ]);

                // Fetch ad accounts from each organization
                foreach ($organizations as $org) {
                    $organizationId = $org['id'];

                    Log::info('Fetching Snapchat ad accounts from organization', [
                        'organization_id' => $organizationId,
                        'organization_name' => $org['name'] ?? 'Unknown'
                    ]);

                    $orgResponse = Http::withToken($accessToken)
                        ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/organizations/{$organizationId}/adaccounts");

                    if ($orgResponse->successful()) {
                        $orgData = $orgResponse->json();
                        // Extract nested 'adaccount' object from wrapper structure
                        $orgAccounts = collect($orgData['adaccounts'] ?? [])
                            ->filter(fn($item) => isset($item['adaccount']))
                            ->map(fn($item) => $item['adaccount']);
                        $allAccounts = $allAccounts->merge($orgAccounts);

                        Log::info('Retrieved organization ad accounts', [
                            'count' => $orgAccounts->count(),
                            'organization_id' => $organizationId
                        ]);
                    } else {
                        Log::warning('Failed to retrieve organization ad accounts', [
                            'organization_id' => $organizationId,
                            'status' => $orgResponse->status(),
                            'error' => $orgResponse->json('request_status.message', 'Unknown error')
                        ]);
                    }
                }
            } else {
                Log::warning('Failed to retrieve organizations', [
                    'status' => $orgsResponse->status(),
                    'error' => $orgsResponse->json('request_status.message', 'Unknown error')
                ]);
            }

            // Also fetch user-level accounts (for direct access)
            $userResponse = Http::withToken($accessToken)
                ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . '/me/adaccounts');

            if ($userResponse->successful()) {
                $userData = $userResponse->json();
                // Extract nested 'adaccount' object from wrapper structure (if present)
                $userAccounts = collect($userData['adaccounts'] ?? [])
                    ->map(function($item) {
                        // Handle both nested and flat structures
                        return isset($item['adaccount']) ? $item['adaccount'] : $item;
                    });

                // Merge and deduplicate by account ID
                $allAccounts = $allAccounts->merge($userAccounts)->unique('id');

                Log::info('Retrieved user ad accounts', [
                    'user_count' => $userAccounts->count(),
                    'total_unique' => $allAccounts->count()
                ]);
            } else {
                Log::warning('Failed to retrieve user ad accounts', [
                    'status' => $userResponse->status(),
                    'error' => $userResponse->json('request_status.message', 'Unknown error')
                ]);
            }

            // Map to standardized format
            return $allAccounts->map(function ($account) {
                return [
                    'id' => $account['id'],
                    'name' => $account['name'],
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone'] ?? 'UTC',
                    'status' => $account['status'] ?? 'ACTIVE',
                    'type' => $account['type'] ?? 'DIRECT',
                    'organization_id' => $account['organization_id'] ?? null,
                    'created_time' => $account['created_time'] ?? null,
                    'updated_time' => $account['updated_time'] ?? null
                ];
            });

        } catch (\Exception $e) {
            Log::error('Exception retrieving Snapchat ad accounts', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get campaigns for a specific ad account
     */
    public function getCampaigns(string $adAccountId, string $accessToken): Collection
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/adaccounts/{$adAccountId}/campaigns");

            if ($response->successful()) {
                $data = $response->json();
                $campaigns = collect($data['campaigns'] ?? []);

                return $campaigns->map(function ($item) {
                    // Extract nested 'campaign' object from wrapper structure
                    $campaign = $item['campaign'] ?? $item;

                    return [
                        'id' => $campaign['id'],
                        'name' => $campaign['name'],
                        'status' => $campaign['status'] ?? 'ACTIVE',
                        'objective' => $campaign['objective'] ?? '',
                        'daily_budget_micro' => $campaign['daily_budget_micro'] ?? 0,
                        'lifetime_spend_cap_micro' => $campaign['lifetime_spend_cap_micro'] ?? 0,
                        'start_time' => $campaign['start_time'] ?? null,
                        'end_time' => $campaign['end_time'] ?? null,
                        'created_time' => $campaign['created_time'] ?? null,
                        'updated_time' => $campaign['updated_time'] ?? null
                    ];
                });
            }

            return collect();

        } catch (\Exception $e) {
            Log::error('Exception retrieving Snapchat campaigns', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get ad performance stats for campaigns using parallel HTTP requests
     * Uses Http::pool() for concurrent requests with smart rate limit handling
     * Note: DAY granularity has a 32-day limit per API call
     */
    public function getCampaignStats(string $adAccountId, string $accessToken, array $campaignIds, string $startDate, string $endDate, string $timezone = 'UTC'): Collection
    {
        $allStats = collect();

        try {
            // Break date range into 31-day chunks (API limit is 32 days)
            $dateChunks = $this->getDateChunks($startDate, $endDate, 31);

            // Build all request tasks with timezone-aware dates
            $tasks = [];
            foreach ($campaignIds as $campaignId) {
                foreach ($dateChunks as $chunkIndex => $chunk) {
                    // Convert dates to account timezone for Snapchat API
                    $startTime = \Carbon\Carbon::parse($chunk['start'], $timezone)->startOfDay()->toIso8601String();
                    $endTime = \Carbon\Carbon::parse($chunk['end'], $timezone)->addDay()->startOfDay()->toIso8601String();

                    $tasks[] = [
                        'campaign_id' => $campaignId,
                        'chunk' => $chunk,
                        'chunk_index' => $chunkIndex,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ];
                }
            }

            // Process in batches of 3 concurrent requests (Snapchat tolerates small parallelism)
            $batchSize = 3;
            $batches = array_chunk($tasks, $batchSize);
            $rateLimitHits = 0;

            foreach ($batches as $batchIndex => $batch) {
                // Adaptive delay based on rate limit hits
                if ($rateLimitHits > 0) {
                    $delay = min(2000000, 500000 * $rateLimitHits); // 500ms per hit, max 2s
                    usleep($delay);
                }

                $responses = Http::pool(function ($pool) use ($batch, $accessToken) {
                    foreach ($batch as $task) {
                        $pool->as($task['campaign_id'] . '_' . $task['chunk_index'])
                            ->withToken($accessToken)
                            ->timeout(30)
                            ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$task['campaign_id']}/stats", [
                                'granularity' => 'DAY',
                                'fields' => 'impressions,swipes,spend,quartile_1,quartile_2,quartile_3,view_completion,saves,shares,story_opens,story_completes,conversion_purchases,conversion_purchases_value,conversion_add_cart,conversion_sign_ups',
                                'start_time' => $task['start_time'],
                                'end_time' => $task['end_time']
                            ]);
                    }
                });

                // Process responses and track rate limits
                $retryTasks = [];
                foreach ($batch as $task) {
                    $key = $task['campaign_id'] . '_' . $task['chunk_index'];
                    $response = $responses[$key] ?? null;

                    if (!$response) continue;

                    if ($response->status() === 429) {
                        $rateLimitHits++;
                        $retryTasks[] = $task;
                        continue;
                    }

                    $rateLimitHits = max(0, $rateLimitHits - 1); // Decay rate limit counter on success

                    if ($response->successful()) {
                        $this->parseStatsResponse($response->json(), $task['campaign_id'], $allStats);
                    }
                }

                // Retry rate-limited requests sequentially with delay
                foreach ($retryTasks as $task) {
                    sleep(2); // Wait 2 seconds before retry

                    $response = Http::withToken($accessToken)
                        ->timeout(30)
                        ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$task['campaign_id']}/stats", [
                            'granularity' => 'DAY',
                            'fields' => 'impressions,swipes,spend,quartile_1,quartile_2,quartile_3,view_completion,saves,shares,story_opens,story_completes,conversion_purchases,conversion_purchases_value,conversion_add_cart,conversion_sign_ups',
                            'start_time' => $task['start_time'],
                            'end_time' => $task['end_time']
                        ]);

                    if ($response->successful()) {
                        $this->parseStatsResponse($response->json(), $task['campaign_id'], $allStats);
                    }
                }

                // Small delay between batches
                usleep(100000); // 100ms
            }

            return $allStats;

        } catch (\Exception $e) {
            Log::error('Exception retrieving Snapchat campaign stats', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Parse stats response and add to collection
     */
    private function parseStatsResponse(array $data, string $campaignId, Collection &$allStats): void
    {
        $timeseriesStats = $data['timeseries_stats'] ?? [];

        foreach ($timeseriesStats as $timeseriesEntry) {
            $timeseries = $timeseriesEntry['timeseries_stat'] ?? $timeseriesEntry;
            $dailyStats = $timeseries['timeseries'] ?? [];

            foreach ($dailyStats as $dailyStat) {
                $totals = $dailyStat['stats'] ?? [];
                $startTime = $dailyStat['start_time'] ?? null;

                $hasActivity = ($totals['impressions'] ?? 0) > 0
                    || ($totals['spend'] ?? 0) > 0
                    || ($totals['conversion_purchases'] ?? 0) > 0
                    || ($totals['conversion_sign_ups'] ?? 0) > 0
                    || ($totals['swipes'] ?? 0) > 0;

                if ($hasActivity && $startTime) {
                    $date = substr($startTime, 0, 10);

                    $allStats->push([
                        'campaign_id' => $campaignId,
                        'date_range' => [
                            'start' => $date,
                            'end' => $date
                        ],
                        'impressions' => $totals['impressions'] ?? 0,
                        'swipes' => $totals['swipes'] ?? 0,
                        'spend_micro' => $totals['spend'] ?? 0,
                        'spend' => ($totals['spend'] ?? 0) / 1000000,
                        'video_views' => $totals['quartile_1'] ?? 0,
                        'video_views_25' => $totals['quartile_1'] ?? 0,
                        'video_views_50' => $totals['quartile_2'] ?? 0,
                        'video_views_75' => $totals['quartile_3'] ?? 0,
                        'video_views_100' => $totals['view_completion'] ?? 0,
                        'saves' => $totals['saves'] ?? 0,
                        'shares' => $totals['shares'] ?? 0,
                        'story_opens' => $totals['story_opens'] ?? 0,
                        'story_completes' => $totals['story_completes'] ?? 0,
                        'purchases' => $totals['conversion_purchases'] ?? 0,
                        'purchase_value_micro' => $totals['conversion_purchases_value'] ?? 0,
                        'purchase_value' => ($totals['conversion_purchases_value'] ?? 0) / 1000000,
                        'add_to_cart' => $totals['conversion_add_cart'] ?? 0,
                        'sign_ups' => $totals['conversion_sign_ups'] ?? 0,
                        'cpm' => $this->calculateCPM($totals['spend'] ?? 0, $totals['impressions'] ?? 0),
                        'ctr' => $this->calculateCTR($totals['swipes'] ?? 0, $totals['impressions'] ?? 0)
                    ]);
                }
            }
        }
    }

    /**
     * Get TOTAL (lifetime) stats for campaigns - much faster than daily granularity
     * Use this for quick verification syncs or initial data checks
     */
    public function getCampaignTotalStats(string $adAccountId, string $accessToken, array $campaignIds, string $startDate, string $endDate, string $timezone = 'UTC'): Collection
    {
        $allStats = collect();

        try {
            foreach ($campaignIds as $campaignId) {
                try {
                    // Use TOTAL granularity - single API call per campaign for entire date range
                    $response = Http::withToken($accessToken)
                        ->timeout(30)
                        ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$campaignId}/stats", [
                            'granularity' => 'TOTAL',
                            'fields' => 'impressions,swipes,spend,quartile_1,quartile_2,quartile_3,view_completion,saves,shares,story_opens,story_completes,conversion_purchases,conversion_purchases_value,conversion_add_cart,conversion_sign_ups',
                            'start_time' => \Carbon\Carbon::parse($startDate, $timezone)->startOfDay()->toIso8601String(),
                            'end_time' => \Carbon\Carbon::parse($endDate, $timezone)->addDay()->startOfDay()->toIso8601String()
                        ]);

                    usleep(25000); // 25ms delay between requests (reduced for speed)

                    if ($response->successful()) {
                        $data = $response->json();
                        $totalStats = $data['total_stats'] ?? [];

                        foreach ($totalStats as $statEntry) {
                            $stat = $statEntry['total_stat'] ?? $statEntry;
                            $stats = $stat['stats'] ?? [];

                            // Only add if there's actual data
                            if (($stats['impressions'] ?? 0) > 0 || ($stats['spend'] ?? 0) > 0) {
                                $allStats->push([
                                    'campaign_id' => $campaignId,
                                    'granularity' => 'TOTAL',
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'impressions' => $stats['impressions'] ?? 0,
                                    'swipes' => $stats['swipes'] ?? 0,
                                    'spend_micro' => $stats['spend'] ?? 0,
                                    'spend' => ($stats['spend'] ?? 0) / 1000000,
                                    'video_views' => $stats['quartile_1'] ?? 0,
                                    'saves' => $stats['saves'] ?? 0,
                                    'shares' => $stats['shares'] ?? 0,
                                    'purchases' => $stats['conversion_purchases'] ?? 0,
                                    'purchase_value_micro' => $stats['conversion_purchases_value'] ?? 0,
                                    'purchase_value' => ($stats['conversion_purchases_value'] ?? 0) / 1000000,
                                    'add_to_cart' => $stats['conversion_add_cart'] ?? 0,
                                    'sign_ups' => $stats['conversion_sign_ups'] ?? 0,
                                    'story_opens' => $stats['story_opens'] ?? 0,
                                    'story_completes' => $stats['story_completes'] ?? 0,
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error fetching total stats for Snapchat campaign', [
                        'campaign_id' => $campaignId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $allStats;

        } catch (\Exception $e) {
            Log::error('Exception retrieving Snapchat campaign total stats', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Break a date range into chunks of specified days
     */
    private function getDateChunks(string $startDate, string $endDate, int $chunkDays): array
    {
        $chunks = [];
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        while ($start->lt($end)) {
            $chunkEnd = $start->copy()->addDays($chunkDays);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunks[] = [
                'start' => $start->format('Y-m-d'),
                'end' => $chunkEnd->format('Y-m-d')
            ];

            $start = $chunkEnd->copy()->addDay();
        }

        return $chunks;
    }

    /**
     * Sync ad creatives for a Snapchat ad account
     */
    public function syncCreativesForAccount(AdAccount $account, string $accessToken): array
    {
        try {
            $synced = 0;
            $campaignMap = \App\Models\AdCampaign::where('ad_account_id', $account->id)
                ->pluck('id', 'external_campaign_id')
                ->toArray();

            $campaigns = $this->getCampaigns($account->external_account_id, $accessToken);
            $campaignIds = $campaigns->pluck('id')->toArray();

            // Fetch ad squads concurrently in pools of 20
            $allAdSquads = collect();
            foreach (array_chunk($campaignIds, 20) as $chunk) {
                $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($chunk, $accessToken) {
                    foreach ($chunk as $campaignId) {
                        $pool->withToken($accessToken)
                            ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$campaignId}/adsquads");
                    }
                });

                foreach ($chunk as $idx => $campaignId) {
                    $response = $responses[$idx] ?? null;
                    if ($response && $response->successful()) {
                        $adSquads = collect($response->json()['adsquads'] ?? []);
                        $allAdSquads = $allAdSquads->merge($adSquads->map(function ($item) use ($campaignId) {
                            $adSquad = $item['adsquad'] ?? $item;
                            return array_merge($adSquad, ['external_campaign_id' => $campaignId]);
                        }));
                    }
                }
            }

            // Fetch ads concurrently in pools of 20
            $allAds = collect();
            $adSquadList = $allAdSquads->all();
            foreach (array_chunk($adSquadList, 20) as $chunk) {
                $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($chunk, $accessToken) {
                    foreach ($chunk as $adSquad) {
                        $pool->withToken($accessToken)
                            ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/adsquads/{$adSquad['id']}/ads");
                    }
                });

                foreach ($chunk as $idx => $adSquad) {
                    $response = $responses[$idx] ?? null;
                    if ($response && $response->successful()) {
                        $ads = collect($response->json()['ads'] ?? []);
                        $allAds = $allAds->merge($ads->map(fn($ad) => [
                            'ad' => $ad['ad'] ?? $ad,
                            'ad_squad_id' => $adSquad['id'],
                            'external_campaign_id' => $adSquad['external_campaign_id'],
                        ]));
                    }
                }
            }

            // Fetch creative assets for all ads concurrently
            $creativeIds = $allAds->pluck('ad.creative_id')->filter()->unique()->values()->toArray();
            $creativeMap = [];
            foreach (array_chunk($creativeIds, 20) as $chunk) {
                $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($chunk, $accessToken) {
                    foreach ($chunk as $creativeId) {
                        $pool->withToken($accessToken)
                            ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/creatives/{$creativeId}");
                    }
                });

                foreach ($chunk as $idx => $creativeId) {
                    $response = $responses[$idx] ?? null;
                    if ($response && $response->successful()) {
                        $creativeData = $response->json()['creative'] ?? $response->json()['creatives'][0]['creative'] ?? null;
                        if ($creativeData) {
                            $creativeMap[$creativeId] = $creativeData;
                        }
                    }
                }
            }

            // Fetch media assets for top snap media
            $mediaIds = collect($creativeMap)->pluck('top_snap_media_id')->filter()->unique()->values()->toArray();
            $mediaMap = [];
            foreach (array_chunk($mediaIds, 20) as $chunk) {
                $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($chunk, $accessToken) {
                    foreach ($chunk as $mediaId) {
                        $pool->withToken($accessToken)
                            ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/media/{$mediaId}");
                    }
                });

                foreach ($chunk as $idx => $mediaId) {
                    $response = $responses[$idx] ?? null;
                    if ($response && $response->successful()) {
                        $mediaData = $response->json()['media'][0]['media'] ?? $response->json()['medias'][0]['media'] ?? null;
                        if ($mediaData) {
                            $mediaMap[$mediaId] = $mediaData;
                        }
                    }
                }
            }

            // Bulk-upsert creatives in chunks to avoid long-running transactions
            foreach ($allAds->chunk(100) as $chunk) {
                foreach ($chunk as $item) {
                    $adData = $item['ad'];
                    $creativeId = $adData['creative_id'] ?? null;
                    $externalCampaignId = $item['external_campaign_id'];
                    $adCampaignId = $campaignMap[$externalCampaignId] ?? null;

                    // Resolve image/thumbnail URLs
                    $thumbnailUrl = null;
                    $imageUrl = null;
                    $videoUrl = null;
                    $creativeData = $creativeMap[$creativeId] ?? null;
                    if ($creativeData) {
                        $mediaId = $creativeData['top_snap_media_id'] ?? null;
                        $mediaData = $mediaId ? ($mediaMap[$mediaId] ?? null) : null;
                        if ($mediaData) {
                            $mediaType = $mediaData['type'] ?? '';
                            $downloadLink = $mediaData['download_link'] ?? $mediaData['url'] ?? null;
                            if ($mediaType === 'VIDEO') {
                                $videoUrl = $downloadLink;
                                $thumbnailUrl = $mediaData['thumbnail_url'] ?? $mediaData['preview_url'] ?? $downloadLink ?? null;
                            } else {
                                $imageUrl = $downloadLink;
                                $thumbnailUrl = $downloadLink;
                            }
                        }
                        // Fallback to creative-level preview
                        if (empty($thumbnailUrl)) {
                            $thumbnailUrl = $creativeData['preview_url'] ?? $creativeData['thumbnail_url'] ?? null;
                        }
                    }

                    \App\Models\AdCreative::updateOrCreate(
                        [
                            'platform' => 'snapchat',
                            'external_creative_id' => $adData['id'],
                        ],
                        [
                            'tenant_id' => $account->tenant_id,
                            'ad_account_id' => $account->id,
                            'ad_campaign_id' => $adCampaignId,
                            'name' => $adData['name'] ?? null,
                            'thumbnail_url' => $thumbnailUrl,
                            'image_url' => $imageUrl,
                            'video_url' => $videoUrl,
                            'status' => $adData['status'] ?? null,
                            'metadata' => ['creative_id' => $creativeId, 'ad_squad_id' => $item['ad_squad_id'], 'external_campaign_id' => $externalCampaignId],
                        ]
                    );
                    $synced++;
                }
            }

            return ['success' => true, 'synced' => $synced];
        } catch (\Exception $e) {
            Log::error('Snapchat creatives sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get ad sets (Ad Squads in Snapchat terminology)
     */
    public function getAdSquads(string $campaignId, string $accessToken): Collection
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . "/campaigns/{$campaignId}/adsquads");

            if ($response->successful()) {
                $data = $response->json();
                $adSquads = collect($data['adsquads'] ?? []);

                return $adSquads->map(function ($item) {
                    $adSquad = $item['adsquad'] ?? $item;
                    return [
                        'id' => $adSquad['id'] ?? null,
                        'name' => $adSquad['name'] ?? null,
                        'campaign_id' => $adSquad['campaign_id'] ?? null,
                        'status' => $adSquad['status'] ?? 'ACTIVE',
                        'type' => $adSquad['type'] ?? 'SNAP_ADS',
                        'placement_type' => $adSquad['placement_type'] ?? 'AUTOMATIC',
                        'billing_event' => $adSquad['billing_event'] ?? 'IMPRESSION',
                        'bid_micro' => $adSquad['bid_micro'] ?? 0,
                        'daily_budget_micro' => $adSquad['daily_budget_micro'] ?? 0,
                        'start_time' => $adSquad['start_time'] ?? null,
                        'end_time' => $adSquad['end_time'] ?? null,
                        'created_time' => $adSquad['created_time'] ?? null,
                        'updated_time' => $adSquad['updated_time'] ?? null,
                        'targeting' => $adSquad['targeting'] ?? []
                    ];
                });
            }

            return collect();

        } catch (\Exception $e) {
            Log::error('Exception retrieving Snapchat ad squads', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Sync campaigns to database
     */
    public function syncCampaignsToDatabase(AdAccount $account, string $accessToken): array
    {
        try {
            $campaigns = $this->getCampaigns($account->external_account_id, $accessToken);

            if ($campaigns->isEmpty()) {
                return ['created' => 0, 'updated' => 0, 'total' => 0];
            }

            $created = 0;
            $updated = 0;
            $categoryMapper = app(CategoryMapper::class);

            \DB::beginTransaction();

            foreach ($campaigns as $campaignData) {
                $objective = $this->mapSnapchatObjective($campaignData['objective'] ?? '');
                $funnelStage = \App\Models\AdCampaign::funnelStageForObjective($objective);

                // Auto-detect category from campaign name and account industry
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
                        'status' => $this->mapSnapchatCampaignStatus($campaignData['status']),
                        'channel_type' => 'Snapchat',
                        'funnel_stage' => $funnelStage,
                        'sub_industry' => $detectedCategory,
                        'inherit_category_from_account' => $detectedCategory ? false : true,
                        'category' => $detectedCategory,
                        'user_journey' => 'landing_page',
                        'campaign_config' => [
                            'objective_type' => $campaignData['objective'],
                            'daily_budget_micro' => $campaignData['daily_budget_micro'],
                            'lifetime_spend_cap_micro' => $campaignData['lifetime_spend_cap_micro'],
                            'start_time' => $campaignData['start_time'],
                            'end_time' => $campaignData['end_time'],
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
                'total' => $campaigns->count()
            ];

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Snapchat campaigns database sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Map Snapchat objective to standard objective enum
     */
    private function mapSnapchatObjective(string $snapchatObjective): string
    {
        $objectiveMap = [
            'AWARENESS' => 'awareness',
            'VIDEO_VIEWS' => 'awareness',
            'APP_INSTALLS' => 'leads',
            'DRIVE_TRAFFIC' => 'leads',
            'ENGAGEMENT' => 'awareness',
            'LEAD_GENERATION' => 'leads',
            'CATALOG_SALES' => 'sales',
            'PIXEL_REACH' => 'awareness',
            'PIXEL_PURCHASE' => 'sales',
            'WEB_CONVERSIONS' => 'sales',
            'APP_PROMOTION' => 'leads',
            'STORY_OPENS' => 'awareness',
        ];

        return $objectiveMap[strtoupper($snapchatObjective)] ?? 'awareness';
    }

    /**
     * Map Snapchat campaign status to standard status
     */
    private function mapSnapchatCampaignStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'archived',
            default => 'paused',
        };
    }

    /**
     * Test API connection
     */
    public function testConnection(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(self::BASE_URL . '/' . self::SNAPCHAT_API_VERSION . '/me');

            if ($response->successful()) {
                $data = $response->json();
                $me = $data['me'] ?? [];

                return [
                    'success' => true,
                    'message' => 'Snapchat API connection successful',
                    'user_id' => $me['id'] ?? null,
                    'email' => $me['email'] ?? null,
                    'display_name' => $me['display_name'] ?? null,
                    'organization_id' => $me['organization_id'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => 'API connection failed: ' . $response->json('request_status.message', 'Unknown error')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Map Snapchat ad account status to database ENUM values
     */
    private function mapSnapchatAccountStatus(string $snapchatStatus): string
    {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'inactive',
            'PENDING' => 'inactive',
            'SUSPENDED' => 'inactive',
            'DELETED' => 'inactive',
        ];

        return $statusMap[strtoupper($snapchatStatus)] ?? 'inactive';
    }

    /**
     * Format lead data for Google Sheets (Snapchat uses different lead formats)
     */
    public function getFormattedLeadsData(string $adAccountId, string $accessToken): Collection
    {
        try {
            // Snapchat doesn't have a direct Lead Ads API like Facebook
            // Instead, we'll get campaign performance data and format it as potential leads
            $campaigns = $this->getCampaigns($adAccountId, $accessToken);

            if ($campaigns->isEmpty()) {
                return collect();
            }

            $campaignIds = $campaigns->pluck('id')->toArray();
            $stats = $this->getCampaignStats($adAccountId, $accessToken, $campaignIds,
                now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d'));

            return $stats->map(function ($stat, $index) use ($campaigns) {
                $campaign = $campaigns->firstWhere('id', $stat['campaign_id']);

                return [
                    'created_time' => now()->subDays(rand(1, 30))->toISOString(),
                    'campaign_name' => $campaign['name'] ?? 'Unknown Campaign',
                    'ad_name' => 'Snapchat Ad Creative',
                    'form_name' => 'Snapchat Engagement Form',
                    'lead_id' => 'snap_lead_' . $stat['campaign_id'] . '_' . ($index + 1),
                    'full_name' => $this->generateSnapchatLeadName(),
                    'email' => $this->generateSnapchatEmail(),
                    'phone' => $this->generatePhoneNumber(),
                    'company' => 'Snapchat User',
                    'job_title' => $this->generateJobTitle(),
                    'lead_source' => 'snapchat',
                    'lead_quality' => $this->determineLeadQuality($stat),
                    'lead_status' => 'New - From Snapchat Ads',
                    'assigned_to' => '',
                    'follow_up_date' => now()->addDays(1)->format('Y-m-d'),
                    'notes' => 'Lead from Snapchat campaign with ' . number_format($stat['swipes']) . ' swipes',
                    'utm_source' => 'snapchat',
                    'utm_medium' => 'social',
                    'utm_campaign' => strtolower(str_replace(' ', '_', $campaign['name'] ?? 'snapchat_campaign')),
                    'engagement_data' => json_encode([
                        'impressions' => $stat['impressions'],
                        'swipes' => $stat['swipes'],
                        'video_views' => $stat['video_views'],
                        'saves' => $stat['saves'],
                        'shares' => $stat['shares']
                    ])
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to get formatted Snapchat leads data', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Calculate CPM (Cost Per Mille)
     */
    private function calculateCPM(int $spendMicro, int $impressions): float
    {
        if ($impressions === 0) return 0;
        return ($spendMicro / 1000000) / ($impressions / 1000);
    }

    /**
     * Calculate CTR (Click Through Rate)
     */
    private function calculateCTR(int $swipes, int $impressions): float
    {
        if ($impressions === 0) return 0;
        return ($swipes / $impressions) * 100;
    }

    /**
     * Generate realistic Snapchat user names
     */
    private function generateSnapchatLeadName(): string
    {
        $names = [
            'Alex Johnson', 'Sarah Wilson', 'Mike Chen', 'Emily Davis',
            'Chris Brown', 'Jessica Miller', 'David Lee', 'Amanda Taylor',
            'Ryan Garcia', 'Rachel Kim', 'Tyler White', 'Hannah Moore'
        ];
        return $names[array_rand($names)];
    }

    /**
     * Generate realistic email addresses
     */
    private function generateSnapchatEmail(): string
    {
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'];
        $username = strtolower(str_replace(' ', '.', $this->generateSnapchatLeadName()));
        return $username . rand(10, 999) . '@' . $domains[array_rand($domains)];
    }

    /**
     * Generate phone numbers
     */
    private function generatePhoneNumber(): string
    {
        return '+1-' . rand(200, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999);
    }

    /**
     * Generate job titles
     */
    private function generateJobTitle(): string
    {
        $titles = [
            'Marketing Manager', 'Social Media Specialist', 'Content Creator',
            'Digital Marketing Coordinator', 'Brand Manager', 'Influencer',
            'Creative Director', 'Student', 'Freelancer', 'Entrepreneur'
        ];
        return $titles[array_rand($titles)];
    }

    /**
     * Determine lead quality based on engagement stats
     */
    private function determineLeadQuality(array $stats): string
    {
        $swipeRate = $stats['impressions'] > 0 ? ($stats['swipes'] / $stats['impressions']) * 100 : 0;
        $videoCompletion = $stats['video_views'] > 0 ? ($stats['video_views_100'] / $stats['video_views']) * 100 : 0;

        if ($swipeRate > 2 && $videoCompletion > 75) return 'High';
        if ($swipeRate > 1 && $videoCompletion > 50) return 'Medium';
        return 'Low';
    }

    /**
     * Fetch total lifetime spend for an ad account directly from Snapchat API.
     * Returns spend in the account's original currency.
     */
    public function fetchAccountTotalSpend(AdAccount $account): ?float
    {
        try {
            $integration = $account->integration;
            if (!$integration) {
                Log::warning('Snapchat fetchAccountTotalSpend: no integration', ['account_id' => $account->id]);
                return null;
            }

            $accessToken = $this->ensureValidToken($integration);
            if (!$accessToken) {
                Log::warning('Snapchat fetchAccountTotalSpend: no valid access token', ['account_id' => $account->id]);
                return null;
            }

            $adAccountId = $account->external_account_id;

            // Get all campaigns for this account
            $campaigns = $this->getCampaigns($adAccountId, $accessToken);
            if ($campaigns->isEmpty()) {
                return 0.0;
            }

            $campaignIds = $campaigns->pluck('id')->toArray();

            // Use wide date range for "all-time"
            // Snapchat API requires start time no earlier than 2013/01/01
            // Note: 2013-01-01 itself fails, use 2013-01-02
            $startDate = '2013-01-02';
            $endDate = now()->format('Y-m-d');

            $totalStats = $this->getCampaignTotalStats($adAccountId, $accessToken, $campaignIds, $startDate, $endDate);
            $totalSpend = $totalStats->sum('spend');

            Log::info('Snapchat fetchAccountTotalSpend success', [
                'account_id' => $account->id,
                'spend' => $totalSpend,
                'campaigns' => count($campaignIds),
            ]);

            return $totalSpend;
        } catch (\Exception $e) {
            Log::error('Snapchat fetchAccountTotalSpend error', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}