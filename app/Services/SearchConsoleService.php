<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = 'sc:analytics:' . $integration->id . ':' . md5("{$siteUrl}:{$startDate}:{$endDate}:{$dimension}:{$rowLimit}");
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

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

            Cache::put($cacheKey, $rows, 900);
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
     * Get sitemaps coverage summary (submitted, indexed, errors, warnings).
     */
    public function getSitemapsCoverage(Integration $integration, string $siteUrl): array
    {
        $cacheKey = 'sc:sitemaps:' . $integration->id . ':' . md5($siteUrl);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $client = $this->buildClient($integration);
            $service = new \Google\Service\SearchConsole($client);
            $sitemaps = $service->sitemaps->listSitemaps($siteUrl);

            $totalSubmitted = 0;
            $totalIndexed = 0;
            $totalWarnings = 0;
            $totalErrors = 0;
            $sitemapDetails = [];

            foreach ($sitemaps->getSitemap() ?? [] as $sitemap) {
                $warnings = (int) $sitemap->getWarnings();
                $errors = (int) $sitemap->getErrors();
                $submitted = 0;
                $indexed = 0;
                foreach ($sitemap->getContents() ?? [] as $content) {
                    $submitted += (int) $content->getSubmitted();
                    $indexed += (int) $content->getIndexed();
                }
                $totalSubmitted += $submitted;
                $totalIndexed += $indexed;
                $totalWarnings += $warnings;
                $totalErrors += $errors;
                $sitemapDetails[] = [
                    'path' => $sitemap->getPath(),
                    'submitted' => $submitted,
                    'indexed' => $indexed,
                    'warnings' => $warnings,
                    'errors' => $errors,
                    'last_submitted' => $sitemap->getLastSubmitted(),
                ];
            }

            $result = [
                'total_submitted' => $totalSubmitted,
                'total_indexed' => $totalIndexed,
                'total_warnings' => $totalWarnings,
                'total_errors' => $totalErrors,
                'index_rate' => $totalSubmitted > 0 ? round(($totalIndexed / $totalSubmitted) * 100, 1) : 0,
                'sitemaps' => $sitemapDetails,
            ];

            Cache::put($cacheKey, $result, 3600);
            return $result;
        } catch (\Exception $e) {
            Log::error('Search Console getSitemapsCoverage failed', [
                'site_url' => $siteUrl,
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
        $cacheKey = 'sc:timeseries:' . $integration->id . ':' . md5("{$siteUrl}:{$startDate}:{$endDate}");
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

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

            Cache::put($cacheKey, $rows, 900);
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
