<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GA4AnalyticsService
{
    protected GoogleAdsService $googleAdsService;

    protected const DATA_API_BASE = 'https://analyticsdata.googleapis.com/v1beta';
    protected const ADMIN_API_BASE = 'https://analyticsadmin.googleapis.com/v1beta';

    public function __construct(GoogleAdsService $googleAdsService)
    {
        $this->googleAdsService = $googleAdsService;
    }

    /**
     * Get a fresh access token from the integration's refresh token.
     */
    protected function getAccessToken(Integration $integration): ?string
    {
        $config = $integration->app_config ?? [];
        $refreshToken = $config['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $config['access_token'] ?? null;
        }

        $cacheKey = 'google_token:' . $integration->id;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $tokenData = $this->googleAdsService->refreshAccessToken($refreshToken);

        if ($tokenData && isset($tokenData['access_token'])) {
            Cache::put($cacheKey, $tokenData['access_token'], 300);
            return $tokenData['access_token'];
        }

        return $config['access_token'] ?? null;
    }

    /**
     * List GA4 properties accessible to this integration.
     */
    public function listProperties(Integration $integration): array
    {
        try {
            $accessToken = $this->getAccessToken($integration);
            if (!$accessToken) {
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get(self::ADMIN_API_BASE . '/accountSummaries');

            if (!$response->successful()) {
                Log::warning('GA4 listProperties failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $properties = [];

            foreach ($data['accountSummaries'] ?? [] as $account) {
                $accountName = $account['displayName'] ?? 'Unknown Account';

                foreach ($account['propertySummaries'] ?? [] as $property) {
                    $propertyId = str_replace('properties/', '', $property['property'] ?? '');
                    $properties[] = [
                        'property_id' => $propertyId,
                        'display_name' => $property['displayName'] ?? 'Unknown Property',
                        'account_name' => $accountName,
                    ];
                }
            }

            return $properties;
        } catch (\Exception $e) {
            Log::error('GA4 listProperties error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get a full GA4 report: summary metrics, timeseries, and top pages.
     */
    public function getReport(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $cacheKey = "ga4:report:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) {
            return ['summary' => [], 'timeseries' => [], 'top_pages' => [], 'traffic_sources' => [], 'devices' => [], 'geo' => []];
        }

        $summary = $this->fetchSummary($accessToken, $propertyId, $startDate, $endDate);
        $timeseries = $this->fetchTimeSeries($accessToken, $propertyId, $startDate, $endDate);
        $topPages = $this->fetchTopPages($accessToken, $propertyId, $startDate, $endDate);
        $trafficSources = $this->fetchTrafficSources($accessToken, $propertyId, $startDate, $endDate);
        $devices = $this->fetchDevices($accessToken, $propertyId, $startDate, $endDate);
        $geo = $this->fetchGeo($accessToken, $propertyId, $startDate, $endDate);
        $browsers = $this->fetchBrowserData($accessToken, $propertyId, $startDate, $endDate);

        $result = [
            'summary' => $summary,
            'timeseries' => $timeseries,
            'top_pages' => $topPages,
            'traffic_sources' => $trafficSources,
            'devices' => $devices,
            'geo' => $geo,
            'browsers' => $browsers,
        ];

        // Conditionally fetch ecommerce data
        if (($summary['ecommerce_purchases'] ?? 0) > 0 || ($summary['total_revenue'] ?? 0) > 0) {
            $result['top_ecommerce_pages'] = $this->fetchTopEcommercePages($accessToken, $propertyId, $startDate, $endDate);
            $result['top_products'] = $this->fetchTopProducts($accessToken, $propertyId, $startDate, $endDate, 5, false);
            $result['least_products'] = $this->fetchTopProducts($accessToken, $propertyId, $startDate, $endDate, 5, true);
        }

        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    /**
     * Get summary-only report (no pages/traffic/devices) — used for period comparison.
     */
    public function getSummaryOnly(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $cacheKey = "ga4:summary:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) {
            return [];
        }

        $result = $this->fetchSummary($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    protected function fetchSummary(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                // GA4 Data API limits to 10 metrics per request
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'bounceRate'],
                    ['name' => 'keyEvents'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'averageSessionDuration'],
                    ['name' => 'newUsers'],
                    ['name' => 'activeUsers'],
                    ['name' => 'ecommercePurchases'],
                    ['name' => 'totalRevenue'],
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('GA4 fetchSummary failed', ['status' => $response->status(), 'body' => $response->body()]);
                return [];
            }

            $data = $response->json();
            $row = $data['rows'][0]['metricValues'] ?? [];

            $bounceRate = round((float) ($row[2]['value'] ?? 0) * 100, 1);

            return [
                'sessions' => (int) ($row[0]['value'] ?? 0),
                'total_users' => (int) ($row[1]['value'] ?? 0),
                'bounce_rate' => $bounceRate,
                'conversions' => (int) ($row[3]['value'] ?? 0),
                'page_views' => (int) ($row[4]['value'] ?? 0),
                'avg_session_duration' => round((float) ($row[5]['value'] ?? 0), 1),
                'new_users' => (int) ($row[6]['value'] ?? 0),
                'active_users' => (int) ($row[7]['value'] ?? 0),
                'ecommerce_purchases' => (int) ($row[8]['value'] ?? 0),
                'total_revenue' => round((float) ($row[9]['value'] ?? 0), 2),
                'engagement_rate' => round(100 - $bounceRate, 1),
            ];
        } catch (\Exception $e) {
            Log::error('GA4 fetchSummary error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchTimeSeries(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'date']],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'bounceRate'],
                    ['name' => 'activeUsers'],
                ],
                'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $rows = [];

            foreach ($data['rows'] ?? [] as $row) {
                $dateStr = $row['dimensionValues'][0]['value'] ?? '';
                // GA4 returns date as YYYYMMDD
                $formatted = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);

                $rows[] = [
                    'date' => $formatted,
                    'sessions' => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'users' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'bounce_rate' => round((float) ($row['metricValues'][2]['value'] ?? 0) * 100, 1),
                    'active_users' => (int) ($row['metricValues'][3]['value'] ?? 0),
                ];
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('GA4 fetchTimeSeries error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getTopPages(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:top_pages:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchTopPages($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getTrafficSources(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:traffic_sources:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchTrafficSources($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getDevices(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:devices:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchDevices($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getGeo(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:geo:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchGeo($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getBrowsers(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:browsers:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchBrowserData($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getTopEcommercePages(Integration $integration, string $propertyId, string $startDate, string $endDate): array
    {
        $cacheKey = "ga4:ecom_pages:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchTopEcommercePages($accessToken, $propertyId, $startDate, $endDate);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    public function getTopProducts(Integration $integration, string $propertyId, string $startDate, string $endDate, int $limit = 5, bool $ascending = false): array
    {
        $cacheKey = "ga4:products:{$integration->id}:{$propertyId}:{$startDate}:{$endDate}:{$limit}:" . ($ascending ? '1' : '0');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];
        $result = $this->fetchTopProducts($accessToken, $propertyId, $startDate, $endDate, $limit, $ascending);
        Cache::put($cacheKey, $result, 900);
        return $result;
    }

    protected function fetchTopPages(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'pagePath']],
                'metrics' => [
                    ['name' => 'screenPageViews'],
                    ['name' => 'totalUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'totalRevenue'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $pages = [];

            foreach ($data['rows'] ?? [] as $row) {
                $pages[] = [
                    'page_path' => $row['dimensionValues'][0]['value'] ?? '',
                    'page_views' => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'users' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'sessions' => (int) ($row['metricValues'][2]['value'] ?? 0),
                    'revenue' => round((float) ($row['metricValues'][3]['value'] ?? 0), 2),
                ];
            }

            return $pages;
        } catch (\Exception $e) {
            Log::error('GA4 fetchTopPages error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchTrafficSources(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalRevenue'],
                    ['name' => 'totalUsers'],
                    ['name' => 'engagementRate'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                'limit' => 50,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $sources = [];
            $totalSessions = 0;
            $totalRevenue = 0;

            // First pass: sum totals
            foreach ($data['rows'] ?? [] as $row) {
                $totalSessions += (int) ($row['metricValues'][0]['value'] ?? 0);
                $totalRevenue += (float) ($row['metricValues'][1]['value'] ?? 0);
            }

            foreach ($data['rows'] ?? [] as $row) {
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                $revenue = round((float) ($row['metricValues'][1]['value'] ?? 0), 2);
                $sources[] = [
                    'channel' => $row['dimensionValues'][0]['value'] ?? 'Unknown',
                    'sessions' => $sessions,
                    'share' => $totalSessions > 0 ? round(($sessions / $totalSessions) * 100, 1) : 0,
                    'revenue' => $revenue,
                    'users' => (int) ($row['metricValues'][2]['value'] ?? 0),
                    'engagement_rate' => round((float) ($row['metricValues'][3]['value'] ?? 0) * 100, 1),
                    'rev_share' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0,
                    'rev_per_session' => $sessions > 0 ? round($revenue / $sessions, 2) : 0,
                ];
            }

            return $sources;
        } catch (\Exception $e) {
            Log::error('GA4 fetchTrafficSources error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchDevices(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'deviceCategory']],
                'metrics' => [
                    ['name' => 'totalUsers'],
                    ['name' => 'sessions'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]],
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $devices = [];
            $totalUsers = 0;

            foreach ($data['rows'] ?? [] as $row) {
                $totalUsers += (int) ($row['metricValues'][0]['value'] ?? 0);
            }

            foreach ($data['rows'] ?? [] as $row) {
                $users = (int) ($row['metricValues'][0]['value'] ?? 0);
                $devices[] = [
                    'device' => $row['dimensionValues'][0]['value'] ?? 'Unknown',
                    'users' => $users,
                    'sessions' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'share' => $totalUsers > 0 ? round(($users / $totalUsers) * 100, 1) : 0,
                ];
            }

            return $devices;
        } catch (\Exception $e) {
            Log::error('GA4 fetchDevices error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchTopEcommercePages(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'itemName']],
                'metrics' => [
                    ['name' => 'itemsViewed'],
                    ['name' => 'itemsPurchased'],
                    ['name' => 'itemRevenue'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'itemRevenue'], 'desc' => true]],
                'limit' => 20,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $products = [];

            foreach ($data['rows'] ?? [] as $row) {
                $name = $row['dimensionValues'][0]['value'] ?? '';
                if ($name === '' || $name === '(not set)' || ctype_digit($name)) {
                    continue;
                }

                $products[] = [
                    'name' => $name,
                    'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'purchases' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'revenue' => round((float) ($row['metricValues'][2]['value'] ?? 0), 2),
                ];
            }

            return array_slice($products, 0, 5);
        } catch (\Exception $e) {
            Log::error('GA4 fetchTopEcommercePages error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchTopProducts(string $accessToken, string $propertyId, string $startDate, string $endDate, int $limit = 5, bool $ascending = false): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'itemName']],
                'metrics' => [
                    ['name' => 'itemsViewed'],
                    ['name' => 'totalUsers'],
                    ['name' => 'engagementRate'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'itemsViewed'], 'desc' => !$ascending]],
                'limit' => max($limit * 4, 20),
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $products = [];

            foreach ($data['rows'] ?? [] as $row) {
                $name = $row['dimensionValues'][0]['value'] ?? '';
                if ($name === '' || $name === '(not set)' || ctype_digit($name)) {
                    continue;
                }

                $products[] = [
                    'name' => $name,
                    'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'users' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'engagement_rate' => round((float) ($row['metricValues'][2]['value'] ?? 0) * 100, 1),
                ];
            }

            return array_slice($products, 0, $limit);
        } catch (\Exception $e) {
            Log::error('GA4 fetchTopProducts error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchBrowserData(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'browser']],
                'metrics' => [
                    ['name' => 'totalUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'totalRevenue'],
                    ['name' => 'engagementRate'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]],
                'limit' => 10,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $browsers = [];
            $totalUsers = 0;
            $totalRevenue = 0;

            foreach ($data['rows'] ?? [] as $row) {
                $totalUsers += (int) ($row['metricValues'][0]['value'] ?? 0);
                $totalRevenue += (float) ($row['metricValues'][2]['value'] ?? 0);
            }

            foreach ($data['rows'] ?? [] as $row) {
                $users = (int) ($row['metricValues'][0]['value'] ?? 0);
                $revenue = round((float) ($row['metricValues'][2]['value'] ?? 0), 2);
                $browsers[] = [
                    'browser' => $row['dimensionValues'][0]['value'] ?? 'Unknown',
                    'users' => $users,
                    'sessions' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'share' => $totalUsers > 0 ? round(($users / $totalUsers) * 100, 1) : 0,
                    'revenue' => $revenue,
                    'rev_share' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0,
                    'engagement_rate' => round((float) ($row['metricValues'][3]['value'] ?? 0) * 100, 1),
                ];
            }

            return $browsers;
        } catch (\Exception $e) {
            Log::error('GA4 fetchBrowserData error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchGeo(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'country']],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'engagementRate'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                'limit' => 10,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $geo = [];
            $totalSessions = 0;

            foreach ($data['rows'] ?? [] as $row) {
                $totalSessions += (int) ($row['metricValues'][0]['value'] ?? 0);
            }

            foreach ($data['rows'] ?? [] as $row) {
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                $geo[] = [
                    'country' => $row['dimensionValues'][0]['value'] ?? 'Unknown',
                    'sessions' => $sessions,
                    'users' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'share' => $totalSessions > 0 ? round(($sessions / $totalSessions) * 100, 1) : 0,
                    'engagement_rate' => round((float) ($row['metricValues'][2]['value'] ?? 0) * 100, 1),
                ];
            }

            return $geo;
        } catch (\Exception $e) {
            Log::error('GA4 fetchGeo error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── AI Visibility Live Sync ──────────────────────────────────────────────

    /**
     * Get AI-channel (AI Direct) sessions + total sessions grouped by YYYY-MM.
     * Returns [month => ['ai_sessions', 'ai_key_events', 'ai_revenue', 'total_sessions']]
     */
    public function getAiTrafficByMonth(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];

        $aiRows    = $this->fetchAiSessionsByMonth($accessToken, $propertyId, $startDate, $endDate);
        $totalRows = $this->fetchTotalSessionsByMonth($accessToken, $propertyId, $startDate, $endDate);

        $result = [];
        foreach ($totalRows as $month => $total) {
            $result[$month] = ['total_sessions' => $total['sessions']];
        }
        foreach ($aiRows as $month => $ai) {
            $result[$month] = array_merge($result[$month] ?? ['total_sessions' => 0], [
                'ai_sessions'   => $ai['sessions'],
                'ai_key_events' => $ai['key_events'],
                'ai_revenue'    => $ai['revenue'],
            ]);
        }
        return $result;
    }

    /**
     * Get AI-referred sessions broken down by source (platform) for the snapshot.
     * Returns [['platform' => string, 'sessions' => int], ...]
     */
    public function getAiPlatformBreakdown(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];

        try {
            $response = Http::withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                    'dateRanges'      => [['startDate' => $startDate, 'endDate' => $endDate]],
                    'dimensions'      => [['name' => 'sessionSource']],
                    'metrics'         => [['name' => 'sessions']],
                    'dimensionFilter' => [
                        'filter' => [
                            'fieldName'   => 'sessionDefaultChannelGroup',
                            'stringFilter' => ['matchType' => 'EXACT', 'value' => 'AI Direct'],
                        ],
                    ],
                    'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                    'limit' => 20,
                ]);

            if (!$response->successful()) return [];

            $platforms = [];
            foreach ($response->json()['rows'] ?? [] as $row) {
                $source   = $row['dimensionValues'][0]['value'] ?? '';
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                if ($source && $sessions > 0) {
                    $platforms[] = ['platform' => $source, 'sessions' => $sessions];
                }
            }
            return $platforms;
        } catch (\Exception $e) {
            Log::error('GA4 getAiPlatformBreakdown error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get top pages by AI-referred sessions for the snapshot.
     * Returns [['page' => string, 'sessions' => int], ...]
     */
    public function getAiTopPages(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];

        try {
            $response = Http::withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                    'dateRanges'      => [['startDate' => $startDate, 'endDate' => $endDate]],
                    'dimensions'      => [['name' => 'pagePath']],
                    'metrics'         => [['name' => 'sessions']],
                    'dimensionFilter' => [
                        'filter' => [
                            'fieldName'   => 'sessionDefaultChannelGroup',
                            'stringFilter' => ['matchType' => 'EXACT', 'value' => 'AI Direct'],
                        ],
                    ],
                    'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                    'limit' => 20,
                ]);

            if (!$response->successful()) return [];

            $pages = [];
            foreach ($response->json()['rows'] ?? [] as $row) {
                $page     = $row['dimensionValues'][0]['value'] ?? '';
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                if ($page && $sessions > 0) {
                    $pages[] = ['page' => $page, 'sessions' => $sessions];
                }
            }
            return $pages;
        } catch (\Exception $e) {
            Log::error('GA4 getAiTopPages error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get top countries by AI-referred sessions for the snapshot.
     * Returns [['country' => string, 'sessions' => int], ...]
     */
    public function getAiTopCountries(
        Integration $integration,
        string $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $accessToken = $this->getAccessToken($integration);
        if (!$accessToken) return [];

        try {
            $response = Http::withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                    'dateRanges'      => [['startDate' => $startDate, 'endDate' => $endDate]],
                    'dimensions'      => [['name' => 'country']],
                    'metrics'         => [['name' => 'sessions']],
                    'dimensionFilter' => [
                        'filter' => [
                            'fieldName'   => 'sessionDefaultChannelGroup',
                            'stringFilter' => ['matchType' => 'EXACT', 'value' => 'AI Direct'],
                        ],
                    ],
                    'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                    'limit' => 20,
                ]);

            if (!$response->successful()) return [];

            $countries = [];
            foreach ($response->json()['rows'] ?? [] as $row) {
                $country  = $row['dimensionValues'][0]['value'] ?? '';
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                if ($country && $sessions > 0) {
                    $countries[] = ['country' => $country, 'sessions' => $sessions];
                }
            }
            return $countries;
        } catch (\Exception $e) {
            Log::error('GA4 getAiTopCountries error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchAiSessionsByMonth(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                    'dateRanges'      => [['startDate' => $startDate, 'endDate' => $endDate]],
                    'dimensions'      => [['name' => 'yearMonth']],
                    'metrics'         => [
                        ['name' => 'sessions'],
                        ['name' => 'keyEvents'],
                        ['name' => 'totalRevenue'],
                    ],
                    'dimensionFilter' => [
                        'filter' => [
                            'fieldName'   => 'sessionDefaultChannelGroup',
                            'stringFilter' => ['matchType' => 'EXACT', 'value' => 'AI Direct'],
                        ],
                    ],
                ]);

            if (!$response->successful()) return [];

            $result = [];
            foreach ($response->json()['rows'] ?? [] as $row) {
                $ym    = $row['dimensionValues'][0]['value'] ?? '';
                $month = substr($ym, 0, 4) . '-' . substr($ym, 4, 2);
                $result[$month] = [
                    'sessions'   => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'key_events' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'revenue'    => (float) ($row['metricValues'][2]['value'] ?? 0),
                ];
            }
            return $result;
        } catch (\Exception $e) {
            Log::error('GA4 fetchAiSessionsByMonth error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchTotalSessionsByMonth(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                    'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                    'dimensions' => [['name' => 'yearMonth']],
                    'metrics'    => [['name' => 'sessions']],
                ]);

            if (!$response->successful()) return [];

            $result = [];
            foreach ($response->json()['rows'] ?? [] as $row) {
                $ym    = $row['dimensionValues'][0]['value'] ?? '';
                $month = substr($ym, 0, 4) . '-' . substr($ym, 4, 2);
                $result[$month] = ['sessions' => (int) ($row['metricValues'][0]['value'] ?? 0)];
            }
            return $result;
        } catch (\Exception $e) {
            Log::error('GA4 fetchTotalSessionsByMonth error', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
