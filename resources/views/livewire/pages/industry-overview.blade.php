<div class="min-h-screen bg-[#F7F6F3]">
    <div class="px-4 sm:px-6 lg:px-8 py-6">

        {{-- Page Header --}}
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <h1 class="section-title">{{ __('pages.industry_overview.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ __('pages.industry_overview.description') }}</p>
                </div>
                <div class="mt-4 md:mt-0 flex gap-x-3">
                    <button class="btn-secondary">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        {{ __('common.export') }}
                    </button>
                    <button class="btn-primary">
                        <x-heroicon-o-share class="w-4 h-4" />
                        {{ __('common.share') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('common.filters') }}</h3>
                @if($platform || !empty($selectedIndustries) || $preset !== '30d')
                    <button wire:click="resetFilters" class="btn-ghost">
                        {{ __('filters.clear_filters') }}
                    </button>
                @endif
            </div>

            {{-- Date Range Presets --}}
            <div class="mb-4">
                <label class="micro-label block mb-2">{{ __('labels.time_period') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($datePresets as $dp)
                        <button
                            wire:click="applyDatePreset('{{ $dp['value'] }}')"
                            class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors {{ $preset === $dp['value'] ? 'bg-brand-600 text-white' : 'bg-[#F1F1EF] text-[#6B6B66] hover:bg-gray-200' }}"
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
                        <label class="micro-label block mb-2">{{ __('labels.custom_date_range') }}</label>
                        <div class="flex gap-x-2">
                            <input wire:model.live="dateFrom" type="date" class="input-field" />
                            <span class="flex items-center text-gray-400">&rarr;</span>
                            <input wire:model.live="dateTo" type="date" class="input-field" />
                        </div>
                    </div>
                @endif

                <div>
                    <x-select2
                        wire-model="platform"
                        :label="__('Platform')"
                        :placeholder="__('pages.industry_overview.all_platforms')"
                        :options="array_merge(['' => __('filters.all')], ['facebook' => __('platforms.facebook'), 'google' => __('pages.industry_overview.google_ads'), 'tiktok' => __('platforms.tiktok'), 'snapchat' => __('pages.industry_overview.snapchat'), 'linkedin' => __('platforms.linkedin')])"
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
                    <button wire:click="fetchData" wire:loading.attr="disabled" class="btn-primary w-full">
                        <span wire:loading wire:target="fetchData">
                            <svg class="animate-spin -ms-1 me-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
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
                    <div class="card p-4 animate-pulse">
                        <div class="flex items-center gap-x-3">
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
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#F1F1EF] text-[#6B6B66]">
                            <x-heroicon-s-squares-2x2 class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('pages.industry_overview.total_industries') }}</p>
                            <p class="kpi-value">{{ $totals['total_industries'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Accounts --}}
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#EDF3EC] text-[#346538]">
                            <x-heroicon-s-users class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('pages.industry_overview.total_accounts') }}</p>
                            <p class="kpi-value">{{ number_format($totals['total_accounts']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Spend --}}
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#FBF3DB] text-[#956400]">
                            <x-heroicon-s-currency-dollar class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('kpis.spend') }}</p>
                            <p class="kpi-value"><span class="text-xs font-normal text-gray-500">SAR</span> {{ formatNumber($totals['total_spend']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Clicks --}}
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#E1F3FE] text-[#1F6C9F]">
                            <x-heroicon-s-cursor-arrow-rays class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('labels.total_clicks') }}</p>
                            <p class="kpi-value">{{ formatNumber($totals['total_clicks']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Avg CTR --}}
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#F1F1EF] text-[#6B6B66]">
                            <x-heroicon-s-chart-bar class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('labels.avg_ctr') }}</p>
                            <p class="kpi-value">{{ number_format($totals['avg_ctr'], 2) }}%</p>
                        </div>
                    </div>
                </div>

                {{-- Avg CPC --}}
                <div class="kpi-card">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-[#FBF3DB] text-[#956400]">
                            <x-heroicon-s-banknotes class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <p class="kpi-label truncate">{{ __('labels.avg_cpc') }}</p>
                            <p class="kpi-value"><span class="text-xs font-normal text-gray-500">SAR</span> {{ number_format($totals['avg_cpc'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Error State --}}
        @if($error)
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                <div class="flex">
                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-400" />
                    <div class="ms-3">
                        <h3 class="text-sm font-semibold text-red-800">{{ __('pages.industry_overview.error_loading') }}</h3>
                        <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$loading && $totals && count($industryData) > 0)
            {{-- Charts --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {{-- Top Industries by Metric --}}
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('pages.industry_overview.top_industries_by', ['metric' => __(('kpis.' . $currentMetric))]) }}</h3>
                            <div class="flex flex-wrap gap-1">
                                @foreach(['spend' => __('kpis.spend'), 'clicks' => __('alerts.metrics.clicks'), 'impressions' => __('alerts.metrics.impressions'), 'leads' => __('alerts.metrics.leads')] as $key => $label)
                                    <button wire:click="$set('currentMetric', '{{ $key }}')" class="px-2.5 py-1 text-xs font-semibold rounded-full transition-colors {{ $currentMetric === $key ? 'bg-brand-600 text-white' : 'bg-[#F1F1EF] text-[#6B6B66] hover:bg-gray-200' }}">
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
                    <div class="card p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('pages.industry_overview.monthly_spend_trend') }}</h3>
                        @if(count($monthlyTrend) > 0)
                            <div style="height: 400px;"
                                 wire:key="trend-{{ md5(json_encode($monthlyTrendChartConfig)) }}"
                                 x-data x-init="new Chart($refs.trendCanvas, @js($monthlyTrendChartConfig))">
                                <canvas x-ref="trendCanvas"></canvas>
                            </div>
                        @else
                            <div class="empty-state h-[400px] text-gray-400">
                                <p class="text-sm">{{ __('messages.no_data') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Industry Efficiency Comparison --}}
                    <div class="card p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('pages.industry_overview.industry_efficiency_comparison') }}</h3>
                        @if(count($industryEfficiency) > 0)
                            <div style="height: 400px;"
                                 wire:key="efficiency-{{ md5(json_encode($efficiencyChartConfig)) }}"
                                 x-data x-init="new Chart($refs.efficiencyCanvas, @js($efficiencyChartConfig))">
                                <canvas x-ref="efficiencyCanvas"></canvas>
                            </div>
                        @else
                            <div class="empty-state h-[400px] text-gray-400">
                                <p class="text-sm">{{ __('messages.no_data') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Platform Distribution --}}
                    <div class="card p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('pages.industry_overview.accounts_by_platform') }}</h3>
                        <div style="height: 400px;"
                             wire:key="doughnut-{{ md5(json_encode($platformChartConfig)) }}"
                             x-data x-init="new Chart($refs.doughnutCanvas, @js($platformChartConfig))">
                            <canvas x-ref="doughnutCanvas"></canvas>
                        </div>
                    </div>
                </div>
            {{-- Industry Performance Table --}}
                <div class="card">
                    {{-- Table Header --}}
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-sm font-semibold text-gray-900">{{ __('pages.industry_overview.industry_performance') }}</h2>
                                <p class="text-sm text-gray-600 mt-1">{{ __('pages.industry_overview.click_to_expand') }}</p>
                            </div>
                            <div class="flex items-center gap-x-3">
                                {{-- Column group toggle --}}
                                <div class="flex">
                                    @foreach(['all' => __('filters.all'), 'efficiency' => __('common.efficiency'), 'conversions' => __('common.conversions')] as $key => $label)
                                        <button wire:click="$set('tableMetric', '{{ $key }}')" class="px-3 py-1.5 text-xs font-semibold {{ $tableMetric === $key ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} {{ $loop->first ? 'rounded-s-md' : '' }} {{ $loop->last ? 'rounded-e-md' : '' }} border border-gray-200 {{ !$loop->first ? '-ms-px' : '' }}">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Search --}}
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="{{ __('placeholders.search_industries') }}" class="input-field w-full md:w-56 ps-9 pe-9" />
                                    @if($searchQuery)
                                        <div class="absolute inset-y-0 end-0 pe-3 flex items-center">
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
                        <table class="data-table">
                            <thead>
                                <tr>
                                    @php
                                        $columns = [
                                            ['key' => 'industry', 'label' => __('pages.industry_overview.industry'), 'group' => 'core'],
                                            ['key' => 'accounts', 'label' => __('pages.industry_overview.accounts'), 'group' => 'core'],
                                            ['key' => 'platforms', 'label' => __('labels.platforms'), 'group' => 'core', 'sortable' => false],
                                            ['key' => 'impressions', 'label' => __('alerts.metrics.impressions'), 'group' => 'core'],
                                            ['key' => 'clicks', 'label' => __('alerts.metrics.clicks'), 'group' => 'core'],
                                            ['key' => 'spend', 'label' => __('kpis.spend'), 'group' => 'core'],
                                            ['key' => 'ctr', 'label' => __('kpis.ctr'), 'group' => 'efficiency'],
                                            ['key' => 'cpc', 'label' => __('kpis.cpc'), 'group' => 'efficiency'],
                                            ['key' => 'cpm', 'label' => __('kpis.cpm'), 'group' => 'efficiency'],
                                            ['key' => 'leads', 'label' => __('alerts.metrics.leads'), 'group' => 'conversions'],
                                            ['key' => 'cvr', 'label' => __('kpis.cvr'), 'group' => 'conversions'],
                                            ['key' => 'cpl', 'label' => __('kpis.cpl'), 'group' => 'conversions'],
                                        ];
                                    @endphp
                                    @foreach($columns as $col)
                                        @if($col['group'] === 'core' || $tableMetric === 'all' || $tableMetric === $col['group'])
                                            <th
                                                @if(($col['sortable'] ?? true))
                                                    wire:click="handleSort('{{ $col['key'] }}')"
                                                @endif
                                                class="whitespace-nowrap {{ ($col['sortable'] ?? true) ? 'cursor-pointer hover:bg-gray-100' : '' }}"
                                            >
                                                <div class="flex items-center gap-x-1">
                                                    <span>{{ $col['label'] }}</span>
                                                    @if($sortBy === $col['key'])
                                                        <x-heroicon-s-chevron-down class="w-3.5 h-3.5 text-gray-900 transition-transform {{ $sortOrder === 'asc' ? 'rotate-180' : '' }}" />
                                                    @endif
                                                </div>
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredIndustryData as $item)
                                    {{-- Industry Row --}}
                                    <tr wire:click="toggleIndustry('{{ $item['industry'] }}')" class="cursor-pointer">
                                        {{-- Industry Name --}}
                                        <td>
                                            <div class="flex items-center">
                                                @if(in_array($item['industry'], $expandedIndustries))
                                                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 me-2 flex-shrink-0" />
                                                @else
                                                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400 me-2 flex-shrink-0" />
                                                @endif
                                                <span class="font-medium text-gray-900">{{ $item['industry_display'] }}</span>
                                            </div>
                                        </td>

                                        {{-- Accounts --}}
                                        <td>{{ number_format($item['accounts_count']) }}</td>

                                        {{-- Platform Badges --}}
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                @php
                                                    $platformBadgeColors = [
                                                        'facebook' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                                        'google' => 'bg-[#FDEBEC] text-[#9F2F2D]',
                                                        'tiktok' => 'bg-[#F1F1EF] text-[#6B6B66]',
                                                        'snapchat' => 'bg-[#FBF3DB] text-[#956400]',
                                                        'linkedin' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                                    ];
                                                    $platformShortNames = [
                                                        'facebook' => 'FB',
                                                        'google' => 'G',
                                                        'tiktok' => 'TT',
                                                        'snapchat' => 'SC',
                                                        'linkedin' => 'LI',
                                                    ];
                                                @endphp
                                                @foreach($item['platform_counts'] ?? [] as $p => $cnt)<span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $platformBadgeColors[$p] ?? 'bg-[#F1F1EF] text-[#6B6B66]' }}">
                                                        {{ $platformShortNames[$p] ?? strtoupper(substr($p, 0, 2)) }} {{ $cnt }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>

                                        {{-- Impressions --}}
                                        <td>{{ formatNumber($item['total_impressions']) }}</td>

                                        {{-- Clicks --}}
                                        <td>{{ formatNumber($item['total_clicks']) }}</td>

                                        {{-- Spend --}}
                                        <td>
                                            <span class="text-xs text-gray-500">SAR</span> {{ number_format($item['total_spend'], 2) }}
                                        </td>

                                        {{-- Efficiency columns --}}
                                        @if($tableMetric === 'all' || $tableMetric === 'efficiency')
                                            {{-- CTR --}}
                                            <td>
                                                <div class="flex items-center gap-x-1.5">
                                                    <span>{{ number_format($item['ctr'], 2) }}%</span>
                                                    @if(!empty($item['benchmark_status']['ctr']) && $item['benchmark_status']['ctr'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['ctr']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPC --}}
                                            <td>
                                                <div class="flex items-center gap-x-1.5">
                                                    <span><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpc'], 2) }}</span>
                                                    @if(!empty($item['benchmark_status']['cpc']) && $item['benchmark_status']['cpc'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cpc']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPM --}}
                                            <td>
                                                <div class="flex items-center gap-x-1.5">
                                                    <span><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpm'], 2) }}</span>
                                                    @if(!empty($item['benchmark_status']['cpm']) && $item['benchmark_status']['cpm'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cpm']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif

                                        {{-- Conversion columns --}}
                                        @if($tableMetric === 'all' || $tableMetric === 'conversions')
                                            {{-- Leads --}}
                                            <td>{{ formatNumber($item['total_leads']) }}</td>
                                            {{-- CVR --}}
                                            <td>
                                                <div class="flex items-center gap-x-1.5">
                                                    <span>{{ number_format($item['cvr'], 2) }}%</span>
                                                    @if(!empty($item['benchmark_status']['cvr']) && $item['benchmark_status']['cvr'] !== 'no_data')
                                                        <span class="inline-block w-2 h-2 rounded-full {{ benchmarkDotColor($item['benchmark_status']['cvr']) }}"></span>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- CPL --}}
                                            <td>
                                                <div class="flex items-center gap-x-1.5">
                                                    <span><span class="text-xs text-gray-500">SAR</span> {{ number_format($item['cpl'], 2) }}</span>
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
                                            <tr class="bg-gray-50/70">
                                                {{-- Category Name --}}
                                                <td>
                                                    <div class="flex items-center ps-10">
                                                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full me-2"></div>
                                                        <span class="text-gray-700">{{ $category['category_display'] }}</span>
                                                    </div>
                                                </td>

                                                {{-- Accounts --}}
                                                <td class="text-gray-600">{{ number_format($category['accounts_count']) }}</td>

                                                {{-- Platforms (empty for sub-rows) --}}
                                                <td class="text-gray-400">&mdash;</td>

                                                {{-- Impressions --}}
                                                <td class="text-gray-600">{{ formatNumber($category['total_impressions']) }}</td>

                                                {{-- Clicks --}}
                                                <td class="text-gray-600">{{ formatNumber($category['total_clicks']) }}</td>

                                                {{-- Spend --}}
                                                <td class="text-gray-600">
                                                    <span class="text-xs text-gray-400">SAR</span> {{ number_format($category['total_spend'], 2) }}
                                                </td>

                                                {{-- Efficiency columns --}}
                                                @if($tableMetric === 'all' || $tableMetric === 'efficiency')
                                                    <td class="text-gray-600">{{ number_format($category['ctr'], 2) }}%</td>
                                                    <td class="text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpc'], 2) }}</td>
                                                    <td class="text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpm'], 2) }}</td>
                                                @endif

                                                {{-- Conversion columns --}}
                                                @if($tableMetric === 'all' || $tableMetric === 'conversions')
                                                    <td class="text-gray-600">{{ formatNumber($category['total_leads']) }}</td>
                                                    <td class="text-gray-600">{{ number_format($category['cvr'], 2) }}%</td>
                                                    <td class="text-gray-600"><span class="text-xs text-gray-400">SAR</span> {{ number_format($category['cpl'], 2) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Benchmark Legend --}}
                    <div class="px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center gap-x-4 text-xs text-gray-500">
                            <span class="font-medium">{{ __('common.performance') }}:</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-green-500 me-1"></span> {{ __('common.excellent') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-amber-500 me-1"></span> {{ __('common.good') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-yellow-500 me-1"></span> {{ __('common.average') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-orange-500 me-1"></span> {{ __('common.below_average') }}</span>
                            <span class="flex items-center"><span class="inline-block w-2 h-2 rounded-full bg-red-500 me-1"></span> {{ __('common.poor') }}</span>
                        </div>
                    </div>
                </div>
        @elseif(!$loading && count($industryData) === 0)
            {{-- Empty State --}}
            <div class="card empty-state">
                <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="empty-title mt-2">{{ __('messages.no_data') }}</h3>
                <p class="empty-message">{{ __('pages.industry_overview.no_data') }}</p>
            </div>
        @endif

        {{-- Loading State (full-page spinner for initial load) --}}
        @if($loading && !$totals)
            <div class="card p-12 text-center">
                <svg class="animate-spin mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ __('pages.industry_overview.loading') }}</h3>
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
        'good' => 'bg-amber-500',
        'average' => 'bg-yellow-500',
        'below_average' => 'bg-orange-500',
        'poor' => 'bg-red-500',
        default => 'bg-gray-300',
    };
}
@endphp
