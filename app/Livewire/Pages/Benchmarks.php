<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\IndustryBenchmark;
use App\Services\BenchmarkService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Carbon\Carbon;

class Benchmarks extends Component
{
    // Filters
    public string $dateFrom = '';
    public string $dateTo = '';
    public array $selectedPlatforms = [];
    public array $selectedIndustries = [];
    public array $selectedCountries = [];
    public array $selectedCategories = [];
    public array $selectedSubCategories = [];
    public array $selectedFunnelStages = [];
    public string $objective = '';
    public string $searchQuery = '';
    public string $tableSearchQuery = '';

    // View state
    public string $viewMode = 'table';
    public string $tableGroupBy = 'industry';
    public string $sortField = 'total_spend';
    public string $sortDirection = 'desc';
    public bool $showColumnSelector = false;

    // Table column sort & filter (server-side)
    public string $tableSortField = '';
    public string $tableSortDirection = 'desc';
    public array $columnFilters = [];

    // Auto-refresh
    public bool $autoRefreshEnabled = false;
    public int $autoRefreshCountdown = 60;

    // Column visibility
    public array $visibleColumns = [
        'industry' => true,
        'country' => true,
        'category' => true,
        'subCategory' => true,
        'funnelStage' => true,
        'campaignObjective' => true,
        'spend' => true,
        'impressions' => true,
        'clicks' => true,
        'leads' => true,
        'installs' => false,
        'conversions' => true,
        'cpm' => true,
        'cpc' => true,
        'ctr' => true,
        'cpl' => true,
        'cpa' => true,
        'cpi' => false,
        'global_cpm' => true,
        'global_cpc' => true,
        'global_ctr' => true,
        'global_cpl' => true,
        'global_cpa' => true,
        'global_cpi' => false,
        'actions' => true,
    ];

    // Spend visibility
    public bool $showAllSpend = true;

    // Data
    public bool $loading = false;
    public array $benchmarkData = [];
    public array $globalBenchmarkData = [];
    public array $externalBenchmarkData = [];

    // Accounts modal
    public bool $showAccountsModal = false;
    public string $modalIndustry = '';
    public array $modalAccounts = [];

    // Inline editing state
    public ?int $editingAccountId = null;
    public string $editingField = '';
    public string $editValue = '';
    public array $summary = [
        'total_industries' => 0,
        'total_accounts' => 0,
        'total_spend' => 0,
        'best_performing' => [],
        'needs_improvement' => [],
    ];
    public array $unfilteredSummary = ['total_accounts' => 0];

    // Overview metrics (computed in refreshData to avoid Livewire computed-property cache issues)
    public array $yourOverallMetrics = [
        'industries_count' => 0,
        'accounts_count' => 0,
        'campaigns_count' => 0,
        'total_spend' => 0,
        'total_impressions' => 0,
        'total_clicks' => 0,
        'total_conversions' => 0,
    ];
    public array $yourPerformanceIndicators = [
        'ctr' => 0,
        'cvr' => 0,
    ];

    // Available filter options
    public array $availablePlatforms = [
        'facebook'  => 'Facebook / Meta',
        'google'    => 'Google Ads',
        'tiktok'    => 'TikTok',
        'snapchat'  => 'Snapchat',
        'linkedin'  => 'LinkedIn',
    ];
    public array $availableIndustries = [];
    public array $availableCountries = [];
    public array $availableCategories = [];
    public array $availableSubCategories = [];
    public array $availableFunnelStages = [];

    public function mount()
    {
        $this->dateFrom = '2010-01-01';
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->refreshData();
    }

    public function updatedSelectedPlatforms() { $this->refreshData(); }
    public function updatedSelectedIndustries()
    {
        $this->validateDownstream(['country', 'category', 'sub_category', 'funnel_stage']);
        $this->refreshData();
    }
    public function updatedSelectedCountries()
    {
        $this->validateDownstream(['category', 'sub_category', 'funnel_stage']);
        $this->refreshData();
    }
    public function updatedSelectedCategories()
    {
        $this->validateDownstream(['sub_category', 'funnel_stage']);
        $this->refreshData();
    }
    public function updatedSelectedSubCategories() { $this->refreshData(); }
    public function updatedSelectedFunnelStages() { $this->refreshData(); }
    public function updatedDateFrom() { $this->refreshData(); }
    public function updatedDateTo() { $this->refreshData(); }
    public function updatedTableGroupBy() { $this->refreshData(); }

    public function setDateRange($from, $to)
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->loading = true;
        $this->columnFilters = [];

