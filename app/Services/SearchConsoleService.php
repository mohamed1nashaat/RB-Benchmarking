<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Log;

class SearchConsoleService
{
    protected GoogleAdsService $googleAdsService;

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
     * Build a Google_Client with the integration's credentials.
     */
    protected function buildClient(Integration $integration): \Google\Client
    {
        $accessToken = $this->getAccessToken($integration);

        $client = new \Google\Client();
        $client->setAccessToken($accessToken);

        return $client;
    }

    /**
     * List verified Search Console sites.
     */
    public function listSites(Integration $integration): array
    {
        try {
            $client = $this->buildClient($integration);
            $service = new \Google\Service\SearchConsole($client);

            $siteList = $service->sites->listSites();
            $sites = [];

            foreach ($siteList->getSiteEntry() ?? [] as $site) {
                $sites[] = [
                    'site_url' => $site->getSiteUrl(),
                    'permission_level' => $site->getPermissionLevel(),
                ];
            }

            return $sites;
        } catch (\Exception $e) {
            Log::error('Search Console listSites failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            if ($e->getCode() === 403) {
                throw $e; // Re-throw 403 so the controller can detect scope issues
            }

            return [];
        }
    }

    /**
     * Fetch search analytics data (queries, pages, or dates dimension).
     */
    public function getSearchAnalytics(
        Integration $integration,
        string $siteUrl,
        string $startDate,
        string $endDate,
        string $dimension = 'query',
        int $rowLimit = 25
    ): array {
        try {
            $client = $this->buildClient($integration);
            $service = new \Google\Service\SearchConsole($client);

            $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions([$dimension]);
            $request->setRowLimit($rowLimit);

            $response = $service->searchanalytics->query($siteUrl, $request);

            $rows = [];
            foreach ($response->getRows() ?? [] as $row) {
                $keys = $row->getKeys();
                $rows[] = [
                    'key' => $keys[0] ?? '',
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => round($row->getCtr() * 100, 2),
                    'position' => round($row->getPosition(), 1),
                ];
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('Search Console getSearchAnalytics failed', [
                'site_url' => $siteUrl,
                'dimension' => $dimension,
                'error' => $e->getMessage(),
            ]);

            if ($e->getCode() === 403) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * Fetch time series data (date dimension) for charts.
     */
    public function getTimeSeries(
        Integration $integration,
        string $siteUrl,
        string $startDate,
        string $endDate
    ): array {
        try {
            $client = $this->buildClient($integration);
            $service = new \Google\Service\SearchConsole($client);

            $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions(['date']);
            $request->setRowLimit(500);

            $response = $service->searchanalytics->query($siteUrl, $request);

            $rows = [];
            foreach ($response->getRows() ?? [] as $row) {
                $keys = $row->getKeys();
                $rows[] = [
                    'date' => $keys[0] ?? '',
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => round($row->getCtr() * 100, 2),
                    'position' => round($row->getPosition(), 1),
                ];
            }

            // Sort by date
            usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

            return $rows;
        } catch (\Exception $e) {
            Log::error('Search Console getTimeSeries failed', [
                'site_url' => $siteUrl,
                'error' => $e->getMessage(),
            ]);

            if ($e->getCode() === 403) {
                throw $e;
            }

            return [];
        }
    }
}
