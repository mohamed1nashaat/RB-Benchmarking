<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AccountCategory;
use App\Models\Industry;
use App\Models\Integration;
use App\Models\SeoTechnicalIssue;
use App\Models\Tenant;
use App\Services\ClientDashboardService;
use App\Services\ClientPerformanceReportService;
use App\Services\CurrencyConversionService;
use App\Services\SearchConsoleService;
use App\Services\GA4AnalyticsService;
use App\Services\PageSpeedService;
use App\Services\SeoAuditService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Carbon\Carbon;

class ClientDashboard extends Component
{
    public int $clientId;

    // Tab
    public int $activeTabIndex = 0;

    // Filters
    public int $selectedPeriod = 30;
    public string $selectedPlatform = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Chart type selectors
    public string $platformChartType = 'doughnut';
    public string $trendChartType = 'line';
    public string $campaignsChartType = 'bar';

    // State
    public bool $loading = true;
    public bool $exporting = false;
    public ?array $dashboardData = null;
    public string $error = '';

    // SEO state
    public int $seoPeriod = 30;
    public string $seoDateFrom = '';
    public string $seoDateTo = '';
    public bool $seoLoading = false;
    public ?array $seoReportData = null;

    // SEO Settings
    public bool $seoSettingsOpen = true;
    public bool $seoSaving = false;
    public bool $seoSaved = false;
    public array $seoSearchConsoleSites = [];
    public array $seoGa4Properties = [];
    public ?string $seoSearchConsoleSite = null;
    public ?string $seoGa4PropertyId = null;
    public ?string $seoGa4PropertyName = null;
    public ?string $seoPagespeedUrl = null;
    public bool $seoHasIntegration = false;
    public bool $seoLoadingSites = false;
    public bool $seoLoadingProperties = false;
    private bool $seoSettingsLoaded = false;

    // Comparison toggle
    public bool $seoCompareEnabled = true;

    // PageSpeed on-demand state
    public bool $pagespeedLoading = false;

    // Audit sub-tab
    public array $auditIssues = [];
    public bool $auditLoading = false;
    public string $auditStatusFilter = 'all';
    public string $auditSeverityFilter = 'all';
    public ?int $editingIssueId = null;
    public string $editingIssueStatus = '';
    public string $editingIssueNotes = '';

    // Inline editing state for Ad Accounts
    public ?int $editingAccountId = null;
    public string $editAccountIndustry = '';
    public string $editAccountCategory = '';
    public string $editAccountSubCategory = '';

    public function mount(int $id)
    {
        $this->clientId = $id;
        $this->fetchDashboard();

        // Auto-select SEO tab when ?seo or ?tab=seo is in the URL
        if (request()->has('seo') || request()->query('tab') === 'seo') {
            $this->activeTabIndex = 1;
            $this->loadSeoProperties();
            $this->fetchSeoReport();

            // Panel is open by default — pre-load sites & properties
            if ($this->seoHasIntegration) {
                $this->loadSearchConsoleSites();
                $this->loadGa4Properties();
            }
        }
    }

    public function setActiveTab(int $index)
    {
        $this->activeTabIndex = $index;
        if ($index === 1) {
            $this->loadSeoProperties();
            if ($this->seoReportData === null) {
                $this->fetchSeoReport();
            }
            // Panel is open by default — pre-load sites & properties
            if ($this->seoSettingsOpen && empty($this->seoSearchConsoleSites) && $this->seoHasIntegration) {
                $this->loadSearchConsoleSites();
                $this->loadGa4Properties();
            }
        }
    }

