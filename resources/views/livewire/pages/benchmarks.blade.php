<div class="min-h-screen bg-gray-50">
    <div class="px-4 py-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    {{ __('pages.benchmarks.title') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('pages.benchmarks.description') }}
                    @if($autoRefreshEnabled)
                        <span class="inline-flex items-center ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
                            {{ __('pages.benchmarks.live_data') }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3 md:ml-4">
                {{-- Auto-refresh toggle --}}
                <div class="flex items-center space-x-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="autoRefreshEnabled" wire:change="toggleAutoRefresh" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" />
                        <span class="ml-2 text-sm text-gray-700">{{ __('pages.benchmarks.auto_refresh') }}</span>
                    </label>
                </div>

                <button wire:click="refreshData" wire:loading.attr="disabled" class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                    <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" wire:loading.class="animate-spin" wire:target="refreshData" />
                    <span class="hidden sm:inline">{{ __('pages.benchmarks.refresh') }}</span>
                    <span class="sm:hidden">{{ __('pages.benchmarks.refresh_data') }}</span>
                </button>

                <livewire:components.export-menu />
            </div>
        </div>

        {{-- Loading Skeletons for Summary --}}
        @if($loading)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                @for($i = 0; $i < 4; $i++)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-5 animate-pulse">
                            <div class="flex items-center">
                                <div class="h-12 w-12 bg-gray-200 rounded-lg"></div>
                                <div class="ml-5 flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-8 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @endif

        {{-- Enhanced Filters --}}
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-3 sm:space-y-0">
                <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('filters.benchmark_filters') }}</h3>
                    @php $activeFilterCount = count($selectedPlatforms) + count($selectedIndustries) + count($selectedCountries) + count($selectedCategories) + count($selectedSubCategories) + count($selectedFunnelStages); @endphp
                    @if($activeFilterCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                            {{ $activeFilterCount }} {{ __('active') }}
                        </span>
                    @endif
                </div>
                @if($activeFilterCount > 0)
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <x-heroicon-o-x-mark class="h-4 w-4 mr-2" />
                        {{ __('filters.clear_all') }}
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                {{-- Platform --}}
                <x-select2-multi
                    wire-model="selectedPlatforms"
                    :options="$availablePlatforms"
                    :label="__('labels.platform')"
                    icon="heroicon-o-server"
                    :placeholder="__('All Platforms')"
                />

                {{-- Industry --}}
                <x-select2-multi
                    wire-model="selectedIndustries"
                    :options="array_merge(['' => __('All')], collect($availableIndustries)->mapWithKeys(fn($ind) => [$ind => $this->getIndustryLabel($ind)])->all())"
                    :label="__('labels.industry')"
                    icon="heroicon-o-building-office"
                    :placeholder="__('All Industries')"
                />

                {{-- Country --}}
                <x-select2-multi
                    wire-model="selectedCountries"
                    :options="array_merge(['' => __('All')], array_combine($availableCountries, $availableCountries))"
                    :label="__('labels.country')"
                    icon="heroicon-o-globe-alt"
                    :placeholder="__('All Countries')"
                />

                {{-- Category --}}
                <x-select2-multi
                    wire-model="selectedCategories"
                    :options="array_merge(['' => __('All')], $availableCategories)"
                    :label="__('Category')"
                    icon="heroicon-o-tag"
                    :placeholder="__('All Categories')"
                />

                {{-- Sub-Category --}}
                <x-select2-multi
                    wire-model="selectedSubCategories"
                    :options="array_merge(['' => __('All')], $availableSubCategories)"
                    :label="__('Sub-Category')"
                    icon="heroicon-o-tag"
                    :placeholder="__('All Sub-Categories')"
                />

                {{-- Funnel Stage --}}
                <x-select2-multi
                    wire-model="selectedFunnelStages"
                    :options="array_merge(['' => __('All')], collect($availableFunnelStages)->mapWithKeys(fn($s) => [$s => $s])->all())"
                    :label="__('funnel_stages.label')"
                    icon="heroicon-o-funnel"
                    :placeholder="__('All Stages')"
                />

                {{-- Date Range --}}
                <x-date-range-picker
                    wire-model-from="dateFrom"
                    wire-model-to="dateTo"
                    :label="__('Date Range')"
                />
            </div>
        </div>

        {{-- Filter Status Indicator --}}
        @if(count($selectedIndustries) > 0 || count($selectedPlatforms) > 0 || count($selectedCountries) > 0)
            <div class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700">
                    <span class="font-semibold">{{ __('pages.benchmarks.filtered_view.label') }}</span>
                    {{ __('pages.benchmarks.filtered_view.showing') }} {{ $summary['total_accounts'] }}
                    {{ __('pages.benchmarks.filtered_view.of') }} {{ $unfilteredSummary['total_accounts'] }}
                    {{ __('pages.benchmarks.filtered_view.accounts') }}
                    @foreach($selectedIndustries as $ind)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $this->getIndustryLabel($ind) }}</span>
                    @endforeach
                    @foreach($selectedPlatforms as $pl)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($pl) }}</span>
                    @endforeach
                    @foreach($selectedCountries as $ctry)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $ctry }}</span>
                    @endforeach
                </p>
            </div>
        @endif

        {{-- Main Content --}}
        @if($loading)
            {{-- Loading Skeleton --}}
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="animate-pulse">
                        <div class="h-6 bg-gray-200 rounded w-1/4 mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                    </div>
                </div>
                <div class="p-6 animate-pulse">
                    <div class="h-64 bg-gray-200 rounded"></div>
                </div>
            </div>
        @else
            {{-- Your Performance Overview --}}
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('pages.benchmarks.your_performance_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.benchmarks.your_performance_subtitle') }}</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        {{-- Industries Tracked --}}
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-blue-600">{{ __('pages.benchmarks.industries_tracked') }}</p>
                                    <p class="text-xl font-bold text-blue-900">{{ $yourOverallMetrics['industries_count'] }}</p>
                                </div>
                                <div class="p-2 bg-blue-200 rounded-full"><x-heroicon-o-chart-bar class="h-5 w-5 text-blue-600" /></div>
                            </div>
                        </div>

                        {{-- Total Accounts --}}
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-purple-600">{{ __('pages.benchmarks.total_accounts') }}</p>
                                    <p class="text-xl font-bold text-purple-900">{{ $yourOverallMetrics['accounts_count'] }}</p>
                                </div>
                                <div class="p-2 bg-purple-200 rounded-full"><x-heroicon-o-building-office class="h-5 w-5 text-purple-600" /></div>
                            </div>
                            <p class="text-xs text-purple-600 mt-1">{{ $yourOverallMetrics['campaigns_count'] }} {{ __('pages.benchmarks.campaigns') }}</p>
                        </div>

                        {{-- Total Spend --}}
                        <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-primary-600">{{ __('pages.benchmarks.total_spend') }}</p>
                                    <p class="text-xl font-bold text-primary-900">{{ $this->formatCurrency($yourOverallMetrics['total_spend']) }}</p>
                                </div>
                                <div class="p-2 bg-primary-200 rounded-full"><x-heroicon-o-currency-dollar class="h-5 w-5 text-primary-600" /></div>
                            </div>
                        </div>

                        {{-- Total Impressions --}}
                        <div class="bg-gradient-to-r from-cyan-50 to-cyan-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-cyan-600">{{ __('pages.benchmarks.total_impressions') }}</p>
                                    <p class="text-xl font-bold text-cyan-900">{{ $this->formatNumber($yourOverallMetrics['total_impressions']) }}</p>
                                </div>
                                <div class="p-2 bg-cyan-200 rounded-full"><x-heroicon-o-eye class="h-5 w-5 text-cyan-600" /></div>
                            </div>
                        </div>

                        {{-- Total Clicks --}}
                        <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-green-600">{{ __('pages.benchmarks.total_clicks') }}</p>
                                    <p class="text-xl font-bold text-green-900">{{ $this->formatNumber($yourOverallMetrics['total_clicks']) }}</p>
                                </div>
                                <div class="p-2 bg-green-200 rounded-full"><x-heroicon-o-cursor-arrow-rays class="h-5 w-5 text-green-600" /></div>
                            </div>
                            <p class="text-xs text-green-600 mt-1">CTR: {{ number_format($yourPerformanceIndicators['ctr'], 2) }}%</p>
                        </div>

                        {{-- Total Conversions --}}
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-amber-600">{{ __('pages.benchmarks.total_conversions') }}</p>
                                    <p class="text-xl font-bold text-amber-900">{{ $this->formatNumber($yourOverallMetrics['total_conversions']) }}</p>
                                </div>
                                <div class="p-2 bg-amber-200 rounded-full"><x-heroicon-o-check-circle class="h-5 w-5 text-amber-600" /></div>
                            </div>
                            <p class="text-xs text-amber-600 mt-1">CVR: {{ number_format($yourPerformanceIndicators['cvr'], 2) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Industry Benchmarks Container --}}
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('pages.benchmarks.performance.comparison_title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('pages.benchmarks.performance.comparison_description') }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-700">{{ __('pages.benchmarks.performance.sort_by') }}</span>
                            <div class="w-48">
                                <x-select2
                                    wire-model="sortField"
                                    :options="['name' => __('pages.benchmarks.performance.sort_industry_name'), 'accounts_count' => __('pages.benchmarks.performance.sort_accounts'), 'total_spend' => __('pages.benchmarks.performance.sort_spend'), 'total_leads' => __('pages.benchmarks.performance.sort_leads')]"
                                    :allow-clear="false"
                                    :searchable="false"
                                />
                            </div>
                            <button wire:click="toggleSortDirection" class="p-1 hover:bg-gray-100 rounded transition-colors duration-150">
                                @if($sortDirection === 'asc')
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                {{-- View Mode Toggle --}}
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-700 mr-2">{{ __('View') }}:</span>
                        <button wire:click="$set('viewMode', 'card')" class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'card' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            <x-heroicon-o-squares-2x2 class="h-5 w-5 mr-1.5" />
                            {{ __('Cards') }}
                        </button>
                        <button wire:click="$set('viewMode', 'table')" class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'table' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            <x-heroicon-o-table-cells class="h-5 w-5 mr-1.5" />
                            {{ __('Table') }}
                        </button>
                    </div>
                    <div class="flex items-center space-x-3">
                        {{-- Search --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                            </div>
                            <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="{{ __('pages.benchmarks.search_placeholder') }}" class="block w-full md:w-64 pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500" />
                        </div>
                        <span class="text-sm text-gray-500 whitespace-nowrap">
                            {{ count($filteredIndustries) }} {{ $tableGroupBy === 'client' ? __('clients') : __('industries') }}
                        </span>
                    </div>
                </div>

                {{-- Card View --}}
                @if($viewMode === 'card')
                    <div class="p-4">
                        @if(count($filteredIndustries) === 0)
                            <div class="text-center py-8">
                                <p class="text-gray-500">{{ __('pages.benchmarks.performance.no_industries') }}</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($filteredIndustries as $industryKey => $industry)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-150">
                                        <button wire:click="showIndustryAccounts('{{ $industry['industry_slug'] ?? $industryKey }}')" class="text-base font-medium text-gray-900 mb-1 hover:text-primary-600 hover:underline cursor-pointer text-left">
                                            {{ $industry['industry'] ?? $this->getIndustryLabel($industryKey) }}
                                        </button>

                                        {{-- Industry Summary --}}
                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3 pb-2 border-b border-gray-100">
                                            <button wire:click="showIndustryAccounts('{{ $industry['industry_slug'] ?? $industryKey }}')"
                                                class="inline-flex items-center text-primary-600 hover:text-primary-800 hover:underline">
                                                <x-heroicon-o-eye class="h-3 w-3 mr-1" />
                                                {{ $industry['accounts_count'] ?? 0 }} {{ ($industry['accounts_count'] ?? 0) === 1 ? __('benchmarks.account') : __('benchmarks.accounts') }}
                                            </button>
                                            <span class="font-medium">{{ $this->formatCurrency($industry['total_spend'] ?? 0) }}</span>
                                        </div>

                                        {{-- Metrics --}}
                                        <div class="space-y-3">
                                            @foreach(['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'roas'] as $metricKey)
                                                @if(isset($industry['metrics'][$metricKey]))
                                                    @php $metric = $industry['metrics'][$metricKey]; @endphp
                                                    <div>
                                                        <div class="flex items-center justify-between text-sm">
                                                            <span class="text-gray-600">{{ $this->getMetricLabel($metricKey) }}:</span>
                                                            <div class="flex items-center space-x-2">
                                                                <span class="font-medium text-gray-900">{{ $this->formatMetricValue($metricKey, $metric['actual'] ?? null) }}</span>
                                                                @if(isset($metric['status']))
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClass($metric['status']) }}">
                                                                        {{ $this->getStatusLabel($metric['status']) }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if(isset($metric['benchmark']))
                                                            <div class="flex items-center justify-end text-xs text-gray-500 mt-0.5">
                                                                <span>Benchmark: <span class="font-medium">{{ $this->formatMetricValue($metricKey, $metric['benchmark']['avg'] ?? null) }}</span></span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Table View --}}
                @if($viewMode === 'table')
                    <div x-data="resizableTable" x-init="loadWidths()" @mousemove.window="onResize($event)" @mouseup.window="stopResize()">
                    <div x-data="benchmarkTable({
                        rows: @js($tableData),
                        filterOptions: @js($columnFilterOptions),
                        initialFilters: @js($columnFilters),
                        initialSort: { field: '{{ $tableSortField }}', dir: '{{ $tableSortDirection }}' },
                        initialVisibleColumns: @js($visibleColumns),
                        showAllSpend: {{ $showAllSpend ? 'true' : 'false' }},
                    })">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-base font-medium text-gray-900">
                                    {{ $tableGroupBy === 'industry' ? __('pages.benchmarks.industry_performance') : __('Client Performance') }}
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">{{ __('pages.benchmarks.hierarchical_breakdown') }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                {{-- Group By Toggle --}}
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-700">{{ __('Group by') }}:</span>
                                    <button wire:click="$set('tableGroupBy', 'industry')" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $tableGroupBy === 'industry' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        {{ __('Industry') }}
                                    </button>
                                    <button wire:click="$set('tableGroupBy', 'client')" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $tableGroupBy === 'client' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        {{ __('Client') }}
                                    </button>
                                </div>

                                {{-- Column Selector --}}
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                        <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                                        {{ __('pages.benchmarks.columns_button') }}
                                    </button>

                                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                        <div class="p-3 border-b border-gray-200">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ __('pages.benchmarks.select_columns') }}</h4>
                                        </div>
                                        <div class="max-h-80 overflow-y-auto p-2">
                                            @foreach(['account' => __('pages.benchmarks.column_groups.ad_account_level'), 'campaign' => __('pages.benchmarks.column_groups.campaign_level'), 'volume' => __('pages.benchmarks.column_groups.volume_metrics'), 'efficiency' => __('pages.benchmarks.column_groups.efficiency_metrics')] as $group => $groupLabel)
                                                <div class="mb-3">
                                                    <div class="flex items-center justify-between px-2 py-1">
                                                        <span class="text-xs font-semibold text-gray-700 uppercase">{{ $groupLabel }}</span>
                                                        <div class="flex space-x-1">
                                                            <button @click="toggleGroupColumns('{{ $group }}', true)" class="text-xs text-blue-600 hover:underline">{{ __('All') }}</button>
                                                            <span class="text-gray-300">|</span>
                                                            <button @click="toggleGroupColumns('{{ $group }}', false)" class="text-xs text-blue-600 hover:underline">{{ __('None') }}</button>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-1">
                                                        @foreach(collect($columnConfig)->where('group', $group) as $col)
                                                            <label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer {{ ($col['locked'] ?? false) ? 'opacity-50' : '' }}">
                                                                <input type="checkbox" @click.prevent="toggleColumn('{{ $col['key'] }}')" :checked="isColumnVisible('{{ $col['key'] }}')" {{ ($col['locked'] ?? false) ? 'disabled' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                                                                <span class="ml-2 text-sm text-gray-700">{{ $col['label'] }}</span>
                                                                @if($col['locked'] ?? false)
                                                                    <span class="ml-auto text-xs text-gray-400">({{ __('required') }})</span>
                                                                @endif
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="p-2 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                                            <button @click="open = false" class="w-full px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                {{ __('pages.benchmarks.column_actions.done') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Clear Filters --}}
                                <template x-if="hasActiveFilters">
                                    <button @click="clearFilters()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                        <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        {{ __('Clear filters') }}
                                    </button>
                                </template>

                                {{-- Toggle Spend Visibility --}}
                                <button @click="showAllSpend = !showAllSpend" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <template x-if="showAllSpend">
                                        <span class="inline-flex items-center">
                                            <x-heroicon-o-eye-slash class="h-4 w-4 mr-2 text-gray-500" />
                                            {{ __('Hide spend') }}
                                        </span>
                                    </template>
                                    <template x-if="!showAllSpend">
                                        <span class="inline-flex items-center">
                                            <x-heroicon-o-eye class="h-4 w-4 mr-2 text-gray-500" />
                                            {{ __('Show spend') }}
                                        </span>
                                    </template>
                                </button>

                                {{-- Reset Column Widths --}}
                                <button @click="resetWidths()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    {{ __('Reset widths') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Hierarchical Table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr class="border-b-2 border-gray-300">
                                    <th x-show="getVisibleColumnCount('account') > 0" :colspan="getVisibleColumnCount('account')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-blue-50 border-r border-gray-300">{{ __('pages.benchmarks.column_groups.ad_account_level') }}</th>
                                    <th x-show="getVisibleColumnCount('campaign') > 0" :colspan="getVisibleColumnCount('campaign')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-green-50 border-r border-gray-300">{{ __('pages.benchmarks.column_groups.campaign_level') }}</th>
                                    <th x-show="getVisibleColumnCount('volume') > 0" :colspan="getVisibleColumnCount('volume')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-purple-50 border-r border-gray-300">{{ __('pages.benchmarks.column_groups.volume_metrics') }}</th>
                                    <th x-show="getVisibleColumnCount('efficiency') > 0" :colspan="getVisibleColumnCount('efficiency')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-amber-50">{{ __('pages.benchmarks.column_groups.efficiency_metrics') }}</th>
                                </tr>
                                <tr>
                                    @foreach($columnConfig as $col)
                                        @php
                                            $key = $col['key'];
                                            $bgClass = match($col['group']) {
                                                'account' => 'bg-blue-50',
                                                'campaign' => 'bg-green-50',
                                                'volume' => 'bg-purple-50',
                                                'efficiency' => 'bg-amber-50',
                                                default => 'bg-gray-50',
                                            };
                                            $borderClass = in_array($key, ['subCategory', 'funnelStage', 'conversions']) ? 'border-r border-gray-300' : '';
                                            $alignClass = in_array($col['group'], ['volume', 'efficiency']) ? 'text-right' : 'text-left';
                                        @endphp
                                        <th x-show="isColumnVisible('{{ $key }}')" class="px-2 py-2 {{ $alignClass }} text-xs font-medium text-gray-600 uppercase {{ $bgClass }} {{ $borderClass }} relative" :style="colStyle('{{ $key }}', '{{ $col['width'] ?? 'auto' }}')">
                                            @if($col['sortable'] ?? false)
                                                <button @click="sortBy('{{ $key }}')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                                    <span>{{ $col['label'] }}</span>
                                                    <template x-if="isSorted('{{ $key }}')">
                                                        <span>
                                                            <svg x-show="isSortAsc()" class="h-3 w-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                            <svg x-show="!isSortAsc()" class="h-3 w-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        </span>
                                                    </template>
                                                    <template x-if="!isSorted('{{ $key }}')">
                                                        <svg class="h-3 w-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                                    </template>
                                                </button>
                                            @else
                                                <span>{{ $col['label'] }}</span>
                                            @endif
                                            <div @mousedown.prevent="startResize($event, '{{ $key }}')" class="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize z-20 group" title="Drag to resize">
                                                <div class="absolute right-1 top-1 bottom-1 w-px bg-gray-300 group-hover:bg-primary-500 transition-colors"></div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                                <tr class="border-b border-gray-200">
                                    @foreach($columnConfig as $col)
                                        @php
                                            $key = $col['key'];
                                            $bgClass = match($col['group']) {
                                                'account' => 'bg-blue-50',
                                                'campaign' => 'bg-green-50',
                                                'volume' => 'bg-purple-50',
                                                'efficiency' => 'bg-amber-50',
                                                default => 'bg-gray-50',
                                            };
                                        @endphp
                                        <th x-show="isColumnVisible('{{ $key }}')" class="px-2 py-1 {{ $bgClass }}">
                                            @if(in_array($key, ['industry', 'country', 'category', 'subCategory', 'funnelStage', 'campaignObjective']))
                                                <div class="filter-select2-wrap">
                                                    <x-select2
                                                        alpineKey="{{ $key }}"
                                                        :options="array_merge(['' => __('All')], array_combine($columnFilterOptions[$key] ?? [], $columnFilterOptions[$key] ?? []))"
                                                        :allowClear="false"
                                                        :searchable="true"
                                                    />
                                                </div>
                                            @elseif($key !== 'spend')
                                                <input
                                                    type="text"
                                                    x-model.debounce.300ms="columnFilters.{{ $key }}"
                                                    placeholder="{{ __('Filter') }}"
                                                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white placeholder-gray-300"
                                                />
                                            @else
                                                <div class="h-7"></div>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template x-for="row in sortedRows" :key="row._id">
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td x-show="isColumnVisible('industry')" class="px-3 py-2 text-gray-700 font-medium truncate max-w-[150px]" :title="row.industry">
                                            <button @click="$wire.showIndustryAccounts(row.industry_key)" class="text-primary-600 hover:text-primary-800 hover:underline cursor-pointer text-left font-medium" x-text="row.industry"></button>
                                        </td>
                                        <td x-show="isColumnVisible('country')" class="px-3 py-2 text-gray-700" x-text="row.country"></td>
                                        <td x-show="isColumnVisible('category')" class="px-3 py-2 text-gray-700" x-text="row.category"></td>
                                        <td x-show="isColumnVisible('subCategory')" class="px-3 py-2 text-gray-600" x-text="row.sub_category"></td>
                                        <td x-show="isColumnVisible('funnelStage')" class="px-3 py-2 whitespace-nowrap">
                                            <span :class="getFunnelBadgeClass(row.funnel_stage)" x-text="row.funnel_stage"></span>
                                        </td>
                                        <td x-show="isColumnVisible('campaignObjective')" class="px-3 py-2 text-gray-600 capitalize" x-text="row.campaignObjective"></td>
                                        <td x-show="isColumnVisible('spend')" class="px-3 py-2 text-right text-gray-900 font-medium whitespace-nowrap">
                                            <template x-if="showAllSpend">
                                                <span x-text="row.total_spend_display"></span>
                                            </template>
                                            <template x-if="!showAllSpend">
                                                <span class="relative cursor-help inline-block group">
                                                    <span class="text-gray-400">***</span>
                                                    <span class="hidden group-hover:block absolute bottom-full right-0 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded shadow-lg whitespace-nowrap z-50" x-text="row.total_spend_display"></span>
                                                </span>
                                            </template>
                                        </td>
                                        <td x-show="isColumnVisible('impressions')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.total_impressions_display"></td>
                                        <td x-show="isColumnVisible('clicks')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.total_clicks_display"></td>
                                        <td x-show="isColumnVisible('leads')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.total_leads_display"></td>
                                        <td x-show="isColumnVisible('installs')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.total_installs_display"></td>
                                        <td x-show="isColumnVisible('conversions')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.total_conversions_display"></td>
                                        <td x-show="isColumnVisible('cpm')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.cpm_display"></td>
                                        <td x-show="isColumnVisible('cpc')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.cpc_display"></td>
                                        <td x-show="isColumnVisible('ctr')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.ctr_display"></td>
                                        <td x-show="isColumnVisible('cpl')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.cpl_display"></td>
                                        <td x-show="isColumnVisible('cpa')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.cpa_display"></td>
                                        <td x-show="isColumnVisible('cpi')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap" x-text="row.cpi_display"></td>
                                        <td x-show="isColumnVisible('actions')" class="px-3 py-2 whitespace-nowrap">
                                            <button @click="$wire.showIndustryAccounts(row.industry_key)"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-primary-600 bg-primary-50 rounded hover:bg-primary-100 transition-colors">
                                                <x-heroicon-o-eye class="h-3 w-3 mr-1" />
                                                {{ __('View Accounts') }}
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="sortedRows.length === 0">
                                    <tr>
                                        <td colspan="20" class="px-6 py-12 text-center text-gray-500">
                                            {{ __('pages.benchmarks.performance.no_industries') }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <style>
                        .filter-select2-wrap .select2-container .select2-selection--single {
                            height: 26px !important;
                            font-size: 12px;
                            border-color: #e5e7eb;
                        }
                        .filter-select2-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
                            line-height: 24px;
                            padding-left: 6px;
                            padding-right: 20px;
                        }
                        .filter-select2-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
                            height: 24px;
                            width: 20px;
                        }
                    </style>
                </div>
                </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Accounts Modal — proven pattern: @if conditional, backdrop on container, simple centered panel --}}
    @if($showAccountsModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto h-full w-full z-50"
         x-data="{
             liveSpend: {},
             loadingSpend: false,
             expandedAccounts: {},
             formatCurrency(amount) {
                 const fmt = (n, d) => n.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
                 if (amount >= 1000000) return 'SAR ' + fmt(amount / 1000000, 1) + 'M';
                 if (amount >= 1000) return 'SAR ' + fmt(amount / 1000, 1) + 'K';
                 return 'SAR ' + fmt(amount, 2);
             },
             init() {
                 this.fetchLiveSpend();
             },
             fetchLiveSpend() {
                 this.loadingSpend = true;
                 const accountIds = {{ json_encode(array_column($modalAccounts, 'id')) }};
                 if (!accountIds.length) {
                     this.loadingSpend = false;
                     return;
                 }
                 fetch('/api/ad-accounts/live-spend-batch', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                     },
                     credentials: 'same-origin',
                     body: JSON.stringify({ account_ids: accountIds }),
                 })
                 .then(r => {
                     if (!r.ok) throw new Error('HTTP ' + r.status);
                     return r.json();
                 })
                 .then(data => {
                     this.liveSpend = data;
                     this.loadingSpend = false;
                 })
                 .catch(err => {
                     console.warn('Live spend fetch failed:', err);
                     this.loadingSpend = false;
                 });
             }
         }"
         x-on:keydown.escape.window="$wire.closeAccountsModal()">
        <div class="relative top-10 mx-auto border shadow-2xl rounded-xl bg-white max-w-7xl w-[98%] sm:w-[95%] my-8 overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5 sm:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                            <x-heroicon-o-building-office-2 class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg leading-6 font-semibold text-white">{{ $modalIndustry }}</h3>
                            <p class="mt-0.5 text-sm text-primary-100">{{ count($modalAccounts) }} {{ count($modalAccounts) === 1 ? __('account') : __('accounts') }}</p>
                        </div>
                    </div>
                    <button @click="$wire.closeAccountsModal()" class="text-primary-200 hover:text-white transition-colors">
                        <x-heroicon-o-x-mark class="h-6 w-6" />
                    </button>
                </div>
            </div>

            {{-- Content --}}
            <div class="px-0 py-0 max-h-[60vh] overflow-y-auto">
                @php
                    /** @var array<string,string> Hardcoded SVG map — safe for {!! !!} output */
                    $platformSvgs = [
                        'facebook' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                        'google' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M3.292 16.794l6.474-11.218a3.2 3.2 0 014.374-1.17 3.2 3.2 0 011.17 4.374L8.835 19.997a3.2 3.2 0 01-5.543-3.203z" fill="#FBBC04"/><path d="M20.708 16.794L14.234 5.576a3.2 3.2 0 00-4.374-1.17 3.2 3.2 0 00-1.17 4.374l6.475 11.217a3.2 3.2 0 005.543-3.203z" fill="#4285F4"/><circle cx="6.036" cy="18.396" r="3.2" fill="#34A853"/></svg>',
                        'tiktok' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.81a8.23 8.23 0 004.76 1.5V6.86a4.85 4.85 0 01-1-.17z"/></svg>',
                        'snapchat' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.922-.214.04-.012.06-.012.06-.012.076 0 .166.045.226.135a.34.34 0 01.046.296c-.093.285-.3.51-.478.63a3.08 3.08 0 01-.226.136c-.12.06-.226.12-.33.195-.195.135-.27.315-.225.525.06.27.254.57.449.87.09.135.195.285.3.435.658.9 1.484 1.635 1.92 1.845.09.045.165.09.225.135.135.09.21.21.21.361a.46.46 0 01-.165.33c-.39.345-1.11.585-1.875.72a4.04 4.04 0 01-.285.045c-.09.015-.18.03-.27.061-.15.06-.195.18-.21.272v.015a1.14 1.14 0 01-.045.21c-.06.135-.195.24-.4.299a5.5 5.5 0 01-.975.18c-.24.03-.48.06-.705.12-.33.09-.615.315-.975.615-.495.405-1.11.915-2.19.915h-.03c-1.08 0-1.695-.51-2.19-.915-.36-.3-.645-.525-.975-.615-.225-.06-.465-.09-.705-.12a5.78 5.78 0 01-.975-.18c-.21-.06-.345-.165-.4-.3v-.014a1.32 1.32 0 01-.046-.211v-.016c-.015-.09-.06-.21-.21-.27a2.75 2.75 0 00-.27-.06c-.105-.015-.21-.03-.285-.045-.765-.135-1.485-.375-1.875-.72a.46.46 0 01-.165-.33c0-.15.075-.27.21-.36l.015-.015c.06-.045.135-.09.225-.135.435-.21 1.26-.945 1.92-1.845.105-.15.21-.3.3-.435.195-.3.39-.6.449-.87.046-.21-.03-.39-.225-.525-.105-.075-.21-.135-.33-.195a3.08 3.08 0 01-.225-.136c-.18-.12-.39-.345-.48-.63a.34.34 0 01.046-.296c.06-.09.15-.135.226-.135 0 0 .015 0 .06.012.263.094.622.23.922.214.198 0 .326-.045.4-.09l-.002-.06-.003-.06c-.104-1.628-.23-3.654.299-4.847C7.653 1.069 11.016.793 12.006.793h.2z"/></svg>',
                        'linkedin' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                        'twitter' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                    ];
                    $platformColors = [
                        'facebook' => 'text-blue-600',
                        'google' => 'text-red-500',
                        'tiktok' => 'text-gray-800',
                        'snapchat' => 'text-yellow-500',
                        'linkedin' => 'text-blue-700',
                        'twitter' => 'text-gray-900',
                    ];
                    $platformLabels = [
                        'facebook' => 'Meta',
                        'google' => 'Google',
                        'tiktok' => 'TikTok',
                        'snapchat' => 'Snapchat',
                        'linkedin' => 'LinkedIn',
                        'twitter' => 'X',
                    ];
                @endphp

                @forelse($modalAccounts as $account)
                    @if($loop->first)
                        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                            <p class="text-sm text-gray-600">
                                <span class="font-medium text-gray-900">{{ __('Industry') }}:</span> {{ $modalIndustry }}
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr class="border-b border-gray-200">
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-10">{{ __('Plat') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">{{ __('Industry') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[180px]">{{ __('Account') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">{{ __('Category') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[100px]">{{ __('External ID') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-20">{{ __('Status') }}</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider w-16">{{ __('Camp') }}</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">{{ __('Spend') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">{{ __('Sync') }}</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-16">{{ __('Act') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                    @endif
                                <tr class="hover:bg-gray-50 transition-colors duration-100 even:bg-gray-50/50">
                                    {{-- Platform --}}
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-center w-8 h-8 rounded bg-gray-50 border border-gray-100">
                                            @if(isset($platformSvgs[$account['platform']]))
                                                <span class="{{ $platformColors[$account['platform']] }}">
                                                    {!! $platformSvgs[$account['platform']] !!}
                                                </span>
                                            @else
                                                <x-heroicon-o-server class="h-4 w-4 text-gray-400" />
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Industry --}}
                                    <td class="px-3 py-2.5 group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'industry')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="text-xs border border-gray-300 rounded px-2 py-0.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white max-w-[120px]">
                                                @foreach($availableIndustries as $indSlug)
                                                    <option value="{{ $indSlug }}">{{ $this->getIndustryLabel($indSlug) }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <button wire:click="startEdit({{ $account['id'] }}, 'industry', '{{ $account['industry'] }}')"
                                                    class="inline-flex items-center text-xs text-gray-500 hover:text-primary-600 transition-colors truncate max-w-[120px]"
                                                    title="{{ __('Edit industry') }}">
                                                <span class="truncate max-w-[110px] inline-block">{{ $account['industry'] ?? '-' }}</span>
                                                <x-heroicon-o-pencil class="h-3 w-3 ml-1 opacity-0 group-hover:opacity-100" />
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Account Name --}}
                                    <td class="px-3 py-2.5 group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'name')
                                            <input type="text"
                                                   wire:model="editValue"
                                                   wire:keydown.enter="saveEdit"
                                                   wire:keydown.escape="cancelEdit"
                                                   x-init="$nextTick(() => $el.focus())"
                                                   @blur="$wire.saveEdit()"
                                                   class="text-sm font-medium text-gray-900 border border-primary-300 rounded px-2 py-0.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 w-full max-w-[220px]"
                                            />
                                        @else
                                            <div class="flex items-center space-x-1.5">
                                                <button @click="expandedAccounts[{{ $account['id'] }}] = !expandedAccounts[{{ $account['id'] }}]"
                                                        class="text-gray-400 hover:text-primary-500 transition-colors flex-shrink-0"
                                                        title="{{ __('Toggle campaigns') }}">
                                                    <x-heroicon-o-chevron-down class="h-4 w-4 transition-transform duration-200"
                                                        x-bind:class="expandedAccounts[{{ $account['id'] }}] ? 'rotate-180' : ''" />
                                                </button>
                                                <a href="{{ route('ad-account-detail', ['id' => $account['id']]) }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-gray-900 hover:text-primary-600 truncate max-w-[200px] inline-block" title="{{ $account['name'] }}">
                                                    {{ $account['name'] }}
                                                </a>
                                                <button wire:click="startEdit({{ $account['id'] }}, 'name', '{{ addslashes($account['name']) }}')"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-primary-500 transition-opacity flex-shrink-0"
                                                        title="{{ __('Edit name') }}">
                                                    <x-heroicon-o-pencil class="h-3 w-3" />
                                                </button>
                                            </div>
                                        @endif
                                        @if($account['integration_status'] === 'needs_reauth')
                                            <span class="inline-flex items-center mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700 border border-amber-200">
                                                <x-heroicon-o-exclamation-triangle class="h-3 w-3 mr-0.5" />
                                                {{ __('Re-auth') }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Category --}}
                                    <td class="px-3 py-2.5 group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'category')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="text-xs border border-gray-300 rounded px-2 py-0.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white max-w-[140px]">
                                                <option value="">{{ __('-') }}</option>
                                                @php
                                                    $categories = App\Models\AdAccount::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->toArray();
                                                @endphp
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <button wire:click="startEdit({{ $account['id'] }}, 'category', '{{ addslashes($account['category'] ?? '') }}')"
                                                    class="inline-flex items-center text-xs text-gray-500 hover:text-primary-600 transition-colors"
                                                    title="{{ __('Edit category') }}">
                                                <span class="truncate max-w-[130px] inline-block">{{ $account['category'] ?? '-' }}</span>
                                                <x-heroicon-o-pencil class="h-3 w-3 ml-1 opacity-0 group-hover:opacity-100" />
                                            </button>
                                        @endif
                                    </td>

                                    {{-- External ID --}}
                                    <td class="px-3 py-2.5">
                                        <span class="text-xs text-gray-400 font-mono">{{ $account['external_account_id'] ?? '-' }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-3 py-2.5">
                                        @if($editingAccountId === $account['id'] && $editingField === 'status')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="text-xs font-medium border border-gray-300 rounded-full px-2 py-0.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                                <option value="active">{{ __('Active') }}</option>
                                                <option value="inactive">{{ __('Inactive') }}</option>
                                                <option value="permission_denied">{{ __('Access Denied') }}</option>
                                            </select>
                                        @else
                                            <button wire:click="startEdit({{ $account['id'] }}, 'status', '{{ $account['status'] }}')"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer
                                                        {{ $account['status'] === 'active' ? 'bg-green-100 text-green-700' : ($account['status'] === 'permission_denied' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5
                                                    {{ $account['status'] === 'active' ? 'bg-green-500' : ($account['status'] === 'permission_denied' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                                                {{ $account['status'] === 'permission_denied' ? __('Access Denied') : ucfirst($account['status']) }}
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Campaigns --}}
                                    <td class="px-3 py-2.5 text-right text-gray-700">
                                        {{ $account['campaigns_count'] }}
                                    </td>

                                    {{-- Spend --}}
                                    <td class="px-3 py-2.5 text-right">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span class="text-sm font-bold text-gray-900"
                                                  x-text="liveSpend[{{ $account['id'] }}] ? formatCurrency(liveSpend[{{ $account['id'] }}].spend) : '{{ $account['total_spend_display'] }}'">
                                            </span>
                                            <template x-if="loadingSpend && !liveSpend[{{ $account['id'] }}]">
                                                <span class="inline-block w-3 h-3 border-2 border-gray-200 border-t-primary-500 rounded-full animate-spin"></span>
                                            </template>
                                            <template x-if="liveSpend[{{ $account['id'] }}]?.source === 'api'">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500" title="{{ __('Live API data') }}"></span>
                                            </template>
                                            <template x-if="liveSpend[{{ $account['id'] }}]?.source === 'db'">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-gray-300" title="{{ __('Cached DB data') }}"></span>
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Last Sync --}}
                                    <td class="px-3 py-2.5">
                                        <span class="text-xs text-gray-400 flex items-center">
                                            <x-heroicon-o-clock class="h-3.5 w-3.5 mr-1 flex-shrink-0" />
                                            {{ $account['last_sync_at'] }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-3 py-2.5">
                                        <a href="{{ $account['link'] }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center text-xs text-primary-600 hover:text-primary-800 transition-colors"
                                           title="{{ __('View account') }}">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5 mr-1" />
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                                {{-- Campaigns (collapsible) --}}
                                <tr x-show="expandedAccounts[{{ $account['id'] }}]" x-cloak class="bg-gray-50/50">
                                    <td colspan="10" class="px-0 py-0">
                                        <div class="px-8 py-3">
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                                {{ __('Campaigns') }} ({{ count($account['campaigns']) }})
                                            </h4>
                                            @if(empty($account['campaigns']))
                                                <p class="text-sm text-gray-400 italic">{{ __('No campaigns') }}</p>
                                            @else
                                                <div class="space-y-1">
                                                    @foreach($account['campaigns'] as $campaign)
                                                        <div class="flex items-center justify-between py-1.5 px-3 bg-white rounded border border-gray-100 text-sm">
                                                            <span class="text-gray-700 truncate max-w-[300px]">{{ $campaign['name'] }}</span>
                                                            <div class="flex items-center space-x-3 flex-shrink-0">
                                                                @if($campaign['objective'])
                                                                    <span class="text-xs text-gray-400 capitalize">{{ $campaign['objective'] }}</span>
                                                                @endif
                                                                @if($campaign['funnel_stage'])
                                                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600">{{ $campaign['funnel_stage'] }}</span>
                                                                @endif
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium
                                                                    {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                                    {{ ucfirst($campaign['status'] ?? 'unknown') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                    @if($loop->last)
                            </tbody>
                        </table>
                        </div>
                    @endif
                @empty
                    <div class="text-center py-12 px-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <x-heroicon-o-building-office-2 class="h-8 w-8 text-gray-400" />
                        </div>
                        <p class="text-gray-500 font-medium">{{ __('No accounts found for this industry.') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="bg-gray-50 px-6 py-4 sm:px-8 flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    @php $totalSpend = array_sum(array_column($modalAccounts, 'total_spend')); @endphp
                    {{ __('Combined Spend') }}: <span class="font-semibold text-gray-700">{{ $this->formatCurrency($totalSpend) }}</span>
                </p>
                <button @click="$wire.closeAccountsModal()" type="button" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
