<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdMetric;
use App\Services\Calculators\ObjectiveCalculatorFactory;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function summary(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'objective' => 'required|in:awareness,engagement,traffic,messages,app_installs,in_app_actions,leads,website_sales,retention',
            'account_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'platform' => 'nullable|in:facebook,google,tiktok',
        ]);

        $query = AdMetric::forDateRange($request->from, $request->to);

        if ($request->account_id) {
            $query->forAccount($request->account_id);
        }

        if ($request->campaign_id) {
            $query->forCampaign($request->campaign_id);
        }

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        $metrics = $query->with('adCampaign.adAccount')->get();
        
        $calculator = ObjectiveCalculatorFactory::make($request->objective);
        $kpis = $calculator->calculateKpis($metrics);
        
        $currencies = $metrics->groupBy(function($metric) {
            return $metric->adCampaign->adAccount->account_config['currency'] ?? 'USD';
        });
        
        $primaryCurrency = $currencies->keys()->first() ?? 'USD';

        return response()->json([
            'objective' => $request->objective,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'kpis' => $kpis,
            'primary_kpis' => $calculator->getPrimaryKpis(),
            'secondary_kpis' => $calculator->getSecondaryKpis(),
            'currency' => $primaryCurrency,
            'currency_breakdown' => $currencies->map(function($metrics, $currency) use ($calculator) {
                return [
                    'currency' => $currency,
                    'kpis' => $calculator->calculateKpis($metrics),
                ];
            })->values(),
        ]);
    }

    public function timeseries(Request $request)
    {
        $request->validate([
            'metric' => 'required|in:roas,cpl,cpm,cpc,cvr,cost_per_call,spend,revenue,impressions,clicks,leads,calls',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'objective' => 'required|in:awareness,engagement,traffic,messages,app_installs,in_app_actions,leads,website_sales,retention',
            'group_by' => 'nullable|in:date,campaign,account,platform',
            'account_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'platform' => 'nullable|in:facebook,google,tiktok',
        ]);

        $groupBy = $request->group_by ?? 'date';
        
        $query = AdMetric::forDateRange($request->from, $request->to);

        if ($request->account_id) {
            $query->forAccount($request->account_id);
        }

        if ($request->campaign_id) {
            $query->forCampaign($request->campaign_id);
        }

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        // Group and aggregate data
        $selectFields = $this->getSelectFields($request->metric, $groupBy);
        $groupByField = $this->getGroupByField($groupBy);

        if ($groupBy === 'campaign') {
            $results = $query
                ->with('adCampaign')
                ->selectRaw($selectFields)
                ->groupBy($groupByField)
                ->orderBy($groupByField)
                ->get();
        } else {
            $results = $query
                ->selectRaw($selectFields)
                ->groupBy($groupByField)
                ->orderBy($groupByField)
                ->get();
        }

        // Get currency info for the data
        $metricsForCurrency = $query->with('adCampaign.adAccount')->get();
        $currencies = $metricsForCurrency->groupBy(function($metric) {
            return $metric->adCampaign->adAccount->account_config['currency'] ?? 'USD';
        });
        $primaryCurrency = $currencies->keys()->first() ?? 'USD';

        // Get campaign names if grouping by campaign
        $campaignNames = [];
        if (($request->group_by ?? 'date') === 'campaign') {
            $campaignIds = $results->pluck('campaign_id')->unique();
            $campaigns = \App\Models\AdCampaign::whereIn('id', $campaignIds)->get();
            $campaignNames = $campaigns->keyBy('id')->map(fn($c) => $c->name)->toArray();
        }

        // Calculate KPIs for each group
        $calculator = ObjectiveCalculatorFactory::make($request->objective);
        $timeseriesData = $results->map(function ($row) use ($request, $calculator, $campaignNames) {
            $kpis = $calculator->calculateKpis(collect([$row]));
            
            // Handle raw metrics vs calculated KPIs
            $rawMetrics = [
                'spend' => $row->total_spend ?? 0,
                'impressions' => $row->total_impressions ?? 0,
                'clicks' => $row->total_clicks ?? 0,
                'revenue' => $row->total_revenue ?? 0,
                'leads' => $row->total_leads ?? 0,
                'calls' => $row->total_calls ?? 0,
            ];
            
            // If requesting a raw metric, use raw data; otherwise use calculated KPI
            $value = in_array($request->metric, ['spend', 'impressions', 'clicks', 'revenue', 'leads', 'calls'])
                ? ($rawMetrics[$request->metric] ?? 0)
                : ($kpis[$request->metric] ?? 0);
            
            // Get the appropriate period value based on groupBy
            $period = $row->{$this->getGroupByAlias($request->group_by ?? 'date')};
            
            // If grouping by campaign, use campaign name instead of ID
            if (($request->group_by ?? 'date') === 'campaign' && isset($campaignNames[$period])) {
                $period = $campaignNames[$period];
            }
            
            return [
                'period' => $period,
                'value' => $value,
                'raw_metrics' => $rawMetrics,
            ];
        });

        return response()->json([
            'metric' => $request->metric,
            'objective' => $request->objective,
            'group_by' => $groupBy,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'currency' => $primaryCurrency,
            'data' => $timeseriesData,
        ]);
    }

    public function spendBreakdown(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'account_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'platform' => 'nullable|in:facebook,google,tiktok',
            'group_by' => 'nullable|in:account,campaign',
        ]);

        $groupBy = $request->group_by ?? 'account';
        
        $query = AdMetric::forDateRange($request->from, $request->to);

        if ($request->account_id) {
            $query->forAccount($request->account_id);
        }

        if ($request->campaign_id) {
            $query->forCampaign($request->campaign_id);
        }

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        if ($groupBy === 'campaign') {
            // Group by campaign
            $results = $query
                ->with(['adCampaign.adAccount.integration'])
                ->selectRaw('
                    ad_campaign_id,
                    SUM(spend) as total_spend,
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(leads) as total_leads,
                    SUM(calls) as total_calls,
                    SUM(purchases) as total_purchases,
                    SUM(conversions) as total_conversions,
                    COUNT(DISTINCT date) as days_count
                ')
                ->groupBy('ad_campaign_id')
                ->orderBy('total_spend', 'desc')
                ->get();

            $spendData = $results->map(function ($row) use ($request) {
                $campaign = $row->adCampaign;
                $account = $campaign->adAccount;

                $currency = $account->account_config['currency'] ?? 'USD';

                // Spend is already in SAR in the database - no conversion needed
                $totalSpendSAR = (float) $row->total_spend;
                $dailyAverage = $row->days_count > 0 ? $totalSpendSAR / $row->days_count : 0;

                // Determine results based on campaign name and objective
                $results = $this->determineResults($campaign, $row);
                $costPerResult = $results > 0 ? $totalSpendSAR / $results : 0;

                return [
                    'account_name' => $account->account_name,
                    'campaign_name' => $campaign->name,
                    'platform' => $account->integration->platform,
                    'original_currency' => $currency,
                    'total_spend' => $totalSpendSAR,
                    'daily_average' => $dailyAverage,
                    'results' => (int) $results,
                    'cost_per_result' => $costPerResult,
                ];
            });
        } else {
            // Group by account
            $results = $query
                ->with(['adCampaign.adAccount.integration'])
                ->selectRaw('
                    ad_account_id,
                    SUM(spend) as total_spend,
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(leads) as total_leads,
                    SUM(calls) as total_calls,
                    SUM(purchases) as total_purchases,
                    SUM(conversions) as total_conversions,
                    COUNT(DISTINCT date) as days_count
                ')
                ->groupBy('ad_account_id')
                ->orderBy('total_spend', 'desc')
                ->get();

            $spendData = $results->map(function ($row) use ($request) {
                $account = \App\Models\AdAccount::with('integration')->find($row->ad_account_id);

                $currency = $account->account_config['currency'] ?? 'USD';

                // Spend is already in SAR in the database - no conversion needed
                $totalSpendSAR = (float) $row->total_spend;
                $dailyAverage = $row->days_count > 0 ? $totalSpendSAR / $row->days_count : 0;

                // Determine results based on account objective
                $results = $this->determineResultsForAccount($account, $row);
                $costPerResult = $results > 0 ? $totalSpendSAR / $results : 0;

                return [
                    'account_name' => $account->account_name,
                    'platform' => $account->integration->platform,
                    'original_currency' => $currency,
                    'total_spend' => $totalSpendSAR,
                    'daily_average' => $dailyAverage,
                    'results' => (int) $results,
                    'cost_per_result' => $costPerResult,
                ];
            });
        }

        return response()->json([
            'group_by' => $groupBy,
            'date_range' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
            'currency' => 'SAR',
            'currency_note' => 'All spend amounts are converted to SAR',
            'data' => $spendData,
        ]);
    }

    private function getSelectFields(string $metric, string $groupBy): string
    {
        $groupByField = $this->getGroupByField($groupBy);
        $groupByAlias = $this->getGroupByAlias($groupBy);

        return "{$groupByField} as {$groupByAlias}, " .
               "SUM(spend) as total_spend, " .
               "SUM(impressions) as total_impressions, " .
               "SUM(reach) as total_reach, " .
               "SUM(clicks) as total_clicks, " .
               "SUM(video_views) as total_video_views, " .
               "SUM(conversions) as total_conversions, " .
               "SUM(revenue) as total_revenue, " .
               "SUM(purchases) as total_purchases, " .
               "SUM(leads) as total_leads, " .
               "SUM(calls) as total_calls, " .
               "SUM(sessions) as total_sessions, " .
               "SUM(atc) as total_atc";
    }

    private function getGroupByField(string $groupBy): string
    {
        return match ($groupBy) {
            'date' => 'date',
            'campaign' => 'ad_campaign_id',
            'account' => 'ad_account_id',
            'platform' => 'platform',
            default => 'date',
        };
    }

    private function getGroupByAlias(string $groupBy): string
    {
        return match ($groupBy) {
            'date' => 'date',
            'campaign' => 'campaign_id',
            'account' => 'account_id',
            'platform' => 'platform',
            default => 'date',
        };
    }

    private function determineResults($campaign, $row): int
    {
        // Check campaign name for objective hints
        $campaignName = strtolower($campaign->name);
        
        if (strpos($campaignName, 'whatsapp') !== false || strpos($campaignName, 'message') !== false) {
            // WhatsApp/messaging campaigns - use conversions (messages)
            return (int) ($row->total_conversions ?? 0);
        } elseif (strpos($campaignName, 'leadgen') !== false || strpos($campaignName, 'lead') !== false) {
            // Lead generation campaigns - use leads
            return (int) ($row->total_leads ?? 0);
        } elseif (strpos($campaignName, 'purchase') !== false || strpos($campaignName, 'sales') !== false) {
            // Sales campaigns - use purchases
            return (int) ($row->total_purchases ?? 0);
        } elseif (strpos($campaignName, 'call') !== false) {
            // Call campaigns - use calls
            return (int) ($row->total_calls ?? 0);
        }
        
        // Default fallback based on campaign objective
        switch ($campaign->objective) {
            case 'leads':
                return (int) ($row->total_leads ?? 0);
            case 'sales':
                return (int) ($row->total_purchases ?? 0);
            case 'calls':
                return (int) ($row->total_calls ?? 0);
            default:
                // For awareness campaigns or unclear objectives, prioritize meaningful actions
                return (int) ($row->total_leads ?: ($row->total_purchases ?: ($row->total_calls ?: $row->total_conversions)));
        }
    }

    private function determineResultsForAccount($account, $row): int
    {
        // For account-level aggregation, sum all meaningful results
        $leads = (int) ($row->total_leads ?? 0);
        $purchases = (int) ($row->total_purchases ?? 0);
        $calls = (int) ($row->total_calls ?? 0);
        $conversions = (int) ($row->total_conversions ?? 0);
        
        // Return the sum of all conversion types
        return $leads + $purchases + $calls + $conversions;
    }
}