        try {
            $service = app(BenchmarkService::class);

            $filters = [];
            if (!empty($this->selectedPlatforms)) {
                $filters['platform'] = $this->selectedPlatforms;
            }
            if (!empty($this->selectedFunnelStages)) {
                $filters['funnel_stage'] = $this->selectedFunnelStages;
            }
            if (!empty($this->selectedCountries)) {
                $filters['country'] = $this->selectedCountries;
            }
            if (!empty($this->selectedCategories)) {
                $filters['category'] = $this->selectedCategories;
            }
            if (!empty($this->selectedSubCategories)) {
                $filters['sub_category'] = $this->selectedSubCategories;
            }
            if ($this->objective) {
                $filters['objective'] = $this->objective;
            }

            // Get benchmarks based on group mode
            if ($this->tableGroupBy === 'client') {
                $unfilteredData = $service->getClientBenchmarks($this->dateFrom, $this->dateTo, $filters);
            } else {
                $unfilteredData = $service->getIndustryBenchmarks($this->dateFrom, $this->dateTo, $filters);
            }

            // Compute unfiltered total accounts from the same data
            $this->unfilteredSummary = [
                'total_accounts' => array_sum(array_column($unfilteredData, 'accounts_count')),
            ];

            // Compute best_performing / needs_improvement inline from the same data
            $summaryData = $service->getOverallBenchmarkSummary($this->dateFrom, $this->dateTo, $unfilteredData);

            // Apply industry filter if selected (only in industry mode)
            $industryData = $unfilteredData;
            if ($this->tableGroupBy !== 'client' && !empty($this->selectedIndustries)) {
                $industryData = array_filter($industryData, function ($data, $key) {
                    return in_array($key, $this->selectedIndustries);
                }, ARRAY_FILTER_USE_BOTH);
            }

            $this->benchmarkData = $industryData;

            // Calculate summary from filtered data
            $totalAccounts = 0;
            $totalSpend = 0;
            foreach ($industryData as $industry => $data) {
                $totalAccounts += $data['accounts_count'] ?? 0;
                $totalSpend += $data['total_spend'] ?? 0;
            }

            $this->summary = [
                'total_industries' => count($industryData),
                'total_accounts' => $totalAccounts,
                'total_spend' => $totalSpend,
                'best_performing' => $summaryData['best_performing'] ?? [],
                'needs_improvement' => $summaryData['needs_improvement'] ?? [],
            ];

            // Compute overview metrics directly (avoid computed-property cache)
            $this->yourOverallMetrics = $this->computeOverallMetrics();
            $this->yourPerformanceIndicators = $this->computePerformanceIndicators();

            // Load global benchmarks across all accounts for the same date range
            $this->globalBenchmarkData = $service->getGlobalBenchmarks($this->dateFrom, $this->dateTo, $filters);

            // Load external industry benchmarks (seeded from WordStream/Meta/etc.)
            $this->externalBenchmarkData = Cache::remember('external_benchmarks_by_industry', 3600, function () {
                return IndustryBenchmark::whereIn('metric', ['ctr','cpc','cpm','cvr','cpl','cpa','roas'])
                    ->whereIn('platform', ['facebook','google','tiktok'])
                    ->get()
                    ->groupBy('industry')
                    ->map(fn($rows) => $rows->groupBy('metric')->map(fn($m) => round($m->avg('percentile_50'), 4)))
                    ->toArray();
            });
        } catch (\Throwable $e) {
            $this->benchmarkData = [];
            \Log::error('Benchmarks refresh error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        // Always load filter options so dropdowns update even on service failure
        $this->loadFilterOptions();
        $this->loading = false;
    }

    public function clearFilters()
    {
        $this->selectedPlatforms = [];
        $this->selectedIndustries = [];
        $this->selectedCountries = [];
        $this->selectedCategories = [];
        $this->selectedSubCategories = [];
        $this->selectedFunnelStages = [];
        $this->dateFrom = '2010-01-01';
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->columnFilters = [];
        $this->refreshData();
    }

    private function loadFilterOptions(): void
    {
        $this->availableIndustries = $this->queryDistinctValues('a.industry', ['industry']);
        $this->availableCountries = $this->queryDistinctValues('a.country', ['country']);
        $this->availableCategories = $this->queryDistinctValues(
            "COALESCE(CASE WHEN c.inherit_category_from_account = 0 AND c.category IS NOT NULL AND c.category != '' THEN c.category ELSE a.category END, a.category, '-')",
            ['category']
        );
        $this->availableSubCategories = $this->querySubCategoryValues();
        $this->availableFunnelStages = $this->queryDistinctValues("COALESCE(m.funnel_stage, '-')", ['funnel_stage']);
    }

    /**
     * Validate downstream filter selections against narrowed available options.
     */
    private function validateDownstream(array $filterKeys): void
    {
        foreach ($filterKeys as $key) {
            $available = match ($key) {
                'country' => $this->queryDistinctValues('a.country', ['country']),
                'category' => $this->queryDistinctValues(
                    "COALESCE(CASE WHEN c.inherit_category_from_account = 0 AND c.category IS NOT NULL AND c.category != '' THEN c.category ELSE a.category END, a.category, '-')",
                    ['category']
                ),
                'sub_category' => $this->querySubCategoryValues(),
                'funnel_stage' => $this->queryDistinctValues("COALESCE(m.funnel_stage, '-')", ['funnel_stage']),
            };

            $property = match ($key) {
                'country' => 'selectedCountries',
                'category' => 'selectedCategories',
                'sub_category' => 'selectedSubCategories',
                'funnel_stage' => 'selectedFunnelStages',
            };

            if ($key === 'sub_category') {
                $this->{$property} = array_values(
                    array_intersect($this->{$property}, array_keys($available))
                );
            } else {
                $this->{$property} = array_values(
                    array_intersect($this->{$property}, $available)
                );
            }
        }
    }

    private function queryDistinctValues(string $columnExpr, array $excludeFilters = []): array
    {
        $cacheKey = 'benchmark_filters:' . md5(
            $columnExpr . json_encode($excludeFilters) .
            $this->dateFrom . $this->dateTo .
            json_encode([
                $this->selectedPlatforms,
                $this->selectedIndustries,
                $this->selectedCountries,
                $this->selectedCategories,
                $this->selectedSubCategories,
                $this->selectedFunnelStages,
            ])
        );

        return Cache::remember($cacheKey, 60, function () use ($columnExpr, $excludeFilters) {
            $query = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'a.id', '=', 'm.ad_account_id')
                ->leftJoin('ad_campaigns as c', 'c.id', '=', 'm.ad_campaign_id')
                ->whereBetween('m.date', [$this->dateFrom, $this->dateTo]);

            if (!in_array('platform', $excludeFilters) && !empty($this->selectedPlatforms)) {
                $query->whereIn('m.platform', $this->selectedPlatforms);
            }
            if (!in_array('industry', $excludeFilters) && !empty($this->selectedIndustries)) {
                $query->whereIn('a.industry', $this->selectedIndustries);
            }
            if (!in_array('country', $excludeFilters) && !empty($this->selectedCountries)) {
                $query->whereIn('a.country', $this->selectedCountries);
            }
            if (!in_array('funnel_stage', $excludeFilters) && !empty($this->selectedFunnelStages)) {
                $query->whereIn('m.funnel_stage', $this->selectedFunnelStages);
            }
            if (!in_array('category', $excludeFilters) && !empty($this->selectedCategories)) {
                $categories = $this->selectedCategories;
                $query->where(function ($q) use ($categories) {
                    $q->whereIn('a.category', $categories)
                      ->orWhereIn('c.category', $categories);
                });
            }
            if (!in_array('sub_category', $excludeFilters) && !empty($this->selectedSubCategories)) {
                $query->whereIn('a.sub_industry', $this->selectedSubCategories);
            }

            $values = $query->distinct()->selectRaw("{$columnExpr} as val")->pluck('val')->toArray();
            $filtered = array_values(array_filter($values, fn ($v) => $v !== null && $v !== '' && $v !== '-'));

            return array_combine($filtered, $filtered);
        });
    }

