<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Services\BenchmarkService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class IndustryOverview extends Component
{
    // Filters
    public string $preset = '30d';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $platform = '';
    public array $selectedIndustries = [];

    // Table state
    public string $searchQuery = '';
    public string $sortBy = 'spend';
    public string $sortOrder = 'desc';
    public array $expandedIndustries = [];
    public string $tableMetric = 'all'; // all | efficiency | conversions

    // Chart state
    public string $currentMetric = 'spend';

    // Data
    public bool $loading = false;
    public string $error = '';
    public array $industryData = [];
    public array $platformDistribution = [];
    public array $monthlyTrend = [];
    public array $industryEfficiency = [];
    public ?array $totals = null;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->fetchData();
    }

    public function applyDatePreset(string $preset)
    {
        $this->preset = $preset;
        $today = Carbon::now()->format('Y-m-d');

        switch ($preset) {
            case '7d':
                $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
                $this->dateTo = $today;
                break;
            case '30d':
                $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
                $this->dateTo = $today;
                break;
            case '90d':
                $this->dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');
                $this->dateTo = $today;
                break;
            case '6m':
                $this->dateFrom = Carbon::now()->subDays(180)->format('Y-m-d');
                $this->dateTo = $today;
                break;
            case '1y':
                $this->dateFrom = Carbon::now()->subDays(365)->format('Y-m-d');
                $this->dateTo = $today;
                break;
            case 'all':
                $this->dateFrom = '2020-01-01';
                $this->dateTo = $today;
                break;
            case 'custom':
                return; // Don't fetch — user sets dates manually
        }

        $this->fetchData();
    }

    public function resetFilters()
    {
        $this->platform = '';
        $this->selectedIndustries = [];
        $this->applyDatePreset('30d');
    }

    public function fetchData()
    {
        $this->loading = true;
        $this->error = '';

        try {
            $query = AdAccount::with(['adMetrics' => function ($q) {
                $q->whereBetween('date', [$this->dateFrom, $this->dateTo]);
            }, 'integration']);

            if ($this->platform) {
                $query->whereHas('integration', function ($q) {
                    $q->where('platform', $this->platform);
                });
            }

            if (!empty($this->selectedIndustries)) {
                $query->whereIn('industry', $this->selectedIndustries);
            }

            $accounts = $query->get();

            $currencyService = app(CurrencyConversionService::class);

            // Aggregate by industry with full metrics
            $industries = [];
            $totalAccounts = 0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalLeads = 0;
            $totalConversions = 0;
            $totalSpend = 0;
            $monthlySpend = [];

            foreach ($accounts->groupBy(fn($a) => $a->industry ?? 'other') as $industry => $industryAccounts) {
                $indImpressions = 0;
                $indClicks = 0;
                $indLeads = 0;
                $indConversions = 0;
                $indSpend = 0;
                $categories = [];

                // Platform breakdown for this industry
                $platformCounts = [];
                foreach ($industryAccounts as $account) {
                    $p = $account->integration->platform ?? 'unknown';
                    $platformCounts[$p] = ($platformCounts[$p] ?? 0) + 1;
                }

                foreach ($industryAccounts->groupBy('category') as $category => $catAccounts) {
                    $catImpressions = 0;
                    $catClicks = 0;
                    $catLeads = 0;
                    $catConversions = 0;
                    $catSpend = 0;

                    foreach ($catAccounts as $account) {
                        $metrics = $account->adMetrics;
                        $currency = $account->currency ?? 'USD';
                        $catImpressions += $metrics->sum('impressions');
                        $catClicks += $metrics->sum('clicks');
                        $catLeads += $metrics->sum('leads');
                        $catConversions += $metrics->sum('conversions');
                        $catSpend += $currencyService->convertToSAR((float) $metrics->sum('spend'), $currency);

                        // Accumulate monthly spend
                        foreach ($metrics as $metric) {
                            $month = $metric->date->format('Y-m');
                            $metricSpend = $currencyService->convertToSAR((float) $metric->spend, $currency);
                            $monthlySpend[$month] = ($monthlySpend[$month] ?? 0) + $metricSpend;
                        }
                    }

                    $catCtr = $catImpressions > 0 ? ($catClicks / $catImpressions) * 100 : 0;
                    $catCpc = $catClicks > 0 ? $catSpend / $catClicks : 0;
                    $catCpm = $catImpressions > 0 ? ($catSpend / $catImpressions) * 1000 : 0;
                    $catCvr = $catClicks > 0 ? ($catLeads / $catClicks) * 100 : 0;
                    $catCpl = $catLeads > 0 ? $catSpend / $catLeads : 0;

                    $categories[] = [
                        'category' => $category ?: 'Uncategorized',
                        'category_display' => ucwords(str_replace(['_', '-'], ' ', $category ?: 'Uncategorized')),
                        'accounts_count' => $catAccounts->count(),
                        'total_impressions' => $catImpressions,
                        'total_clicks' => $catClicks,
                        'total_leads' => $catLeads,
                        'total_conversions' => $catConversions,
                        'total_spend' => round($catSpend, 2),
                        'ctr' => round($catCtr, 2),
                        'cpc' => round($catCpc, 2),
                        'cpm' => round($catCpm, 2),
                        'cvr' => round($catCvr, 2),
                        'cpl' => round($catCpl, 2),
                    ];

                    $indImpressions += $catImpressions;
                    $indClicks += $catClicks;
                    $indLeads += $catLeads;
                    $indConversions += $catConversions;
                    $indSpend += $catSpend;
                }

                $indCtr = $indImpressions > 0 ? ($indClicks / $indImpressions) * 100 : 0;
                $indCpc = $indClicks > 0 ? $indSpend / $indClicks : 0;
                $indCpm = $indImpressions > 0 ? ($indSpend / $indImpressions) * 1000 : 0;
                $indCvr = $indClicks > 0 ? ($indLeads / $indClicks) * 100 : 0;
                $indCpl = $indLeads > 0 ? $indSpend / $indLeads : 0;

                $industries[] = [
                    'industry' => $industry,
                    'industry_display' => ucwords(str_replace(['_', '-'], ' ', $industry)),
                    'accounts_count' => $industryAccounts->count(),
                    'total_impressions' => $indImpressions,
                    'total_clicks' => $indClicks,
                    'total_leads' => $indLeads,
                    'total_conversions' => $indConversions,
                    'total_spend' => round($indSpend, 2),
                    'ctr' => round($indCtr, 2),
                    'cpc' => round($indCpc, 2),
                    'cpm' => round($indCpm, 2),
                    'cvr' => round($indCvr, 2),
                    'cpl' => round($indCpl, 2),
                    'platform_counts' => $platformCounts,
                    'benchmark_status' => [],
                    'categories' => $categories,
                ];

                $totalAccounts += $industryAccounts->count();
                $totalImpressions += $indImpressions;
                $totalClicks += $indClicks;
                $totalLeads += $indLeads;
                $totalConversions += $indConversions;
                $totalSpend += $indSpend;
            }

            // Fetch benchmark scores and merge into industry data
            $this->mergeBenchmarkScores($industries);

            $this->industryData = $industries;
            $this->platformDistribution = $accounts
                ->groupBy(fn ($a) => $a->integration->platform ?? 'unknown')
                ->map->count()
                ->sortDesc()
                ->toArray();

            // Sort and store monthly trend
            ksort($monthlySpend);
            $this->monthlyTrend = $monthlySpend;

            // Build efficiency comparison for top 8
            $this->industryEfficiency = collect($industries)
                ->sortByDesc('total_spend')
                ->take(8)
                ->map(fn($i) => [
                    'label' => $i['industry_display'],
                    'cpc' => $i['cpc'],
                    'cpm' => $i['cpm'],
                ])
                ->values()
                ->toArray();

            $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
            $avgCpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
            $avgCpm = $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0;

            $this->totals = [
                'total_industries' => count($industries),
                'total_accounts' => $totalAccounts,
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'total_leads' => $totalLeads,
                'total_conversions' => $totalConversions,
                'total_spend' => round($totalSpend, 2),
                'avg_ctr' => round($avgCtr, 2),
                'avg_cpc' => round($avgCpc, 2),
                'avg_cpm' => round($avgCpm, 2),
            ];
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    private function mergeBenchmarkScores(array &$industries): void
    {
        try {
            $benchmarkService = app(BenchmarkService::class);
            $filters = $this->platform ? ['platform' => $this->platform] : [];
            $benchmarks = $benchmarkService->getIndustryBenchmarks($this->dateFrom, $this->dateTo, $filters);

            foreach ($industries as &$ind) {
                $slug = $ind['industry'];
                if (isset($benchmarks[$slug]['metrics'])) {
                    $statuses = [];
                    foreach ($benchmarks[$slug]['metrics'] as $metric => $data) {
                        $statuses[$metric] = $data['status'] ?? 'no_data';
                    }
                    $ind['benchmark_status'] = $statuses;
                }
            }
        } catch (\Exception $e) {
            // Silently fail — benchmarks are optional enhancement
        }
    }

    public function handleSort(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortOrder = $column === 'industry' ? 'asc' : 'desc';
        }
    }

    public function toggleIndustry(string $industry)
    {
        $key = array_search($industry, $this->expandedIndustries);
        if ($key !== false) {
            unset($this->expandedIndustries[$key]);
            $this->expandedIndustries = array_values($this->expandedIndustries);
        } else {
            $this->expandedIndustries[] = $industry;
        }
    }

    public function getFilteredIndustryDataProperty(): array
    {
        $data = $this->industryData;

        // Filter by search query
        if ($this->searchQuery) {
            $query = strtolower($this->searchQuery);
            $data = array_filter($data, function ($item) use ($query) {
                return str_contains(strtolower($item['industry_display']), $query)
                    || str_contains(strtolower($item['industry']), $query);
            });
        }

        // Sort
        usort($data, function ($a, $b) {
            $aVal = match ($this->sortBy) {
                'industry' => $a['industry_display'],
                'accounts' => $a['accounts_count'],
                'impressions' => $a['total_impressions'],
                'clicks' => $a['total_clicks'],
                'spend' => $a['total_spend'],
                'ctr' => $a['ctr'],
                'cpc' => $a['cpc'],
                'cpm' => $a['cpm'],
                'leads' => $a['total_leads'],
                'cvr' => $a['cvr'],
                'cpl' => $a['cpl'],
                default => $a['total_spend'],
            };
            $bVal = match ($this->sortBy) {
                'industry' => $b['industry_display'],
                'accounts' => $b['accounts_count'],
                'impressions' => $b['total_impressions'],
                'clicks' => $b['total_clicks'],
                'spend' => $b['total_spend'],
                'ctr' => $b['ctr'],
                'cpc' => $b['cpc'],
                'cpm' => $b['cpm'],
                'leads' => $b['total_leads'],
                'cvr' => $b['cvr'],
                'cpl' => $b['cpl'],
                default => $b['total_spend'],
            };

            if (is_string($aVal)) {
                return $this->sortOrder === 'asc' ? strcmp($aVal, $bVal) : strcmp($bVal, $aVal);
            }
            return $this->sortOrder === 'asc' ? $aVal <=> $bVal : $bVal <=> $aVal;
        });

        return array_values($data);
    }

    public function getTopIndustriesChartConfigProperty(): array
    {
        $metricKey = match ($this->currentMetric) {
            'spend' => 'total_spend',
            'clicks' => 'total_clicks',
            'impressions' => 'total_impressions',
            'leads' => 'total_leads',
            default => 'total_spend',
        };

        $metricLabel = match ($this->currentMetric) {
            'spend' => 'Total Spend (SAR)',
            'clicks' => 'Total Clicks',
            'impressions' => 'Total Impressions',
            'leads' => 'Total Leads',
            default => 'Total Spend (SAR)',
        };

        $sorted = collect($this->industryData)->sortByDesc($metricKey)->take(10);

        $colors = [
            'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(168,85,247,0.7)',
            'rgba(251,191,36,0.7)', 'rgba(239,68,68,0.7)', 'rgba(236,72,153,0.7)',
            'rgba(14,165,233,0.7)', 'rgba(245,158,11,0.7)', 'rgba(99,102,241,0.7)',
            'rgba(20,184,166,0.7)',
        ];

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $sorted->pluck('industry_display')->values()->toArray(),
                'datasets' => [[
                    'label' => $metricLabel,
                    'data' => $sorted->pluck($metricKey)->values()->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $sorted->count()),
                    'borderWidth' => 2,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];
    }

    public function getMonthlyTrendChartConfigProperty(): array
    {
        $labels = array_keys($this->monthlyTrend);
        $data = array_values($this->monthlyTrend);

        // Format labels to shorter month names
        $formattedLabels = array_map(function ($ym) {
            return Carbon::createFromFormat('Y-m', $ym)->format('M Y');
        }, $labels);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $formattedLabels,
                'datasets' => [[
                    'label' => 'Monthly Spend (SAR)',
                    'data' => array_map(fn($v) => round($v, 2), $data),
                    'borderColor' => 'rgba(59,130,246,1)',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    public function getEfficiencyChartConfigProperty(): array
    {
        $labels = array_column($this->industryEfficiency, 'label');
        $cpcData = array_column($this->industryEfficiency, 'cpc');
        $cpmData = array_column($this->industryEfficiency, 'cpm');

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Avg CPC (SAR)',
                        'data' => $cpcData,
                        'backgroundColor' => 'rgba(99,102,241,0.7)',
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => 'Avg CPM (SAR)',
                        'data' => $cpmData,
                        'backgroundColor' => 'rgba(236,72,153,0.7)',
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'top']],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    public function getPlatformChartConfigProperty(): array
    {
        $platformLabels = [
            'facebook' => 'Facebook',
            'google' => 'Google Ads',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'linkedin' => 'LinkedIn',
        ];

        $platformColors = [
            'facebook' => 'rgba(59,130,246,0.8)',
            'google' => 'rgba(234,67,53,0.8)',
            'tiktok' => 'rgba(37,244,238,0.8)',
            'snapchat' => 'rgba(255,252,0,0.8)',
            'linkedin' => 'rgba(0,119,181,0.8)',
        ];

        $defaultColor = 'rgba(156,163,175,0.8)';

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($this->platformDistribution as $platform => $count) {
            $labels[] = $platformLabels[$platform] ?? ucfirst($platform);
            $data[] = $count;
            $colors[] = $platformColors[$platform] ?? $defaultColor;
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom']],
            ],
        ];
    }

    public function render()
    {
        // Get all distinct industries for the filter dropdown
        $availableIndustries = AdAccount::whereNotNull('industry')
            ->where('industry', '!=', '')
            ->distinct()
            ->pluck('industry')
            ->sort()
            ->mapWithKeys(fn($ind) => [$ind => ucwords(str_replace(['_', '-'], ' ', $ind))])
            ->toArray();

        return view('livewire.pages.industry-overview', [
            'filteredIndustryData' => $this->getFilteredIndustryDataProperty(),
            'topIndustriesChartConfig' => $this->getTopIndustriesChartConfigProperty(),
            'monthlyTrendChartConfig' => $this->getMonthlyTrendChartConfigProperty(),
            'efficiencyChartConfig' => $this->getEfficiencyChartConfigProperty(),
            'platformChartConfig' => $this->getPlatformChartConfigProperty(),
            'availableIndustries' => $availableIndustries,
            'datePresets' => [
                ['label' => __('Last 7 Days'), 'value' => '7d'],
                ['label' => __('Last 30 Days'), 'value' => '30d'],
                ['label' => __('Last 90 Days'), 'value' => '90d'],
                ['label' => __('Last 6 Months'), 'value' => '6m'],
                ['label' => __('Last Year'), 'value' => '1y'],
                ['label' => __('All Time'), 'value' => 'all'],
                ['label' => __('Custom'), 'value' => 'custom'],
            ],
        ])->layout('layouts.app');
    }
}
