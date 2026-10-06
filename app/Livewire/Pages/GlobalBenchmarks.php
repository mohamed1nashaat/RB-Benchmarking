<?php

namespace App\Livewire\Pages;

use App\Models\IndustryBenchmark;
use Livewire\Component;

class GlobalBenchmarks extends Component
{
    public string $selectedPlatform = 'facebook';
    public string $selectedIndustry = '';

    public array $platforms = [
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
        $this->selectedPlatform = 'facebook';
        $this->selectedIndustry = '';
    }

    public function updatedSelectedPlatform(): void {}
    public function updatedSelectedIndustry(): void {}

    public function getTableDataProperty(): array
    {
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

    public function getSourcesProperty(): array
    {
        $row = IndustryBenchmark::where('platform', $this->selectedPlatform)->first();
        if (!$row || empty($row->sources)) {
            return [];
        }
        return $row->sources;
    }

    public function getAvailableIndustriesProperty(): array
    {
        return IndustryBenchmark::where('platform', $this->selectedPlatform)
            ->distinct()
            ->pluck('industry')
            ->toArray();
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
            'tableData'          => $this->tableData,
            'sources'            => $this->sources,
            'availableIndustries' => $this->availableIndustries,
        ])->layout('layouts.app');
    }
}
