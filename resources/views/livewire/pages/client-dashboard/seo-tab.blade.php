        {{-- SEO Report Tab --}}
        @if($activeTabIndex === 1)
            {{-- SEO Settings Panel --}}
            <div class="card mb-6" x-data>
                <div class="flex items-center justify-between p-4 border-b border-white/8">
                    <div class="flex items-center gap-3">
                        <button wire:click="toggleSeoSettings" class="btn-ghost gap-2 {{ $seoSettingsOpen ? 'bg-brand-500/15 text-brand-400' : 'text-gray-300 hover:bg-white/5' }}">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                            {{ __('SEO Properties') }}
                            <x-heroicon-o-chevron-down class="w-3 h-3 transition-transform {{ $seoSettingsOpen ? 'rotate-180' : '' }}" />
                        </button>
                        @if($seoSaved)
                            <span class="inline-flex items-center text-xs text-green-400 font-medium">
                                <x-heroicon-o-check-circle class="w-4 h-4 me-1" />
                                {{ __('Saved') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- View Full Report Button --}}
                        <a href="{{ route('client-seo-report', $clientId) }}" target="_blank"
                           class="btn-primary">
                            <x-heroicon-o-document-chart-bar class="w-4 h-4 me-1.5" />
                            {{ __('View Full Report') }}
                        </a>
                    </div>
                </div>

                @if($seoSettingsOpen)
                    <div class="p-4 bg-gray-950 border-b border-white/8">
                        @if(!$seoHasIntegration)
                            <div class="flex items-center gap-3 p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg mb-4">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                <div class="text-sm text-amber-400">
                                    {{ __('No active Google integration found.') }}
                                    <a href="{{ route('integrations') }}" class="font-medium underline hover:text-amber-300" wire:navigate>{{ __('Connect Google') }}</a>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Search Console Site --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-200 mb-1">{{ __('Search Console Site') }}</label>
                                <div class="relative">
                                    <select wire:model="seoSearchConsoleSite"
                                            class="input-field pe-8"
                                            {{ !$seoHasIntegration ? 'disabled' : '' }}>
                                        <option value="">{{ __('-- Select Site --') }}</option>
                                        @foreach($seoSearchConsoleSites as $site)
                                            <option value="{{ $site['site_url'] }}">{{ $site['site_url'] }}</option>
                                        @endforeach
                                    </select>
                                    @if($seoLoadingSites)
                                        <div class="absolute end-8 top-2.5">
                                            <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- GA4 Property --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-200 mb-1">{{ __('GA4 Property') }}</label>
                                <div class="relative">
                                    <select wire:model="seoGa4PropertyId"
                                            class="input-field pe-8"
                                            {{ !$seoHasIntegration ? 'disabled' : '' }}>
                                        <option value="">{{ __('-- Select Property --') }}</option>
                                        @foreach($seoGa4Properties as $prop)
                                            <option value="{{ $prop['property_id'] }}">{{ $prop['display_name'] }} ({{ $prop['account_name'] ?? '' }})</option>
                                        @endforeach
                                    </select>
                                    @if($seoLoadingProperties)
                                        <div class="absolute end-8 top-2.5">
                                            <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- PageSpeed URL --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-200 mb-1">{{ __('PageSpeed URL') }}</label>
                                <input wire:model="seoPagespeedUrl" type="url" placeholder="{{ __('placeholders.website') }}"
                                       class="input-field" />
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <button wire:click="saveSeoProperties" wire:loading.attr="disabled"
                                    class="btn-primary disabled:opacity-50">
                                <span wire:loading.remove wire:target="saveSeoProperties">{{ __('Save Properties') }}</span>
                                <span wire:loading wire:target="saveSeoProperties" class="inline-flex items-center">
                                    <svg class="animate-spin h-4 w-4 me-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('Saving...') }}
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- SEO Period Filter + Export --}}
            <div class="filter-bar mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Period Preset Dropdown --}}
                        <select wire:model.live="seoPeriod" class="input-field pe-8">
                            <option value="7">{{ __('Last 7 days') }}</option>
                            <option value="14">{{ __('Last 14 days') }}</option>
                            <option value="30">{{ __('Last 30 days') }}</option>
                            <option value="90">{{ __('Last 90 days') }}</option>
                            <option value="180">{{ __('Last 6 months') }}</option>
                            <option value="365">{{ __('Last 12 months') }}</option>
                            @if($seoPeriod === 0)
                                <option value="0">{{ __('Custom') }}</option>
                            @endif
                        </select>

                        {{-- From / To Date Pickers --}}
                        <div class="flex items-center gap-2">
                            <input wire:model.live.debounce.500ms="seoDateFrom" type="date" max="{{ now()->format('Y-m-d') }}"
                                class="input-field" />
                            <span class="text-xs text-gray-400">–</span>
                            <input wire:model.live.debounce.500ms="seoDateTo" type="date" max="{{ now()->format('Y-m-d') }}"
                                class="input-field" />
                        </div>

                        {{-- Compare Toggle --}}
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <span class="text-sm font-medium text-gray-300">{{ __('Compare') }}</span>
                            <button wire:click="$toggle('seoCompareEnabled')" type="button"
                                class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $seoCompareEnabled ? 'bg-brand-500' : 'bg-white/8' }}"
                                role="switch" aria-checked="{{ $seoCompareEnabled ? 'true' : 'false' }}">
                                <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $seoCompareEnabled ? 'translate-x-4 rtl:-translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        </label>

                        {{-- Comparison Period Label --}}
                        @if($seoCompareEnabled && $seoDateFrom && $seoDateTo)
                            @php
                                $daysDiff = \Carbon\Carbon::parse($seoDateFrom)->diffInDays(\Carbon\Carbon::parse($seoDateTo));
                                $cmpStart = \Carbon\Carbon::parse($seoDateFrom)->subDays($daysDiff);
                                $cmpEnd = \Carbon\Carbon::parse($seoDateFrom)->subDay();
                            @endphp
                            <span class="text-xs text-gray-400">{{ __('common.vs') }} {{ $cmpStart->format('M d') }} – {{ $cmpEnd->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($seoLoading)
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="animate-spin h-4 w-4 me-2 text-brand-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('common.loading') }}
                            </div>
                        @endif
                        {{-- Export Dropdown --}}
                        <div class="relative" x-data="{ exportOpen: false }">
                            <button @click="exportOpen = !exportOpen" class="btn-secondary">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4 me-1.5" />
                                {{ __('common.export') }}
                            </button>
                            <div x-show="exportOpen" @click.outside="exportOpen = false"
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute end-0 z-20 mt-2 w-48 origin-top-right rounded-xl bg-gray-900 border border-white/10">
                                <div class="py-1">
                                    <a href="{{ route('client-seo-report-pdf', $clientId) }}?period={{ $seoPeriod }}" target="_blank" @click="exportOpen = false" class="flex items-center px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                        <x-heroicon-o-document class="w-4 h-4 me-2 text-gray-400" /> {{ __('Download PDF') }}
                                    </a>
                                    <a href="{{ route('client-seo-report-excel', $clientId) }}?period={{ $seoPeriod }}" target="_blank" @click="exportOpen = false" class="flex items-center px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                        <x-heroicon-o-table-cells class="w-4 h-4 me-2 text-green-600" /> {{ __('Export Excel') }}
                                    </a>
                                    <button @click="window.print(); exportOpen = false" class="flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                        <x-heroicon-o-printer class="w-4 h-4 me-2 text-gray-500" /> {{ __('Print') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($seoLoading && !$seoReportData)
                <div class="space-y-6 animate-pulse">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @for($i = 0; $i < 8; $i++)
                            <div class="bg-gray-900 rounded-lg border border-white/10 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-2 flex-1">
                                        <div class="h-3 bg-white/8 rounded w-24"></div>
                                        <div class="h-7 bg-white/8 rounded w-16"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-white/8 rounded-lg"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="bg-gray-900 rounded-lg border border-white/10 p-6">
                        <div class="h-4 bg-white/8 rounded w-48 mb-4"></div>
                        <div class="h-48 bg-white/5 rounded"></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg border border-white/10 p-6">
                        <div class="h-4 bg-white/8 rounded w-32 mb-4"></div>
                        <div class="space-y-3">
                            @for($i = 0; $i < 5; $i++)
                                <div class="h-3 bg-white/5 rounded"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            @elseif($seoReportData)
                @if(isset($seoReportData['error']))
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-6 text-center">
                        <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-yellow-400" />
                        <p class="mt-2 text-sm text-amber-400">{{ $seoReportData['error'] }}</p>
                    </div>
                @else
                    @php
                        $channelIcons = [
                            'Organic Search' => ['icon' => 'heroicon-o-magnifying-glass', 'color' => 'text-green-400', 'bg' => 'bg-green-500/10'],
                            'Direct'         => ['icon' => 'heroicon-o-globe-alt', 'color' => 'text-gray-400', 'bg' => 'bg-white/8'],
                            'Social'         => ['icon' => 'heroicon-o-share', 'color' => 'text-amber-400', 'bg' => 'bg-amber-500/10'],
                            'Referral'       => ['icon' => 'heroicon-o-link', 'color' => 'text-brand-400', 'bg' => 'bg-brand-500/10'],
                            'Paid Search'    => ['icon' => 'heroicon-o-currency-dollar', 'color' => 'text-red-400', 'bg' => 'bg-red-500/10'],
                            'Email'          => ['icon' => 'heroicon-o-envelope', 'color' => 'text-brand-400', 'bg' => 'bg-brand-500/10'],
                            'Paid Social'    => ['icon' => 'heroicon-o-megaphone', 'color' => 'text-red-400', 'bg' => 'bg-red-500/10'],
                            'Display'        => ['icon' => 'heroicon-o-tv', 'color' => 'text-green-400', 'bg' => 'bg-green-500/10'],
                        ];
                        $hasEcommerce = (($seoReportData['ga4']['summary']['ecommerce_purchases'] ?? 0) > 0) || (($seoReportData['ga4']['summary']['total_revenue'] ?? 0) > 0);
                    @endphp

                    @php
                        $hasSc = !empty($seoReportData['search_console']);
                        $hasGa4 = !empty($seoReportData['ga4']);
                        $hasPs = !empty($seoPagespeedUrl);
                    @endphp

                    {{-- Sub-Tab Navigation (Feature 13) --}}
                    <div x-data="{ subTab: 'overview' }">
                        <nav class="border-b border-white/10 mb-6 -mb-px flex gap-x-6 overflow-x-auto">
                            <button @click="subTab = 'overview'" :class="subTab === 'overview' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                {{ __('client_dashboard.seo.tab_overview') }}
                            </button>
                            @if($hasSc)
                                <button @click="subTab = 'search_console'" :class="subTab === 'search_console' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_search_console') }}
                                </button>
                            @endif
                            @if($hasGa4)
                                <button @click="subTab = 'analytics'" :class="subTab === 'analytics' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_analytics') }}
                                </button>
                            @endif
                            @if($hasPs)
                                <button @click="subTab = 'pagespeed'" :class="subTab === 'pagespeed' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_pagespeed') }}
                                </button>
                            @endif
                            @if($hasEcommerce)
                                <button @click="subTab = 'ecommerce'" :class="subTab === 'ecommerce' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_ecommerce') }}
                                </button>
                            @endif
                            <button @click="subTab = 'audit'" :class="subTab === 'audit' ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                {{ __('client_dashboard.seo.tab_audit') }}
                            </button>
                        </nav>

                    {{-- ═══ OVERVIEW SUB-TAB ═══ --}}
                    <div x-show="subTab === 'overview'">
                    {{-- SEO Key Metrics --}}
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-4">
                            <x-heroicon-o-chart-bar-square class="w-5 h-5 text-gray-400" />
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Key Metrics') }}</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @php $cmp = $seoReportData['comparison'] ?? []; $spk = $seoReportData['sparklines'] ?? []; @endphp
                            @if(isset($seoReportData['ga4']['summary']['active_users']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.active_users') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['active_users']) }}</p>
                                            @if(isset($cmp['active_users']))
                                                <span class="kpi-change {{ $cmp['active_users']['direction'] }}">
                                                    @if($cmp['active_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['active_users']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['active_users']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['active_users']) }}" fill="none" stroke="#EF4444" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-users class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['search_console']['summary']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.search_impressions') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['search_console']['summary']['total_impressions'] ?? 0) }}</p>
                                            @if(isset($cmp['total_impressions']))
                                                <span class="kpi-change {{ $cmp['total_impressions']['direction'] }}">
                                                    @if($cmp['total_impressions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_impressions']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['total_impressions']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['total_impressions']) }}" fill="none" stroke="#a855f7" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-eye class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.avg_position') }}</p>
                                            <p class="kpi-value">{{ $seoReportData['search_console']['summary']['avg_position'] ?? '-' }}</p>
                                            @if(isset($cmp['avg_position']))
                                                <span class="kpi-change {{ $cmp['avg_position']['direction'] }}">
                                                    @if($cmp['avg_position']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['avg_position']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.search_clicks') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['search_console']['summary']['total_clicks'] ?? 0) }}</p>
                                            @if(isset($cmp['total_clicks']))
                                                <span class="kpi-change {{ $cmp['total_clicks']['direction'] }}">
                                                    @if($cmp['total_clicks']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_clicks']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['total_clicks']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['total_clicks']) }}" fill="none" stroke="#6366f1" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-cursor-arrow-rays class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['pagespeed_mobile']['scores']['performance']) && $seoReportData['pagespeed_mobile']['scores']['performance'] > 0)
                                @php
                                    $score = $seoReportData['pagespeed_mobile']['scores']['performance'];
                                    $scoreColor = $score >= 90 ? 'text-green-400' : ($score >= 50 ? 'text-orange-400' : 'text-red-400');
                                @endphp
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.mobile_speed') }}</p>
                                            <p class="kpi-value {{ $scoreColor }}">{{ $score }}</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-bolt class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['sessions']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.sessions') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['sessions']) }}</p>
                                            @if(isset($cmp['sessions']))
                                                <span class="kpi-change {{ $cmp['sessions']['direction'] }}">
                                                    @if($cmp['sessions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['sessions']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['sessions']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['sessions']) }}" fill="none" stroke="#16a34a" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['page_views']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.page_views') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['page_views']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-document-text class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['total_users']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.total_users') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['total_users']) }}</p>
                                            @if(isset($cmp['total_users']))
                                                <span class="kpi-change {{ $cmp['total_users']['direction'] }}">
                                                    @if($cmp['total_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_users']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-user-group class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['new_users']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.new_users') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['new_users']) }}</p>
                                            @if(isset($cmp['new_users']))
                                                <span class="kpi-change {{ $cmp['new_users']['direction'] }}">
                                                    @if($cmp['new_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['new_users']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-user-plus class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['conversions']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.conversions') }}</p>
                                            <p class="kpi-value">{{ fmtNum($seoReportData['ga4']['summary']['conversions']) }}</p>
                                            @if(isset($cmp['conversions']))
                                                <span class="kpi-change {{ $cmp['conversions']['direction'] }}">
                                                    @if($cmp['conversions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['conversions']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-check-circle class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(($seoReportData['ga4']['summary']['total_revenue'] ?? 0) > 0)
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.total_revenue') }}</p>
                                            <p class="kpi-value">{{ fmtCurrency($seoReportData['ga4']['summary']['total_revenue']) }}</p>
                                            @if(isset($cmp['total_revenue']))
                                                <span class="kpi-change {{ $cmp['total_revenue']['direction'] }}">
                                                    @if($cmp['total_revenue']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_revenue']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-banknotes class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['bounce_rate']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.bounce_rate') }}</p>
                                            <p class="kpi-value">{{ number_format($seoReportData['ga4']['summary']['bounce_rate'], 1) }}%</p>
                                            @if(isset($cmp['bounce_rate']))
                                                <span class="kpi-change {{ $cmp['bounce_rate']['direction'] }}">
                                                    @if($cmp['bounce_rate']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['bounce_rate']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['bounce_rate']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['bounce_rate']) }}" fill="none" stroke="#f43f5e" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-arrow-uturn-left class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['avg_session_duration']))
                                @php
                                    $dur = $seoReportData['ga4']['summary']['avg_session_duration'];
                                    $mins = floor($dur / 60);
                                    $secs = round($dur % 60);
                                @endphp
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.avg_engagement_time') }}</p>
                                            <p class="kpi-value">{{ $mins }}m {{ $secs }}s</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['engagement_rate']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.engagement_rate') }}</p>
                                            <p class="kpi-value">{{ number_format($seoReportData['ga4']['summary']['engagement_rate'], 1) }}%</p>
                                            @if(isset($cmp['engagement_rate']))
                                                <span class="kpi-change {{ $cmp['engagement_rate']['direction'] }}">
                                                    @if($cmp['engagement_rate']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['engagement_rate']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-fire class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['ga4']['traffic_sources']))
                                @php
                                    $organicSource = collect($seoReportData['ga4']['traffic_sources'])->firstWhere('channel', 'Organic Search');
                                    $organicShare = $organicSource['share'] ?? 0;
                                @endphp
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.organic_share') }}</p>
                                            <p class="kpi-value text-green-400">{{ number_format($organicShare, 1) }}%</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['search_console']['summary']['avg_ctr']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.search_ctr') }}</p>
                                            <p class="kpi-value">{{ number_format($seoReportData['search_console']['summary']['avg_ctr'], 2) }}%</p>
                                            @if(isset($cmp['avg_ctr']))
                                                <span class="kpi-change {{ $cmp['avg_ctr']['direction'] }}">
                                                    @if($cmp['avg_ctr']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['avg_ctr']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-arrow-up-right class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['search_console']['pages']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.unique_pages_ranking') }}</p>
                                            <p class="kpi-value">{{ count($seoReportData['search_console']['pages']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-document-duplicate class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['search_console']['queries']))
                                <div class="kpi-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="kpi-label">{{ __('client_dashboard.seo.unique_keywords') }}</p>
                                            <p class="kpi-value">{{ count($seoReportData['search_console']['queries']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-white/5 rounded-lg">
                                            <x-heroicon-o-key class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Search Console Trend Chart --}}
                    @if(!empty($seoReportData['search_console']['timeseries']))
                        <div class="card p-6 mb-6">
                            <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.search_performance_trend') }}</h3>
                            <div class="h-64 sm:h-72 md:h-80" x-data="{ chart: null }" x-init="chart = new Chart($refs.scTrendCanvas, @js($scTrendChartConfig))" wire:ignore>
                                <canvas x-ref="scTrendCanvas"></canvas>
                            </div>
                        </div>
                    @endif

                    {{-- GA4 Analytics Trend Chart --}}
                    @if(!empty($seoReportData['ga4']['timeseries']))
                        <div class="card p-6 mb-6">
                            <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.ga4_analytics_trend') }}</h3>
                            <div class="h-64 sm:h-72 md:h-80" x-data="{ chart: null }" x-init="chart = new Chart($refs.ga4TrendCanvas, @js($ga4TrendChartConfig))" wire:ignore>
                                <canvas x-ref="ga4TrendCanvas"></canvas>
                            </div>
                        </div>
                    @endif
                    </div>{{-- end overview sub-tab --}}

                    {{-- ═══ PAGESPEED SUB-TAB ═══ --}}
                    <div x-show="subTab === 'pagespeed'">
                    {{-- PageSpeed Section (On-Demand) --}}
                    @if($seoPagespeedUrl)
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.pagespeed_title') }}</h3>
                                <div class="flex items-center gap-3">
                                    <button wire:click.stop="runPageSpeed" wire:loading.attr="disabled" wire:target="runPageSpeed"
                                            class="btn-secondary disabled:opacity-50"
                                            @if(($seoReportData['pagespeed_mobile']['_error'] ?? null) === 'quota_exceeded') disabled @endif>
                                        <span wire:loading.remove wire:target="runPageSpeed">
                                            <x-heroicon-o-bolt class="w-4 h-4 me-1.5 inline-block -mt-0.5" />
                                            {{ __('client_dashboard.seo.run_pagespeed') }}
                                        </span>
                                        <span wire:loading wire:target="runPageSpeed" class="inline-flex items-center">
                                            <svg class="animate-spin h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('client_dashboard.seo.pagespeed_analyzing') }}
                                        </span>
                                    </button>
                                    <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                                </div>
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            @if(isset($seoReportData['pagespeed_mobile']['scores']) || isset($seoReportData['pagespeed_desktop']['scores']))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach(['mobile' => $seoReportData['pagespeed_mobile'] ?? null, 'desktop' => $seoReportData['pagespeed_desktop'] ?? null] as $type => $ps)
                                        @if($ps && isset($ps['scores']))
                                            <div class="border border-white/10 rounded-lg p-4">
                                                <h4 class="text-sm font-medium text-gray-200 mb-3 capitalize">{{ $type }}</h4>
                                                <div class="flex items-center justify-around gap-3 mb-4">
                                                    @foreach(['performance' => __('client_dashboard.seo.score_performance'), 'accessibility' => __('client_dashboard.seo.score_accessibility'), 'best_practices' => __('client_dashboard.seo.score_best_practices'), 'seo' => __('client_dashboard.seo.score_seo')] as $key => $label)
                                                        @php
                                                            $val = $ps['scores'][$key] ?? 0;
                                                            $circumference = 2 * 3.14159 * 28;
                                                            $offset = $circumference * (1 - $val / 100);
                                                            $ringColor = $val >= 90 ? '#16a34a' : ($val >= 50 ? '#d97706' : '#dc2626');
                                                        @endphp
                                                        <div class="text-center">
                                                            <div class="relative inline-block w-16 h-16">
                                                                <svg width="64" height="64" viewBox="0 0 64 64" class="transform -rotate-90">
                                                                    <circle cx="32" cy="32" r="28" fill="none" stroke="rgba(212, 212, 210, 0.6)" stroke-width="5"/>
                                                                    <circle cx="32" cy="32" r="28" fill="none" stroke="{{ $ringColor }}" stroke-width="5"
                                                                            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"/>
                                                                </svg>
                                                                <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold" style="color: {{ $ringColor }}">{{ $val }}</span>
                                                            </div>
                                                            <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                {{-- Core Web Vitals --}}
                                                @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                                    <div class="mt-4 pt-4 border-t border-white/10">
                                                        <h5 class="text-xs font-medium text-gray-300 mb-2">{{ __('client_dashboard.seo.core_web_vitals') }}</h5>
                                                        <div class="grid grid-cols-3 gap-2">
                                                            @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                                @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                                    @php $cwv = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                                    <div class="text-center p-2 rounded {{ ($cwv['score'] ?? '') === 'good' ? 'bg-green-500/10' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'bg-amber-500/10' : 'bg-red-500/10') }}">
                                                                        <div class="text-sm font-semibold {{ ($cwv['score'] ?? '') === 'good' ? 'text-green-400' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'text-amber-400' : 'text-red-400') }}">
                                                                            {{ $cwv['display'] ?? $cwv['value'] ?? '-' }}
                                                                        </div>
                                                                        <div class="text-xs text-gray-500">{{ $cwvLabel }}</div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @elseif(($seoReportData['pagespeed_mobile']['_error'] ?? null) === 'quota_exceeded' || ($seoReportData['pagespeed_desktop']['_error'] ?? null) === 'quota_exceeded')
                                <div class="empty-state">
                                    <x-heroicon-o-clock class="mx-auto h-10 w-10 text-orange-300" />
                                    <p class="empty-title">{{ __('client_dashboard.seo.pagespeed_quota_exceeded') }}</p>
                                    <p class="empty-message">{{ __('client_dashboard.seo.pagespeed_quota_hint') }}</p>
                                </div>
                            @else
                                <div class="empty-state">
                                    <x-heroicon-o-bolt class="mx-auto h-10 w-10 text-gray-300" />
                                    <p class="empty-message">{{ __('client_dashboard.seo.pagespeed_prompt') }}</p>
                                </div>
                            @endif
                            </div>
                        </div>
                    @endif
                    </div>{{-- end pagespeed sub-tab --}}

                    {{-- ═══ ANALYTICS SUB-TAB ═══ --}}
                    <div x-show="subTab === 'analytics'">
                    {{-- Traffic Sources (Collapsible + Channel Icons) --}}
                    @if(!empty($seoReportData['ga4']['traffic_sources']))
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Traffic Sources') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-hidden rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Channel') }}</th>
                                            <th class="text-end">{{ __('Sessions') }}</th>
                                            <th class="text-end">{{ __('Share') }}</th>
                                            <th class="text-end">{{ __('Revenue') }}</th>
                                            <th class="text-end">{{ __('client_dashboard.seo.rev_share') }}</th>
                                            <th class="text-end">{{ __('client_dashboard.seo.rev_per_session') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Sessions') }}</th>@endif
                                            <th style="min-width:120px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['traffic_sources'] as $source)
                                            @php $ci = $channelIcons[$source['channel'] ?? ''] ?? null; @endphp
                                            <tr>
                                                <td class="font-medium">
                                                    <span class="inline-flex items-center gap-2">
                                                        @if($ci)
                                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $ci['bg'] }}">
                                                                <x-dynamic-component :component="$ci['icon']" class="w-3.5 h-3.5 {{ $ci['color'] }}" />
                                                            </span>
                                                        @endif
                                                        {{ $source['channel'] ?? '-' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">{{ fmtNum($source['sessions'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($source['share'] ?? 0, 1) }}%</td>
                                                <td class="text-end">{{ fmtCurrency($source['revenue'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($source['rev_share'] ?? 0, 1) }}%</td>
                                                <td class="text-end">{{ fmtCurrency($source['rev_per_session'] ?? 0) }}</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_traffic_sources'][$source['channel']]))
                                                        @php $prev = $cmp['ga4_traffic_sources'][$source['channel']]; $d = $prev['sessions'] > 0 ? round((($source['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($source['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                                <td>
                                                    <div class="w-full bg-white/8 rounded-full h-2">
                                                        <div class="bg-brand-500 h-2 rounded-full" style="width: {{ min($source['share'] ?? 0, 100) }}%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    @endif

                    {{-- Top Pages (Two Columns, Collapsible, Position Badges, Show All) --}}
                    @if(!empty($seoReportData['search_console']['pages']) || !empty($seoReportData['ga4']['top_pages']))
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                            {{-- Search Console Pages --}}
                            @if(!empty($seoReportData['search_console']['pages']))
                                <div class="card mb-0" x-data="{ open: true, expanded: false }">
                                    <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                        <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Top Pages (Search Console)') }}</h3>
                                        <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                                    </div>
                                    <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                    <div class="overflow-auto max-h-[32rem] rounded-xl">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Page') }}</th>
                                                    <th class="text-end">{{ __('Clicks') }}</th>
                                                    <th class="text-end">{{ __('Impressions') }}</th>
                                                    <th class="text-end">{{ __('CTR') }}</th>
                                                    <th class="text-end">{{ __('Position') }}</th>
                                                    @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Clicks') }}</th>@endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                @foreach($seoReportData['search_console']['pages'] as $idx => $page)<tr x-show="expanded || {{ $idx }} < 10">
                                                        <td class="truncate max-w-[200px]" title="{{ $page['key'] ?? '' }}">
                                                            @if(!empty($page['key']))
                                                                <a href="{{ $page['key'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $page['key'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                        <td class="text-end">{{ fmtNum($page['clicks'] ?? 0) }}</td>
                                                        <td class="text-end">{{ fmtNum($page['impressions'] ?? 0) }}</td>
                                                        <td class="text-end">{{ number_format($page['ctr'] ?? 0, 1) }}%</td>
                                                        <td class="text-end">
                                                            @php $pos = $page['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-500/15 text-green-400' : ($pos <= 10 ? 'bg-amber-500/15 text-amber-400' : 'bg-white/8 text-gray-400'); @endphp
                                                            <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                        </td>
                                                        @if($seoCompareEnabled)
                                                            @if(isset($cmp['sc_pages'][$page['key']]))
                                                                @php $prev = $cmp['sc_pages'][$page['key']]; $d = $prev['clicks'] > 0 ? round((($page['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($page['clicks'] > 0 ? 100 : 0); @endphp
                                                                <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                            @else
                                                                <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                            @endif
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(count($seoReportData['search_console']['pages']) > 10)
                                        <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                            <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['pages']) }}'"></span>
                                        </button>
                                    @endif
                                    </div>
                                </div>
                            @endif
                            {{-- GA4 Top Pages --}}
                            @if(!empty($seoReportData['ga4']['top_pages']))
                                <div class="card mb-0" x-data="{ open: true, expanded: false }">
                                    <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                        <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Top Pages (GA4)') }}</h3>
                                        <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                                    </div>
                                    <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                    <div class="overflow-auto max-h-[32rem] rounded-xl">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Page') }}</th>
                                                    <th class="text-end">{{ __('Views') }}</th>
                                                    <th class="text-end">{{ __('Users') }}</th>
                                                    <th class="text-end">{{ __('Sessions') }}</th>
                                                    @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Views') }}</th>@endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                @foreach($seoReportData['ga4']['top_pages'] as $idx => $page)<tr x-show="expanded || {{ $idx }} < 10">
                                                        <td class="truncate max-w-[200px]" title="{{ $page['page_path'] ?? '' }}">
                                                            @if(!empty($page['page_path']) && !empty($dashboardData['client']['website']))
                                                                <a href="{{ rtrim($dashboardData['client']['website'], '/') }}{{ $page['page_path'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $page['page_path'] }}</a>
                                                            @else
                                                                {{ $page['page_path'] ?? '-' }}
                                                            @endif
                                                        </td>
                                                        <td class="text-end">{{ fmtNum($page['page_views'] ?? 0) }}</td>
                                                        <td class="text-end">{{ fmtNum($page['users'] ?? 0) }}</td>
                                                        <td class="text-end">{{ fmtNum($page['sessions'] ?? 0) }}</td>
                                                        @if($seoCompareEnabled)
                                                            @if(isset($cmp['ga4_pages'][$page['page_path']]))
                                                                @php $prev = $cmp['ga4_pages'][$page['page_path']]; $d = $prev['page_views'] > 0 ? round((($page['page_views'] - $prev['page_views']) / $prev['page_views']) * 100, 1) : ($page['page_views'] > 0 ? 100 : 0); @endphp
                                                                <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                            @else
                                                                <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                            @endif
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(count($seoReportData['ga4']['top_pages']) > 10)
                                        <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                            <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['ga4']['top_pages']) }}'"></span>
                                        </button>
                                    @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Top Search Queries (Collapsible, Position Badges, Show All) --}}
                    @if(!empty($seoReportData['search_console']['queries']))
                        <div class="card mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Top Search Queries') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Query') }}</th>
                                            <th class="text-end">{{ __('Clicks') }}</th>
                                            <th class="text-end">{{ __('Impressions') }}</th>
                                            <th class="text-end">{{ __('CTR') }}</th>
                                            <th class="text-end">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['queries'] as $idx => $query)<tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">
                                                    @if(!empty($query['key']))
                                                        <a href="https://www.google.com/search?q={{ urlencode($query['key']) }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $query['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-end">{{ fmtNum($query['clicks'] ?? 0) }}</td>
                                                <td class="text-end">{{ fmtNum($query['impressions'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($query['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-end">
                                                    @php $pos = $query['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-500/15 text-green-400' : ($pos <= 10 ? 'bg-amber-500/15 text-amber-400' : 'bg-white/8 text-gray-400'); @endphp
                                                    <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_queries'][$query['key']]))
                                                        @php $prev = $cmp['sc_queries'][$query['key']]; $d = $prev['clicks'] > 0 ? round((($query['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($query['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['queries']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['queries']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif

                    {{-- Devices (Collapsible + Device Icons) --}}
                    @if(!empty($seoReportData['ga4']['devices']))
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Devices') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-hidden rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Device') }}</th>
                                            <th class="text-end">{{ __('Users') }}</th>
                                            <th class="text-end">{{ __('Sessions') }}</th>
                                            <th class="text-end">{{ __('Share') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Sessions') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['devices'] as $device)
                                            @php
                                                $deviceIcon = match(strtolower($device['device'] ?? '')) {
                                                    'desktop' => 'heroicon-o-computer-desktop',
                                                    'mobile' => 'heroicon-o-device-phone-mobile',
                                                    'tablet' => 'heroicon-o-device-tablet',
                                                    default => 'heroicon-o-tv',
                                                };
                                            @endphp
                                            <tr>
                                                <td class="font-medium">
                                                    <span class="inline-flex items-center gap-2">
                                                        <x-dynamic-component :component="$deviceIcon" class="w-4 h-4 text-gray-400" />
                                                        {{ ucfirst($device['device'] ?? '-') }}
                                                    </span>
                                                </td>
                                                <td class="text-end">{{ fmtNum($device['users'] ?? 0) }}</td>
                                                <td class="text-end">{{ fmtNum($device['sessions'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($device['share'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_devices'][$device['device']]))
                                                        @php $prev = $cmp['ga4_devices'][$device['device']]; $d = $prev['sessions'] > 0 ? round((($device['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($device['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    @endif

                    {{-- Geographic Data (Collapsible, Country Flags, Show All) --}}
                    @if(!empty($seoReportData['ga4']['geo']))
                        <div class="card mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Geographic Data') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Country') }}</th>
                                            <th class="text-end">{{ __('Sessions') }}</th>
                                            <th class="text-end">{{ __('Users') }}</th>
                                            <th class="text-end">{{ __('Share') }}</th>
                                            <th class="text-end">{{ __('Engagement Rate') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Sessions') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['geo'] as $idx => $geo)<tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">{{ countryFlag($geo['country'] ?? '') }} {{ $geo['country'] ?? '-' }}</td>
                                                <td class="text-end">{{ fmtNum($geo['sessions'] ?? 0) }}</td>
                                                <td class="text-end">{{ fmtNum($geo['users'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($geo['share'] ?? 0, 1) }}%</td>
                                                <td class="text-end">{{ number_format($geo['engagement_rate'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_geo'][$geo['country']]))
                                                        @php $prev = $cmp['ga4_geo'][$geo['country']]; $d = $prev['sessions'] > 0 ? round((($geo['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($geo['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['ga4']['geo']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['ga4']['geo']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif

                    </div>{{-- end analytics sub-tab (main content) --}}

                    {{-- ═══ SEARCH CONSOLE SUB-TAB ═══ --}}
                    <div x-show="subTab === 'search_console'">
                    @if(!empty($seoReportData['search_console']))
                        {{-- SC Summary KPIs --}}
                        @if(isset($seoReportData['search_console']['summary']))
                            @php $scSum = $seoReportData['search_console']['summary']; @endphp
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                <div class="kpi-card">
                                    <p class="kpi-label">{{ __('client_dashboard.seo.search_clicks') }}</p>
                                    <p class="kpi-value">{{ fmtNum($scSum['total_clicks'] ?? 0) }}</p>
                                    @if(isset($cmp['total_clicks']))
                                        <span class="kpi-change {{ $cmp['total_clicks']['direction'] }}">
                                            @if($cmp['total_clicks']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['total_clicks']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="kpi-card">
                                    <p class="kpi-label">{{ __('client_dashboard.seo.search_impressions') }}</p>
                                    <p class="kpi-value">{{ fmtNum($scSum['total_impressions'] ?? 0) }}</p>
                                    @if(isset($cmp['total_impressions']))
                                        <span class="kpi-change {{ $cmp['total_impressions']['direction'] }}">
                                            @if($cmp['total_impressions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['total_impressions']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="kpi-card">
                                    <p class="kpi-label">{{ __('client_dashboard.seo.search_ctr') }}</p>
                                    <p class="kpi-value">{{ number_format($scSum['avg_ctr'] ?? 0, 2) }}%</p>
                                    @if(isset($cmp['avg_ctr']))
                                        <span class="kpi-change {{ $cmp['avg_ctr']['direction'] }}">
                                            @if($cmp['avg_ctr']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['avg_ctr']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="kpi-card">
                                    <p class="kpi-label">{{ __('client_dashboard.seo.avg_position') }}</p>
                                    <p class="kpi-value">{{ $scSum['avg_position'] ?? '-' }}</p>
                                    @if(isset($cmp['avg_position']))
                                        <span class="kpi-change {{ $cmp['avg_position']['direction'] }}">
                                            @if($cmp['avg_position']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['avg_position']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- SC Trend Chart --}}
                        @if(!empty($seoReportData['search_console']['timeseries']))
                            <div class="card p-6 mb-6">
                                <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.search_performance_trend') }}</h3>
                                <div class="h-64 sm:h-72 md:h-80" x-data="{ chart: null }" x-init="chart = new Chart($refs.scTrendCanvas2, @js($scTrendChartConfig))" wire:ignore>
                                    <canvas x-ref="scTrendCanvas2"></canvas>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- SC Pages --}}
                    @if(!empty($seoReportData['search_console']['pages']))
                        <div class="card mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Top Pages (Search Console)') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Page') }}</th>
                                            <th class="text-end">{{ __('Clicks') }}</th>
                                            <th class="text-end">{{ __('Impressions') }}</th>
                                            <th class="text-end">{{ __('CTR') }}</th>
                                            <th class="text-end">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['pages'] as $idx => $page)<tr x-show="expanded || {{ $idx }} < 10">
                                                <td class="truncate max-w-[200px]" title="{{ $page['key'] ?? '' }}">
                                                    @if(!empty($page['key']))
                                                        <a href="{{ $page['key'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $page['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-end">{{ fmtNum($page['clicks'] ?? 0) }}</td>
                                                <td class="text-end">{{ fmtNum($page['impressions'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($page['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-end">
                                                    @php $pos = $page['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-500/15 text-green-400' : ($pos <= 10 ? 'bg-amber-500/15 text-amber-400' : 'bg-white/8 text-gray-400'); @endphp
                                                    <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_pages'][$page['key']]))
                                                        @php $prev = $cmp['sc_pages'][$page['key']]; $d = $prev['clicks'] > 0 ? round((($page['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($page['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['pages']) > 10)
                                <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['pages']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif
                    {{-- SC Queries --}}
                    @if(!empty($seoReportData['search_console']['queries']))
                        <div class="card mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Top Search Queries') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Query') }}</th>
                                            <th class="text-end">{{ __('Clicks') }}</th>
                                            <th class="text-end">{{ __('Impressions') }}</th>
                                            <th class="text-end">{{ __('CTR') }}</th>
                                            <th class="text-end">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['queries'] as $idx => $query)<tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">
                                                    @if(!empty($query['key']))
                                                        <a href="https://www.google.com/search?q={{ urlencode($query['key']) }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $query['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-end">{{ fmtNum($query['clicks'] ?? 0) }}</td>
                                                <td class="text-end">{{ fmtNum($query['impressions'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($query['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-end">
                                                    @php $pos = $query['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-500/15 text-green-400' : ($pos <= 10 ? 'bg-amber-500/15 text-amber-400' : 'bg-white/8 text-gray-400'); @endphp
                                                    <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_queries'][$query['key']]))
                                                        @php $prev = $cmp['sc_queries'][$query['key']]; $d = $prev['clicks'] > 0 ? round((($query['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($query['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['queries']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full btn-ghost">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['queries']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif
                    {{-- SC Coverage in search_console tab --}}
                    @if(!empty($seoReportData['search_console']['coverage']) && !empty($seoReportData['search_console']['coverage']['sitemaps']))
                        @php $cov2 = $seoReportData['search_console']['coverage']; @endphp
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                    <div class="text-center border border-white/10 rounded-lg p-4"><p class="stat-num text-white">{{ fmtNum($cov2['total_submitted']) }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p></div>
                                    <div class="text-center border border-white/10 rounded-lg p-4"><p class="stat-num text-green-600">{{ fmtNum($cov2['total_indexed']) }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p></div>
                                    <div class="text-center border border-white/10 rounded-lg p-4">@php $rc = $cov2['index_rate'] >= 90 ? 'text-green-600' : ($cov2['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp<p class="stat-num {{ $rc }}">{{ $cov2['index_rate'] }}%</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p></div>
                                    <div class="text-center border border-white/10 rounded-lg p-4"><p class="stat-num text-red-600">{{ $cov2['total_errors'] }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p></div>
                                    <div class="text-center border border-white/10 rounded-lg p-4"><p class="stat-num text-orange-600">{{ $cov2['total_warnings'] }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p></div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if(empty($seoReportData['search_console']))
                        <div class="empty-state">
                            <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="empty-title">{{ __('No Search Console data') }}</h3>
                            <p class="empty-message">{{ __('Configure a Search Console property in SEO Properties to view data.') }}</p>
                        </div>
                    @endif
                    </div>{{-- end search_console sub-tab --}}

                    {{-- ═══ ECOMMERCE SUB-TAB ═══ --}}
                    <div x-show="subTab === 'ecommerce'">
                    {{-- Ecommerce Sections --}}
                    @if($hasEcommerce)
                        {{-- Ecommerce Section Divider --}}
                        <div class="flex items-center gap-2 mb-4 mt-2">
                            <x-heroicon-o-shopping-cart class="w-5 h-5 text-gray-400" />
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('Ecommerce') }}</h3>
                            <div class="flex-1 h-px bg-white/8"></div>
                        </div>

                        {{-- Top Products by Revenue --}}
                        @php
                            $ecomPages = $seoReportData['ga4']['top_ecommerce_pages'] ?? [];
                        @endphp
                        @if(!empty($ecomPages))
                            <div class="card p-6 mb-6">
                                <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.top_ecommerce_pages') }}</h3>
                                <div class="overflow-hidden rounded-xl">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('client_dashboard.seo.product') }}</th>
                                                <th class="text-end">{{ __('Views') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.purchases') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.revenue_sar') }}</th>
                                                @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Views') }}</th>@endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                            @foreach($ecomPages as $ePage)
                                                <tr>
                                                    <td class="truncate max-w-[200px]" title="{{ $ePage['name'] ?? '' }}">
                                                        @if(!empty($ePage['name']))
                                                            <a href="https://www.google.com/search?q={{ urlencode($ePage['name']) }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $ePage['name'] }}</a>
                                                        @else - @endif
                                                    </td>
                                                    <td class="text-end">{{ fmtNum($ePage['views'] ?? 0) }}</td>
                                                    <td class="text-end">{{ fmtNum($ePage['purchases'] ?? 0) }}</td>
                                                    <td class="text-end">{{ fmtCurrency($ePage['revenue'] ?? 0) }}</td>
                                                    @if($seoCompareEnabled)
                                                        @if(isset($cmp['ga4_ecom_pages'][$ePage['name']]))
                                                            @php $prev = $cmp['ga4_ecom_pages'][$ePage['name']]; $d = $prev['views'] > 0 ? round((($ePage['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($ePage['views'] > 0 ? 100 : 0); @endphp
                                                            <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                        @else
                                                            <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                        @endif
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Top 5 / Least 5 Products --}}
                        @if(!empty($seoReportData['ga4']['top_products']) || !empty($seoReportData['ga4']['least_products']))
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                {{-- Top 5 Products --}}
                                @if(!empty($seoReportData['ga4']['top_products']))
                                    <div class="card p-6">
                                        <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.top_products') }}</h3>
                                        <div class="overflow-hidden rounded-xl">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th class="w-10">#</th>
                                                        <th>{{ __('client_dashboard.seo.product') }}</th>
                                                        <th class="text-end">{{ __('Views') }}</th>
                                                        <th class="text-end">{{ __('Users') }}</th>
                                                        @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Views') }}</th>@endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                    @foreach($seoReportData['ga4']['top_products'] as $prod)
                                                        <tr>
                                                            <td class="text-gray-400">{{ $loop->iteration }}</td>
                                                            <td class="font-medium truncate max-w-[200px]">
                                                            @if(!empty($prod['name']))
                                                                <a href="https://www.google.com/search?q={{ urlencode($prod['name']) }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $prod['name'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                            <td class="text-end">{{ fmtNum($prod['views'] ?? 0) }}</td>
                                                            <td class="text-end">{{ fmtNum($prod['users'] ?? 0) }}</td>
                                                            @if($seoCompareEnabled)
                                                                @if(isset($cmp['ga4_top_products'][$prod['name']]))
                                                                    @php $prev = $cmp['ga4_top_products'][$prod['name']]; $d = $prev['views'] > 0 ? round((($prod['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($prod['views'] > 0 ? 100 : 0); @endphp
                                                                    <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                                @else
                                                                    <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                                @endif
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                {{-- Least 5 Products --}}
                                @if(!empty($seoReportData['ga4']['least_products']))
                                    <div class="card p-6">
                                        <h3 class="text-base font-semibold tracking-tight text-white mb-4">{{ __('client_dashboard.seo.least_products') }}</h3>
                                        <div class="overflow-hidden rounded-xl">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th class="w-10">#</th>
                                                        <th>{{ __('client_dashboard.seo.product') }}</th>
                                                        <th class="text-end">{{ __('Views') }}</th>
                                                        <th class="text-end">{{ __('Users') }}</th>
                                                        <th class="text-end">{{ __('Engagement Rate') }}</th>
                                                        @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Views') }}</th>@endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                    @foreach($seoReportData['ga4']['least_products'] as $prod)
                                                        <tr>
                                                            <td class="text-gray-400">{{ $loop->iteration }}</td>
                                                            <td class="font-medium truncate max-w-[200px]">
                                                            @if(!empty($prod['name']))
                                                                <a href="https://www.google.com/search?q={{ urlencode($prod['name']) }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $prod['name'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                            <td class="text-end">{{ fmtNum($prod['views'] ?? 0) }}</td>
                                                            <td class="text-end">{{ fmtNum($prod['users'] ?? 0) }}</td>
                                                            <td class="text-end">{{ number_format($prod['engagement_rate'] ?? 0, 1) }}%</td>
                                                            @if($seoCompareEnabled)
                                                                @if(isset($cmp['ga4_least_products'][$prod['name']]))
                                                                    @php $prev = $cmp['ga4_least_products'][$prod['name']]; $d = $prev['views'] > 0 ? round((($prod['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($prod['views'] > 0 ? 100 : 0); @endphp
                                                                    <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                                @else
                                                                    <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                                @endif
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                    </div>{{-- end ecommerce sub-tab --}}

                    {{-- ═══ remaining sections visible in analytics sub-tab ═══ --}}
                    <div x-show="subTab === 'analytics'">
                    {{-- Browser / Technology (Collapsible) --}}
                    @if(!empty($seoReportData['ga4']['browsers']))
                        @php $browserTotalRevenue = array_sum(array_column($seoReportData['ga4']['browsers'], 'revenue')); @endphp
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.browser_technology') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] rounded-xl">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('client_dashboard.seo.browser') }}</th>
                                            <th class="text-end">{{ __('Users') }}</th>
                                            <th class="text-end">{{ __('Share') }}</th>
                                            @if($browserTotalRevenue > 0)
                                                <th class="text-end">{{ __('client_dashboard.seo.revenue_sar') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.rev_share') }}</th>
                                            @endif
                                            <th class="text-end">{{ __('Engagement Rate') }}</th>
                                            @if($seoCompareEnabled)<th class="text-end">{{ __('Δ Users') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['browsers'] as $browser)
                                            <tr>
                                                <td class="font-medium">{{ $browser['browser'] ?? '-' }}</td>
                                                <td class="text-end">{{ fmtNum($browser['users'] ?? 0) }}</td>
                                                <td class="text-end">{{ number_format($browser['share'] ?? 0, 1) }}%</td>
                                                @if($browserTotalRevenue > 0)
                                                    <td class="text-end">{{ fmtCurrency($browser['revenue'] ?? 0) }}</td>
                                                    <td class="text-end">{{ number_format($browser['rev_share'] ?? 0, 1) }}%</td>
                                                @endif
                                                <td class="text-end">{{ number_format($browser['engagement_rate'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_browsers'][$browser['browser']]))
                                                        @php $prev = $cmp['ga4_browsers'][$browser['browser']]; $d = $prev['users'] > 0 ? round((($browser['users'] - $prev['users']) / $prev['users']) * 100, 1) : ($browser['users'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-end"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-end"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    @endif

                    {{-- Search Console Coverage (Collapsible) --}}
                    @if(!empty($seoReportData['search_console']['coverage']) && !empty($seoReportData['search_console']['coverage']['sitemaps']))
                        @php $cov = $seoReportData['search_console']['coverage']; @endphp
                        <div class="card mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-white">{{ fmtNum($cov['total_submitted']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-green-600">{{ fmtNum($cov['total_indexed']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    @php $rateColor = $cov['index_rate'] >= 90 ? 'text-green-600' : ($cov['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp
                                    <p class="stat-num {{ $rateColor }}">{{ $cov['index_rate'] }}%</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-red-600">{{ $cov['total_errors'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-orange-600">{{ $cov['total_warnings'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p>
                                </div>
                            </div>
                            @if(count($cov['sitemaps']) > 1)
                                <div class="mt-4 overflow-hidden rounded-xl">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('client_dashboard.seo.sitemap') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.submitted_pages') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.indexed_pages') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.errors') }}</th>
                                                <th class="text-end">{{ __('client_dashboard.seo.warnings') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cov['sitemaps'] as $sm)
                                                <tr>
                                                    <td class="truncate max-w-[300px]" title="{{ $sm['path'] }}">
                                                        <a href="{{ $sm['path'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline">{{ $sm['path'] }}</a>
                                                    </td>
                                                    <td class="text-end">{{ fmtNum($sm['submitted']) }}</td>
                                                    <td class="text-end">{{ fmtNum($sm['indexed']) }}</td>
                                                    <td class="text-end {{ $sm['errors'] > 0 ? 'text-red-600 font-medium' : '' }}">{{ $sm['errors'] }}</td>
                                                    <td class="text-end {{ $sm['warnings'] > 0 ? 'text-orange-600 font-medium' : '' }}">{{ $sm['warnings'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            </div>
                        </div>
                    @endif
                    </div>{{-- end analytics sub-tab (browser/coverage) --}}

                    {{-- ═══ AUDIT & RECOMMENDATIONS SUB-TAB ═══ --}}
                    <div x-show="subTab === 'audit'">

                    {{-- Section 1: Overall SEO Health Score --}}
                    <div class="card p-6 mb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400" />
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.audit_health_score') }}</h3>
                        </div>
                        <p class="text-sm text-gray-500 mb-6">{{ __('client_dashboard.seo.audit_health_desc') }}</p>

                        @php
                            // Compute component scores
                            $auditComponents = [];

                            // PageSpeed SEO score (avg mobile+desktop)
                            $psMobile = $seoReportData['pagespeed_mobile']['scores'] ?? null;
                            $psDesktop = $seoReportData['pagespeed_desktop']['scores'] ?? null;
                            if ($psMobile && $psDesktop) {
                                $auditComponents['pagespeed_seo'] = ['value' => round((($psMobile['seo'] ?? 0) + ($psDesktop['seo'] ?? 0)) / 2), 'weight' => 25, 'label' => __('client_dashboard.seo.score_seo')];
                                $auditComponents['pagespeed_perf'] = ['value' => round((($psMobile['performance'] ?? 0) + ($psDesktop['performance'] ?? 0)) / 2), 'weight' => 20, 'label' => __('client_dashboard.seo.score_performance')];
                            } elseif ($psMobile) {
                                $auditComponents['pagespeed_seo'] = ['value' => $psMobile['seo'] ?? 0, 'weight' => 25, 'label' => __('client_dashboard.seo.score_seo')];
                                $auditComponents['pagespeed_perf'] = ['value' => $psMobile['performance'] ?? 0, 'weight' => 20, 'label' => __('client_dashboard.seo.score_performance')];
                            } elseif ($psDesktop) {
                                $auditComponents['pagespeed_seo'] = ['value' => $psDesktop['seo'] ?? 0, 'weight' => 25, 'label' => __('client_dashboard.seo.score_seo')];
                                $auditComponents['pagespeed_perf'] = ['value' => $psDesktop['performance'] ?? 0, 'weight' => 20, 'label' => __('client_dashboard.seo.score_performance')];
                            }

                            // SC Index Rate
                            $auditCov = $seoReportData['search_console']['coverage'] ?? null;
                            if ($auditCov && isset($auditCov['index_rate'])) {
                                $auditComponents['index_rate'] = ['value' => round($auditCov['index_rate']), 'weight' => 25, 'label' => __('client_dashboard.seo.index_rate')];
                            }

                            // Issues Score
                            $criticalCount = 0; $highCount = 0; $mediumCount = 0; $lowCount = 0;
                            foreach ($auditIssues as $ai) {
                                if (($ai['current_status'] ?? '') === 'resolved') continue;
                                match($ai['severity'] ?? '') {
                                    'critical' => $criticalCount++,
                                    'high' => $highCount++,
                                    'medium' => $mediumCount++,
                                    'low' => $lowCount++,
                                    default => null,
                                };
                            }
                            $issuesRaw = 100 - min(40, $criticalCount * 15 + $highCount * 8 + $mediumCount * 3 + $lowCount * 1);
                            $auditComponents['issues'] = ['value' => max(0, $issuesRaw), 'weight' => 30, 'label' => __('client_dashboard.seo.audit_issues_score')];

                            // Adaptive weighting
                            $totalWeight = array_sum(array_column($auditComponents, 'weight'));
                            $healthScore = 0;
                            if ($totalWeight > 0) {
                                foreach ($auditComponents as $comp) {
                                    $healthScore += $comp['value'] * ($comp['weight'] / $totalWeight);
                                }
                            }
                            $healthScore = round($healthScore);
                            $healthColor = $healthScore >= 80 ? '#16a34a' : ($healthScore >= 50 ? '#d97706' : '#dc2626');
                            $healthCircumference = 2 * 3.14159 * 42;
                            $healthOffset = $healthCircumference * (1 - $healthScore / 100);
                        @endphp

                        <div class="flex flex-col md:flex-row items-center gap-8">
                            {{-- Large gauge --}}
                            <div class="relative inline-block w-24 h-24 flex-shrink-0">
                                <svg width="96" height="96" viewBox="0 0 96 96" class="transform -rotate-90">
                                    <circle cx="48" cy="48" r="42" fill="none" stroke="rgba(212, 212, 210, 0.6)" stroke-width="6"/>
                                    <circle cx="48" cy="48" r="42" fill="none" stroke="{{ $healthColor }}" stroke-width="6"
                                            stroke-dasharray="{{ $healthCircumference }}" stroke-dashoffset="{{ $healthOffset }}" stroke-linecap="round"/>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-2xl font-semibold" style="color: {{ $healthColor }}">{{ $healthScore }}</span>
                            </div>

                            {{-- Breakdown badges --}}
                            <div class="flex flex-wrap gap-3">
                                @foreach($auditComponents as $compKey => $comp)
                                    @php
                                        $badgeColor = $comp['value'] >= 80 ? 'bg-green-500/15 text-green-400' : ($comp['value'] >= 50 ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400');
                                    @endphp
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $badgeColor }}">
                                        <span class="text-lg font-semibold">{{ $comp['value'] }}</span>
                                        <span class="text-xs font-medium">{{ $comp['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: PageSpeed Audit Summary --}}
                    @if(isset($seoReportData['pagespeed_mobile']['scores']) || isset($seoReportData['pagespeed_desktop']['scores']))
                        <div class="card p-6 mb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-bolt class="w-5 h-5 text-gray-400" />
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.pagespeed_title') }}</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach(['mobile' => $seoReportData['pagespeed_mobile'] ?? null, 'desktop' => $seoReportData['pagespeed_desktop'] ?? null] as $type => $ps)
                                    @if($ps && isset($ps['scores']))
                                        <div class="border border-white/10 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-gray-200 mb-3 capitalize">{{ $type }}</h4>
                                            <div class="flex items-center justify-around gap-3 mb-4">
                                                @foreach(['performance' => __('client_dashboard.seo.score_performance'), 'accessibility' => __('client_dashboard.seo.score_accessibility'), 'best_practices' => __('client_dashboard.seo.score_best_practices'), 'seo' => __('client_dashboard.seo.score_seo')] as $key => $label)
                                                    @php
                                                        $val = $ps['scores'][$key] ?? 0;
                                                        $circumference = 2 * 3.14159 * 28;
                                                        $offset = $circumference * (1 - $val / 100);
                                                        $ringColor = $val >= 90 ? '#16a34a' : ($val >= 50 ? '#d97706' : '#dc2626');
                                                    @endphp
                                                    <div class="text-center">
                                                        <div class="relative inline-block w-16 h-16">
                                                            <svg width="64" height="64" viewBox="0 0 64 64" class="transform -rotate-90">
                                                                <circle cx="32" cy="32" r="28" fill="none" stroke="rgba(212, 212, 210, 0.6)" stroke-width="5"/>
                                                                <circle cx="32" cy="32" r="28" fill="none" stroke="{{ $ringColor }}" stroke-width="5"
                                                                        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"/>
                                                            </svg>
                                                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold" style="color: {{ $ringColor }}">{{ $val }}</span>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                                <div class="mt-4 pt-4 border-t border-white/10">
                                                    <h5 class="text-xs font-medium text-gray-300 mb-2">{{ __('client_dashboard.seo.core_web_vitals') }}</h5>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                            @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                                @php $cwv = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                                <div class="text-center p-2 rounded {{ ($cwv['score'] ?? '') === 'good' ? 'bg-green-500/10' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'bg-amber-500/10' : 'bg-red-500/10') }}">
                                                                    <div class="text-sm font-semibold {{ ($cwv['score'] ?? '') === 'good' ? 'text-green-400' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'text-amber-400' : 'text-red-400') }}">
                                                                        {{ $cwv['display'] ?? $cwv['value'] ?? '-' }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">{{ $cwvLabel }}</div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Section 3: Search Console Coverage Summary --}}
                    @if(!empty($seoReportData['search_console']['coverage']) && !empty($seoReportData['search_console']['coverage']['sitemaps']))
                        @php $auditCovData = $seoReportData['search_console']['coverage']; @endphp
                        <div class="card p-6 mb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-globe-alt class="w-5 h-5 text-gray-400" />
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-white">{{ fmtNum($auditCovData['total_submitted']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-green-600">{{ fmtNum($auditCovData['total_indexed']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    @php $auditRateColor = $auditCovData['index_rate'] >= 90 ? 'text-green-600' : ($auditCovData['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp
                                    <p class="stat-num {{ $auditRateColor }}">{{ $auditCovData['index_rate'] }}%</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-red-600">{{ $auditCovData['total_errors'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p>
                                </div>
                                <div class="text-center border border-white/10 rounded-lg p-4">
                                    <p class="stat-num text-orange-600">{{ $auditCovData['total_warnings'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Section 4: Technical Issues Table --}}
                    <div class="card mb-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-6 gap-4">
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('client_dashboard.seo.technical_issues') }}</h3>
                            <div class="flex items-center gap-3">
                                <select wire:model.live="auditStatusFilter" class="input-field">
                                    <option value="all">{{ __('client_dashboard.seo.all_statuses') }}</option>
                                    <option value="open">{{ __('client_dashboard.seo.status_open') }}</option>
                                    <option value="in_progress">{{ __('client_dashboard.seo.status_in_progress') }}</option>
                                    <option value="resolved">{{ __('client_dashboard.seo.status_resolved') }}</option>
                                    <option value="wont_fix">{{ __('client_dashboard.seo.status_wont_fix') }}</option>
                                </select>
                                <select wire:model.live="auditSeverityFilter" class="input-field">
                                    <option value="all">{{ __('client_dashboard.seo.all_severities') }}</option>
                                    <option value="critical">{{ __('client_dashboard.seo.severity_critical') }}</option>
                                    <option value="high">{{ __('client_dashboard.seo.severity_high') }}</option>
                                    <option value="medium">{{ __('client_dashboard.seo.severity_medium') }}</option>
                                    <option value="low">{{ __('client_dashboard.seo.severity_low') }}</option>
                                </select>
                            </div>
                        </div>

                        @if(count($auditIssues) > 0)
                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('client_dashboard.seo.severity') }}</th>
                                            <th>{{ __('client_dashboard.seo.issue_type') }}</th>
                                            <th>{{ __('client_dashboard.seo.description') }}</th>
                                            <th>{{ __('client_dashboard.seo.page_url') }}</th>
                                            <th>{{ __('client_dashboard.seo.status_open') }}</th>
                                            <th>{{ __('client_dashboard.seo.date_found') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($auditIssues as $issue)
                                            <tr>
                                                <td>
                                                    @php
                                                        $sevColors = ['critical' => 'bg-red-500/15 text-red-400', 'high' => 'bg-amber-500/15 text-amber-400', 'medium' => 'bg-brand-500/15 text-brand-400', 'low' => 'bg-white/8 text-gray-400'];
                                                        $sevClass = $sevColors[$issue['severity']] ?? 'bg-white/5 text-gray-200';
                                                    @endphp
                                                    <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $sevClass }}">
                                                        {{ __('client_dashboard.seo.severity_' . $issue['severity']) }}
                                                    </span>
                                                </td>
                                                <td class="text-sm text-gray-200">{{ $issue['readable_type'] ?? $issue['issue_type'] ?? '-' }}</td>
                                                <td class="text-sm text-gray-300 max-w-[250px] truncate" title="{{ app()->getLocale() === 'ar' ? ($issue['description_ar'] ?? $issue['description_en'] ?? '') : ($issue['description_en'] ?? '') }}">
                                                    {{ app()->getLocale() === 'ar' ? ($issue['description_ar'] ?? $issue['description_en'] ?? '-') : ($issue['description_en'] ?? '-') }}
                                                </td>
                                                <td class="text-sm max-w-[200px] truncate">
                                                    @if($issue['page_url'])
                                                        <a href="{{ $issue['page_url'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 hover:underline" title="{{ $issue['page_url'] }}">{{ $issue['page_url'] }}</a>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($editingIssueId === $issue['id'])
                                                        <select wire:model="editingIssueStatus" class="input-field">
                                                            <option value="open">{{ __('client_dashboard.seo.status_open') }}</option>
                                                            <option value="in_progress">{{ __('client_dashboard.seo.status_in_progress') }}</option>
                                                            <option value="resolved">{{ __('client_dashboard.seo.status_resolved') }}</option>
                                                            <option value="wont_fix">{{ __('client_dashboard.seo.status_wont_fix') }}</option>
                                                        </select>
                                                    @else
                                                        @php
                                                            $statusColors = ['open' => 'bg-red-500/15 text-red-400', 'in_progress' => 'bg-amber-500/15 text-amber-400', 'resolved' => 'bg-green-500/15 text-green-400', 'wont_fix' => 'bg-white/8 text-gray-400'];
                                                            $statusClass = $statusColors[$issue['current_status']] ?? 'bg-white/5 text-gray-200';
                                                        @endphp
                                                        <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $statusClass }}">
                                                            {{ __('client_dashboard.seo.status_' . $issue['current_status']) }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-sm text-gray-500 whitespace-nowrap">{{ $issue['date_found'] ? \Carbon\Carbon::parse($issue['date_found'])->format('Y-m-d') : '-' }}</td>
                                                <td>
                                                    @if($editingIssueId === $issue['id'])
                                                        <div class="flex items-center gap-1">
                                                            <button wire:click="saveIssueUpdate" class="text-green-600 hover:text-green-800" title="{{ __('client_dashboard.seo.audit_save') }}">
                                                                <x-heroicon-o-check class="w-4 h-4" />
                                                            </button>
                                                            <button wire:click="cancelEditIssue" class="text-gray-400 hover:text-gray-300" title="{{ __('client_dashboard.seo.audit_cancel') }}">
                                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    @else
                                                        <button wire:click="startEditIssue({{ $issue['id'] }})" class="text-gray-400 hover:text-brand-400" title="{{ __('common.edit') }}">
                                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            {{-- Inline editing expanded row --}}
                                            @if($editingIssueId === $issue['id'])
                                                <tr class="bg-gray-950">
                                                    <td colspan="7" class="px-6 py-3">
                                                        <label class="block text-xs font-medium text-gray-300 mb-1">{{ __('client_dashboard.seo.notes') }}</label>
                                                        <textarea wire:model="editingIssueNotes" rows="2" class="input-field" placeholder="{{ __('client_dashboard.seo.notes') }}..."></textarea>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                <x-heroicon-o-check-badge class="mx-auto h-10 w-10 text-green-300" />
                                <p class="empty-message">{{ __('client_dashboard.seo.no_issues') }}</p>
                            </div>
                        @endif
                    </div>

                    </div>{{-- end audit sub-tab --}}

                    </div>{{-- end sub-tab x-data wrapper --}}
                @endif
            @else
                <div class="empty-state">
                    <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="empty-title">{{ __('No SEO data available') }}</h3>
                    <p class="empty-message">{{ __('Configure SEO properties to view reports') }}</p>
                </div>
            @endif

    @else
        {{-- Error State --}}
        <div class="text-center py-12">
            <p class="text-gray-500">{{ $error ?: __('Failed to load client dashboard') }}</p>
        </div>
    @endif
</div>
