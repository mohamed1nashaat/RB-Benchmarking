<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Tenant;
use App\Services\GA4AnalyticsService;
use App\Services\PageSpeedService;
use App\Services\SearchConsoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeoReportController extends Controller
{
    public function __construct(
        protected SearchConsoleService $searchConsoleService,
        protected GA4AnalyticsService $ga4Service,
        protected PageSpeedService $pageSpeedService,
    ) {}

    /**
     * Find the Google integration for this tenant.
     */
    protected function getGoogleIntegration(Tenant $tenant): ?Integration
    {
        return Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('platform', 'google')
            ->where('status', 'active')
            ->first();
    }

    /**
     * Check if Google integration exists and has SC scope.
     */
    public function status(Tenant $tenant): JsonResponse
    {
        $integration = $this->getGoogleIntegration($tenant);

        if (!$integration) {
            return response()->json([
                'has_integration' => false,
                'has_search_console_scope' => false,
                'message' => 'No active Google integration found.',
            ]);
        }

        // Try listing SC sites to verify scope
        $hasSCScope = true;
        $scErrorReason = null;
        try {
            $this->searchConsoleService->listSites($integration);
        } catch (\Exception $e) {
            if ($e->getCode() === 403) {
                $hasSCScope = false;
                $msg = $e->getMessage();
                if (str_contains($msg, 'has not been used in project') || str_contains($msg, 'it is disabled')) {
                    $scErrorReason = 'api_not_enabled';
                } elseif (str_contains($msg, 'does not have sufficient permission for site')) {
                    $scErrorReason = 'no_site_access';
                } elseif (str_contains($msg, 'insufficient') || str_contains($msg, 'Insufficient')) {
                    $scErrorReason = 'insufficient_scope';
                } else {
                    $scErrorReason = 'forbidden';
                }
            }
        }

        return response()->json([
            'has_integration' => true,
            'has_search_console_scope' => $hasSCScope,
            'sc_error_reason' => $scErrorReason,
        ]);
    }

    /**
     * Get saved SEO property selections from tenant.settings.seo.
     */
    public function getProperties(Tenant $tenant): JsonResponse
    {
        $settings = $tenant->settings ?? [];
        $seo = $settings['seo'] ?? [];

        return response()->json([
            'search_console_site' => $seo['search_console_site'] ?? null,
            'ga4_property_id' => $seo['ga4_property_id'] ?? null,
            'ga4_property_name' => $seo['ga4_property_name'] ?? null,
            'pagespeed_url' => $seo['pagespeed_url'] ?? ($tenant->website ?? null),
        ]);
    }

    /**
     * Save SEO property selections to tenant.settings.seo.
     */
    public function saveProperties(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'search_console_site' => 'nullable|string',
            'ga4_property_id' => 'nullable|string',
            'ga4_property_name' => 'nullable|string',
            'pagespeed_url' => 'nullable|url',
        ]);

        $settings = $tenant->settings ?? [];
        $settings['seo'] = [
            'search_console_site' => $validated['search_console_site'] ?? null,
            'ga4_property_id' => $validated['ga4_property_id'] ?? null,
            'ga4_property_name' => $validated['ga4_property_name'] ?? null,
            'pagespeed_url' => $validated['pagespeed_url'] ?? null,
        ];

        $tenant->update(['settings' => $settings]);

        return response()->json(['message' => 'SEO properties saved.', 'seo' => $settings['seo']]);
    }

    /**
     * List Search Console sites from Google API.
     */
    public function listSearchConsoleSites(Tenant $tenant): JsonResponse
    {
        $integration = $this->getGoogleIntegration($tenant);
        if (!$integration) {
            return response()->json(['error' => 'No Google integration found.'], 404);
        }

        try {
            $sites = $this->searchConsoleService->listSites($integration);
            return response()->json(['sites' => $sites]);
        } catch (\Exception $e) {
            if ($e->getCode() === 403) {
                return response()->json([
                    'error' => 'Search Console scope not authorized. Please re-authorize Google.',
                    'needs_reauth' => true,
                ], 403);
            }

            return response()->json(['error' => 'Failed to fetch Search Console sites.'], 500);
        }
    }

    /**
     * List GA4 properties from Google API.
     */
    public function listGA4Properties(Tenant $tenant): JsonResponse
    {
        $integration = $this->getGoogleIntegration($tenant);
        if (!$integration) {
            return response()->json(['error' => 'No Google integration found.'], 404);
        }

        $properties = $this->ga4Service->listProperties($integration);
        return response()->json(['properties' => $properties]);
    }

    /**
     * Full SEO report: SC + GA4 + PageSpeed combined.
     */
    public function report(Request $request, Tenant $tenant): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $settings = $tenant->settings ?? [];
        $seo = $settings['seo'] ?? [];
        $integration = $this->getGoogleIntegration($tenant);

        $result = [
            'search_console' => null,
            'ga4' => null,
            'pagespeed_mobile' => null,
            'pagespeed_desktop' => null,
        ];

        // Search Console
        $scSite = $seo['search_console_site'] ?? null;
        if ($scSite && $integration) {
            try {
                $queries = $this->searchConsoleService->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'query', 25);
                $pages = $this->searchConsoleService->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'page', 25);
                $timeseries = $this->searchConsoleService->getTimeSeries($integration, $scSite, $startDate, $endDate);

                // Calculate totals from timeseries
                $totalClicks = array_sum(array_column($timeseries, 'clicks'));
                $totalImpressions = array_sum(array_column($timeseries, 'impressions'));
                $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
                $avgPosition = count($timeseries) > 0
                    ? round(array_sum(array_column($timeseries, 'position')) / count($timeseries), 1)
                    : 0;

                $result['search_console'] = [
                    'summary' => [
                        'total_clicks' => $totalClicks,
                        'total_impressions' => $totalImpressions,
                        'avg_ctr' => $avgCtr,
                        'avg_position' => $avgPosition,
                    ],
                    'timeseries' => $timeseries,
                    'queries' => $queries,
                    'pages' => $pages,
                ];
            } catch (\Exception $e) {
                Log::warning('SEO report: Search Console failed', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
                if ($e->getCode() === 403) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'has not been used in project') || str_contains($msg, 'it is disabled')) {
                        $result['search_console'] = ['error' => 'api_not_enabled'];
                    } elseif (str_contains($msg, 'does not have sufficient permission for site')) {
                        $result['search_console'] = ['error' => 'no_site_access'];
                    } else {
                        $result['search_console'] = ['error' => 'needs_reauth'];
                    }
                } else {
                    $result['search_console'] = ['error' => 'failed'];
                }
            }
        }

        // GA4
        $ga4PropertyId = $seo['ga4_property_id'] ?? null;
        if ($ga4PropertyId && $integration) {
            try {
                $result['ga4'] = $this->ga4Service->getReport($integration, $ga4PropertyId, $startDate, $endDate);
            } catch (\Exception $e) {
                Log::warning('SEO report: GA4 failed', ['error' => $e->getMessage()]);
                $result['ga4'] = ['error' => 'failed'];
            }
        }

        // PageSpeed
        $pageSpeedUrl = $seo['pagespeed_url'] ?? ($tenant->website ?? null);
        if ($pageSpeedUrl) {
            try {
                $result['pagespeed_mobile'] = $this->pageSpeedService->analyze($pageSpeedUrl, 'mobile');
                $result['pagespeed_desktop'] = $this->pageSpeedService->analyze($pageSpeedUrl, 'desktop');
            } catch (\Exception $e) {
                Log::warning('SEO report: PageSpeed failed', ['error' => $e->getMessage()]);
            }
            // Flag when URL is configured but data couldn't be fetched
            if (!$result['pagespeed_mobile'] && !$result['pagespeed_desktop']) {
                $result['pagespeed_error'] = true;
            }
        }

        return response()->json($result);
    }
}
