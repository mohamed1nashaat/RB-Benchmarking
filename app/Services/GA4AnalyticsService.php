<?php

namespace App\Services;

use App\Models\Integration;
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

        $tokenData = $this->googleAdsService->refreshAccessToken($refreshToken);

        if ($tokenData && isset($tokenData['access_token'])) {
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

        return [
            'summary' => $summary,
            'timeseries' => $timeseries,
            'top_pages' => $topPages,
            'traffic_sources' => $trafficSources,
            'devices' => $devices,
            'geo' => $geo,
        ];
    }

    protected function fetchSummary(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->post(self::DATA_API_BASE . "/properties/{$propertyId}:runReport", [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'bounceRate'],
                    ['name' => 'conversions'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'averageSessionDuration'],
                    ['name' => 'newUsers'],
                    ['name' => 'activeUsers'],
                    ['name' => 'ecommercePurchases'],
                    ['name' => 'totalRevenue'],
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('GA4 fetchSummary failed', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            $row = $data['rows'][0]['metricValues'] ?? [];

            return [
                'sessions' => (int) ($row[0]['value'] ?? 0),
                'total_users' => (int) ($row[1]['value'] ?? 0),
                'bounce_rate' => round((float) ($row[2]['value'] ?? 0) * 100, 1),
                'conversions' => (int) ($row[3]['value'] ?? 0),
                'page_views' => (int) ($row[4]['value'] ?? 0),
                'avg_session_duration' => round((float) ($row[5]['value'] ?? 0), 1),
                'new_users' => (int) ($row[6]['value'] ?? 0),
                'active_users' => (int) ($row[7]['value'] ?? 0),
                'ecommerce_purchases' => (int) ($row[8]['value'] ?? 0),
                'total_revenue' => round((float) ($row[9]['value'] ?? 0), 2),
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
                ],
                'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit' => 10,
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
                ],
                'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                'limit' => 15,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $sources = [];
            $totalSessions = 0;

            // First pass: sum total sessions
            foreach ($data['rows'] ?? [] as $row) {
                $totalSessions += (int) ($row['metricValues'][0]['value'] ?? 0);
            }

            foreach ($data['rows'] ?? [] as $row) {
                $sessions = (int) ($row['metricValues'][0]['value'] ?? 0);
                $sources[] = [
                    'channel' => $row['dimensionValues'][0]['value'] ?? 'Unknown',
                    'sessions' => $sessions,
                    'share' => $totalSessions > 0 ? round(($sessions / $totalSessions) * 100, 1) : 0,
                    'revenue' => round((float) ($row['metricValues'][1]['value'] ?? 0), 2),
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
}