    public function updatedSelectedPeriod()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->fetchDashboard();
    }

    public function updatedSelectedPlatform()
    {
        $this->fetchDashboard();
    }

    public function updatedSeoPeriod()
    {
        // Preset selected — sync date fields to match
        $this->seoDateFrom = now()->subDays($this->seoPeriod)->format('Y-m-d');
        $this->seoDateTo = now()->format('Y-m-d');
        $this->fetchSeoReport();
    }

    public function updatedSeoDateFrom()
    {
        if ($this->seoDateFrom && $this->seoDateTo) {
            $this->syncSeoPeriodFromDates();
            $this->fetchSeoReport();
        }
    }

    public function updatedSeoDateTo()
    {
        if ($this->seoDateFrom && $this->seoDateTo) {
            $this->syncSeoPeriodFromDates();
            $this->fetchSeoReport();
        }
    }

    public function updatedSeoCompareEnabled()
    {
        $this->fetchSeoReport();
    }

    /**
     * Check if the current seoDateFrom/To match a preset, and update seoPeriod accordingly.
     * Sets seoPeriod to 0 for custom ranges.
     */
    private function syncSeoPeriodFromDates(): void
    {
        $from = $this->seoDateFrom;
        $to = $this->seoDateTo;
        foreach ([7, 14, 30, 90, 180, 365] as $days) {
            if ($from === now()->subDays($days)->format('Y-m-d') && $to === now()->format('Y-m-d')) {
                $this->seoPeriod = $days;
                return;
            }
        }
        $this->seoPeriod = 0; // custom range
    }

    public function updatedDateFrom()
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->selectedPeriod = 0;
            $this->fetchDashboard();
        }
    }

    public function updatedDateTo()
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->selectedPeriod = 0;
            $this->fetchDashboard();
        }
    }

    public function resetFilters()
    {
        $this->selectedPeriod = 30;
        $this->selectedPlatform = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->fetchDashboard();
    }

    public function fetchDashboard()
    {
        $this->loading = true;
        $this->error = '';

        try {
            $client = Tenant::findOrFail($this->clientId);
            $service = app(ClientPerformanceReportService::class);
            $legacyService = app(ClientDashboardService::class);

            $filters = [];
            if ($this->selectedPeriod > 0) {
                $filters['period'] = $this->selectedPeriod;
            }
            if ($this->selectedPlatform) {
                $filters['platform'] = $this->selectedPlatform;
            }
            if ($this->dateFrom) {
                $filters['from'] = $this->dateFrom;
            }
            if ($this->dateTo) {
                $filters['to'] = $this->dateTo;
            }

            // New PDF-matching data
            $summaryMetrics = $service->getSummaryMetrics($client, $filters);
            $platformBreakdown = $service->getPlatformBreakdown($client, $filters);
            $teadsDaily = $service->getTeadsDailyTable($client, $filters);

            if ($client->isEcommerce()) {
                $funnelData = $service->getEcommerceFunnelData($client, $filters);
                $revenueVsRoas = $service->getRevenueVsRoasTrend($client, $filters);
                $conversionsVsCpa = $service->getConversionsVsCpaTrend($client, $filters);
                $leadVsCpl = [];
                $leadVsSpent = [];
            } else {
                $funnelData = $service->getFunnelData($client, $filters);
                $leadVsCpl = $service->getLeadVsCplTrend($client, $filters);
                $leadVsSpent = $service->getLeadVsSpentTrend($client, $filters);
                $revenueVsRoas = [];
                $conversionsVsCpa = [];
            }

            // Top ads per platform
            $topAds = [];
            foreach (['facebook', 'snapchat', 'google', 'tiktok', 'linkedin'] as $platform) {
                $ads = $service->getTopAdsByPlatform($client, $platform, 6, $filters);
                if (!empty($ads)) {
                    $topAds[$platform] = $ads;
                }
            }

            // Legacy data for fallback / SEO tab / exports
            $legacyMetrics = $legacyService->getClientStatistics($client, $filters);
            $adAccounts = $legacyService->getAdAccountsWithStatus($client, $filters);
            $topCampaigns = $legacyService->getTopCampaigns($client, 20, $filters);
            $trends = $legacyService->getPerformanceTrends($client, $filters);
            $sparklines = $this->generateSparklines($trends);
            $trendsAnalysis = $this->analyzeTrends($trends);
            $executiveSummary = $this->generateExecutiveSummary($legacyMetrics, $platformBreakdown, $topCampaigns);

            $this->dashboardData = [
                'client' => [
                    'name' => $client->name,
                    'industry' => $client->industry,
                    'website' => $client->website,
                    'subscription_tier' => ucfirst($client->subscription_tier ?? 'Standard'),
                ],
                'summary_metrics' => $summaryMetrics,
                'funnel_data' => $funnelData,
                'lead_vs_cpl' => $leadVsCpl,
                'lead_vs_spent' => $leadVsSpent,
                'revenue_vs_roas' => $revenueVsRoas,
                'conversions_vs_cpa' => $conversionsVsCpa,
                'platform_breakdown' => $platformBreakdown,
                'top_ads' => $topAds,
                'teads_daily' => $teadsDaily,
                'is_ecommerce' => $client->isEcommerce(),
                // Legacy structures preserved for export and SEO tab compatibility
                'metrics' => $legacyMetrics,
                'trends' => $trends,
                'ad_accounts' => $adAccounts,
                'top_campaigns' => $topCampaigns,
                'executive_summary' => $executiveSummary,
                'sparklines' => $sparklines,
                'trends_direction' => $trendsAnalysis['direction'],
                'trends_percent' => $trendsAnalysis['percent'],
            ];
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function fetchSeoReport()
    {
        $this->seoLoading = true;

        try {
            $client = Tenant::findOrFail($this->clientId);

            $settings = $client->settings ?? [];
            $seo = $settings['seo'] ?? [];

            // Compute date range from custom dates or seoPeriod fallback
            if ($this->seoDateFrom && $this->seoDateTo) {
                $startDate = $this->seoDateFrom;
                $endDate = $this->seoDateTo;
            } else {
                $endDate = now()->format('Y-m-d');
                $startDate = now()->subDays($this->seoPeriod)->format('Y-m-d');
                $this->seoDateFrom = $startDate;
                $this->seoDateTo = $endDate;
            }

            // Find Google integration (tenant-specific, or fall back to user's)
            $integration = $this->findGoogleIntegration($client->id);

            // Preserve existing PageSpeed data across period changes
            // If no in-memory data, try loading from cache (auto-show on page refresh)
            $existingMobile = $this->seoReportData['pagespeed_mobile'] ?? null;
            $existingDesktop = $this->seoReportData['pagespeed_desktop'] ?? null;

            if ($existingMobile === null && $existingDesktop === null) {
                $pageSpeedUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);
                if ($pageSpeedUrl) {
                    $cachedMobile = Cache::get('pagespeed:' . md5($pageSpeedUrl . ':mobile'));
                    $cachedDesktop = Cache::get('pagespeed:' . md5($pageSpeedUrl . ':desktop'));
                    if ($cachedMobile && !isset($cachedMobile['_error'])) {
                        $existingMobile = $cachedMobile;
                    }
                    if ($cachedDesktop && !isset($cachedDesktop['_error'])) {
                        $existingDesktop = $cachedDesktop;
                    }
                }
            }

            $existingPagespeed = [
                'pagespeed_mobile' => $existingMobile,
                'pagespeed_desktop' => $existingDesktop,
            ];

            $report = [];

            // Search Console
            $scSite = $seo['search_console_site'] ?? null;
            if ($scSite && $integration) {
                try {
                    $searchConsole = app(SearchConsoleService::class);
                    $queries = $searchConsole->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'query', 25);
                    $pages = $searchConsole->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'page', 25);
                    $timeseries = $searchConsole->getTimeSeries($integration, $scSite, $startDate, $endDate);

                    $totalClicks = array_sum(array_column($timeseries, 'clicks'));
                    $totalImpressions = array_sum(array_column($timeseries, 'impressions'));
                    $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
                    $avgPosition = count($timeseries) > 0
                        ? round(array_sum(array_column($timeseries, 'position')) / count($timeseries), 1)
                        : 0;

                    $report['search_console'] = [
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
                    $report['search_console'] = null;
                }
            }

            // Search Console Coverage (sitemaps)
            if ($scSite && $integration && is_array($report['search_console'] ?? null)) {
                try {
                    $searchConsole = $searchConsole ?? app(SearchConsoleService::class);
                    $report['search_console']['coverage'] = $searchConsole->getSitemapsCoverage($integration, $scSite);
                } catch (\Exception $e) {
                    // Coverage fetch failed — skip silently, other SC data is intact
                }

                // Auto-detect issues from Search Console coverage
                if (!empty($report['search_console']['coverage'])) {
                    app(SeoAuditService::class)->processSearchConsoleIssues(
                        $this->clientId,
                        $scSite,
                        $report['search_console']['coverage']
                    );
                }
            }

            // GA4
            $ga4PropertyId = $seo['ga4_property_id'] ?? null;
            if ($ga4PropertyId && $integration) {
                try {
                    $ga4 = app(GA4AnalyticsService::class);
                    $report['ga4'] = $ga4->getReport($integration, $ga4PropertyId, $startDate, $endDate);
                } catch (\Exception $e) {
                    $report['ga4'] = null;
                }
            }

            // Restore existing PageSpeed data (on-demand only)
            $report = array_merge($report, $existingPagespeed);

            // Auto-detect issues from cached/existing PageSpeed data
            $psUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);
            $psMobile = $report['pagespeed_mobile'] ?? [];
            $psDesktop = $report['pagespeed_desktop'] ?? [];
            if ($psUrl && (!empty($psMobile['audits']) || !empty($psDesktop['audits']))) {
                app(SeoAuditService::class)->processPageSpeedAudits(
                    $this->clientId, $psUrl, $psMobile, $psDesktop
                );
            }

            // ── Period Comparison (Feature 10) ──────────────────────────
            if ($this->seoCompareEnabled) {
                $periodDays = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
                $prevEnd = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
                $prevStart = \Carbon\Carbon::parse($startDate)->subDays($periodDays)->format('Y-m-d');
                $comparison = [];

                // Previous SC summary via timeseries
                if ($scSite && $integration) {
                    try {
                        $searchConsole = $searchConsole ?? app(SearchConsoleService::class);
                        $prevScTs = $searchConsole->getTimeSeries($integration, $scSite, $prevStart, $prevEnd);
                        $prevClicks = array_sum(array_column($prevScTs, 'clicks'));
                        $prevImpressions = array_sum(array_column($prevScTs, 'impressions'));
                        $prevAvgCtr = $prevImpressions > 0 ? round(($prevClicks / $prevImpressions) * 100, 2) : 0;
                        $prevAvgPos = count($prevScTs) > 0 ? round(array_sum(array_column($prevScTs, 'position')) / count($prevScTs), 1) : 0;

                        $curClicks = $report['search_console']['summary']['total_clicks'] ?? 0;
                        $curImpressions = $report['search_console']['summary']['total_impressions'] ?? 0;
                        $curCtr = $report['search_console']['summary']['avg_ctr'] ?? 0;
                        $curPos = $report['search_console']['summary']['avg_position'] ?? 0;

                        $comparison['total_clicks'] = $this->computeDelta($curClicks, $prevClicks);
                        $comparison['total_impressions'] = $this->computeDelta($curImpressions, $prevImpressions);
                        $comparison['avg_ctr'] = $this->computeDelta($curCtr, $prevAvgCtr);
                        $comparison['avg_position'] = $this->computeDelta($curPos, $prevAvgPos, true); // inverted

                        // Previous period SC queries & pages for table comparison
                        $prevQueries = $searchConsole->getSearchAnalytics($integration, $scSite, $prevStart, $prevEnd, 'query', 25);
                        $prevPages = $searchConsole->getSearchAnalytics($integration, $scSite, $prevStart, $prevEnd, 'page', 25);
                        $comparison['sc_queries'] = collect($prevQueries)->keyBy('key')->toArray();
                        $comparison['sc_pages'] = collect($prevPages)->keyBy('key')->toArray();
                    } catch (\Exception $e) {
                        // skip comparison for SC
                    }
                }

                // Previous GA4 summary
                if ($ga4PropertyId && $integration) {
                    try {
                        $ga4 = $ga4 ?? app(GA4AnalyticsService::class);
                        $prevGa4 = $ga4->getSummaryOnly($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $curGa4 = $report['ga4']['summary'] ?? [];

                        foreach (['sessions', 'active_users', 'page_views', 'total_users', 'new_users', 'conversions', 'total_revenue'] as $key) {
                            if (isset($curGa4[$key]) && isset($prevGa4[$key])) {
                                $comparison[$key] = $this->computeDelta($curGa4[$key], $prevGa4[$key]);
                            }
                        }
                        // Inverted metrics (lower is better)
                        if (isset($curGa4['bounce_rate']) && isset($prevGa4['bounce_rate'])) {
                            $comparison['bounce_rate'] = $this->computeDelta($curGa4['bounce_rate'], $prevGa4['bounce_rate'], true);
                        }
                        if (isset($curGa4['engagement_rate']) && isset($prevGa4['engagement_rate'])) {
                            $comparison['engagement_rate'] = $this->computeDelta($curGa4['engagement_rate'], $prevGa4['engagement_rate']);
                        }

                        // Previous period GA4 per-row data for table comparisons
                        $prevGa4Pages = $ga4->getTopPages($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_pages'] = collect($prevGa4Pages)->keyBy('page_path')->toArray();

                        $prevTrafficSources = $ga4->getTrafficSources($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_traffic_sources'] = collect($prevTrafficSources)->keyBy('channel')->toArray();

                        $prevDevices = $ga4->getDevices($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_devices'] = collect($prevDevices)->keyBy('device')->toArray();

                        $prevGeo = $ga4->getGeo($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_geo'] = collect($prevGeo)->keyBy('country')->toArray();

                        $prevBrowsers = $ga4->getBrowsers($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_browsers'] = collect($prevBrowsers)->keyBy('browser')->toArray();

                        $prevEcomPages = $ga4->getTopEcommercePages($integration, $ga4PropertyId, $prevStart, $prevEnd);
                        $comparison['ga4_ecom_pages'] = collect($prevEcomPages)->keyBy('name')->toArray();

                        $prevTopProducts = $ga4->getTopProducts($integration, $ga4PropertyId, $prevStart, $prevEnd, 5, false);
                        $comparison['ga4_top_products'] = collect($prevTopProducts)->keyBy('name')->toArray();

                        $prevLeastProducts = $ga4->getTopProducts($integration, $ga4PropertyId, $prevStart, $prevEnd, 5, true);
                        $comparison['ga4_least_products'] = collect($prevLeastProducts)->keyBy('name')->toArray();
                    } catch (\Exception $e) {
                        // skip comparison for GA4
                    }
                }

                $report['comparison'] = $comparison;
            }

            // ── Sparkline Arrays (Feature 11) ───────────────────────────
            $sparklines = [];
            $ga4Ts = $report['ga4']['timeseries'] ?? [];
            $scTs = $report['search_console']['timeseries'] ?? [];

            if (!empty($ga4Ts)) {
                $sparklines['sessions'] = array_column($ga4Ts, 'sessions');
                $sparklines['active_users'] = array_column($ga4Ts, 'users');
                $sparklines['bounce_rate'] = array_column($ga4Ts, 'bounce_rate');
            }
            if (!empty($scTs)) {
                $sparklines['total_clicks'] = array_column($scTs, 'clicks');
                $sparklines['total_impressions'] = array_column($scTs, 'impressions');
            }
            $report['sparklines'] = $sparklines;

            $this->seoReportData = $report;
            $this->fetchAuditIssues();

            // Auto-trigger PageSpeed refresh when no issues exist and audit data is stale/missing
            $psAutoUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);
            $hasFreshAudits = !empty($report['pagespeed_mobile']['audits'] ?? []) ||
                              !empty($report['pagespeed_desktop']['audits'] ?? []);
            if (empty($this->auditIssues) && $psAutoUrl && !$hasFreshAudits) {
                $this->js('$wire.runPageSpeed()');
            }
        } catch (\Exception $e) {
            $this->seoReportData = ['error' => $e->getMessage()];
        } finally {
            $this->seoLoading = false;
        }
    }

    /**
     * Find a Google integration for the given tenant, falling back to
     * any active Google integration owned by the current user.
     */
    protected function findGoogleIntegration(int $tenantId): ?Integration
    {
        return Integration::query()
            ->where('platform', 'google')
            ->where('status', 'active')
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('user_id', auth()->id());
            })
            ->orderByRaw('tenant_id = ? DESC', [$tenantId]) // prefer tenant-specific
            ->first();
    }

    public function loadSeoProperties()
    {
        if ($this->seoSettingsLoaded) {
            return;
        }

        $client = Tenant::find($this->clientId);
        if (!$client) {
            return;
        }

        $settings = $client->settings ?? [];
        $seo = $settings['seo'] ?? [];

        $this->seoSearchConsoleSite = $seo['search_console_site'] ?? null;
        $this->seoGa4PropertyId = $seo['ga4_property_id'] ?? null;
        $this->seoGa4PropertyName = $seo['ga4_property_name'] ?? null;
        $this->seoPagespeedUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);

        $this->seoHasIntegration = (bool) $this->findGoogleIntegration($client->id);
        $this->seoSettingsLoaded = true;
    }

    public function toggleSeoSettings()
    {
        $this->seoSettingsOpen = !$this->seoSettingsOpen;
        $this->seoSaved = false;

        if ($this->seoSettingsOpen && empty($this->seoSearchConsoleSites) && $this->seoHasIntegration) {
            $this->loadSearchConsoleSites();
            $this->loadGa4Properties();
        }
    }

    public function loadSearchConsoleSites()
    {
        $this->seoLoadingSites = true;

        try {
            $integration = $this->findGoogleIntegration($this->clientId);

            if ($integration) {
                $searchConsole = app(SearchConsoleService::class);
                $this->seoSearchConsoleSites = $searchConsole->listSites($integration);
            }
        } catch (\Exception $e) {
            // Silently fail — user will see empty dropdown
        } finally {
            $this->seoLoadingSites = false;
        }
    }

    public function loadGa4Properties()
    {
        $this->seoLoadingProperties = true;

        try {
            $integration = $this->findGoogleIntegration($this->clientId);

            if ($integration) {
                $ga4 = app(GA4AnalyticsService::class);
                $this->seoGa4Properties = $ga4->listProperties($integration);
            }
        } catch (\Exception $e) {
            // Silently fail — user will see empty dropdown
        } finally {
            $this->seoLoadingProperties = false;
        }
    }

    public function saveSeoProperties()
    {
        $this->seoSaving = true;
        $this->seoSaved = false;

        try {
            $client = Tenant::findOrFail($this->clientId);
            $settings = $client->settings ?? [];

            // If user selected a GA4 property, find its display name
            if ($this->seoGa4PropertyId) {
                foreach ($this->seoGa4Properties as $prop) {
                    if (($prop['property_id'] ?? '') === $this->seoGa4PropertyId) {
                        $this->seoGa4PropertyName = $prop['display_name'] ?? null;
                        break;
                    }
                }
            }

            $settings['seo'] = [
                'search_console_site' => $this->seoSearchConsoleSite,
                'ga4_property_id' => $this->seoGa4PropertyId,
                'ga4_property_name' => $this->seoGa4PropertyName,
                'pagespeed_url' => $this->seoPagespeedUrl,
            ];

            $client->update(['settings' => $settings]);
            $this->seoSaved = true;

            // Refresh SEO report with new settings
            $this->seoReportData = null;
            $this->fetchSeoReport();
        } catch (\Exception $e) {
            $this->seoSaved = false;
        } finally {
            $this->seoSaving = false;
        }
    }

    // ── PageSpeed On-Demand ─────────────────────────────────────

    public function runPageSpeed(): void
    {
        $this->pagespeedLoading = true;
        try {
            $client = Tenant::findOrFail($this->clientId);
            $settings = $client->settings ?? [];
            $pageSpeedUrl = $settings['seo']['pagespeed_url'] ?? ($client->website ?? null);
            if ($pageSpeedUrl) {
                $pagespeed = app(PageSpeedService::class);

                // Save existing good data so we can restore on failure
                $existingMobile = $this->seoReportData['pagespeed_mobile'] ?? null;
                $existingDesktop = $this->seoReportData['pagespeed_desktop'] ?? null;
                $hadGoodMobile = $existingMobile && !isset($existingMobile['_error']);
                $hadGoodDesktop = $existingDesktop && !isset($existingDesktop['_error']);

                // Clear cache to force fresh fetch
                $mobileCacheKey = 'pagespeed:' . md5($pageSpeedUrl . ':mobile');
                $desktopCacheKey = 'pagespeed:' . md5($pageSpeedUrl . ':desktop');
                Cache::forget($mobileCacheKey);
                Cache::forget($desktopCacheKey);

                $mobile = $pagespeed->analyze($pageSpeedUrl, 'mobile');
                $desktop = $pagespeed->analyze($pageSpeedUrl, 'desktop');

                // If fresh fetch failed but we had good data, keep the old results
                $mobileFailed = $mobile === null || isset($mobile['_error']);
                $desktopFailed = $desktop === null || isset($desktop['_error']);

                if ($mobileFailed && $hadGoodMobile) {
                    $this->seoReportData['pagespeed_mobile'] = $existingMobile;
                    // Restore good data in cache
                    Cache::put($mobileCacheKey, $existingMobile, 24 * 3600);
                } else {
                    $this->seoReportData['pagespeed_mobile'] = $mobile;
                }

                if ($desktopFailed && $hadGoodDesktop) {
                    $this->seoReportData['pagespeed_desktop'] = $existingDesktop;
                    Cache::put($desktopCacheKey, $existingDesktop, 24 * 3600);
                } else {
                    $this->seoReportData['pagespeed_desktop'] = $desktop;
                }

                // Notify user if quota was exceeded but old data was preserved
                if (($mobileFailed || $desktopFailed) && ($hadGoodMobile || $hadGoodDesktop)) {
                    $this->dispatch('notify', type: 'warning', message: __('client_dashboard.seo.pagespeed_quota_exceeded') . ' ' . __('client_dashboard.seo.pagespeed_showing_cached'));
                }

                // Auto-detect SEO issues from Lighthouse audits
                app(SeoAuditService::class)->processPageSpeedAudits(
                    $this->clientId,
                    $pageSpeedUrl,
                    $this->seoReportData['pagespeed_mobile'] ?? [],
                    $this->seoReportData['pagespeed_desktop'] ?? []
                );
                $this->fetchAuditIssues();
            }
        } catch (\Exception $e) {
            // silent
        } finally {
            $this->pagespeedLoading = false;
        }
    }

    // ── Audit Sub-Tab ─────────────────────────────────────────

    public function fetchAuditIssues(): void
    {
        $this->auditLoading = true;

        try {
            $query = SeoTechnicalIssue::where('tenant_id', $this->clientId);

            if ($this->auditStatusFilter !== 'all') {
                $query->where('current_status', $this->auditStatusFilter);
            }
            if ($this->auditSeverityFilter !== 'all') {
                $query->where('severity', $this->auditSeverityFilter);
            }

            $this->auditIssues = $query
                ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
                ->orderByDesc('date_found')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->auditIssues = [];
        } finally {
            $this->auditLoading = false;
        }
    }

    public function startEditIssue(int $issueId): void
    {
        $issue = SeoTechnicalIssue::find($issueId);
        if ($issue && $issue->tenant_id === $this->clientId) {
            $this->editingIssueId = $issue->id;
            $this->editingIssueStatus = $issue->current_status;
            $this->editingIssueNotes = $issue->notes ?? '';
        }
    }

    public function saveIssueUpdate(): void
    {
        $issue = SeoTechnicalIssue::find($this->editingIssueId);
        if (!$issue || $issue->tenant_id !== $this->clientId) {
            return;
        }

        $issue->current_status = $this->editingIssueStatus;
        $issue->notes = $this->editingIssueNotes;

        if ($this->editingIssueStatus === 'resolved' && !$issue->date_fixed) {
            $issue->date_fixed = now();
        }

        $issue->save();
        $this->cancelEditIssue();
        $this->fetchAuditIssues();
        $this->dispatch('notify', type: 'success', message: __('client_dashboard.seo.issue_updated'));
    }

    public function cancelEditIssue(): void
    {
        $this->editingIssueId = null;
        $this->editingIssueStatus = '';
        $this->editingIssueNotes = '';
    }

    public function updatedAuditStatusFilter(): void
    {
        $this->fetchAuditIssues();
    }

    public function updatedAuditSeverityFilter(): void
    {
        $this->fetchAuditIssues();
    }

    public function exportDashboard(string $format)
    {
        $this->exporting = true;

        $this->dispatch('export-dashboard', [
            'format' => $format,
            'clientId' => $this->clientId,
            'period' => $this->selectedPeriod,
            'platform' => $this->selectedPlatform,
        ]);

        $this->exporting = false;
    }

    public function getPlatformChartConfigProperty(): array
    {
        if (!$this->dashboardData || empty($this->dashboardData['platform_breakdown'])) {
            return ['type' => $this->platformChartType, 'data' => ['labels' => [], 'datasets' => [['data' => []]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false]];
        }

        $colors = ['rgba(239, 68, 68,0.8)', 'rgba(107, 107, 102, 0.85)', 'rgba(17, 17, 17, 0.85)', 'rgba(63, 63, 60, 0.85)', 'rgba(168, 168, 164, 0.9)', 'rgba(107, 107, 102,0.8)'];

        return [
            'type' => $this->platformChartType,
            'data' => [
                'labels' => array_map(fn($p) => ucfirst($p['platform']), $this->dashboardData['platform_breakdown']),
                'datasets' => [[
                    'data' => array_map(fn($p) => $p['spend'], $this->dashboardData['platform_breakdown']),
                    'backgroundColor' => array_slice($colors, 0, count($this->dashboardData['platform_breakdown'])),
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom']],
            ],
        ];
    }

    public function getTrendChartConfigProperty(): array
    {
        if (!$this->dashboardData || empty($this->dashboardData['trends']['spend'])) {
            return ['type' => $this->trendChartType, 'data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true, 'maintainAspectRatio' => false]];
        }

        $trends = $this->dashboardData['trends'];
        $labels = array_map(fn($t) => $t['date'], $trends['spend']);

        return [
            'type' => $this->trendChartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Spend (SAR)', 'data' => array_map(fn($t) => $t['value'], $trends['spend']), 'borderColor' => 'rgb(239, 68, 68)', 'backgroundColor' => 'rgba(239, 68, 68,0.1)', 'tension' => 0.3, 'fill' => true, 'yAxisID' => 'y'],
                    ['label' => 'Clicks', 'data' => array_map(fn($t) => $t['value'], $trends['clicks']), 'borderColor' => 'rgb(107, 107, 102)', 'backgroundColor' => 'rgba(107, 107, 102,0.1)', 'tension' => 0.3, 'yAxisID' => 'y1'],
                    ['label' => 'Conversions', 'data' => array_map(fn($t) => $t['value'], $trends['conversions']), 'borderColor' => 'rgb(212, 212, 210)', 'backgroundColor' => 'rgba(212, 212, 210,0.1)', 'tension' => 0.3, 'yAxisID' => 'y1'],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }

    public function getCampaignsChartConfigProperty(): array
    {
        if (!$this->dashboardData || empty($this->dashboardData['top_campaigns'])) {
            return ['type' => 'bar', 'data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true, 'maintainAspectRatio' => false]];
        }

        $campaigns = array_slice($this->dashboardData['top_campaigns'], 0, 10);

        return [
            'type' => $this->campaignsChartType,
            'data' => [
                'labels' => array_map(fn($c) => substr($c['name'], 0, 30), $campaigns),
                'datasets' => [
                    ['label' => 'Spend (SAR)', 'data' => array_map(fn($c) => $c['spend'], $campaigns), 'backgroundColor' => 'rgba(239, 68, 68,0.7)'],
                    ['label' => 'Conversions', 'data' => array_map(fn($c) => $c['conversions'], $campaigns), 'backgroundColor' => 'rgba(120, 119, 116,0.7)'],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'top']],
            ],
        ];
    }

    public function getScTrendChartConfigProperty(): array
    {
        $timeseries = $this->seoReportData['search_console']['timeseries'] ?? [];
        if (empty($timeseries)) {
            return ['type' => 'line', 'data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true, 'maintainAspectRatio' => false]];
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => array_map(fn($t) => $t['date'], $timeseries),
                'datasets' => [
                    [
                        'label' => __('client_dashboard.seo.clicks'),
                        'data' => array_column($timeseries, 'clicks'),
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68,0.1)',
                        'tension' => 0.3,
                        'fill' => true,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => __('client_dashboard.seo.impressions'),
                        'data' => array_column($timeseries, 'impressions'),
                        'borderColor' => 'rgb(107, 107, 102)',
                        'backgroundColor' => 'rgba(107, 107, 102,0.1)',
                        'tension' => 0.3,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left', 'title' => ['display' => true, 'text' => __('client_dashboard.seo.clicks')]],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => __('client_dashboard.seo.impressions')]],
                ],
            ],
        ];
    }

    public function getGa4TrendChartConfigProperty(): array
    {
        $timeseries = $this->seoReportData['ga4']['timeseries'] ?? [];
        if (empty($timeseries)) {
            return ['type' => 'line', 'data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true, 'maintainAspectRatio' => false]];
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => array_map(fn($t) => $t['date'], $timeseries),
                'datasets' => [
                    [
                        'label' => __('client_dashboard.seo.sessions'),
                        'data' => array_column($timeseries, 'sessions'),
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68,0.1)',
                        'tension' => 0.3,
                        'fill' => true,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => __('client_dashboard.seo.users'),
                        'data' => array_column($timeseries, 'users'),
                        'borderColor' => 'rgba(120, 119, 116, 0.1)',
                        'backgroundColor' => 'rgba(120, 119, 116,0.1)',
                        'tension' => 0.3,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => __('client_dashboard.seo.bounce_rate'),
                        'data' => array_column($timeseries, 'bounce_rate'),
                        'borderColor' => 'rgb(212, 212, 210)',
                        'backgroundColor' => 'rgba(212, 212, 210,0.1)',
                        'tension' => 0.3,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => __('client_dashboard.seo.bounce_rate') . ' (%)'], 'ticks' => ['callback' => '']],
                ],
            ],
        ];
    }

    public function getPeriodLabelProperty(): string
    {
        if ($this->dateFrom && $this->dateTo) {
            return "{$this->dateFrom} to {$this->dateTo}";
        }
        if (!$this->selectedPeriod || $this->selectedPeriod === 0) {
            return 'All Time';
        }
        return "Last {$this->selectedPeriod} Days";
    }

    private function computeDelta(float $current, float $previous, bool $inverted = false): array
    {
        if ($previous == 0) {
            $pct = $current > 0 ? 100.0 : 0.0;
        } else {
            $pct = round((($current - $previous) / abs($previous)) * 100, 1);
        }
        $direction = $inverted ? ($pct <= 0 ? 'positive' : 'negative') : ($pct >= 0 ? 'positive' : 'negative');
        return ['value' => $pct, 'direction' => $direction];
    }

    private function formatNumber($num): string
    {
        if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }

    /**
     * Generate SVG sparkline from data points.
     *
     * @security-safe Output is internally-generated SVG markup containing only
     *                numeric coordinates and hardcoded colors. No user input
     *                reaches the returned string. Safe for {!! !!} rendering.
     */
    protected function generateSparkline(array $data, int $width = 80, int $height = 24): string
    {
        if (empty($data) || count($data) < 2) {
            return '';
        }

        $values = array_values($data);
        $count = count($values);
        $max = max($values) ?: 1;
        $min = min($values);
        $range = $max - $min ?: 1;

        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $x = ($i / ($count - 1)) * $width;
            $y = $height - (($values[$i] - $min) / $range) * ($height - 4) - 2;
            $points[] = round($x, 1) . ',' . round($y, 1);
        }

        $firstHalf = array_slice($values, 0, (int)($count / 2));
        $secondHalf = array_slice($values, (int)($count / 2));
        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        $trendColor = $secondAvg >= $firstAvg ? '#787774' : '#ef4444';

        return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">'
            . '<polyline fill="none" stroke="' . $trendColor . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" points="' . implode(' ', $points) . '"/>'
            . '</svg>';
    }

    /**
     * Generate sparklines for all key metrics
     */
    protected function generateSparklines(array $trends): array
    {
        $sparklines = [
            'spend' => '',
            'impressions' => '',
            'clicks' => '',
            'conversions' => '',
        ];

        if (empty($trends)) {
            return $sparklines;
        }

        $spendData = [];
        $impressionsData = [];
        $clicksData = [];
        $conversionsData = [];

        foreach ($trends as $day) {
            $spendData[] = $day['spend'] ?? 0;
            $impressionsData[] = $day['impressions'] ?? 0;
            $clicksData[] = $day['clicks'] ?? 0;
            $conversionsData[] = $day['conversions'] ?? 0;
        }

        $sparklines['spend'] = $this->generateSparkline($spendData);
        $sparklines['impressions'] = $this->generateSparkline($impressionsData);
        $sparklines['clicks'] = $this->generateSparkline($clicksData);
        $sparklines['conversions'] = $this->generateSparkline($conversionsData);

        return $sparklines;
    }

    /**
     * Analyze trends to determine direction and percentage change
     */
    protected function analyzeTrends(array $trends): array
    {
        $direction = [
            'spend' => 'flat',
            'impressions' => 'flat',
            'clicks' => 'flat',
            'conversions' => 'flat',
            'ctr' => 'flat',
            'cvr' => 'flat',
            'cpc' => 'flat',
            'roas' => 'flat',
        ];

        $percent = [
            'spend' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0,
            'cvr' => 0,
            'cpc' => 0,
            'roas' => 0,
        ];

        if (empty($trends) || count($trends) < 2) {
            return ['direction' => $direction, 'percent' => $percent];
        }

        $count = count($trends);
        $midpoint = (int)($count / 2);

        $firstHalf = array_slice($trends, 0, $midpoint);
        $secondHalf = array_slice($trends, $midpoint);

        $metrics = ['spend', 'impressions', 'clicks', 'conversions'];

        foreach ($metrics as $metric) {
            $firstSum = array_sum(array_column($firstHalf, $metric));
            $secondSum = array_sum(array_column($secondHalf, $metric));

            if ($firstSum > 0) {
                $change = (($secondSum - $firstSum) / $firstSum) * 100;
                $percent[$metric] = round($change, 1);
                $direction[$metric] = $change > 2 ? 'up' : ($change < -2 ? 'down' : 'flat');
            }
        }

        $firstImpressions = array_sum(array_column($firstHalf, 'impressions'));
        $secondImpressions = array_sum(array_column($secondHalf, 'impressions'));
        $firstClicks = array_sum(array_column($firstHalf, 'clicks'));
        $secondClicks = array_sum(array_column($secondHalf, 'clicks'));

        $firstCtr = $firstImpressions > 0 ? ($firstClicks / $firstImpressions) * 100 : 0;
        $secondCtr = $secondImpressions > 0 ? ($secondClicks / $secondImpressions) * 100 : 0;
        if ($firstCtr > 0) {
            $ctrChange = (($secondCtr - $firstCtr) / $firstCtr) * 100;
            $percent['ctr'] = round($ctrChange, 1);
            $direction['ctr'] = $ctrChange > 2 ? 'up' : ($ctrChange < -2 ? 'down' : 'flat');
        }

        return ['direction' => $direction, 'percent' => $percent];
    }

    /**
     * Generate executive summary with auto-generated insights
     */
    protected function generateExecutiveSummary(array $stats, array $platforms, array $campaigns): array
    {
        $insights = [];
        $health = 'healthy';
        $healthScore = 100;

        $roas = $stats['roas'] ?? 0;
        if ($roas >= 3) {
            $insights[] = [
                'type' => 'success',
                'icon' => '★',
                'text' => 'Excellent ROAS of ' . number_format($roas, 2) . 'x - campaigns are highly profitable.',
            ];
        } elseif ($roas >= 2) {
            $insights[] = [
                'type' => 'success',
                'icon' => '✓',
                'text' => 'Strong ROAS of ' . number_format($roas, 2) . 'x indicates healthy return on ad spend.',
            ];
        } elseif ($roas >= 1) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '!',
                'text' => 'ROAS of ' . number_format($roas, 2) . 'x is breaking even. Consider optimizing underperforming campaigns.',
            ];
            $healthScore -= 20;
        } else {
            $insights[] = [
                'type' => 'danger',
                'icon' => '✗',
                'text' => 'ROAS of ' . number_format($roas, 2) . 'x indicates negative returns. Immediate optimization needed.',
            ];
            $healthScore -= 40;
        }

        if (!empty($platforms)) {
            $topPlatform = $platforms[0];
            $platformSpend = $topPlatform['spend'] ?? 0;
            $totalSpend = ($stats['total_spend'] ?? 0) > 0 ? $stats['total_spend'] : 1;
            $platformPct = ($platformSpend / $totalSpend) * 100;

            $insights[] = [
                'type' => 'info',
                'icon' => '◆',
                'text' => ucfirst($topPlatform['platform']) . ' leads with ' . number_format($platformPct, 0) . '% of total spend (' . number_format($platformSpend, 0) . ' SAR).',
            ];
        }

        if (!empty($campaigns)) {
            $bestCampaign = null;
            $bestRoas = 0;
            foreach ($campaigns as $campaign) {
                if (($campaign['roas'] ?? 0) > $bestRoas && ($campaign['spend'] ?? 0) > 100) {
                    $bestRoas = $campaign['roas'];
                    $bestCampaign = $campaign;
                }
            }

            if ($bestCampaign && $bestRoas >= 2) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => '▲',
                    'text' => 'Top performer: "' . \Illuminate\Support\Str::limit($bestCampaign['name'], 30) . '" with ' . number_format($bestRoas, 1) . 'x ROAS.',
                ];
            }
        }

        $ctr = $stats['ctr'] ?? 0;
        if ($ctr >= 2) {
            $insights[] = [
                'type' => 'success',
                'icon' => '●',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is above industry average, showing strong ad relevance.',
            ];
        } elseif ($ctr >= 1) {
            $insights[] = [
                'type' => 'info',
                'icon' => '○',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is within normal range.',
            ];
        } else {
            $insights[] = [
                'type' => 'warning',
                'icon' => '!',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is below average. Review ad creative and targeting.',
            ];
            $healthScore -= 15;
        }

        $conversions = $stats['total_conversions'] ?? 0;
        $cvr = $stats['cvr'] ?? 0;
        if ($conversions > 0) {
            if ($cvr >= 3) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => '✓',
                    'text' => 'Strong conversion rate of ' . number_format($cvr, 2) . '% with ' . number_format($conversions) . ' total conversions.',
                ];
            } elseif ($cvr < 1 && $conversions < 10) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => '!',
                    'text' => 'Low conversion volume (' . number_format($conversions) . '). Consider expanding reach or improving landing pages.',
                ];
                $healthScore -= 10;
            }
        }

        if ($healthScore >= 80) {
            $health = 'healthy';
        } elseif ($healthScore >= 50) {
            $health = 'needs_attention';
        } else {
            $health = 'critical';
        }

        return [
            'insights' => array_slice($insights, 0, 5),
            'health' => $health,
            'health_score' => $healthScore,
        ];
    }

    public function getFunnelChartConfigProperty(): array
    {
        $stages = $this->dashboardData['funnel_data']['stages'] ?? [];
        $labels = array_map(fn($s) => $s['name'], $stages);
        $values = array_map(fn($s) => $s['value'], $stages);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Volume',
                    'data' => $values,
                    'backgroundColor' => ['#EF4444', '#6B6B66', '#EF4444', '#787774'],
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['display' => false],
                    'y' => ['grid' => ['display' => false], 'ticks' => ['font' => ['size' => 11], 'color' => '#3F3F3C']],
                ],
            ],
        ];
    }

    public function getLeadVsCplChartConfigProperty(): array
    {
        $data = $this->dashboardData['lead_vs_cpl'] ?? [];
        $labels = array_map(fn($d) => \Carbon\Carbon::parse($d['date'])->format('M d'), $data);
        $leads = array_map(fn($d) => $d['leads'], $data);
        $cpl = array_map(fn($d) => $d['cpl'], $data);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Leads',
                        'data' => $leads,
                        'borderColor' => '#787774',
                        'backgroundColor' => 'rgba(120, 119, 116,0.1)',
                        'tension' => 0.3,
                        'fill' => true,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'CPL (SAR)',
                        'data' => $cpl,
                        'borderColor' => '#EF4444',
                        'backgroundColor' => 'rgba(239, 68, 68,0.05)',
                        'tension' => 0.3,
                        'fill' => false,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }

    public function getLeadVsSpentChartConfigProperty(): array
    {
        $data = $this->dashboardData['lead_vs_spent'] ?? [];
        $labels = array_map(fn($d) => $d['period'], $data);
        $leads = array_map(fn($d) => $d['leads'], $data);
        $spend = array_map(fn($d) => $d['spend'], $data);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Leads',
                        'data' => $leads,
                        'backgroundColor' => '#787774',
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Spend (SAR)',
                        'data' => $spend,
                        'backgroundColor' => '#EF4444',
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }

    public function getRevenueVsRoasChartConfigProperty(): array
    {
        $data = $this->dashboardData['revenue_vs_roas'] ?? [];
        $labels = array_map(fn($d) => \Carbon\Carbon::parse($d['date'])->format('M d'), $data);
        $revenue = array_map(fn($d) => $d['revenue'], $data);
        $roas = array_map(fn($d) => $d['roas'], $data);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Revenue (SAR)',
                        'data' => $revenue,
                        'borderColor' => '#787774',
                        'backgroundColor' => 'rgba(120, 119, 116,0.1)',
                        'tension' => 0.3,
                        'fill' => true,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'ROAS',
                        'data' => $roas,
                        'borderColor' => '#EF4444',
                        'backgroundColor' => 'rgba(239, 68, 68,0.05)',
                        'tension' => 0.3,
                        'fill' => false,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }

    public function getConversionsVsCpaChartConfigProperty(): array
    {
        $data = $this->dashboardData['conversions_vs_cpa'] ?? [];
        $labels = array_map(fn($d) => $d['period'], $data);
        $conversions = array_map(fn($d) => $d['conversions'], $data);
        $cpa = array_map(fn($d) => $d['cpa'], $data);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Conversions',
                        'data' => $conversions,
                        'backgroundColor' => '#787774',
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'CPA (SAR)',
                        'data' => $cpa,
                        'backgroundColor' => '#EF4444',
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }

    public function getEcommerceFunnelChartConfigProperty(): array
    {
        $stages = $this->dashboardData['funnel_data']['stages'] ?? [];
        $labels = array_map(fn($s) => $s['name'], $stages);
        $values = array_map(fn($s) => $s['value'], $stages);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Volume',
                    'data' => $values,
                    'backgroundColor' => ['#EF4444', '#6B6B66', '#EF4444', '#787774'],
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['display' => false],
                    'y' => ['grid' => ['display' => false], 'ticks' => ['font' => ['size' => 11], 'color' => '#3F3F3C']],
                ],
            ],
        ];
    }

    /* ================================================================
       Inline Editing for Ad Accounts & Campaigns
       ================================================================ */

    public function startEditAccount(int $accountId): void
    {
        $accounts = $this->dashboardData['ad_accounts'] ?? [];
        $account = collect($accounts)->firstWhere('id', $accountId);

        if (!$account) {
            return;
        }

        $this->editingAccountId = $accountId;
        $this->editAccountIndustry = $account['industry'] ?? '';
        $this->editAccountCategory = $account['category'] ?? '';
        $this->editAccountSubCategory = $account['sub_industry'] ?? '';
    }

    public function cancelAccountEdit(): void
    {
        $this->editingAccountId = null;
        $this->editAccountIndustry = '';
        $this->editAccountCategory = '';
        $this->editAccountSubCategory = '';
    }

    public function saveAccountEdit(): void
    {
        $account = AdAccount::find($this->editingAccountId);
        if (!$account) {
            $this->cancelAccountEdit();
            return;
        }

        $update = [];
        if ($this->editAccountIndustry !== '') {
            $update['industry'] = $this->editAccountIndustry ?: null;
        }
        if ($this->editAccountCategory !== '') {
            $update['category'] = $this->editAccountCategory ?: null;
        }
        if ($this->editAccountSubCategory !== '') {
            $update['sub_industry'] = $this->editAccountSubCategory ?: null;
        }

        $industryChanged  = isset($update['industry'])  && $update['industry']  !== $account->industry;
        $categoryChanged  = isset($update['category'])  && $update['category']  !== $account->category;
        $subCategoryChanged = isset($update['sub_industry']) && $update['sub_industry'] !== $account->sub_industry;

        // Cascade: clear dependent fields only if they weren't explicitly changed
        if ($industryChanged && !$categoryChanged) {
            $update['category'] = null;
        }
        if (($industryChanged || $categoryChanged) && !$subCategoryChanged) {
            $update['sub_industry'] = null;
        }

        if (!empty($update)) {
            $account->update($update);
        }

        $this->cancelAccountEdit();
        $this->fetchDashboard();
    }

    public function updatedEditAccountIndustry(): void
    {
        $this->editAccountCategory = '';
        $this->editAccountSubCategory = '';
    }

    public function updatedEditAccountCategory(): void
    {
        $this->editAccountSubCategory = '';
    }

    public function updateCampaign(int $campaignId, string $field, $value): void
    {
        $campaign = AdCampaign::find($campaignId);
        if (!$campaign) {
            return;
        }

        $allowedFields = ['objective', 'sub_industry', 'funnel_stage'];
        if (!in_array($field, $allowedFields)) {
            return;
        }

        $campaign->update([$field => $value ?: null]);
        $this->fetchDashboard();
    }

    public function render()
    {
        $industries = Industry::active()->ordered()->pluck('display_name', 'name')->toArray();

        $categories = [];
        $subCategories = [];
        if ($this->editingAccountId) {
            $industry = Industry::where('name', $this->editAccountIndustry)->first();
            if ($industry) {
                $categories = $industry->accountCategories()
                    ->where('is_active', true)
                    ->ordered()
                    ->pluck('display_name')
                    ->toArray();
            }
            if ($this->editAccountCategory) {
                $categoryModel = AccountCategory::where('display_name', $this->editAccountCategory)->first();
                $subCategories = $categoryModel
                    ? $categoryModel->subCategories->mapWithKeys(fn($sub) => [$sub->name => $sub->display_name])->all()
                    : [];
            }
        }

        // Campaign sub-categories come from each account's category sub-categories
        $campaignCategories = [];
        $accounts = $this->dashboardData['ad_accounts'] ?? [];
        foreach ($accounts as $account) {
            $categoryName = $account['category'] ?? null;
            if ($categoryName) {
                $categoryModel = AccountCategory::where('display_name', $categoryName)->first();
                if ($categoryModel) {
                    foreach ($categoryModel->subCategories as $sub) {
                        $campaignCategories[$sub->display_name] = $sub->display_name;
                    }
                }
            }
        }
        ksort($campaignCategories);

        return view('livewire.pages.client-dashboard', [
            'platformChartConfig' => $this->getPlatformChartConfigProperty(),
            'trendChartConfig' => $this->getTrendChartConfigProperty(),
            'campaignsChartConfig' => $this->getCampaignsChartConfigProperty(),
            'periodLabel' => $this->getPeriodLabelProperty(),
            'scTrendChartConfig' => $this->getScTrendChartConfigProperty(),
            'ga4TrendChartConfig' => $this->getGa4TrendChartConfigProperty(),
            'funnelChartConfig' => $this->getFunnelChartConfigProperty(),
            'leadVsCplChartConfig' => $this->getLeadVsCplChartConfigProperty(),
            'leadVsSpentChartConfig' => $this->getLeadVsSpentChartConfigProperty(),
            'revenueVsRoasChartConfig' => $this->getRevenueVsRoasChartConfigProperty(),
            'conversionsVsCpaChartConfig' => $this->getConversionsVsCpaChartConfigProperty(),
            'ecommerceFunnelChartConfig' => $this->getEcommerceFunnelChartConfigProperty(),
            'industries' => $industries,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'campaignCategories' => $campaignCategories,
        ])->layout('layouts.app');
    }
}
