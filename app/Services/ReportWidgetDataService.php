<?php

namespace App\Services;

use App\Models\AdMetric;
use App\Models\AdCampaign;
use App\Models\IndustryBenchmark;
use App\Models\Report;
use Illuminate\Support\Facades\Cache;

class ReportWidgetDataService
{
    public function dataForReport(Report $report): array
    {
        $cacheKey = "report_widget_data_{$report->id}_{$report->updated_at->timestamp}";

        return Cache::remember($cacheKey, 300, function () use ($report) {
            return [
                'kpis'            => $this->kpis($report),
                'trend'           => $this->trend($report),
                'platform_split'  => $this->platformSplit($report),
                'campaigns'       => $this->campaigns($report),
                'benchmarks'      => $this->benchmarks($report),
            ];
        });
    }

    public function kpis(Report $report): array
    {
        $base = $this->baseQuery($report);

        $agg = $base->selectRaw('
            SUM(spend)       as spend,
            SUM(impressions) as impressions,
            SUM(clicks)      as clicks,
            SUM(leads)       as leads,
            SUM(conversions) as conversions,
            SUM(reach)       as reach,
            SUM(video_views) as video_views,
            COUNT(DISTINCT ad_campaign_id) as campaigns
        ')->first();

        $spend       = (float) ($agg->spend ?? 0);
        $impressions = (int)   ($agg->impressions ?? 0);
        $clicks      = (int)   ($agg->clicks ?? 0);
        $leads       = (int)   ($agg->leads ?? 0);
        $conversions = (int)   ($agg->conversions ?? 0);
        $reach       = (int)   ($agg->reach ?? 0);
        $videoViews  = (int)   ($agg->video_views ?? 0);
        $campaigns   = (int)   ($agg->campaigns ?? 0);

        $ctr  = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $cpc  = $clicks > 0      ? round($spend / $clicks, 2) : 0;
        $cpm  = $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0;
        $cpl  = $leads > 0       ? round($spend / $leads, 2) : 0;
        $cvr  = $clicks > 0 && $conversions > 0 ? round(($conversions / $clicks) * 100, 2) : 0;
        $cpa  = $conversions > 0 ? round($spend / $conversions, 2) : 0;
        $freq = $reach > 0 ? round($impressions / $reach, 2) : 0;
        $vtr  = $impressions > 0 && $videoViews > 0 ? round(($videoViews / $impressions) * 100, 2) : 0;

        return compact(
            'spend', 'impressions', 'clicks', 'leads', 'conversions',
            'reach', 'videoViews', 'campaigns',
            'ctr', 'cpc', 'cpm', 'cpl', 'cvr', 'cpa', 'freq', 'vtr'
        );
    }

    public function trend(Report $report): array
    {
        $rows = $this->baseQuery($report)
            ->selectRaw('
                DATE(date) as day,
                SUM(spend)       as spend,
                SUM(impressions) as impressions,
                SUM(clicks)      as clicks,
                SUM(leads)       as leads
            ')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $labels = [];
        $spend  = [];
        $clicks = [];
        $leads  = [];
        $impr   = [];

        foreach ($rows as $row) {
            $labels[] = \Carbon\Carbon::parse($row->day)->format('d M');
            $spend[]  = round((float) $row->spend, 0);
            $clicks[] = (int) $row->clicks;
            $leads[]  = (int) $row->leads;
            $impr[]   = (int) $row->impressions;
        }

        return compact('labels', 'spend', 'clicks', 'leads', 'impr');
    }

    public function platformSplit(Report $report): array
    {
        $rows = $this->baseQuery($report)
            ->selectRaw('platform, SUM(spend) as spend, SUM(leads) as leads, SUM(clicks) as clicks')
            ->groupBy('platform')
            ->orderByDesc('spend')
            ->get();

        $totalSpend = $rows->sum('spend') ?: 1;
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'platform' => $row->platform,
                'spend'    => round((float) $row->spend, 0),
                'leads'    => (int) $row->leads,
                'clicks'   => (int) $row->clicks,
                'pct'      => round(($row->spend / $totalSpend) * 100),
            ];
        }

        return $result;
    }

    public function campaigns(Report $report): array
    {
        $rows = $this->baseQuery($report)
            ->selectRaw('
                ad_campaign_id,
                platform,
                SUM(spend)       as spend,
                SUM(impressions) as impressions,
                SUM(clicks)      as clicks,
                SUM(leads)       as leads
            ')
            ->groupBy('ad_campaign_id', 'platform')
            ->orderByDesc('spend')
            ->limit(15)
            ->get();

        // Load campaign names
        $campaignIds = $rows->pluck('ad_campaign_id')->filter()->all();
        $names = $campaignIds
            ? AdCampaign::whereIn('id', $campaignIds)->pluck('name', 'id')
            : collect();

        $result = [];
        foreach ($rows as $row) {
            $spend       = (float) $row->spend;
            $impressions = (int)   $row->impressions;
            $clicks      = (int)   $row->clicks;
            $leads       = (int)   $row->leads;

            $result[] = [
                'name'        => $names[$row->ad_campaign_id] ?? ('Campaign #' . $row->ad_campaign_id),
                'platform'    => $row->platform,
                'spend'       => $spend,
                'impressions' => $impressions,
                'clicks'      => $clicks,
                'leads'       => $leads,
                'ctr'         => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpl'         => $leads > 0 ? round($spend / $leads, 2) : 0,
            ];
        }

        return $result;
    }

    public function benchmarks(Report $report): array
    {
        if (!$report->industry || !$report->platform) {
            return [];
        }

        return IndustryBenchmark::where('industry', $report->industry)
            ->where('platform', $report->platform)
            ->get()
            ->mapWithKeys(fn ($bm) => [$bm->metric => [
                'p25' => $bm->percentile_25,
                'p50' => $bm->percentile_50,
                'p75' => $bm->percentile_75,
            ]])
            ->all();
    }

    private function baseQuery(Report $report)
    {
        $q = AdMetric::where('tenant_id', $report->tenant_id)
            ->whereBetween('date', [$report->period_start, $report->period_end]);

        if ($report->ad_account_id) {
            $q->where('ad_account_id', $report->ad_account_id);
        }
        if ($report->platform) {
            $q->where('platform', $report->platform);
        }

        return $q;
    }
}
