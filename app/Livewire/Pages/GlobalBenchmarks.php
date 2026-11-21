<?php

namespace App\Livewire\Pages;

use App\Models\IndustryBenchmark;
use Livewire\Component;

class GlobalBenchmarks extends Component
{
    public string $selectedPlatform = 'blended';
    public string $selectedIndustry = '';

    public array $platforms = [
        'blended'   => 'All Platforms',
        'facebook'  => 'Facebook / Meta',
        'google'    => 'Google Ads',
        'tiktok'    => 'TikTok',
        'linkedin'  => 'LinkedIn',
        'snapchat'  => 'Snapchat',
    ];

    public array $metrics = ['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'roas'];

    public array $metricLabels = [
        'ctr'  => 'CTR',
        'cpc'  => 'CPC',
        'cpm'  => 'CPM',
        'cvr'  => 'CVR',
        'cpl'  => 'CPL',
        'cpa'  => 'CPA',
        'roas' => 'ROAS',
    ];

    public array $industryLabels = [
        'automotive'                => 'Automotive',
        'e_commerce_retail'         => 'E-commerce & Retail',
        'education'                 => 'Education',
        'fashion_luxury'            => 'Fashion & Luxury',
        'finance_insurance'         => 'Finance & Insurance',
        'fmcg_cpg'                  => 'FMCG / CPG',
        'food_beverage'             => 'Food & Beverage',
        'government_public_sector'  => 'Government & Public Sector',
        'healthcare'                => 'Healthcare',
        'hospitality_food_services' => 'Hospitality & Food Services',
        'professional_services'     => 'Professional Services',
        'real_estate'               => 'Real Estate',
        'technology'                => 'Technology',
        'travel_destinations'       => 'Travel & Destinations',
    ];

    public function mount(): void
    {
        $this->selectedPlatform = 'blended';
        $this->selectedIndustry = '';
    }

    public function updatedSelectedPlatform(): void {}
    public function updatedSelectedIndustry(): void {}

    public function getTableDataProperty(): array
    {
        if ($this->selectedPlatform === 'blended') {
            return $this->getBlendedTableData();
        }

        $query = IndustryBenchmark::query()
            ->where('platform', $this->selectedPlatform)
            ->whereIn('metric', $this->metrics);

        if ($this->selectedIndustry !== '') {
            $query->where('industry', $this->selectedIndustry);
        }

        $rows = $query->get();

        $data = [];
        foreach ($rows as $row) {
            $data[$row->industry][$row->metric] = $row;
        }

        return $data;
    }

    private function getBlendedTableData(): array
    {
        $query = IndustryBenchmark::query()
            ->whereIn('metric', $this->metrics);

        if ($this->selectedIndustry !== '') {
            $query->where('industry', $this->selectedIndustry);
        }

        $rows = $query->get();

        // Group by industry+metric and average the percentiles across platforms
        $sums = [];
        $counts = [];

        foreach ($rows as $row) {
            $key = $row->industry . '|' . $row->metric;
            if (!isset($sums[$key])) {
                $sums[$key] = [
                    'industry' => $row->industry,
                    'metric'   => $row->metric,
                    'p25'      => 0,
                    'p50'      => 0,
                    'p75'      => 0,
                ];
                $counts[$key] = 0;
            }
            $sums[$key]['p25'] += $row->percentile_25 ?? 0;
            $sums[$key]['p50'] += $row->percentile_50 ?? 0;
            $sums[$key]['p75'] += $row->percentile_75 ?? 0;
            $counts[$key]++;
        }

        $data = [];
        foreach ($sums as $key => $s) {
            $n = $counts[$key];
            $obj = new \stdClass();
            $obj->percentile_25 = $n > 0 ? round($s['p25'] / $n, 4) : 0;
            $obj->percentile_50 = $n > 0 ? round($s['p50'] / $n, 4) : 0;
            $obj->percentile_75 = $n > 0 ? round($s['p75'] / $n, 4) : 0;
            $data[$s['industry']][$s['metric']] = $obj;
        }

        return $data;
    }

    protected array $sourceUrlMap = [
        'TwoMinuteReports Google Ads 2025'   => 'https://www.twominutereports.com/blog/google-ads-benchmarks',
        'TwoMinuteReports Facebook Ads 2025' => 'https://www.twominutereports.com/blog/facebook-ads-benchmarks',
        'TwoMinuteReports TikTok Ads 2025'   => 'https://www.twominutereports.com/blog/tiktok-ads-benchmarks',
        'LinkedIn Marketing Solutions 2026'  => 'https://business.linkedin.com/marketing-solutions/ad-benchmarks',
        'Snapchat Ads Benchmarks 2026'       => 'https://forbusiness.snapchat.com/benchmarks',
        'WordStream 2024'                    => 'https://www.wordstream.com/blog/ws/google-adwords-industry-benchmarks',
        'Meta Business Insights 2024'        => 'https://www.facebook.com/business/news',
        'Google Ads Industry Benchmarks 2024' => 'https://ads.google.com/intl/en_us/home/resources/articles/',
        'LinkedIn Marketing Solutions 2024'  => 'https://business.linkedin.com/marketing-solutions/ad-benchmarks',
        'HubSpot Marketing Statistics 2024'  => 'https://www.hubspot.com/marketing-statistics',
        'Datavinity 2026'                    => 'https://datavinity.io',
        'Adliftr 2026'                       => 'https://adliftr.com',
    ];

    // Maps source name → platform slug for the filter click-to-switch behaviour
    protected array $sourcePlatformMap = [
        'TwoMinuteReports Google Ads 2025'   => 'google',
        'TwoMinuteReports Facebook Ads 2025' => 'facebook',
        'TwoMinuteReports TikTok Ads 2025'   => 'tiktok',
        'LinkedIn Marketing Solutions 2026'  => 'linkedin',
        'Snapchat Ads Benchmarks 2026'       => 'snapchat',
    ];

    public function getSourcesProperty(): array
    {
        if ($this->selectedPlatform === 'blended') {
            // Return all distinct per-platform sources (one per platform)
            return IndustryBenchmark::distinct()
                ->pluck('source')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $row = IndustryBenchmark::where('platform', $this->selectedPlatform)->first();
        if (!$row || empty($row->sources)) {
            return [];
        }
        return $row->sources;
    }

    public function switchToSource(string $source): void
    {
        $platform = $this->sourcePlatformMap[$source] ?? null;
        if ($platform) {
            $this->selectedPlatform = $platform;
        }
    }

    public function getSourcePlatformMapProperty(): array
    {
        return $this->sourcePlatformMap;
    }

    public function getSourceUrlMap(): array
    {
        return $this->sourceUrlMap;
    }

    public function getAvailableIndustriesProperty(): array
    {
        $query = IndustryBenchmark::query();
        if ($this->selectedPlatform !== 'blended') {
            $query->where('platform', $this->selectedPlatform);
        }
        return $query->distinct()->pluck('industry')->toArray();
    }

    public function formatValue(string $metric, float $value): string
    {
        return match($metric) {
            'ctr', 'cvr' => number_format($value, 2) . '%',
            'cpc', 'cpm', 'cpl', 'cpa' => '$' . number_format($value, 2),
            'roas' => number_format($value, 2) . 'x',
            default => number_format($value, 2),
        };
    }

    public function render()
    {
        return view('livewire.pages.global-benchmarks', [
            'tableData'           => $this->tableData,
            'sources'             => $this->sources,
            'sourceUrlMap'        => $this->getSourceUrlMap(),
            'sourcePlatformMap'   => $this->sourcePlatformMap,
            'availableIndustries' => $this->availableIndustries,
        ])->layout('layouts.app');
    }
}
