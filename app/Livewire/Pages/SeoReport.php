<?php

namespace App\Livewire\Pages;

use App\Models\Integration;
use App\Models\Tenant;
use App\Services\GA4AnalyticsService;
use App\Services\PageSpeedService;
use App\Services\SearchConsoleService;
use Livewire\Component;

class SeoReport extends Component
{
    public int $clientId;
    public ?array $reportData = null;
    public ?array $clientData = null;
    public int $period = 30;
    public bool $loading = true;
    public string $error = '';

    public function mount(int $id)
    {
        $this->clientId = $id;
        $this->loadReport();
    }

    public function setPeriod(int $days)
    {
        $this->period = $days;
        $this->loadReport();
    }

    public function loadReport()
    {
        $this->loading = true;
        $this->error = '';

        try {
            $client = Tenant::findOrFail($this->clientId);

            $this->clientData = [
                'name' => $client->name,
                'industry' => $client->industry,
                'website' => $client->website,
                'logo' => $client->logo ?? null,
            ];

            $settings = $client->settings ?? [];
            $seo = $settings['seo'] ?? [];

            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays($this->period)->format('Y-m-d');

            $integration = Integration::query()
                ->where('tenant_id', $client->id)
                ->where('platform', 'google')
                ->where('status', 'active')
                ->first();

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

            // PageSpeed
            $pageSpeedUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);
            if ($pageSpeedUrl) {
                try {
                    $pagespeed = app(PageSpeedService::class);
                    $report['pagespeed_mobile'] = $pagespeed->analyze($pageSpeedUrl, 'mobile');
                    $report['pagespeed_desktop'] = $pagespeed->analyze($pageSpeedUrl, 'desktop');
                } catch (\Exception $e) {
                    $report['pagespeed_mobile'] = null;
                    $report['pagespeed_desktop'] = null;
                }
            }

            $report['period'] = $this->period;
            $report['start_date'] = $startDate;
            $report['end_date'] = $endDate;
            $report['seo_settings'] = $seo;

            $this->reportData = $report;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.pages.seo-report')
            ->layout('layouts.blank', ['title' => ($this->clientData['name'] ?? 'Client') . ' - SEO Report']);
    }
}
