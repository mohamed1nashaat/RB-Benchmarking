<div class="min-h-screen bg-gray-50">
    <div class="px-4 sm:px-6 lg:px-8 py-6">

        {{-- Page Header --}}
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-semibold text-gray-900">{{ __('pages.industry_overview.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ __('pages.industry_overview.description') }}</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                        {{ __('Export') }}
                    </button>
                    <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <x-heroicon-o-share class="w-4 h-4 mr-2" />
                        {{ __('Share') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-medium text-gray-900">{{ __('Filters') }}</h3>
                @if($platform || !empty($selectedIndustries) || $preset !== '30d')
                    <button wire:click="resetFilters" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        {{ __('Clear filters') }}
                    </button>
                @endif
            </div>

            {{-- Date Range Presets --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Time Period') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($datePresets as $dp)
                        <button
                            wire:click="applyDatePreset('{{ $dp['value'] }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md {{ $preset === $dp['value'] ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            {{ $dp['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Custom Date Range, Platform & Industry Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @if($preset === 'custom')
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Custom Date Range') }}</label>
                        <div class="flex space-x-2">
                            <input wire:model.live="dateFrom" type="date" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                            <span class="flex items-center text-gray-400">&rarr;</span>
                            <input wire:model.live="dateTo" type="date" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                        </div>
                    </div>
                @endif

                <div>
                    <x-select2
                        wire-model="platform"
                        :label="__('Platform')"
                        :placeholder="__('pages.industry_overview.all_platforms')"
                        :options="array_merge(['' => __('All')], ['facebook' => __('platforms.facebook'), 'google' => __('pages.industry_overview.google_ads'), 'tiktok' => __('platforms.tiktok'), 'snapchat' => __('pages.industry_overview.snapchat'), 'linkedin' => 'LinkedIn'])"
                        :allowClear="true"
                    />
                </div>

                <div class="{{ $preset === 'custom' ? '' : 'md:col-span-2' }}">
                    <x-select2-multi
                        wire-model="selectedIndustries"
                        :label="__('Industries')"
                        :placeholder="__('All Industries')"
                        :options="$availableIndustries"
                    />
                </div>

                <div class="flex items-end">
                    <button wire:click="fetchData" wire:loading.attr="disabled" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading wire:target="fetchData">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span wire:loading wire:target="fetchData">{{ __('pages.industry_overview.loading') }}</span>
                        <span wire:loading.remove wire:target="fetchData">{{ __('dashboard.refresh') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary KPI Cards --}}
        @if($loading)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                @for($i = 0; $i < 6; $i++)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 animate-pulse">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
                            <div class="flex-1">
                                <div class="h-3 bg-gray-200 rounded w-16 mb-2"></div>
                                <div class="h-6 bg-gray-200 rounded w-20"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @elseif($totals)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                {{-- Industries --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-squares-2x2 class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_overview.total_industries') }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $totals['total_industries'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Accounts --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-users class="w-6 h-6 text-green-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_overview.total_accounts') }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totals['total_accounts']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Spend --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-currency-dollar class="w-6 h-6 text-yellow-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('kpis.spend') }}</p>
                            <p class="text-2xl font-semibold text-gray-900"><span class="text-xs font-normal text-gray-500">SAR</span> {{ formatNumber($totals['total_spend']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Clicks --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-cursor-arrow-rays class="w-6 h-6 text-purple-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('Total Clicks') }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatNumber($totals['total_clicks']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Avg CTR --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-chart-bar class="w-6 h-6 text-indigo-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('Avg CTR') }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totals['avg_ctr'], 2) }}%</p>
                        </div>
                    </div>
                </div>

                {{-- Avg CPC --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <x-heroicon-s-banknotes class="w-6 h-6 text-orange-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">{{ __('Avg CPC') }}</p>
                            <p class="text-2xl font-semibold text-gray-900"><span class="text-xs font-normal text-gray-500">SAR</span> {{ number_format($totals['avg_cpc'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Error State --}}
        @if($error)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex">
                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-400" />
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ __('pages.industry_overview.error_loading') }}</h3>
                        <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$loading && $totals && count($industryData) > 0)
            {{-- Charts --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {{-- Top Industries by Metric --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-medium text-gray-900">{{ __('Top Industries by') }} {{ ucfirst($currentMetric) }}</h3>
                            <div class="flex flex-wrap gap-1">
                                @foreach(['spend' => __('Spend'), 'clicks' => __('Clicks'), 'impressions' => __('Impressions'), 'leads' => __('Leads')] as $key => $label)
                                    <button wire:click="$set('currentMetric', '{{ $key }}')" class="px-2.5 py-1 text-xs font-medium rounded-md {{ $currentMetric === $key ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div style="height: 400px;"
                             wire:key="bar-{{ md5(json_encode($topIndustriesChartConfig)) }}"
                             x-data x-init="new Chart($refs.barCanvas, @js($topIndustriesChartConfig))">
                            <canvas x-ref="barCanvas"></canvas>
                        </div>
                    </div>

                    {{-- Monthly Spend Trend --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('Monthly Spend Trend') }}</h3>
                        @if(count($monthlyTrend) > 0)
                            <div style="height: 400px;"
                                 wire:key="trend-{{ md5(json_encode($monthlyTrendChartConfig)) }}"
                                 x-data x-init="new Chart($refs.trendCanvas, @js($monthlyTrendChartConfig))">
                                <canvas x-ref="trendCanvas"></canvas>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-[400px] text-gray-400">
                                <p class="text-sm">{{ __('messages.no_data') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Industry Efficiency Comparison --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('Industry Efficiency Comparison') }}</h3>
                        @if(count($industryEfficiency) > 0)
                            <div style="height: 400px;"
                                 wire:key="efficiency-{{ md5(json_encode($efficiencyChartConfig)) }}"
                                 x-data x-init="new Chart($refs.efficiencyCanvas, @js($efficiencyChartConfig))">
                                <canvas x-ref="efficiencyCanvas"></canvas>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-[400px] text-gray-400">
                                <p class="text-sm">{{ __('messages.no_data') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Platform Distribution --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('Accounts by Platform') }}</h3>
                        <div style="height: 400px;"
                             wire:key="doughnut-{{ md5(json_encode($platformChartConfig)) }}"
                             x-data x-init="new Chart($refs.doughnutCanvas, @js($platformChartConfig))">
                            <canvas x-ref="doughnutCanvas"></canvas>
                        </div>
                    </div>
                </div>
            {{-- Industry Performance Table --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    {{-- Table Header --}}
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-base font-medium text-gray-900">{{ __('pages.industry_overview.industry_performance') }}</h2>
                                <p class="text-sm text-gray-600 mt-1">{{ __('Click to expand industry categories') }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                {{-- Column group toggle --}}
                                <div class="flex rounded-md shadow-sm">
                                    @foreach(['all' => __('All'), 'efficiency' => __('Efficiency'), 'conversions' => __('Conversions')] as $key => $label)
                                        <button wire:click="$set('tableMetric', '{{ $key }}')" class="px-3 py-1.5 text-xs font-medium {{ $tableMetric === $key ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} {{ $loop->first ? 'rounded-l-md' : '' }} {{ $loop->last ? 'rounded-r-md' : '' }} border border-gray-300 {{ !$loop->first ? '-ml-px' : '' }}">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Search --}}
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="{{ __('Search industries...') }}" class="block w-full md:w-56 pl-9 pr-9 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500" />
                                    @if($searchQuery)
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button wire:click="$set('searchQuery', '')" class="text-gray-400 hover:text-gray-600">
                                                <x-heroicon-s-x-circle class="h-4 w-4" />
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-500 whitespace-nowrap">
                                    {{ count($filteredIndustryData) }} / {{ count($industryData) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto" style="max-height: 700px; overflow-y: auto;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-20">
                                <tr>
                                    @php
                                        $columns = [
                                            ['key' => 'industry', 'label' => __('pages.industry_overview.industry'), 'group' => 'core'],
                                            ['key' => 'accounts', 'label' => __('pages.industry_overview.accounts'), 'group' => 'core'],
                                            ['key' => 'platforms', 'label' => __('Platforms'), 'group' => 'core', 'sortable' => false],
                                            ['key' => 'impressions', 'label' => __('Impressions'), 'group' => 'core'],
                                            ['key' => 'clicks', 'label' => __('Clicks'), 'group' => 'core'],
                                            ['key' => 'spend', 'label' => __('kpis.spend'), 'group' => 'core'],
                                            ['key' => 'ctr', 'label' => __('CTR'), 'group' => 'efficiency'],
                                            ['key' => 'cpc', 'label' => __('CPC'), 'group' => 'efficiency'],
                                            ['key' => 'cpm', 'label' => __('CPM'), 'group' => 'efficiency'],
                                            ['key' => 'leads', 'label' => __('Leads'), 'group' => 'conversions'],
                                            ['key' => 'cvr', 'label' => __('CVR'), 'group' => 'conversions'],
                                            ['key' => 'cpl', 'label' => __('CPL'), 'group' => 'conversions'],
                                        ];
                                    @endphp
                                    @foreach($columns as $col)
                                        @if($col['group'] === 'core' || $tableMetric === 'all' || $tableMetric === $col['group'])
                                            <th
                                                @if(($col['sortable'] ?? true))
                                                    wire:click="handleSort('{{ $col['key'] }}')"
                                                @endif
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider {{ ($col['sortable'] ?? true) ? 'cursor-pointer hover:bg-gray-100' : '' }} whitespace-nowrap"
                                            >
                                                <div class="flex items-center space-x-1">
                                                    <span>{{ $col['label'] }}</span>
                                                    @if($sortBy === $col['key'])
                                                        <x-heroicon-s-chevron-down class="w-3.5 h-3.5 text-primary-600 transition-transform {{ $sortOrder === 'asc' ? 'rotate-180' : '' }}" />
                                                    @endif
                                                </div>
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($filteredIndustryData as $item)
                                    {{-- Industry Row --}}
                                    <tr wire:click="toggleIndustry('{{ $item['industry'] }}')" class="hover:bg-gray-50 cursor-pointer transition-colors">
                                        {{-- Industry Name --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if(in_array($item['industry'], $expandedIndustries))
                                                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                                @else
                                                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" />
                                                @endif
                                                <span class="text-sm font-medium text-gray-900">{{ $item['industry_display'] }}</span>
                                            </div>
                                        </td>

                                        {{-- Accounts --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-900">{{ number_format($item['accounts_count']) }}</td>

                                        {{-- Platform Badges --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-1">
                                                @php
                                                    $platformBadgeColors = [
                                                        'facebook' => 'bg-blue-100 text-blue-700',
                                                        'google' => 'bg-red-100 text-red-700',
                                                        'tiktok' => 'bg-cyan-100 text-cyan-700',
                                                        'snapchat' => 'bg-yellow-100 text-yellow-700',
                                                        'linkedin' => 'bg-sky-100 text-sky-700',
                                                    ];
                                                    $platformShortNames = [
                                                        'facebook' => 'FB',
                                                        'google' => 'G',
                                                        'tiktok' => 'TT',
                                                        'snapchat' => 'SC',
                                                        'linkedin' => 'LI',
                                                    ];
                                                @endphp
                                                @foreach($item['platform_counts'] ?? [] as $p => $cnt)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $platformBadgeColors[$p] ?? 'bg-gray-100 text-gray-700' }}">
                                                        {{ $platformShortNames[$p] ?? strtoupper(substr($p, 0, 2)) }} {{ $cnt }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>

                                        {{-- Impressions --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-900">{{ formatNumber($item['total_impressions']) }}</td>

                                        {{-- Clicks --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-900">{{ formatNumber($item['total_clicks']) }}</td>

                                        {{-- Spend --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-900">
                                            <span class="text-xs text-gray-500">SAR</span> {{ number_format($item['total_spend'], 2) }}
                                        </td>

                                        {{-- Efficiency columns --}}
                                        @if($tableMetric === 'all' || $tableMetric === 'efficiency')
                                            {{-- CTR --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-gray-900">{{ number_format($item['ctr'], 2) }}%</span>
                                                    @if(!empty($item['benchmark_status']['ctr']) && $item['benchmark_status']['ctr'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['ctr']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPC --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-gray-900"><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpc'], 2) }}</span>
                                                    @if(!empty($item['benchmark_status']['cpc']) && $item['benchmark_status']['cpc'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cpc']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPM --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-gray-900"><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpm'], 2) }}</span>
                                                    @if(!empty($item['benchmark_status']['cpm']) && $item['benchmark_status']['cpm'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cpm']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif

                                        {{-- Conversion columns --}}
                                        @if($tableMetric === 'all' || $tableMetric === 'conversions')
                                            {{-- Leads --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-900">{{ formatNumber($item['total_leads']) }}</td>
                                            {{-- CVR --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-gray-900">{{ number_format($item['cvr'], 2) }}%</span>
                                                    @if(!empty($item['benchmark_status']['cvr']) && $item['benchmark_status']['cvr'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cvr']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPL --}}
                                            <td class="px-4 py-3.5 whitespace-nowrap text-sm">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-gray-900"><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpl'], 2) }}</span>
                                                    @if(!empty($item['benchmark_status']['cpl']) && $item['benchmark_status']['cpl'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cpl']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    {{-- Expanded Category Sub-Rows --}}
                                    @if(in_array($item['industry'], $expandedIndustries) && !empty($item['categories']))
                                        @foreach($item['categories'] as $category)
                                            <tr class="bg-gray-50/70 hover:bg-gray-100 transition-colors">
                                                {{-- Category Name --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <div class="flex items-center pl-10">
                                                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-2"></div>
                                                        <span class="text-sm text-gray-700">{{ $category['category_display'] }}</span>
                                                    </div>
                                                </td>

                                                {{-- Accounts --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ number_format($category['accounts_count']) }}</td>

                                                {{-- Platforms (empty for sub-rows) --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-400">&mdash;</td>

                                                {{-- Impressions --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ formatNumber($category['total_impressions']) }}</td>

                                                {{-- Clicks --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ formatNumber($category['total_clicks']) }}</td>

                                                {{-- Spend --}}
                                                <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">
                                                    <span class="text-xs text-gray-400">SAR</span> {{ number_format($category['total_spend'], 2) }}
                                                </td>

                                                {{-- Efficiency columns --}}
                                                @if($tableMetric === 'all' || $tableMetric === 'efficiency')
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ number_format($category['ctr'], 2) }}%</td>
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpc'], 2) }}</td>
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpm'], 2) }}</td>
                                                @endif

                                                {{-- Conversion columns --}}
                                                @if($tableMetric === 'all' || $tableMetric === 'conversions')
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ formatNumber($category['total_leads']) }}</td>
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600">{{ number_format($category['cvr'], 2) }}%</td>
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpl'], 2) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Benchmark Legend --}}
                    <div class="px-6 py-3 border-t border-gray-200 bg-gray-50/50">
                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                            <span class="font-medium">{{ __('Performance') }}:</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span> {{ __('Excellent') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1"></span> {{ __('Good') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-1"></span> {{ __('Average') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-orange-500 mr-1"></span> {{ __('Below Avg') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span> {{ __('Poor') }}</span>
                        </div>
                    </div>
                </div>
        @elseif(!$loading && count($industryData) === 0)
            {{-- Empty State --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_data') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.industry_overview.no_data') }}</p>
            </div>
        @endif

        {{-- Loading State (full-page spinner for initial load) --}}
        @if($loading && !$totals)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="animate-spin mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.industry_overview.loading') }}</h3>
            </div>
        @endif
    </div>
</div>

@php
function formatNumber($num) {
    if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
    if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
    if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
    return number_format($num);
}

function benchmarkDotColor(string $status): string {
    return match($status) {
        'excellent' => 'bg-green-500',
        'good' => 'bg-blue-500',
        'average' => 'bg-yellow-500',
        'below_average' => 'bg-orange-500',
        'poor' => 'bg-red-500',
        default => 'bg-gray-300',
    };
}
@endphp
