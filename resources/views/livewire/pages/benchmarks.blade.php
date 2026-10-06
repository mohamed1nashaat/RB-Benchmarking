<div class="min-h-screen bg-gray-950">
    <div class="px-4 py-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-white">
                    {{ __('pages.benchmarks.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ __('pages.benchmarks.description') }}
                    @if($autoRefreshEnabled)
                        <span class="inline-flex items-center gap-1 ms-2 px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-green-500/15 text-green-400">
                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                            {{ __('pages.benchmarks.live_data') }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-300 select-none">
                    <input type="checkbox" wire:model.live="autoRefreshEnabled" wire:change="toggleAutoRefresh" class="h-4 w-4 text-brand-600 border-white/15 rounded" />
                    {{ __('pages.benchmarks.auto_refresh') }}
                </label>
                <button wire:click="refreshData" wire:loading.attr="disabled" class="btn-secondary">
                    <x-heroicon-o-arrow-path class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refreshData" />
                    <span class="hidden sm:inline">{{ __('pages.benchmarks.refresh') }}</span>
                </button>
                <livewire:components.export-menu />
            </div>
        </div>

        {{-- Loading Skeletons for Summary --}}
        @if($loading)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                @for($i = 0; $i < 4; $i++)
                    <div class="card overflow-hidden">
                        <div class="p-5 animate-pulse">
                            <div class="flex items-center">
                                <div class="h-12 w-12 bg-white/5 rounded-lg"></div>
                                <div class="ms-5 flex-1">
                                    <div class="h-4 bg-white/5 rounded w-3/4 mb-2"></div>
                                    <div class="h-8 bg-white/5 rounded w-1/2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @endif

        {{-- Enhanced Filters --}}
        <div class="filter-bar">
            <div class="flex items-center justify-between mb-3 w-full">
                <div class="flex items-center gap-x-3">
                    <h3 class="micro-label">{{ __('filters.benchmark_filters') }}</h3>
                    @php $activeFilterCount = count($selectedPlatforms) + count($selectedIndustries) + count($selectedCountries) + count($selectedCategories) + count($selectedSubCategories) + count($selectedFunnelStages); @endphp
                    @if($activeFilterCount > 0)
                        <span class="badge-neutral">
                            {{ $activeFilterCount }} {{ __('common.active') }}
                        </span>
                    @endif
                </div>
                @if($activeFilterCount > 0)
                    <button wire:click="clearFilters" class="inline-flex items-center text-sm text-gray-500 hover:text-white">
                        <x-heroicon-o-x-mark class="h-4 w-4 me-2" />
                        {{ __('filters.clear_all') }}
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 w-full">
                {{-- Platform --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('labels.platform') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedPlatforms) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedPlatforms) ? __('filters.all_platforms') : trans_choice('common.selected_count', count($selectedPlatforms)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availablePlatforms as $value => $label)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $value }}" wire:model.live="selectedPlatforms">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Industry --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('labels.industry') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedIndustries) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedIndustries) ? __('filters.all_industries') : trans_choice('common.selected_count', count($selectedIndustries)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availableIndustries as $ind)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $ind }}" wire:model.live="selectedIndustries">
                                {{ $this->getIndustryLabel($ind) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Country --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('labels.country') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedCountries) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedCountries) ? __('filters.all_countries') : trans_choice('common.selected_count', count($selectedCountries)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availableCountries as $country)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $country }}" wire:model.live="selectedCountries">
                                {{ $country }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Category --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('labels.category') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedCategories) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedCategories) ? __('filters.all_categories') : trans_choice('common.selected_count', count($selectedCategories)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availableCategories as $cat => $catLabel)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $cat }}" wire:model.live="selectedCategories">
                                {{ $catLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Account Sub-Category --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('labels.sub_category') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedSubCategories) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedSubCategories) ? __('filters.all_sub_industries') : trans_choice('common.selected_count', count($selectedSubCategories)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availableSubCategories as $sub => $subLabel)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $sub }}" wire:model.live="selectedSubCategories">
                                {{ $subLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Funnel Stage --}}
                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                    <label class="micro-label block mb-1.5">{{ __('funnel_stages.label') }}</label>
                    <button @click="open = !open" :aria-expanded="open" type="button" class="filter-trigger {{ count($selectedFunnelStages) > 0 ? 'is-active' : '' }}">
                        <span class="truncate">{{ empty($selectedFunnelStages) ? __('filters.all_stages') : trans_choice('common.selected_count', count($selectedFunnelStages)) }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="dropdown-menu">
                        @foreach($availableFunnelStages as $stage)
                            <label class="dropdown-item">
                                <input type="checkbox" value="{{ $stage }}" wire:model.live="selectedFunnelStages">
                                {{ $stage }}
                            </label>
                        @endforeach
                    </div>
                </div>

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
            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                <p class="text-sm text-gray-300">
                    <span class="font-semibold text-white">{{ __('pages.benchmarks.filtered_view.label') }}</span>
                    {{ __('pages.benchmarks.filtered_view.showing') }} {{ $summary['total_accounts'] }}
                    {{ __('pages.benchmarks.filtered_view.of') }} {{ $unfilteredSummary['total_accounts'] }}
                    {{ __('pages.benchmarks.filtered_view.accounts') }}
                    @foreach($selectedIndustries as $ind)
                        <span class="ms-2 badge-neutral">{{ $this->getIndustryLabel($ind) }}</span>
                    @endforeach
                    @foreach($selectedPlatforms as $pl)
                        <span class="ms-2 badge-neutral">{{ ucfirst($pl) }}</span>
                    @endforeach
                    @foreach($selectedCountries as $ctry)
                        <span class="ms-2 badge-neutral">{{ $ctry }}</span>
                    @endforeach
                </p>
            </div>
        @endif

        {{-- Main Content --}}
        @if($loading)
            {{-- Loading Skeleton --}}
            <div class="card mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-white/10">
                    <div class="animate-pulse">
                        <div class="h-6 bg-white/5 rounded w-1/4 mb-2"></div>
                        <div class="h-4 bg-white/5 rounded w-1/2"></div>
                    </div>
                </div>
                <div class="p-6 animate-pulse">
                    <div class="h-64 bg-white/5 rounded"></div>
                </div>
            </div>
        @else
            {{-- Your Performance Overview --}}
            <div class="card mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-white/10">
                    <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.benchmarks.your_performance_title') }}</h3>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('pages.benchmarks.your_performance_subtitle') }}</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        {{-- Industries Tracked --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.industries_tracked') }}</p>
                                    <p class="kpi-value">{{ $yourOverallMetrics['industries_count'] }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-chart-bar class="h-5 w-5" /></div>
                            </div>
                        </div>

                        {{-- Total Accounts --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.total_accounts') }}</p>
                                    <p class="kpi-value">{{ $yourOverallMetrics['accounts_count'] }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-building-office class="h-5 w-5" /></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $yourOverallMetrics['campaigns_count'] }} {{ __('pages.benchmarks.campaigns') }}</p>
                        </div>

                        {{-- Total Spend --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.total_spend') }}</p>
                                    <p class="kpi-value">{{ $this->formatCurrency($yourOverallMetrics['total_spend']) }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-currency-dollar class="h-5 w-5" /></div>
                            </div>
                        </div>

                        {{-- Total Impressions --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.total_impressions') }}</p>
                                    <p class="kpi-value">{{ $this->formatNumber($yourOverallMetrics['total_impressions']) }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-eye class="h-5 w-5" /></div>
                            </div>
                        </div>

                        {{-- Total Clicks --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.total_clicks') }}</p>
                                    <p class="kpi-value">{{ $this->formatNumber($yourOverallMetrics['total_clicks']) }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-cursor-arrow-rays class="h-5 w-5" /></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ __('kpis.ctr') }}: {{ number_format($yourPerformanceIndicators['ctr'], 2) }}%</p>
                        </div>

                        {{-- Total Conversions --}}
                        <div class="kpi-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="kpi-label">{{ __('pages.benchmarks.total_conversions') }}</p>
                                    <p class="kpi-value">{{ $this->formatNumber($yourOverallMetrics['total_conversions']) }}</p>
                                </div>
                                <div class="p-2.5 bg-brand-500/10 rounded-lg text-brand-400"><x-heroicon-o-check-circle class="h-5 w-5" /></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ __('kpis.cvr') }}: {{ number_format($yourPerformanceIndicators['cvr'], 2) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Industry Benchmarks Container --}}
            <div class="card overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.benchmarks.performance.comparison_title') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">{{ __('pages.benchmarks.performance.comparison_description') }}</p>
                        </div>
                        <div class="flex items-center gap-x-2">
                            <span class="micro-label">{{ __('pages.benchmarks.performance.sort_by') }}</span>
                            <div class="w-48">
                                <select wire:model="sortField" class="input-field">
                                    <option value="name">{{ __('pages.benchmarks.performance.sort_industry_name') }}</option>
                                    <option value="accounts_count">{{ __('pages.benchmarks.performance.sort_accounts') }}</option>
                                    <option value="total_spend">{{ __('pages.benchmarks.performance.sort_spend') }}</option>
                                    <option value="total_leads">{{ __('pages.benchmarks.performance.sort_leads') }}</option>
                                </select>
                            </div>
                            <button wire:click="toggleSortDirection" class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-white/5 transition">
                                @if($sortDirection === 'asc')
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                {{-- View Mode Toggle --}}
                <div class="px-4 py-3 bg-gray-950 border-b border-white/10 flex items-center justify-between">
                    <div class="flex items-center gap-x-2">
                        <span class="text-sm text-gray-500 me-2">{{ __('common.view') }}:</span>
                        <button wire:click="$set('viewMode', 'card')" class="flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'card' ? 'bg-brand-600 text-white border border-brand-600' : 'bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-950' }}">
                            <x-heroicon-o-squares-2x2 class="h-4 w-4 me-1.5" />
                            {{ __('common.cards') }}
                        </button>
                        <button wire:click="$set('viewMode', 'table')" class="flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'table' ? 'bg-brand-600 text-white border border-brand-600' : 'bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-950' }}">
                            <x-heroicon-o-table-cells class="h-4 w-4 me-1.5" />
                            {{ __('common.table') }}
                        </button>
                    </div>
                    <div class="flex items-center gap-x-3">
                        {{-- Search --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                            </div>
                            <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="{{ __('pages.benchmarks.search_placeholder') }}" class="input-field ps-9 md:w-64" />
                        </div>
                        <span class="text-sm text-gray-500 whitespace-nowrap">
                            {{ count($filteredIndustries) }} {{ $tableGroupBy === 'client' ? __('common.clients') : __('common.industries') }}
                        </span>
                    </div>
                </div>

                {{-- Card View --}}
                @if($viewMode === 'card')
                    <div class="p-4">
                        @if(count($filteredIndustries) === 0)
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <x-heroicon-o-funnel class="h-5 w-5" />
                                </div>
                                <p class="empty-title">{{ __('pages.benchmarks.performance.no_industries') }}</p>
                                <p class="empty-message">{{ __('filters.try_adjusting') }}</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($filteredIndustries as $industryKey => $industry)
                                    <div class="card p-4 hover:border-white/15 transition-colors">
                                        <button wire:click="showIndustryAccounts('{{ $industry['industry_slug'] ?? $industryKey }}')" class="text-base font-semibold tracking-tight text-white mb-1 hover:underline cursor-pointer text-start">
                                            {{ $industry['industry'] ?? $this->getIndustryLabel($industryKey) }}
                                        </button>

                                        {{-- Industry Summary --}}
                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3 pb-2 border-b border-white/8">
                                            <button wire:click="showIndustryAccounts('{{ $industry['industry_slug'] ?? $industryKey }}')"
                                                class="inline-flex items-center text-gray-500 hover:text-white hover:underline">
                                                <x-heroicon-o-eye class="h-3 w-3 me-1" />
                                                {{ trans_choice('common.accounts', $industry['accounts_count'] ?? 0) }}
                                            </button>
                                            <span class="font-medium">{{ $this->formatCurrency($industry['total_spend'] ?? 0) }}</span>
                                        </div>

                                        {{-- Metrics --}}
                                        <div class="space-y-2.5">
                                            @foreach(['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'roas'] as $metricKey)
                                                @if(isset($industry['metrics'][$metricKey]))
                                                    @php
                                                        $metric = $industry['metrics'][$metricKey];
                                                        $actual = $metric['actual'] ?? 0;
                                                        $benchmark = $metric['benchmark']['avg'] ?? 0;
                                                        $barPct = ($benchmark > 0 && $actual > 0) ? min(100, round(($actual / max($actual, $benchmark)) * 100)) : 0;
                                                        $barColor = isset($metric['status']) && $metric['status'] === 'above' ? '#EF4444' : 'rgba(255,255,255,0.2)';
                                                    @endphp
                                                    <div>
                                                        <div class="flex items-center justify-between text-sm mb-1">
                                                            <span class="text-gray-400 text-xs">{{ $this->getMetricLabel($metricKey) }}</span>
                                                            <div class="flex items-center gap-x-1.5">
                                                                <span class="font-semibold text-white text-xs">{{ $this->formatMetricValue($metricKey, $actual) }}</span>
                                                                @if(isset($metric['status']))
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $this->getStatusBadgeClass($metric['status']) }}">
                                                                        {{ $this->getStatusLabel($metric['status']) }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if($barPct > 0)
                                                            <div class="h-0.5 rounded-full bg-white/8 overflow-hidden">
                                                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $barPct }}%; background-color: {{ $barColor }};"></div>
                                                            </div>
                                                        @endif
                                                        @if(isset($metric['benchmark']) || isset($globalBenchmarkData[$metricKey]))
                                                            <div class="flex items-center justify-between text-[10px] mt-0.5">
                                                                @if(isset($metric['benchmark']))
                                                                    <span style="color: rgba(255,255,255,0.3);">{{ __('common.benchmark') }}: {{ $this->formatMetricValue($metricKey, $metric['benchmark']['avg'] ?? null) }}</span>
                                                                @endif
                                                                @if(isset($globalBenchmarkData[$metricKey]['avg']))
                                                                    <span style="color: rgba(255,255,255,0.25);">{{ __('common.global_avg') }}: {{ $this->formatMetricValue($metricKey, $globalBenchmarkData[$metricKey]['avg']) }}</span>
                                                                @endif
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
                    <div class="px-6 py-4 border-b border-white/10">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-base font-semibold tracking-tight text-white">
                                    {{ $tableGroupBy === 'industry' ? __('pages.benchmarks.industry_performance') : __('Client Performance') }}
                                </h2>
                                <p class="text-sm text-gray-300 mt-1">{{ __('pages.benchmarks.hierarchical_breakdown') }}</p>
                            </div>
                            <div class="flex items-center gap-x-3">
                                {{-- Group By Toggle --}}
                                <div class="flex items-center gap-x-2">
                                    <span class="text-sm text-gray-200">{{ __('labels.group_by') }}:</span>
                                    <button wire:click="$set('tableGroupBy', 'industry')" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $tableGroupBy === 'industry' ? 'bg-brand-600 text-white border border-brand-600' : 'bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-950' }}">
                                        {{ __('labels.industry') }}
                                    </button>
                                    <button wire:click="$set('tableGroupBy', 'client')" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $tableGroupBy === 'client' ? 'bg-brand-600 text-white border border-brand-600' : 'bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-950' }}">
                                        {{ __('labels.client') }}
                                    </button>
                                </div>

                                {{-- Column Selector --}}
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="btn-secondary">
                                        <svg class="h-4 w-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                                        {{ __('pages.benchmarks.columns_button') }}
                                    </button>

                                    <div x-show="open" @click.outside="open = false" x-transition class="absolute end-0 mt-2 w-72 card z-50">
                                        <div class="p-3 border-b border-white/10">
                                            <h4 class="text-sm font-semibold tracking-tight text-white">{{ __('pages.benchmarks.select_columns') }}</h4>
                                        </div>
                                        <div class="max-h-80 overflow-y-auto p-2">
                                            @foreach(['account' => __('pages.benchmarks.column_groups.ad_account_level'), 'campaign' => __('pages.benchmarks.column_groups.campaign_level'), 'volume' => __('pages.benchmarks.column_groups.volume_metrics'), 'efficiency' => __('pages.benchmarks.column_groups.efficiency_metrics'), 'global' => __('pages.benchmarks.column_groups.global_benchmarks')] as $group => $groupLabel)
                                                <div class="mb-3">
                                                    <div class="flex items-center justify-between px-2 py-1">
                                                        <span class="micro-label">{{ $groupLabel }}</span>
                                                        <div class="flex gap-x-1">
                                                            <button wire:click="toggleGroupColumns('{{ $group }}', true)" class="text-xs text-gray-500 hover:text-white hover:underline">{{ __('filters.all') }}</button>
                                                            <span class="text-gray-300">|</span>
                                                            <button wire:click="toggleGroupColumns('{{ $group }}', false)" class="text-xs text-gray-500 hover:text-white hover:underline">{{ __('common.none') }}</button>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-1">
                                                        @foreach(collect($columnConfig)->where('group', $group) as $col)
                                                            <label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-950 cursor-pointer {{ ($col['locked'] ?? false) ? 'opacity-50' : '' }}">
                                                                <input type="checkbox" wire:click.prevent="toggleColumn('{{ $col['key'] }}')" @checked($col['key'] === 'industry' || ($visibleColumns[$col['key']] ?? false)) {{ ($col['locked'] ?? false) ? 'disabled' : '' }} class="h-4 w-4 text-white border-white/15 rounded" />
                                                                <span class="ms-2 text-sm text-gray-200">{{ $col['label'] }}</span>
                                                                @if($col['locked'] ?? false)
                                                                    <span class="ms-auto text-xs text-gray-400">({{ __('common.required') }})</span>
                                                                @endif
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="p-2 border-t border-white/10 bg-gray-950 rounded-b-xl">
                                            <button @click="open = false" class="btn-secondary w-full justify-center">
                                                {{ __('pages.benchmarks.column_actions.done') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Clear Filters --}}
                                @if($this->hasActiveColumnFilters)
                                    <button wire:click="clearColumnFilters" class="btn-secondary">
                                        <svg class="h-4 w-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        {{ __('filters.clear_filters') }}
                                    </button>
                                @endif

                                {{-- Toggle Spend Visibility --}}
                                <button wire:click="$toggle('showAllSpend')" class="btn-secondary">
                                    @if($showAllSpend)
                                        <span class="inline-flex items-center">
                                            <x-heroicon-o-eye-slash class="h-4 w-4 me-2 text-gray-500" />
                                            {{ __('common.hide_spend') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center">
                                            <x-heroicon-o-eye class="h-4 w-4 me-2 text-gray-500" />
                                            {{ __('common.show_spend') }}
                                        </span>
                                    @endif
                                </button>

                                {{-- Reset Column Widths --}}
                                <button @click="resetWidths()" class="btn-secondary">
                                    <svg class="h-4 w-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    {{ __('common.reset_widths') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Hierarchical Table --}}
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead class="bg-gray-950 sticky top-0 z-10">
                                <tr class="border-b-2 border-white/10">
                                    @if(($visibleGroupCounts['account'] ?? 0) > 0)
                                        <th colspan="{{ $visibleGroupCounts['account'] }}" class="text-center border-e border-white/10">{{ __('pages.benchmarks.column_groups.ad_account_level') }}</th>
                                    @endif
                                    @if(($visibleGroupCounts['campaign'] ?? 0) > 0)
                                        <th colspan="{{ $visibleGroupCounts['campaign'] }}" class="text-center border-e border-white/10">{{ __('pages.benchmarks.column_groups.campaign_level') }}</th>
                                    @endif
                                    @if(($visibleGroupCounts['volume'] ?? 0) > 0)
                                        <th colspan="{{ $visibleGroupCounts['volume'] }}" class="text-center border-e border-white/10">{{ __('pages.benchmarks.column_groups.volume_metrics') }}</th>
                                    @endif
                                    @if(($visibleGroupCounts['efficiency'] ?? 0) > 0)
                                        <th colspan="{{ $visibleGroupCounts['efficiency'] }}" class="text-center border-e border-white/10">{{ __('pages.benchmarks.column_groups.efficiency_metrics') }}</th>
                                    @endif
                                    @if(($visibleGroupCounts['global'] ?? 0) > 0)
                                        <th colspan="{{ $visibleGroupCounts['global'] }}" class="text-center">{{ __('pages.benchmarks.column_groups.global_benchmarks') }}</th>
                                    @endif
                                </tr>
                                <tr>
                                    @foreach($columnConfig as $col)
                                        @php
                                            $key = $col['key'];
                                            $isVisible = $key === 'industry' || ($visibleColumns[$key] ?? false);
                                            $bgClass = match($col['group']) {
                                                'account' => 'bg-blue-50',
                                                'campaign' => 'bg-green-500/5',
                                                'volume' => 'bg-purple-50',
                                                'efficiency' => 'bg-amber-50',
                                                'global' => 'bg-teal-50',
                                                default => 'bg-gray-950',
                                            };
                                            $borderClass = in_array($key, ['subCategory', 'funnelStage', 'conversions', 'cpi']) ? 'border-e border-white/15' : '';
                                            $alignClass = in_array($col['group'], ['volume', 'efficiency']) ? 'text-end' : 'text-start';
                                        @endphp
                                        @if($isVisible)
                                            <th class="{{ $alignClass }} {{ $bgClass }} {{ $borderClass }} relative" :style="colStyle('{{ $key }}', '{{ $col['width'] ?? 'auto' }}')">
                                                @if($col['sortable'] ?? false)
                                                    <button wire:click="sortBy('{{ $key }}')" class="inline-flex items-center gap-1 hover:text-white">
                                                        <span>{{ $col['label'] }}</span>
                                                        @if($tableSortField === $key)
                                                            @if($tableSortDirection === 'asc')
                                                                <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                            @else
                                                                <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                            @endif
                                                        @else
                                                            <svg class="h-3 w-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                                        @endif
                                                    </button>
                                                @else
                                                    <span>{{ $col['label'] }}</span>
                                                @endif
                                                <div @mousedown.prevent="startResize($event, '{{ $key }}')" class="absolute end-0 top-0 bottom-0 w-3 cursor-col-resize z-20 group" title="{{ __('common.drag_to_resize') }}">
                                                    <div class="absolute end-1 top-1 bottom-1 w-px bg-white/20 group-hover:bg-white/40 transition-colors"></div>
                                                </div>
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                                <tr class="border-b border-white/10">
                                    @foreach($columnConfig as $col)
                                        @php
                                            $key = $col['key'];
                                            $isVisible = $key === 'industry' || ($visibleColumns[$key] ?? false);
                                            $bgClass = match($col['group']) {
                                                'account' => 'bg-blue-50',
                                                'campaign' => 'bg-green-500/5',
                                                'volume' => 'bg-purple-50',
                                                'efficiency' => 'bg-amber-50',
                                                'global' => 'bg-teal-50',
                                                default => 'bg-gray-950',
                                            };
                                        @endphp
                                        @if($isVisible)
                                            <th class="{{ $bgClass }}">
                                                @php
                                                    $opts = $columnFilterOptions[$key] ?? [];
                                                    $selectOptions = array_combine($opts, $opts);
                                                @endphp
                                                <div class="select2-compact">
                                                    <x-select2
                                                        wireModel="columnFilters.{{ $key }}"
                                                        uid="col-filter-{{ $key }}-{{ count($opts) }}-{{ md5(json_encode($opts)) }}"
                                                        placeholder="{{ __('filters.all') }}"
                                                        :options="$selectOptions"
                                                        :allowClear="true"
                                                        :searchable="true"
                                                    />
                                                </div>
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-gray-900">
                                @forelse($filteredTableData as $row)
                                    <tr wire:key="row-{{ $row['_id'] }}">
                                        @if($visibleColumns['industry'] ?? true)
                                            <td class="text-gray-200 font-medium truncate max-w-[150px]" title="{{ $row['industry'] }}">
                                                <button wire:click="showIndustryAccounts('{{ $row['industry_key'] }}')" class="text-white hover:underline cursor-pointer text-start font-medium">{{ $row['industry'] }}</button>
                                            </td>
                                        @endif
                                        @if($visibleColumns['country'] ?? false)
                                            <td class="text-gray-200">{{ $row['country'] }}</td>
                                        @endif
                                        @if($visibleColumns['category'] ?? false)
                                            <td class="text-gray-200">{{ $row['category'] }}</td>
                                        @endif
                                        @if($visibleColumns['subCategory'] ?? false)
                                            <td class="text-gray-300">{{ $row['sub_category'] }}</td>
                                        @endif
                                        @if($visibleColumns['funnelStage'] ?? false)
                                            <td class="whitespace-nowrap">
                                                <span class="{{ $this->getFunnelBadgeClass($row['funnel_stage']) }}">{{ $row['funnel_stage'] }}</span>
                                            </td>
                                        @endif
                                        @if($visibleColumns['campaignObjective'] ?? false)
                                            <td class="text-gray-300 capitalize">{{ $row['campaignObjective'] }}</td>
                                        @endif
                                        @if($visibleColumns['spend'] ?? false)
                                            <td class="text-end text-white font-medium whitespace-nowrap">
                                                @if($showAllSpend)
                                                    {{ $row['total_spend_display'] }}
                                                @else
                                                    <span class="relative cursor-help inline-block group">
                                                        <span class="text-gray-400">***</span>
                                                        <span class="hidden group-hover:block absolute bottom-full end-0 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded shadow-lg whitespace-nowrap z-50">{{ $row['total_spend_display'] }}</span>
                                                    </span>
                                                @endif
                                            </td>
                                        @endif
                                        @if($visibleColumns['impressions'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['total_impressions_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['clicks'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['total_clicks_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['leads'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['total_leads_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['installs'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['total_installs_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['conversions'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['total_conversions_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['cpm'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['cpm_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['cpc'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['cpc_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['ctr'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['ctr_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['cpl'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['cpl_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['cpa'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['cpa_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['cpi'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['cpi_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_cpm'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_cpm_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_cpc'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_cpc_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_ctr'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_ctr_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_cpl'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_cpl_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_cpa'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_cpa_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['global_cpi'] ?? false)
                                            <td class="text-end text-gray-200 whitespace-nowrap">{{ $row['global_cpi_display'] }}</td>
                                        @endif
                                        @if($visibleColumns['actions'] ?? false)
                                            <td class="whitespace-nowrap">
                                                <button wire:click="showIndustryAccounts('{{ $row['industry_key'] }}')"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-200 bg-white/5 rounded hover:bg-white/8 transition-colors">
                                                    <x-heroicon-o-eye class="h-3 w-3 me-1" />
                                                    {{ __('common.view_accounts') }}
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="20" class="px-6 py-12">
                                            <div class="empty-state">
                                                <p class="empty-title">{{ __('pages.benchmarks.performance.no_industries') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Accounts Modal — proven pattern: @if conditional, backdrop on container, simple centered panel --}}
    @if($showAccountsModal)
    <div class="fixed inset-0 bg-black/60 overflow-y-auto h-full w-full z-50"
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
        <div class="relative top-10 mx-auto card max-w-7xl w-[98%] sm:w-[95%] my-8 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-5 sm:px-8 border-b border-white/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-x-3">
                        <div class="p-2 bg-white/5 rounded-lg text-gray-400">
                            <x-heroicon-o-building-office-2 class="h-6 w-6 text-gray-500" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-white">{{ $modalIndustry }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">{{ trans_choice('common.accounts', count($modalAccounts)) }}</p>
                        </div>
                    </div>
                    <button @click="$wire.closeAccountsModal()" class="text-gray-400 hover:text-gray-300 transition-colors">
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
                        'tiktok' => 'text-gray-100',
                        'snapchat' => 'text-yellow-500',
                        'linkedin' => 'text-blue-700',
                        'twitter' => 'text-white',
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
                        <div class="px-6 py-3 bg-gray-950 border-b border-white/10">
                            <p class="text-sm text-gray-300">
                                <span class="font-medium text-white">{{ __('labels.industry') }}:</span> {{ $modalIndustry }}
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead class="bg-gray-950 sticky top-0 z-10">
                                <tr class="border-b border-white/10">
                                    <th class="w-10">{{ __('labels.platform_short') }}</th>
                                    <th class="w-28">{{ __('labels.industry') }}</th>
                                    <th class="min-w-[180px]">{{ __('labels.account') }}</th>
                                    <th class="w-28">{{ __('labels.category') }}</th>
                                    <th class="min-w-[100px]">{{ __('labels.external_id') }}</th>
                                    <th class="w-20">{{ __('labels.status') }}</th>
                                    <th class="w-16 text-end">{{ __('labels.campaigns_short') }}</th>
                                    <th class="w-24 text-end">{{ __('kpis.spend') }}</th>
                                    <th class="w-24">{{ __('common.sync') }}</th>
                                    <th class="w-16">{{ __('labels.actions_short') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                    @endif
                                <tr>
                                    {{-- Platform --}}
                                    <td>
                                        <div class="flex items-center justify-center w-8 h-8 rounded bg-gray-950 border border-white/8">
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
                                    <td class="group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'industry')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="input-field text-xs py-1 max-w-[120px]">
                                                @foreach($availableIndustries as $indSlug)
                                                    <option value="{{ $indSlug }}">{{ $this->getIndustryLabel($indSlug) }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <button wire:click="startEdit({{ $account['id'] }}, 'industry', '{{ $account['industry'] }}')"
                                                    class="inline-flex items-center text-xs text-gray-500 hover:text-white transition-colors truncate max-w-[120px]"
                                                    title="{{ __('common.edit_industry') }}">
                                                <span class="truncate max-w-[110px] inline-block">{{ $account['industry'] ?? '-' }}</span>
                                                <x-heroicon-o-pencil class="h-3 w-3 ms-1 opacity-0 group-hover:opacity-100" />
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Account Name --}}
                                    <td class="group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'name')
                                            <input type="text"
                                                   wire:model="editValue"
                                                   wire:keydown.enter="saveEdit"
                                                   wire:keydown.escape="cancelEdit"
                                                   x-init="$nextTick(() => $el.focus())"
                                                   @blur="$wire.saveEdit()"
                                                   class="input-field text-sm max-w-[220px]"
                                            />
                                        @else
                                            <div class="flex items-center gap-x-1.5">
                                                <button @click="expandedAccounts[{{ $account['id'] }}] = !expandedAccounts[{{ $account['id'] }}]"
                                                        class="text-gray-400 hover:text-white transition-colors flex-shrink-0"
                                                        title="{{ __('common.toggle_campaigns') }}">
                                                    <x-heroicon-o-chevron-down class="h-4 w-4 transition-transform duration-200"
                                                        x-bind:class="expandedAccounts[{{ $account['id'] }}] ? 'rotate-180' : ''" />
                                                </button>
                                                <a href="{{ route('ad-account-detail', ['id' => $account['id']]) }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-white hover:text-gray-300 truncate max-w-[200px] inline-block" title="{{ $account['name'] }}">
                                                    {{ $account['name'] }}
                                                </a>
                                                <button wire:click="startEdit({{ $account['id'] }}, 'name', '{{ addslashes($account['name']) }}')"
                                                        class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-white transition-opacity flex-shrink-0"
                                                        title="{{ __('common.edit_name') }}">
                                                    <x-heroicon-o-pencil class="h-3 w-3" />
                                                </button>
                                            </div>
                                        @endif
                                        @if($account['integration_status'] === 'needs_reauth')
                                            <span class="inline-flex items-center mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-white/10">
                                                <x-heroicon-o-exclamation-triangle class="h-3 w-3 me-0.5" />
                                                {{ __('common.reauth') }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Category --}}
                                    <td class="group">
                                        @if($editingAccountId === $account['id'] && $editingField === 'category')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="input-field text-xs py-1 max-w-[140px]">
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
                                                    class="inline-flex items-center text-xs text-gray-500 hover:text-white transition-colors"
                                                    title="{{ __('common.edit_category') }}">
                                                <span class="truncate max-w-[130px] inline-block">{{ $account['category'] ?? '-' }}</span>
                                                <x-heroicon-o-pencil class="h-3 w-3 ms-1 opacity-0 group-hover:opacity-100" />
                                            </button>
                                        @endif
                                    </td>

                                    {{-- External ID --}}
                                    <td>
                                        <span class="text-xs text-gray-400 font-mono">{{ $account['external_account_id'] ?? '-' }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        @if($editingAccountId === $account['id'] && $editingField === 'status')
                                            <select wire:model="editValue"
                                                    wire:change="saveEdit"
                                                    x-init="$nextTick(() => $el.focus())"
                                                    @blur="$wire.cancelEdit()"
                                                    class="input-field text-xs py-1 max-w-[140px]">
                                                <option value="active">{{ __('status.active') }}</option>
                                                <option value="inactive">{{ __('status.inactive') }}</option>
                                                <option value="permission_denied">{{ __('status.access_denied') }}</option>
                                            </select>
                                        @else
                                            <button wire:click="startEdit({{ $account['id'] }}, 'status', '{{ $account['status'] }}')"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer {{ $account['status'] === 'active' ? 'bg-green-500/15 text-green-400' : ($account['status'] === 'permission_denied' ? 'bg-amber-500/15 text-amber-400' : 'bg-white/5 text-gray-300') }}">
                                                <span class="w-1.5 h-1.5 rounded-full me-1.5 {{ $account['status'] === 'active' ? 'bg-green-500' : ($account['status'] === 'permission_denied' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                                                {{ $account['status'] === 'permission_denied' ? __('Access Denied') : ucfirst($account['status']) }}
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Campaigns --}}
                                    <td class="text-end text-gray-200">
                                        {{ $account['campaigns_count'] }}
                                    </td>

                                    {{-- Spend --}}
                                    <td class="text-end">
                                        <div class="flex items-center justify-end gap-x-1">
                                            <span class="text-sm font-semibold text-white"
                                                  x-text="liveSpend[{{ $account['id'] }}] ? formatCurrency(liveSpend[{{ $account['id'] }}].spend) : '{{ $account['total_spend_display'] }}'">
                                            </span>
                                            <template x-if="loadingSpend && !liveSpend[{{ $account['id'] }}]">
                                                <span class="inline-block w-3 h-3 border-2 border-white/10 border-t-gray-600 rounded-full animate-spin"></span>
                                            </template>
                                            <template x-if="liveSpend[{{ $account['id'] }}]?.source === 'api'">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500" title="{{ __('common.live_api_data') }}"></span>
                                            </template>
                                            <template x-if="liveSpend[{{ $account['id'] }}]?.source === 'db'">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-white/30" title="{{ __('common.cached_db_data') }}"></span>
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Last Sync --}}
                                    <td>
                                        <span class="text-xs text-gray-400 flex items-center">
                                            <x-heroicon-o-clock class="h-3.5 w-3.5 me-1 flex-shrink-0" />
                                            {{ $account['last_sync_at'] }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td>
                                        <a href="{{ $account['link'] }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center text-xs text-gray-500 hover:text-white transition-colors"
                                           title="{{ __('common.view_account') }}">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5 me-1" />
                                            {{ __('common.view') }}
                                        </a>
                                    </td>
                                </tr>
                                {{-- Campaigns (collapsible) --}}
                                <tr x-show="expandedAccounts[{{ $account['id'] }}]" x-cloak class="bg-gray-950/50">
                                    <td colspan="10" class="px-0 py-0">
                                        <div class="px-8 py-3">
                                            <h4 class="micro-label mb-2">
                                                {{ __('labels.campaigns') }} ({{ count($account['campaigns']) }})
                                            </h4>
                                            @if(empty($account['campaigns']))
                                                <p class="text-sm text-gray-400 italic">{{ __('common.no_campaigns') }}</p>
                                            @else
                                                <div class="space-y-1">
                                                    @foreach($account['campaigns'] as $campaign)
                                                        <div class="flex items-center justify-between py-1.5 px-3 bg-gray-900 rounded-lg border border-white/8 text-sm">
                                                            <span class="text-gray-200 truncate max-w-[300px]">{{ $campaign['name'] }}</span>
                                                            <div class="flex items-center gap-x-3 flex-shrink-0">
                                                                @if($campaign['objective'])
                                                                    <span class="text-xs text-gray-400 capitalize">{{ $campaign['objective'] }}</span>
                                                                @endif
                                                                @if($campaign['funnel_stage'])
                                                                    <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-white/8 text-gray-400">{{ $campaign['funnel_stage'] }}</span>
                                                                @endif
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-white/5 text-gray-300' }}">
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
                    <div class="empty-state">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/5 mb-4">
                            <x-heroicon-o-building-office-2 class="h-8 w-8 text-gray-300" />
                        </div>
                        <p class="empty-title">{{ __('pages.benchmarks.no_accounts_for_industry') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="bg-gray-950 px-6 py-4 sm:px-8 flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    @php $totalSpend = array_sum(array_column($modalAccounts, 'total_spend')); @endphp
                    {{ __('pages.benchmarks.combined_spend') }}: <span class="font-semibold text-gray-200">{{ $this->formatCurrency($totalSpend) }}</span>
                </p>
                <button @click="$wire.closeAccountsModal()" type="button" class="btn-secondary">
                    {{ __('common.close') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