    /**
     * Query distinct sub-categories with proper display names from account_sub_categories.
     * Returns map of slug => display_name.
     */
    private function querySubCategoryValues(): array
    {
        $cacheKey = 'benchmark_filters:sub_categories:' . md5(
            $this->dateFrom . $this->dateTo .
            json_encode([
                $this->selectedPlatforms,
                $this->selectedIndustries,
                $this->selectedCountries,
                $this->selectedCategories,
                $this->selectedSubCategories,
                $this->selectedFunnelStages,
            ])
        );

        return Cache::remember($cacheKey, 60, function () {
            $query = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'a.id', '=', 'm.ad_account_id')
                ->leftJoin('account_categories as ac', 'ac.display_name', '=', 'a.category')
                ->leftJoin('account_sub_categories as asc', function ($join) {
                    $join->on('asc.name', '=', 'a.sub_industry')
                         ->on('asc.account_category_id', '=', 'ac.id');
                })
                ->whereBetween('m.date', [$this->dateFrom, $this->dateTo])
                ->whereNotNull('a.sub_industry')
                ->where('a.sub_industry', '!=', '')
                ->where('a.sub_industry', '!=', '-');

            if (!empty($this->selectedPlatforms)) {
                $query->whereIn('m.platform', $this->selectedPlatforms);
            }
            if (!empty($this->selectedIndustries)) {
                $query->whereIn('a.industry', $this->selectedIndustries);
            }
            if (!empty($this->selectedCountries)) {
                $query->whereIn('a.country', $this->selectedCountries);
            }
            if (!empty($this->selectedFunnelStages)) {
                $query->whereIn('m.funnel_stage', $this->selectedFunnelStages);
            }
            if (!empty($this->selectedCategories)) {
                $query->whereIn('a.category', $this->selectedCategories);
            }

            $rows = $query
                ->distinct()
                ->selectRaw("COALESCE(a.sub_industry, '-') as slug, COALESCE(asc.display_name, a.sub_industry, '-') as display_name")
                ->get();

            $result = [];
            foreach ($rows as $row) {
                if ($row->slug !== '-' && $row->slug !== '') {
                    $result[$row->slug] = $row->display_name;
                }
            }

            ksort($result);
            return $result;
        });
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefreshEnabled = !$this->autoRefreshEnabled;
    }

    public function toggleSortDirection()
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function sortBy(string $field): void
    {
        if ($this->tableSortField === $field) {
            $this->tableSortDirection = $this->tableSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->tableSortField = $field;
            $this->tableSortDirection = 'desc';
        }
    }

    public function clearColumnFilters(): void
    {
        $this->columnFilters = [];
    }

    public function toggleColumn(string $key): void
    {
        if ($key === 'industry') {
            return;
        }
        $this->visibleColumns[$key] = !($this->visibleColumns[$key] ?? false);
    }

    public function toggleGroupColumns(string $group, bool $visible): void
    {
        $map = [
            'account' => ['country', 'category', 'subCategory'],
            'campaign' => ['funnelStage', 'campaignObjective'],
            'volume' => ['spend', 'impressions', 'clicks', 'leads', 'installs', 'conversions'],
            'efficiency' => ['cpm', 'cpc', 'ctr', 'cpl', 'cpa', 'cpi'],
            'global' => ['global_cpm', 'global_cpc', 'global_ctr', 'global_cpl', 'global_cpa', 'global_cpi'],
        ];
        foreach ($map[$group] ?? [] as $col) {
            $this->visibleColumns[$col] = $visible;
        }
    }

    #[Computed]
    public function hasActiveColumnFilters(): bool
    {
        return collect($this->columnFilters)->some(fn ($v) => !empty($v));
    }

    #[Computed]
    public function getFilteredIndustriesProperty(): array
    {
        $data = $this->benchmarkData;

        // Apply search filter (matches industry name and sub-row category/sub_category)
        if ($this->searchQuery) {
            $query = strtolower($this->searchQuery);
            $data = array_filter($data, function ($val, $key) use ($query) {
                $label = ucwords(str_replace(['_', '-'], ' ', $key));
                $industryName = strtolower($val['industry'] ?? '');
                if (str_contains(strtolower($label), $query)
                    || str_contains(strtolower($key), $query)
                    || str_contains($industryName, $query)) {
                    return true;
                }
                // Search within sub-rows
                foreach ($val['sub_rows'] ?? [] as $sr) {
                    if (str_contains(strtolower($sr['category'] ?? ''), $query)
                        || str_contains(strtolower($sr['sub_category'] ?? ''), $query)) {
                        return true;
                    }
                }
                return false;
            }, ARRAY_FILTER_USE_BOTH);
        }

        // Sort
        if ($this->sortField) {
            uasort($data, function ($a, $b) {
                $aVal = match ($this->sortField) {
                    'name' => 0,
                    'accounts_count' => $a['accounts_count'] ?? 0,
                    'total_spend' => $a['total_spend'] ?? 0,
                    'total_leads' => $a['total_leads'] ?? 0,
                    default => $a['total_spend'] ?? 0,
                };
                $bVal = match ($this->sortField) {
                    'name' => 0,
                    'accounts_count' => $b['accounts_count'] ?? 0,
                    'total_spend' => $b['total_spend'] ?? 0,
                    'total_leads' => $b['total_leads'] ?? 0,
                    default => $b['total_spend'] ?? 0,
                };
                return $this->sortDirection === 'asc' ? $aVal <=> $bVal : $bVal <=> $aVal;
            });
        }

        return $data;
    }

    private function computeOverallMetrics(): array
    {
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;
        $accountsCount = 0;
        $campaignsCount = 0;

        foreach ($this->benchmarkData as $industry => $data) {
            $accountsCount += $data['accounts_count'] ?? 0;
            $totalSpend += $data['total_spend'] ?? 0;
            $totalImpressions += $data['total_impressions'] ?? 0;
            $totalClicks += $data['total_clicks'] ?? 0;
            $totalConversions += $data['total_conversions'] ?? 0;
            $campaignsCount += $data['campaigns_count'] ?? 0;
        }

        return [
            'industries_count' => count($this->benchmarkData),
            'accounts_count' => $accountsCount,
            'campaigns_count' => $campaignsCount,
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
        ];
    }

    private function computePerformanceIndicators(): array
    {
        $metrics = $this->yourOverallMetrics;
        return [
            'ctr' => $metrics['total_impressions'] > 0 ? ($metrics['total_clicks'] / $metrics['total_impressions']) * 100 : 0,
            'cvr' => $metrics['total_clicks'] > 0 ? ($metrics['total_conversions'] / $metrics['total_clicks']) * 100 : 0,
        ];
    }

    public function getIndustryLabel(string $key): string
    {
        $labels = [
            'automotive'               => 'Automotive',
            'e_commerce_retail'        => 'E-commerce & Retail',
            'ecommerce'                => 'E-commerce & Retail',
            'education'                => 'Education',
            'fashion_luxury'           => 'Fashion & Luxury',
            'finance'                  => 'Finance & Insurance',
            'finance_insurance'        => 'Finance & Insurance',
            'fmcg_cpg'                 => 'FMCG / CPG',
            'food_beverage'            => 'Food & Beverage',
            'government_public_sector' => 'Government & Public Sector',
            'healthcare'               => 'Healthcare',
            'home_garden'              => 'Home & Garden',
            'hospitality_food_services'=> 'Hospitality & Food Services',
            'professional_services'    => 'Professional Services',
            'real_estate'              => 'Real Estate',
            'retail'                   => 'Retail & E-commerce',
            'technology'               => 'Technology',
            'travel'                   => 'Travel & Destinations',
            'travel_destinations'      => 'Travel & Destinations',
            'travel_tourism'           => 'Travel & Tourism',
        ];

        return $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public function getMetricLabel(string $key): string
    {
        return match ($key) {
            'ctr' => 'CTR',
            'cpc' => 'CPC',
            'cpm' => 'CPM',
            'cvr' => 'CVR',
            'cpl' => 'CPL',
            'cpa' => 'CPA',
            'roas' => 'ROAS',
            default => strtoupper($key),
        };
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'excellent' => 'bg-green-100 text-green-800',
            'good' => 'bg-blue-100 text-blue-800',
            'average' => 'bg-yellow-100 text-yellow-800',
            'below_average' => 'bg-orange-100 text-orange-800',
            'poor' => 'bg-red-100 text-red-800',
            'no_benchmark' => 'bg-gray-100 text-gray-400',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFunnelBadgeClass(string $stage): string
    {
        return match ($stage) {
            'TOF' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-blue-600 bg-blue-50',
            'MOF' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-amber-600 bg-amber-50',
            'BOF' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-green-600 bg-green-50',
            default => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-gray-500 bg-gray-100',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'excellent' => __('Excellent'),
            'good' => __('Good'),
            'average' => __('Average'),
            'below_average' => __('Below Avg'),
            'poor' => __('Poor'),
            'no_data' => __('No Data'),
            'no_benchmark' => '-',
            default => ucfirst($status),
        };
    }

    public function formatCurrency(float $amount): string
    {
        if ($amount >= 1000000) return 'SAR ' . number_format($amount / 1000000, 1) . 'M';
        if ($amount >= 1000) return 'SAR ' . number_format($amount / 1000, 1) . 'K';
        return 'SAR ' . number_format($amount, 2);
    }

    public function formatNumber($num): string
    {
        if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }

    public function formatMetricValue(string $metric, $value): string
    {
        if ($value === null) return '-';

        return match ($metric) {
            'ctr', 'cvr' => number_format($value, 2) . '%',
            'cpc', 'cpm', 'cpl', 'cpa', 'cpi' => 'SAR ' . number_format($value, 2),
            'roas' => number_format($value, 2) . 'x',
            default => number_format($value, 2),
        };
    }

    public function getTableData(): array
    {
        $rows = [];
        foreach ($this->getFilteredIndustriesProperty() as $industryKey => $industry) {
            $industryName = $industry['industry'] ?? $this->getIndustryLabel($industryKey);
            $subRows = $industry['sub_rows'] ?? [];

            if (empty($subRows)) {
                $rows[] = $this->formatRow($industryName, $industry);
                continue;
            }

            foreach ($subRows as $sr) {
                $rows[] = $this->formatRow($industryName, $sr);
            }
        }
        return $rows;
    }

    public function getFilteredTableData(): array
    {
        $rows = $this->getTableData();

        // Apply column filters (all dropdowns use exact match on display values)
        foreach ($this->columnFilters as $col => $val) {
            if (empty($val)) {
                continue;
            }
            $dataKey = $this->getFilterDataKey($col);
            $rows = array_values(array_filter($rows, function ($row) use ($dataKey, $val) {
                return (string) ($row[$dataKey] ?? '') === (string) $val;
            }));
        }

        // Apply sort
        if ($this->tableSortField) {
            $sortKey = $this->getFilterDataKey($this->tableSortField);
            // Sort by raw numeric keys, not display keys
            $sortKey = match ($sortKey) {
                'total_spend_display' => 'total_spend',
                'total_impressions_display' => 'total_impressions',
                'total_clicks_display' => 'total_clicks',
                'total_leads_display' => 'total_leads',
                'total_installs_display' => 'total_installs',
                'total_conversions_display' => 'total_conversions',
                'cpm_display' => 'cpm',
                'cpc_display' => 'cpc',
                'ctr_display' => 'ctr',
                'cpl_display' => 'cpl',
                'cpa_display' => 'cpa',
                'cpi_display' => 'cpi',
                default => $sortKey,
            };
            $isText = in_array($sortKey, ['industry', 'country', 'category', 'sub_category', 'funnel_stage', 'campaignObjective'], true);
            usort($rows, function ($a, $b) use ($sortKey, $isText) {
                if ($isText) {
                    $result = strcasecmp((string) ($a[$sortKey] ?? ''), (string) ($b[$sortKey] ?? ''));
                } else {
                    $result = ($a[$sortKey] ?? 0) <=> ($b[$sortKey] ?? 0);
                }
                return $this->tableSortDirection === 'asc' ? $result : -$result;
            });
        }

        return $rows;
    }

    private function formatRow(string $industryName, array $data): array
    {
        return [
            '_id' => md5($industryName . serialize($data)),
            'industry' => $industryName,
            'industry_key' => $data['industry_slug'] ?? $data['group_key'] ?? $industryName,
            'country' => $data['country'] ?? '-',
            'category' => $data['category'] ?? '-',
            'sub_category' => $data['sub_category_display'] ?? $data['sub_category'] ?? '-',
            'funnel_stage' => $data['funnel_stage'] ?? '-',
            'campaignObjective' => $data['objective'] ?? '-',
            'total_spend' => $data['total_spend'] ?? 0,
            'total_spend_display' => $this->formatCurrency($data['total_spend'] ?? 0),
            'total_impressions' => $data['total_impressions'] ?? 0,
            'total_impressions_display' => $this->formatNumber($data['total_impressions'] ?? 0),
            'total_clicks' => $data['total_clicks'] ?? 0,
            'total_clicks_display' => $this->formatNumber($data['total_clicks'] ?? 0),
            'total_leads' => $data['total_leads'] ?? 0,
            'total_leads_display' => $this->formatNumber($data['total_leads'] ?? 0),
            'total_conversions' => $data['total_conversions'] ?? 0,
            'total_conversions_display' => $this->formatNumber($data['total_conversions'] ?? 0),
            'total_installs' => $data['total_installs'] ?? 0,
            'total_installs_display' => $this->formatNumber($data['total_installs'] ?? 0),
            'cpm' => $data['metrics']['cpm']['actual'] ?? ($data['cpm'] ?? null),
            'cpm_display' => $this->formatMetricValue('cpm', $data['metrics']['cpm']['actual'] ?? ($data['cpm'] ?? null)),
            'cpc' => $data['metrics']['cpc']['actual'] ?? ($data['cpc'] ?? null),
            'cpc_display' => $this->formatMetricValue('cpc', $data['metrics']['cpc']['actual'] ?? ($data['cpc'] ?? null)),
            'ctr' => $data['metrics']['ctr']['actual'] ?? ($data['ctr'] ?? null),
            'ctr_display' => $this->formatMetricValue('ctr', $data['metrics']['ctr']['actual'] ?? ($data['ctr'] ?? null)),
            'cpl' => $data['metrics']['cpl']['actual'] ?? ($data['cpl'] ?? null),
            'cpl_display' => $this->formatMetricValue('cpl', $data['metrics']['cpl']['actual'] ?? ($data['cpl'] ?? null)),
            'cpa' => $data['metrics']['cpa']['actual'] ?? ($data['cpa'] ?? null),
            'cpa_display' => $this->formatMetricValue('cpa', $data['metrics']['cpa']['actual'] ?? ($data['cpa'] ?? null)),
            'cpi' => $data['metrics']['cpi']['actual'] ?? ($data['cpi'] ?? null),
            'cpi_display' => $this->formatMetricValue('cpi', $data['metrics']['cpi']['actual'] ?? ($data['cpi'] ?? null)),
            'global_cpm' => $this->globalBenchmarkData['cpm']['avg'] ?? null,
            'global_cpm_display' => $this->formatMetricValue('cpm', $this->globalBenchmarkData['cpm']['avg'] ?? null),
            'global_cpc' => $this->globalBenchmarkData['cpc']['avg'] ?? null,
            'global_cpc_display' => $this->formatMetricValue('cpc', $this->globalBenchmarkData['cpc']['avg'] ?? null),
            'global_ctr' => $this->globalBenchmarkData['ctr']['avg'] ?? null,
            'global_ctr_display' => $this->formatMetricValue('ctr', $this->globalBenchmarkData['ctr']['avg'] ?? null),
            'global_cpl' => $this->globalBenchmarkData['cpl']['avg'] ?? null,
            'global_cpl_display' => $this->formatMetricValue('cpl', $this->globalBenchmarkData['cpl']['avg'] ?? null),
            'global_cpa' => $this->globalBenchmarkData['cpa']['avg'] ?? null,
            'global_cpa_display' => $this->formatMetricValue('cpa', $this->globalBenchmarkData['cpa']['avg'] ?? null),
            'global_cpi' => $this->globalBenchmarkData['cpi']['avg'] ?? null,
            'global_cpi_display' => $this->formatMetricValue('cpi', $this->globalBenchmarkData['cpi']['avg'] ?? null),
        ];
    }

    private function getFilterDataKey(string $col): string
    {
        return match ($col) {
            'subCategory' => 'sub_category',
            'funnelStage' => 'funnel_stage',
            'campaignObjective' => 'campaignObjective',
            'spend' => 'total_spend_display',
            'impressions' => 'total_impressions_display',
            'clicks' => 'total_clicks_display',
            'leads' => 'total_leads_display',
            'installs' => 'total_installs_display',
            'conversions' => 'total_conversions_display',
            'cpm' => 'cpm_display',
            'cpc' => 'cpc_display',
            'ctr' => 'ctr_display',
            'cpl' => 'cpl_display',
            'cpa' => 'cpa_display',
            'cpi' => 'cpi_display',
            'global_cpm' => 'global_cpm_display',
            'global_cpc' => 'global_cpc_display',
            'global_ctr' => 'global_ctr_display',
            'global_cpl' => 'global_cpl_display',
            'global_cpa' => 'global_cpa_display',
            'global_cpi' => 'global_cpi_display',
            default => $col,
        };
    }

    private function buildColumnFilterOptions(array $rows): array
    {
        $dataKeys = [
            'industry', 'country', 'category', 'sub_category', 'funnel_stage', 'campaignObjective',
            'total_spend_display', 'total_impressions_display', 'total_clicks_display',
            'total_leads_display', 'total_installs_display', 'total_conversions_display',
            'cpm_display', 'cpc_display', 'ctr_display', 'cpl_display', 'cpa_display', 'cpi_display',
            'global_cpm_display', 'global_cpc_display', 'global_ctr_display', 'global_cpl_display', 'global_cpa_display', 'global_cpi_display',
        ];

        $bladeKeyMap = [
            'sub_category' => 'subCategory',
            'funnel_stage' => 'funnelStage',
            'campaignObjective' => 'campaignObjective',
            'total_spend_display' => 'spend',
            'total_impressions_display' => 'impressions',
            'total_clicks_display' => 'clicks',
            'total_leads_display' => 'leads',
            'total_installs_display' => 'installs',
            'total_conversions_display' => 'conversions',
            'cpm_display' => 'cpm',
            'cpc_display' => 'cpc',
            'ctr_display' => 'ctr',
            'cpl_display' => 'cpl',
            'cpa_display' => 'cpa',
            'cpi_display' => 'cpi',
            'global_cpm_display' => 'global_cpm',
            'global_cpc_display' => 'global_cpc',
            'global_ctr_display' => 'global_ctr',
            'global_cpl_display' => 'global_cpl',
            'global_cpa_display' => 'global_cpa',
            'global_cpi_display' => 'global_cpi',
        ];

        $options = [];
        foreach ($dataKeys as $dataKey) {
            $values = array_filter(array_unique(array_column($rows, $dataKey)));
            sort($values);
            $bladeKey = $bladeKeyMap[$dataKey] ?? $dataKey;
            $options[$bladeKey] = array_values($values);
        }

        return $options;
    }

    public function getColumnConfig(): array
    {
        return [
            ['key' => 'industry',         'label' => $this->tableGroupBy === 'client' ? __('labels.client') : __('Industry'), 'group' => 'account',    'filterable' => true,  'visible' => true,                               'locked' => true, 'sortable' => true,  'width' => '160px'],
            ['key' => 'country',          'label' => __('Country'),          'group' => 'account',    'filterable' => true,  'visible' => $this->visibleColumns['country'],          'sortable' => true,  'width' => '100px'],
            ['key' => 'category',         'label' => __('Category'),         'group' => 'account',    'filterable' => true,  'visible' => $this->visibleColumns['category'],         'sortable' => true,  'width' => '120px'],
            ['key' => 'subCategory',      'label' => __('labels.sub_category'), 'group' => 'account', 'filterable' => true,  'visible' => $this->visibleColumns['subCategory'],      'sortable' => true,  'width' => '120px'],
            ['key' => 'funnelStage',      'label' => __('Funnel Stage'),     'group' => 'campaign',   'filterable' => true,  'visible' => $this->visibleColumns['funnelStage'],      'sortable' => true,  'width' => '100px'],
            ['key' => 'campaignObjective','label' => __('Objective'),        'group' => 'campaign',   'filterable' => true,  'visible' => $this->visibleColumns['campaignObjective'],'sortable' => true,  'width' => '100px'],
            ['key' => 'spend',            'label' => __('Spend'),            'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['spend'],            'sortable' => true,  'width' => '110px'],
            ['key' => 'impressions',      'label' => __('Impressions'),      'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['impressions'],      'sortable' => true,  'width' => '110px'],
            ['key' => 'clicks',           'label' => __('Clicks'),           'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['clicks'],           'sortable' => true,  'width' => '100px'],
            ['key' => 'leads',            'label' => __('Leads'),            'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['leads'],            'sortable' => true,  'width' => '100px'],
            ['key' => 'installs',         'label' => __('Installs'),         'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['installs'],         'sortable' => true,  'width' => '100px'],
            ['key' => 'conversions',      'label' => __('Conversions'),      'group' => 'volume',     'filterable' => false, 'visible' => $this->visibleColumns['conversions'],      'sortable' => true,  'width' => '110px'],
            ['key' => 'cpm',              'label' => 'CPM',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['cpm'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'cpc',              'label' => 'CPC',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['cpc'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'ctr',              'label' => 'CTR',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['ctr'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'cpl',              'label' => 'CPL',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['cpl'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'cpa',              'label' => 'CPA',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['cpa'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'cpi',              'label' => 'CPI',                  'group' => 'efficiency', 'filterable' => false, 'visible' => $this->visibleColumns['cpi'],              'sortable' => true,  'width' => '90px'],
            ['key' => 'global_cpm',       'label' => 'GB CPM',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_cpm'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'global_cpc',       'label' => 'GB CPC',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_cpc'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'global_ctr',       'label' => 'GB CTR',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_ctr'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'global_cpl',       'label' => 'GB CPL',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_cpl'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'global_cpa',       'label' => 'GB CPA',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_cpa'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'global_cpi',       'label' => 'GB CPI',               'group' => 'global',     'filterable' => false, 'visible' => $this->visibleColumns['global_cpi'],       'sortable' => true,  'width' => '90px'],
            ['key' => 'actions',          'label' => __('Actions'),          'group' => 'account',    'filterable' => false, 'visible' => true,                                      'sortable' => false, 'width' => '110px'],
        ];
    }

    public function showIndustryAccounts(string $industry): void
    {
        $this->modalIndustry = $industry;
        $this->modalAccounts = AdAccount::query()
            ->select('id', 'integration_id', 'account_name', 'status', 'external_account_id', 'last_metrics_sync_at', 'category', 'industry')
            ->where('industry', $industry)
            ->with('integration:id,platform,status')
            ->with(['adCampaigns' => function ($q) {
                $q->select('id', 'ad_account_id', 'name', 'status', 'objective', 'funnel_stage')
                  ->orderBy('name');
            }])
            ->withCount('adCampaigns')
            ->withSum('adMetrics', 'spend')
            ->orderBy('account_name')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->account_name,
                'platform' => $a->integration?->platform ?? '-',
                'status' => $a->status,
                'integration_status' => $a->integration?->status ?? 'unknown',
                'external_account_id' => $a->external_account_id,
                'category' => $a->category,
                'industry' => $a->industry,
                'campaigns_count' => $a->ad_campaigns_count,
                'campaigns' => $a->adCampaigns->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'status' => $c->status,
                    'objective' => $c->objective,
                    'funnel_stage' => $c->funnel_stage,
                ])->toArray(),
                'total_spend' => (float) app(CurrencyConversionService::class)->convertToSAR($a->ad_metrics_sum_spend ?? 0, $a->currency ?? 'USD'),
                'total_spend_display' => $this->formatCurrency((float) app(CurrencyConversionService::class)->convertToSAR($a->ad_metrics_sum_spend ?? 0, $a->currency ?? 'USD')),
                'last_sync_at' => $a->last_metrics_sync_at?->diffForHumans() ?? __('Never'),
                'link' => route('ad-account-detail', ['id' => $a->id]),
            ])
            ->toArray();
        $this->showAccountsModal = true;
    }

    public function closeAccountsModal(): void
    {
        $this->showAccountsModal = false;
        $this->modalIndustry = '';
        $this->modalAccounts = [];
    }

    public function startEdit(int $accountId, string $field, string $value): void
    {
        $this->editingAccountId = $accountId;
        $this->editingField = $field;
        $this->editValue = $value;
    }

    public function cancelEdit(): void
    {
        $this->editingAccountId = null;
        $this->editingField = '';
        $this->editValue = '';
    }

    public function saveEdit(): void
    {
        if (!$this->editingAccountId || !$this->editingField) {
            $this->cancelEdit();
            return;
        }

        $account = AdAccount::find($this->editingAccountId);
        if (!$account) {
            $this->dispatch('notify', type: 'error', message: __('Account not found'));
            $this->cancelEdit();
            return;
        }

        $field = $this->editingField;
        $value = trim($this->editValue);

        if ($field === 'name') {
            $account->update(['account_name' => $value]);
            $this->dispatch('notify', type: 'success', message: __('Account name updated'));
        } elseif ($field === 'status') {
            $account->update(['status' => $value]);
            $this->dispatch('notify', type: 'success', message: __('Status updated'));
        } elseif ($field === 'industry') {
            $account->update(['industry' => $value]);
            $this->refreshData();
            $this->dispatch('notify', type: 'success', message: __('Industry reassigned'));
        } elseif ($field === 'category') {
            $account->update(['category' => $value]);
            $this->dispatch('notify', type: 'success', message: __('Category updated'));
        } elseif ($field === 'sub_industry') {
            $account->update(['sub_industry' => $value]);
            $this->dispatch('notify', type: 'success', message: __('Sub-industry updated'));
        }

        $this->cancelEdit();
        $this->refreshModalAccounts();
    }

    private function refreshModalAccounts(): void
    {
        if (!$this->modalIndustry) {
            return;
        }
        $this->modalAccounts = AdAccount::query()
            ->select('id', 'integration_id', 'account_name', 'status', 'external_account_id', 'last_metrics_sync_at', 'category', 'industry')
            ->where('industry', $this->modalIndustry)
            ->with('integration:id,platform,status')
            ->with(['adCampaigns' => function ($q) {
                $q->select('id', 'ad_account_id', 'name', 'status', 'objective', 'funnel_stage')
                  ->orderBy('name');
            }])
            ->withCount('adCampaigns')
            ->withSum('adMetrics', 'spend')
            ->orderBy('account_name')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->account_name,
                'platform' => $a->integration?->platform ?? '-',
                'status' => $a->status,
                'integration_status' => $a->integration?->status ?? 'unknown',
                'external_account_id' => $a->external_account_id,
                'category' => $a->category,
                'industry' => $a->industry,
                'campaigns_count' => $a->ad_campaigns_count,
                'campaigns' => $a->adCampaigns->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'status' => $c->status,
                    'objective' => $c->objective,
                    'funnel_stage' => $c->funnel_stage,
                ])->toArray(),
                'total_spend' => (float) app(CurrencyConversionService::class)->convertToSAR($a->ad_metrics_sum_spend ?? 0, $a->currency ?? 'USD'),
                'total_spend_display' => $this->formatCurrency((float) app(CurrencyConversionService::class)->convertToSAR($a->ad_metrics_sum_spend ?? 0, $a->currency ?? 'USD')),
                'last_sync_at' => $a->last_metrics_sync_at?->diffForHumans() ?? __('Never'),
                'link' => route('ad-account-detail', ['id' => $a->id]),
            ])
            ->toArray();
    }

    /**
     * Apply all active column filters except the given key.
     */
    private function applyColumnFiltersExcept(array $rows, string $excludeKey): array
    {
        foreach ($this->columnFilters as $col => $val) {
            if (empty($val) || $col === $excludeKey) {
                continue;
            }
            $dataKey = $this->getFilterDataKey($col);
            $rows = array_values(array_filter($rows, function ($row) use ($dataKey, $val) {
                return (string) ($row[$dataKey] ?? '') === (string) $val;
            }));
        }
        return $rows;
    }

    /**
     * Build column filter options with cascading:
     * each column's options come from rows filtered by all OTHER active column filters.
     */
    private function buildCascadingColumnFilterOptions(array $rows): array
    {
        $options = [];
        foreach ($this->getColumnConfig() as $col) {
            $key = $col['key'];
            $filteredRows = $this->applyColumnFiltersExcept($rows, $key);
            $dataKey = $this->getFilterDataKey($key);
            $values = array_filter(array_unique(array_column($filteredRows, $dataKey)));
            sort($values);
            $options[$key] = array_values($values);
        }
        return $options;
    }

    public function render()
    {
        $filteredIndustries = $this->getFilteredIndustriesProperty();
        $tableData = $this->getTableData();
        $filteredTableData = $this->getFilteredTableData();
        $columnFilterOptions = $this->buildCascadingColumnFilterOptions($tableData);

        $groupKeys = [
            'account' => ['industry', 'country', 'category', 'subCategory'],
            'campaign' => ['funnelStage', 'campaignObjective'],
            'volume' => ['spend', 'impressions', 'clicks', 'leads', 'installs', 'conversions'],
            'efficiency' => ['cpm', 'cpc', 'ctr', 'cpl', 'cpa', 'cpi'],
            'global' => ['global_cpm', 'global_cpc', 'global_ctr', 'global_cpl', 'global_cpa', 'global_cpi'],
        ];
        $visibleGroupCounts = [];
        foreach ($groupKeys as $group => $keys) {
            $visibleGroupCounts[$group] = count(array_filter($keys, fn ($k) => $k === 'industry' || ($this->visibleColumns[$k] ?? false)));
        }

        return view('livewire.pages.benchmarks', [
            'filteredIndustries' => $filteredIndustries,
            'tableData' => $tableData,
            'filteredTableData' => $filteredTableData,
            'columnFilterOptions' => $columnFilterOptions,
            'yourOverallMetrics' => $this->yourOverallMetrics,
            'yourPerformanceIndicators' => $this->yourPerformanceIndicators,
            'columnConfig' => $this->getColumnConfig(),
            'visibleGroupCounts' => $visibleGroupCounts,
            'externalBenchmarkData' => $this->externalBenchmarkData,
        ])->layout('layouts.app');
    }
}
