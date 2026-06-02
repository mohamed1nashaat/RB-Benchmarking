<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\AdMetric;
use App\Models\AdCreative;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientPerformanceReportService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    private function getDateRange(array $filters): array
    {
        $startDate = null;
        $endDate = null;
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $startDate = Carbon::parse($filters['from'])->startOfDay();
            $endDate = Carbon::parse($filters['to'])->endOfDay();
        } elseif (!empty($filters['period'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays($filters['period'])->startOfDay();
        }
        return [$startDate, $endDate];
    }

    private function getFilteredAccountIds(Tenant $client, array $filters): \Illuminate\Support\Collection
    {
        $query = $client->adAccounts();
        if (!empty($filters['platform'])) {
            $query->whereHas('integration', function ($q) use ($filters) {
                $q->where('platform', $filters['platform']);
            });
        }
        return $query->pluck('id');
    }

    public function getSummaryMetrics(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds);

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $agg = $query->selectRaw('
            SUM(m.spend) as total_spend,
            SUM(m.impressions) as total_impressions,
            SUM(m.clicks) as total_clicks,
            SUM(m.link_clicks) as total_link_clicks,
            SUM(m.blended_leads) as total_blended_leads,
            SUM(m.instant_leads) + SUM(m.sign_ups) + SUM(m.native_leads) as total_instant_leads,
            SUM(m.native_leads) as total_native_leads,
            SUM(m.sign_ups) as total_sign_ups,
            SUM(m.conversions) as total_conversions,
            SUM(m.revenue) as total_revenue,
            COUNT(DISTINCT m.ad_campaign_id) as total_campaigns
        ')->first();

        $totalSpend = (float) ($agg->total_spend ?? 0);
        $totalImpressions = (int) ($agg->total_impressions ?? 0);
        $totalLinkClicks = (int) ($agg->total_link_clicks ?? 0);
        $totalBlendedLeads = (int) ($agg->total_blended_leads ?? 0);
        $totalCampaigns = (int) ($agg->total_campaigns ?? 0);
        $totalConversions = (int) ($agg->total_conversions ?? 0);
        $totalRevenue = (float) ($agg->total_revenue ?? 0);

        $cpl = $totalBlendedLeads > 0 ? $totalSpend / $totalBlendedLeads : 0;
        $cpm = $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0;
        $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
        $aov = $totalConversions > 0 ? $totalRevenue / $totalConversions : 0;
        $cpa = $totalConversions > 0 ? $totalSpend / $totalConversions : 0;

        return [
            'total_blended_spent' => round($totalSpend, 2),
            'total_blended_leads' => $totalBlendedLeads,
            'total_campaigns' => $totalCampaigns,
            'total_instant_leads' => (int) ($agg->total_instant_leads ?? 0),
            'total_impressions' => $totalImpressions,
            'cpl' => round($cpl, 2),
            'cpm' => round($cpm, 2),
            'total_link_clicks' => $totalLinkClicks,
            'total_conversions' => $totalConversions,
            'total_revenue' => round($totalRevenue, 2),
            'roas' => round($roas, 2),
            'aov' => round($aov, 2),
            'cpa' => round($cpa, 2),
        ];
    }

    public function getFunnelData(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics')
            ->whereIn('ad_account_id', $accountIds);
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $agg = $query->selectRaw('
            SUM(impressions) as impressions,
            SUM(link_clicks) as link_clicks,
            SUM(blended_leads) as leads,
            SUM(conversions) as conversions
        ')->first();

        $impressions = (int) ($agg->impressions ?? 0);
        $linkClicks = (int) ($agg->link_clicks ?? 0);
        $leads = (int) ($agg->leads ?? 0);
        $conversions = (int) ($agg->conversions ?? 0);

        return [
            'stages' => [
                ['name' => 'Impressions', 'value' => $impressions, 'pct' => 100],
                ['name' => 'Link Clicks', 'value' => $linkClicks, 'pct' => $impressions > 0 ? round(($linkClicks / $impressions) * 100, 2) : 0],
                ['name' => 'Leads', 'value' => $leads, 'pct' => $impressions > 0 ? round(($leads / $impressions) * 100, 2) : 0],
                ['name' => 'Conversions', 'value' => $conversions, 'pct' => $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0],
            ]
        ];
    }

    public function getLeadVsCplTrend(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds)
            ->selectRaw('
                DATE(m.date) as date,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.blended_leads) as leads
            ')
            ->groupBy('date', 'a.currency')
            ->orderBy('date');

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $rows = $query->get()->groupBy('date')->map(function ($dayRows) {
            $spend = 0;
            $leads = 0;
            foreach ($dayRows as $row) {
                $currency = $row->currency ?? 'USD';
                $spend += $this->currencyService->convertToSAR((float) $row->spend, $currency);
                $leads += (int) $row->leads;
            }
            return [
                'date' => $dayRows->first()->date,
                'leads' => $leads,
                'cpl' => $leads > 0 ? round($spend / $leads, 2) : 0,
            ];
        })->values();

        return $rows->toArray();
    }

    public function getLeadVsSpentTrend(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds)
            ->selectRaw('
                DATE_FORMAT(m.date, "%Y-%m") as period,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.blended_leads) as leads
            ')
            ->groupBy('period', 'a.currency')
            ->orderBy('period');

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $rows = $query->get()->groupBy('period')->map(function ($periodRows) {
            $spend = 0;
            $leads = 0;
            foreach ($periodRows as $row) {
                $currency = $row->currency ?? 'USD';
                $spend += $this->currencyService->convertToSAR((float) $row->spend, $currency);
                $leads += (int) $row->leads;
            }
            return [
                'period' => $periodRows->first()->period,
                'leads' => $leads,
                'spend' => round($spend, 2),
            ];
        })->values();

        return $rows->toArray();
    }

    public function getRevenueVsRoasTrend(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds)
            ->selectRaw('
                DATE(m.date) as date,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.revenue) as revenue
            ')
            ->groupBy('date', 'a.currency')
            ->orderBy('date');

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $rows = $query->get()->groupBy('date')->map(function ($dayRows) {
            $spend = 0;
            $revenue = 0;
            foreach ($dayRows as $row) {
                $currency = $row->currency ?? 'USD';
                $spend += $this->currencyService->convertToSAR((float) $row->spend, $currency);
                $revenue += $this->currencyService->convertToSAR((float) $row->revenue, $currency);
            }
            return [
                'date' => $dayRows->first()->date,
                'revenue' => round($revenue, 2),
                'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0,
            ];
        })->values();

        return $rows->toArray();
    }

    public function getConversionsVsCpaTrend(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds)
            ->selectRaw('
                DATE_FORMAT(m.date, "%Y-%m") as period,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.conversions) as conversions
            ')
            ->groupBy('period', 'a.currency')
            ->orderBy('period');

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $rows = $query->get()->groupBy('period')->map(function ($periodRows) {
            $spend = 0;
            $conversions = 0;
            foreach ($periodRows as $row) {
                $currency = $row->currency ?? 'USD';
                $spend += $this->currencyService->convertToSAR((float) $row->spend, $currency);
                $conversions += (int) $row->conversions;
            }
            return [
                'period' => $periodRows->first()->period,
                'conversions' => $conversions,
                'cpa' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
            ];
        })->values();

        return $rows->toArray();
    }

    public function getEcommerceFunnelData(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics')
            ->whereIn('ad_account_id', $accountIds);
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $agg = $query->selectRaw('
            SUM(impressions) as impressions,
            SUM(link_clicks) as link_clicks,
            SUM(clicks) as clicks,
            SUM(conversions) as conversions
        ')->first();

        $impressions = (int) ($agg->impressions ?? 0);
        $linkClicks = (int) ($agg->link_clicks ?? 0);
        $clicks = (int) ($agg->clicks ?? 0);
        $conversions = (int) ($agg->conversions ?? 0);

        return [
            'stages' => [
                ['name' => 'Impressions', 'value' => $impressions, 'pct' => 100],
                ['name' => 'Clicks', 'value' => $clicks, 'pct' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0],
                ['name' => 'Link Clicks', 'value' => $linkClicks, 'pct' => $impressions > 0 ? round(($linkClicks / $impressions) * 100, 2) : 0],
                ['name' => 'Purchases', 'value' => $conversions, 'pct' => $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0],
            ]
        ];
    }

    public function getPlatformBreakdown(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereIn('m.ad_account_id', $accountIds)
            ->selectRaw('
                m.platform,
                a.currency,
                SUM(m.spend) as spend,
                SUM(m.impressions) as impressions,
                SUM(m.clicks) as clicks,
                SUM(m.link_clicks) as link_clicks,
                SUM(m.swipes) as swipes,
                SUM(m.blended_leads) as blended_leads,
                SUM(m.instant_leads) as instant_leads,
                SUM(m.native_leads) as native_leads,
                SUM(m.sign_ups) as sign_ups,
                SUM(m.website_leads) as website_leads,
                SUM(m.on_facebook_leads) as on_facebook_leads,
                SUM(m.conversions) as conversions,
                SUM(m.revenue) as revenue,
                COUNT(DISTINCT m.ad_account_id) as accounts_count
            ')
            ->groupBy('m.platform', 'a.currency');

        if ($startDate && $endDate) {
            $query->whereBetween('m.date', [$startDate, $endDate]);
        }

        $rows = $query->get();

        $grouped = $rows->groupBy('platform')->map(function ($platformRows, $platform) {
            $spend = 0;
            $impressions = 0;
            $clicks = 0;
            $linkClicks = 0;
            $swipes = 0;
            $blendedLeads = 0;
            $instantLeads = 0;
            $nativeLeads = 0;
            $signUps = 0;
            $websiteLeads = 0;
            $onFacebookLeads = 0;
            $conversions = 0;
            $revenue = 0;
            $accountsCount = 0;

            foreach ($platformRows as $row) {
                $currency = $row->currency ?? 'USD';
                $spend += $this->currencyService->convertToSAR((float) $row->spend, $currency);
                $revenue += $this->currencyService->convertToSAR((float) $row->revenue, $currency);
                $impressions += (int) $row->impressions;
                $clicks += (int) $row->clicks;
                $linkClicks += (int) $row->link_clicks;
                $swipes += (int) $row->swipes;
                $blendedLeads += (int) $row->blended_leads;
                $instantLeads += (int) $row->instant_leads;
                $nativeLeads += (int) $row->native_leads;
                $signUps += (int) $row->sign_ups;
                $websiteLeads += (int) $row->website_leads;
                $onFacebookLeads += (int) $row->on_facebook_leads;
                $conversions += (int) $row->conversions;
                $accountsCount = max($accountsCount, (int) $row->accounts_count);
            }

            $ctr = $impressions > 0 ? ($linkClicks / $impressions) * 100 : 0;
            $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;
            $cpl = $blendedLeads > 0 ? $spend / $blendedLeads : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $aov = $conversions > 0 ? $revenue / $conversions : 0;
            $cpa = $conversions > 0 ? $spend / $conversions : 0;

            return [
                'platform' => $platform,
                'spend' => round($spend, 2),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'link_clicks' => $linkClicks,
                'swipes' => $swipes,
                'blended_leads' => $blendedLeads,
                'instant_leads' => $instantLeads,
                'native_leads' => $nativeLeads,
                'sign_ups' => $signUps,
                'website_leads' => $websiteLeads,
                'on_facebook_leads' => $onFacebookLeads,
                'conversions' => $conversions,
                'revenue' => round($revenue, 2),
                'accounts_count' => $accountsCount,
                'ctr' => round($ctr, 2),
                'cpm' => round($cpm, 2),
                'cpl' => round($cpl, 2),
                'roas' => round($roas, 2),
                'aov' => round($aov, 2),
                'cpa' => round($cpa, 2),
            ];
        });

        return $grouped->values()->toArray();
    }

    public function getTopAdsByPlatform(Tenant $client, string $platform, int $limit = 6, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        // Aggregate metrics per campaign for the period
        $query = DB::table('ad_metrics')
            ->whereIn('ad_account_id', $accountIds)
            ->where('platform', $platform)
            ->selectRaw('
                ad_campaign_id,
                SUM(spend) as spend,
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(link_clicks) as link_clicks,
                SUM(blended_leads) as blended_leads,
                SUM(conversions) as conversions,
                SUM(revenue) as revenue
            ')
            ->groupBy('ad_campaign_id')
            ->orderByDesc('spend')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $campaignStats = $query->get()->keyBy('ad_campaign_id');
        if ($campaignStats->isEmpty()) {
            return [];
        }

        $campaignIds = $campaignStats->keys()->toArray();

        // Load campaign names for fallback when no creative exists
        $campaignsData = DB::table('ad_campaigns')
            ->whereIn('id', $campaignIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');
        $campaignNames = $campaignsData->pluck('name', 'id')->toArray();

        // Load creatives linked to these campaigns, plus unlinked creatives from same accounts
        $linkedCreatives = AdCreative::whereIn('ad_campaign_id', $campaignIds)
            ->where('platform', $platform)
            ->get()
            ->groupBy('ad_campaign_id');

        $unlinkedCreatives = AdCreative::whereNull('ad_campaign_id')
            ->whereIn('ad_account_id', $accountIds)
            ->where('platform', $platform)
            ->get();

        $result = [];
        foreach ($campaignStats as $campaignId => $stats) {
            $creative = $linkedCreatives->get($campaignId)?->first();
            if (!$creative && $unlinkedCreatives->isNotEmpty()) {
                $creative = $unlinkedCreatives->shift();
            }
            $impressions = (int) ($stats->impressions ?? 0);
            $clicks = (int) ($stats->link_clicks ?? $stats->clicks ?? 0);
            $leads = (int) ($stats->blended_leads ?? 0);
            $spend = (float) ($stats->spend ?? 0);
            $conversions = (int) ($stats->conversions ?? 0);
            $revenue = (float) ($stats->revenue ?? 0);

            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $cpl = $leads > 0 ? $spend / $leads : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $aov = $conversions > 0 ? $revenue / $conversions : 0;
            $cpa = $conversions > 0 ? $spend / $conversions : 0;

            $previewText = null;
            if ($creative) {
                if ($platform === 'google' && !empty($creative->metadata)) {
                    $headlines = $creative->metadata['headlines'] ?? [];
                    $descriptions = $creative->metadata['descriptions'] ?? [];
                    $h = $headlines[0] ?? '';
                    $d = $descriptions[0] ?? '';
                    if ($h && $d) {
                        $previewText = $h . ' — ' . $d;
                    } else {
                        $previewText = $h ?: $d ?: $creative->name;
                    }
                } else {
                    $previewText = $creative->name;
                }
            } else {
                // No creative found — use campaign name as text fallback
                $previewText = $campaignNames[$campaignId] ?? null;
            }

            $campaignName = $campaignNames[$campaignId] ?? null;
            $adName = $creative?->name ?? $campaignName ?? 'Campaign ' . $campaignId;

            // For Google text ads, disambiguate by campaign name when names are generic
            if ($platform === 'google' && $campaignName && $adName === $campaignName) {
                $adName = $campaignName;
            }

            $result[] = [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'name' => $adName,
                'thumbnail_url' => $creative?->thumbnail_url,
                'image_url' => $creative?->image_url,
                'video_url' => $creative?->video_url,
                'preview_text' => $previewText,
                'spend' => round($spend, 2),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'leads' => $leads,
                'conversions' => $conversions,
                'revenue' => round($revenue, 2),
                'ctr' => round($ctr, 2),
                'cpl' => round($cpl, 2),
                'roas' => round($roas, 2),
                'aov' => round($aov, 2),
                'cpa' => round($cpa, 2),
            ];
        }

        return $result;
    }

    public function getTeadsDailyTable(Tenant $client, array $filters = []): array
    {
        $accountIds = $this->getFilteredAccountIds($client, $filters);
        [$startDate, $endDate] = $this->getDateRange($filters);

        $query = DB::table('ad_metrics')
            ->whereIn('ad_account_id', $accountIds)
            ->where('platform', 'teads')
            ->selectRaw('
                DATE(date) as day,
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(spend) as spend
            ')
            ->groupBy('day')
            ->orderBy('day');

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $totalImpressions = $rows->sum('impressions');
        $totalClicks = $rows->sum('clicks');
        $totalSpend = $rows->sum('spend');

        return [
            'rows' => $rows->toArray(),
            'summary' => [
                'impressions' => $totalImpressions,
                'clicks' => $totalClicks,
                'spend' => round($totalSpend, 2),
            ],
        ];
    }
}
