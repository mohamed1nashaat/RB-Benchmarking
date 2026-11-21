<?php

namespace App\Livewire\Pages;

use App\Models\AccountCategory;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CampaignMetrics extends Component
{
    public int $campaignId;

    // Data
    public ?array $campaign = null;
    public ?array $adAccount = null;
    public array $metrics = [];
    public array $dailyData = [];
    public array $availableSubIndustries = [];

    // Editable
    public string $editSubIndustry = '';

    // Date filter
    public string $filterStartDate = '';
    public string $filterEndDate = '';

    // State
    public bool $loading = true;
    public string $error = '';

    protected CurrencyConversionService $currencyService;

    public function boot(CurrencyConversionService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(int $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $this->loading = true;
        $this->error = '';

        try {
            // Load campaign
            $campaign = AdCampaign::with(['adAccount.integration'])->find($this->campaignId);
            if (!$campaign) {
                $this->error = 'Campaign not found.';
                $this->loading = false;
                return;
            }

            $account = $campaign->adAccount;
            $accountCurrency = $account->account_config['currency'] ?? $account->currency ?? 'USD';

            $this->campaign = [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'external_campaign_id' => $campaign->external_campaign_id,
                'objective' => $campaign->objective,
                'sub_industry' => $campaign->sub_industry,
                'funnel_stage' => $campaign->funnel_stage,
                'user_journey' => $campaign->user_journey,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at?->toISOString(),
            ];

            $this->editSubIndustry = $campaign->sub_industry ?? '';

            $this->adAccount = [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'platform' => $account->integration->platform ?? 'unknown',
                'industry' => $account->industry,
                'category' => $account->category,
                'currency' => $accountCurrency,
            ];

            // Build metrics query with date filter
            $metricsQuery = $campaign->adMetrics();
            if ($this->filterStartDate && $this->filterEndDate) {
                $metricsQuery->whereBetween('date', [$this->filterStartDate, $this->filterEndDate]);
            }

            $aggregated = (clone $metricsQuery)->selectRaw('
                SUM(spend) as spend,
                SUM(impressions) as impressions,
                SUM(reach) as reach,
                SUM(clicks) as clicks,
                SUM(conversions) as conversions,
                SUM(revenue) as revenue,
                SUM(leads) as leads,
                SUM(video_views) as video_views,
                SUM(calls) as calls,
                SUM(purchases) as purchases
            ')->first();

            $spend = (float) ($aggregated->spend ?? 0);
            $impressions = (int) ($aggregated->impressions ?? 0);
            $clicks = (int) ($aggregated->clicks ?? 0);
            $conversions = (int) ($aggregated->conversions ?? 0);
            $leads = (int) ($aggregated->leads ?? 0);
            $reach = (int) ($aggregated->reach ?? 0);
            $revenue = (float) ($aggregated->revenue ?? 0);

            // Convert spend and revenue to SAR
            $spendSAR = $this->currencyService->convertToSAR($spend, $accountCurrency);
            $revenueSAR = $this->currencyService->convertToSAR($revenue, $accountCurrency);

            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $cpc = $clicks > 0 ? $spendSAR / $clicks : 0;
            $cpm = $impressions > 0 ? ($spendSAR / ($impressions / 1000)) : 0;
            $cvr = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $frequency = $reach > 0 ? $impressions / $reach : 0;

            $this->metrics = [
                'spend' => $spendSAR,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'leads' => $leads,
                'reach' => $reach,
                'revenue' => $revenueSAR,
                'ctr' => round($ctr, 2),
                'cpc' => round($cpc, 2),
                'cpm' => round($cpm, 2),
                'cvr' => round($cvr, 2),
                'roas' => round($roas, 2),
                'frequency' => round($frequency, 1),
            ];

            // Daily data for chart
            $this->dailyData = (clone $metricsQuery)
                ->select(
                    'date',
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(conversions) as conversions'),
                    DB::raw('SUM(leads) as leads')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($row) use ($accountCurrency) {
                    return [
                        'date' => $row->date->format('Y-m-d'),
                        'spend' => $this->currencyService->convertToSAR((float) $row->spend, $accountCurrency),
                        'impressions' => (int) $row->impressions,
                        'clicks' => (int) $row->clicks,
                        'conversions' => (int) $row->conversions,
                        'leads' => (int) $row->leads,
                    ];
                })
                ->toArray();

            // Load sub-industries
            $this->loadSubIndustries();

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->metrics = [];
        } finally {
            $this->loading = false;
        }
    }

    private function loadSubIndustries(): void
    {
        $categoryName = $this->adAccount['category'] ?? null;
        if (!$categoryName) {
            $this->availableSubIndustries = [];
            return;
        }

        $category = AccountCategory::where('display_name', $categoryName)->first();
        if ($category) {
            $this->availableSubIndustries = $category->subCategories()
                ->where('is_active', true)
                ->pluck('display_name')
                ->toArray();
        }
    }

    public function updateCampaignDetails(): void
    {
        $campaign = AdCampaign::find($this->campaignId);
        if (!$campaign) {
            return;
        }

        $campaign->update([
            'sub_industry' => $this->editSubIndustry ?: null,
        ]);

        if ($this->campaign) {
            $this->campaign['sub_industry'] = $this->editSubIndustry;
        }

        session()->flash('message', __('pages.campaign_metrics.updated'));
    }

    public function setDateRange($from, $to): void
    {
        $this->filterStartDate = $from;
        $this->filterEndDate = $to;
        $this->loadMetrics();
    }

    public function applyDateFilter(): void
    {
        $this->loadMetrics();
    }

    public function clearDateFilter(): void
    {
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->loadMetrics();
    }

    public function getHasMetricsDataProperty(): bool
    {
        if (empty($this->metrics)) {
            return false;
        }
        return ($this->metrics['spend'] ?? 0) > 0
            || ($this->metrics['impressions'] ?? 0) > 0
            || ($this->metrics['clicks'] ?? 0) > 0;
    }

    public function render()
    {
        return view('livewire.pages.campaign-metrics')
            ->layout('layouts.app');
    }
}
