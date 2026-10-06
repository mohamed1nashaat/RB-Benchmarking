        @if($activeTabIndex === 0)
            @php
                $sm = $dashboardData['summary_metrics'] ?? [];
                $pb = $dashboardData['platform_breakdown'] ?? [];
                $topAds = $dashboardData['top_ads'] ?? [];
                $teads = $dashboardData['teads_daily'] ?? [];
                $exec = $dashboardData['executive_summary'] ?? [];
                $healthLabels = ['healthy' => __('pages.client_dashboard.health_healthy'), 'needs_attention' => __('pages.client_dashboard.health_needs_attention'), 'critical' => __('pages.client_dashboard.health_critical')];
                $healthClasses = ['healthy' => 'bg-[#EDF3EC] text-[#346538]', 'needs_attention' => 'bg-[#FBF3DB] text-[#956400]', 'critical' => 'bg-[#FDEBEC] text-[#9F2F2D]'];
            @endphp

            {{-- Filters Panel --}}
            <div class="filter-bar mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="micro-label">{{ __('Period') }}:</span>
                        <div class="inline-flex rounded-lg bg-gray-100 p-1">
                            @foreach([7, 30, 90, 365, 0] as $period)
                                <button wire:click="$set('selectedPeriod', {{ $period }})" class="px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200 {{ $selectedPeriod === $period ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                    {{ $period === 0 ? __('filters.all') : ($period === 365 ? __('filters.1y') : $period . __('filters.days_suffix')) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="hidden md:block h-8 w-px bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="micro-label">{{ __('Custom') }}:</span>
                        <input wire:model.live="dateFrom" type="date" class="input-field" />
                        <span class="text-gray-400">-</span>
                        <input wire:model.live="dateTo" type="date" class="input-field" />
                    </div>
                    <div class="hidden md:block h-8 w-px bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="micro-label">{{ __('Platform') }}:</span>
                        <select wire:model.live="selectedPlatform" class="input-field">
                            <option value="">{{ __('All Platforms') }}</option>
                            <option value="facebook">{{ __('platforms.facebook') }}</option>
                            <option value="google">{{ __('platforms.google') }}</option>
                            <option value="tiktok">{{ __('platforms.tiktok') }}</option>
                            <option value="linkedin">{{ __('platforms.linkedin') }}</option>
                            <option value="snapchat">{{ __('platforms.snapchat') }}</option>
                        </select>
                    </div>
                    @if($selectedPeriod !== 30 || $selectedPlatform || $dateFrom || $dateTo)
                        <button wire:click="resetFilters" class="btn-secondary ms-auto">
                            <x-heroicon-o-x-mark class="w-4 h-4 me-1" />
                            {{ __('Reset') }}
                        </button>
                    @endif
                    <div wire:loading class="ms-auto flex items-center text-sm text-gray-500">
                        <svg class="animate-spin h-4 w-4 me-2 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ __('Loading...') }}
                    </div>
                </div>
            </div>

            {{-- Performance Summary Title --}}
            <div class="mb-6">
                <h2 class="section-title">{{ __('Performance Summary') }}</h2>
            </div>

            {{-- Top KPI Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="kpi-card">
                    <div class="kpi-label">{{ __('Total Blended Spent') }} {{ $dashboardData['client']['name'] }}</div>
                    <div class="kpi-value mt-1">{{ number_format($sm['total_blended_spent'] ?? 0, 0) }}</div>
                </div>
                @if($dashboardData['is_ecommerce'])
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Conversions') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_conversions'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_campaigns'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Revenue') }}</div>
                        <div class="kpi-value mt-1">{{ fmtCurrency($sm['total_revenue'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Imp') }}</div>
                        <div class="kpi-value mt-1">{{ fmtNum($sm['total_impressions'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('ROAS') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['roas'] ?? 0, 1) }}x</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('AOV') }}</div>
                        <div class="kpi-value mt-1">{{ fmtCurrency($sm['aov'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('CPA SAR') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['cpa'] ?? 0, 0) }}</div>
                    </div>
                @else
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total blended leads') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_blended_leads'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_campaigns'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Instant leads') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_instant_leads'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Imp') }}</div>
                        <div class="kpi-value mt-1">{{ fmtNum($sm['total_impressions'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('CPL SAR') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['cpl'] ?? 0, 1) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('CPM SAR') }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['cpm'] ?? 0, 2) }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">{{ __('Total Blended Link Clicks') }} {{ $dashboardData['client']['name'] }}</div>
                        <div class="kpi-value mt-1">{{ number_format($sm['total_link_clicks'] ?? 0) }}</div>
                    </div>
                @endif
            </div>

            {{-- Funnel + Charts Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                @if($dashboardData['is_ecommerce'])
                    {{-- E-Commerce Funnel --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('E-Commerce Funnel') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.funnelCanvas, @js($ecommerceFunnelChartConfig))" wire:ignore>
                            <canvas x-ref="funnelCanvas"></canvas>
                        </div>
                        @if(!empty($dashboardData['funnel_data']['stages']))
                            <div class="mt-4 space-y-2 text-xs">
                                @foreach($dashboardData['funnel_data']['stages'] as $stage)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $stage['name'] }}</span>
                                        <span class="font-semibold text-gray-900">{{ number_format($stage['value']) }} ({{ $stage['pct'] }}%)</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Revenue VS ROAS --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Revenue VS ROAS') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.revenueRoasCanvas, @js($revenueVsRoasChartConfig))" wire:ignore>
                            <canvas x-ref="revenueRoasCanvas"></canvas>
                        </div>
                    </div>

                    {{-- Conversions VS CPA --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Conversions VS CPA') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.conversionsCpaCanvas, @js($conversionsVsCpaChartConfig))" wire:ignore>
                            <canvas x-ref="conversionsCpaCanvas"></canvas>
                        </div>
                    </div>
                @else
                    {{-- Conversion Funnel --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Conversion funnel') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.funnelCanvas, @js($funnelChartConfig))" wire:ignore>
                            <canvas x-ref="funnelCanvas"></canvas>
                        </div>
                        @if(!empty($dashboardData['funnel_data']['stages']))
                            <div class="mt-4 space-y-2 text-xs">
                                @foreach($dashboardData['funnel_data']['stages'] as $stage)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $stage['name'] }}</span>
                                        <span class="font-semibold text-gray-900">{{ number_format($stage['value']) }} ({{ $stage['pct'] }}%)</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Lead VS CPL --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Lead VS CPL') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.leadCplCanvas, @js($leadVsCplChartConfig))" wire:ignore>
                            <canvas x-ref="leadCplCanvas"></canvas>
                        </div>
                    </div>

                    {{-- Lead VS Spent --}}
                    <div class="card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Lead VS Spent') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.leadSpentCanvas, @js($leadVsSpentChartConfig))" wire:ignore>
                            <canvas x-ref="leadSpentCanvas"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Platforms Overview --}}
            @php $accounts = $dashboardData['ad_accounts'] ?? []; @endphp
            @if(!empty($accounts))
                <div class="mb-6">
                    <h2 class="section-title mb-4">{{ __('Platforms') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                        @php
                            $platformGroups = collect($accounts)->groupBy('platform')->sortKeys();
                        @endphp
                        @foreach($platformGroups as $platform => $platformAccounts)
                            @php
                                $platformSpend = $platformAccounts->sum('total_spend');
                                $platformCampaigns = $platformAccounts->sum(fn($a) => count($a['campaigns'] ?? []));
                                $platformColor = match(strtolower($platform)) {
                                    'facebook' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                    'google' => 'bg-[#F1F1EF] text-[#6B6B66]',
                                    'tiktok' => 'bg-zinc-900 text-white',
                                    'snapchat' => 'bg-[#FBF3DB] text-[#956400]',
                                    'linkedin' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                    default => 'bg-[#F1F1EF] text-[#6B6B66]',
                                };
                            @endphp
                            <div class="card p-3 {{ $platformColor }}">
                                <div class="micro-label">{{ ucfirst($platform) }}</div>
                                <div class="text-lg font-semibold tracking-tight mt-1">{{ fmtCurrency($platformSpend) }}</div>
                                <div class="text-xs mt-1 opacity-75">
                                    {{ $platformAccounts->count() }} {{ __('accounts') }} · {{ $platformCampaigns }} {{ __('campaigns') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Ad Accounts & Campaigns --}}
            @if(!empty($accounts))
                <div class="mb-6">
                    <h2 class="section-title mb-4">{{ __('Ad Accounts & Campaigns') }}</h2>
                    <div class="space-y-3">
                        @foreach($accounts as $account)
                            <div class="card overflow-hidden" x-data="{ open: false }">
                                {{-- Account Header --}}
                                <div class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                                     @click="open = !open">
                                    <div class="flex items-center justify-center w-8 h-8 rounded bg-gray-50 border border-gray-100 me-3">
                                        @php
                                            /** @var array<string,string> Hardcoded SVG map — safe for {!! !!} output */
                                            $platformSvgs = [
                                                'facebook' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                                                'google' => '<svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>',
                                                'tiktok' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93v6.16c0 2.52-1.18 4.93-3.22 6.48-2.96 2.2-7.21 1.99-9.92-.47-2.48-2.25-2.8-6.17-.7-8.78 1.62-2.01 4.52-2.78 7.04-2.04v4.17c-1.05-.32-2.32-.18-3.16.56-.87.76-1.12 2.02-.62 3.04.58 1.21 2.1 1.77 3.35 1.28 1.23-.47 1.95-1.83 1.77-3.13V.02h.03z"/></svg>',
                                                'snapchat' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="#FFFC00"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301a.42.42 0 01.17-.032c.127 0 .24.065.305.17.147.255.39.753.39 1.23 0 .75-.36 1.22-.72 1.42a.508.508 0 01-.17.06c.016.147.082.374.27.544.48.45 1.434.795 2.274 1.1.48.165.93.33 1.23.54.39.285.63.735.63 1.23 0 .39-.18.78-.54 1.02-.57.39-1.53.57-2.52.57-.39 0-.78-.03-1.17-.09-.33.18-.81.42-1.35.69-.84.42-1.86.93-3.09 1.26-.57.15-1.17.24-1.8.24-.63 0-1.23-.09-1.8-.24-1.23-.33-2.25-.84-3.09-1.26-.54-.27-1.02-.51-1.35-.69-.39.06-.78.09-1.17.09-.99 0-1.95-.18-2.52-.57-.36-.24-.54-.63-.54-1.02 0-.495.24-.945.63-1.23.3-.21.75-.375 1.23-.54.84-.305 1.794-.65 2.274-1.1.188-.17.254-.397.27-.544a.508.508 0 01-.17-.06c-.36-.2-.72-.67-.72-1.42 0-.477.243-.975.39-1.23a.372.372 0 01.305-.17c.06 0 .12.012.17.032.374.181.733.285 1.033.301.198 0 .326-.045.401-.09-.008-.165-.018-.33-.03-.51l-.003-.06c-.104-1.628-.23-3.654.299-4.847C7.86 1.07 10.215.793 11.205.793h.001z"/></svg>',
                                                'linkedin' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                                            ];
                                            $platformColors = [
                                                'facebook' => 'text-blue-600',
                                                'google' => 'text-gray-700',
                                                'tiktok' => 'text-black',
                                                'snapchat' => 'text-yellow-500',
                                                'linkedin' => 'text-blue-700',
                                            ];
                                        @endphp
                                        @if(isset($platformSvgs[$account['platform']]))
                                            <span class="{{ $platformColors[$account['platform']] ?? 'text-gray-400' }}">
                                                {!! $platformSvgs[$account['platform']] !!}
                                            </span>
                                        @else
                                            <x-heroicon-o-server class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $account['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ ucfirst($account['platform']) }} · {{ count($account['campaigns'] ?? []) }} {{ __('campaigns') }}</p>
                                        @if($editingAccountId !== $account['id'])
                                            <p class="text-[10px] text-gray-400 mt-0.5">
                                                {{ $account['industry'] ? ucwords(str_replace(['_', '-'], ' ', $account['industry'])) : '-' }}
                                                @if($account['category']) › {{ $account['category'] }} @endif
                                                @if($account['sub_industry']) › {{ $account['sub_industry'] }} @endif
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-x-3">
                                        @if($editingAccountId === $account['id'])
                                            <div class="flex items-center gap-x-2" @click.stop>
                                                <select wire:model="editAccountIndustry" class="input-field">
                                                    <option value="">{{ __('labels.industry') }}</option>
                                                    @foreach($industries as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <select wire:model="editAccountCategory" class="input-field">
                                                    <option value="">{{ __('labels.category') }}</option>
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                                    @endforeach
                                                </select>
                                                <select wire:model="editAccountSubCategory" class="input-field">
                                                    <option value="">{{ __('labels.sub_category') }}</option>
                                                    @foreach($subCategories as $slug => $display)
                                                        <option value="{{ $slug }}">{{ $display }}</option>
                                                    @endforeach
                                                </select>
                                                <button wire:click="saveAccountEdit" class="text-[#346538] hover:text-[#2C5330] p-1" title="{{ __('common.save') }}">
                                                    <x-heroicon-o-check class="h-4 w-4" />
                                                </button>
                                                <button wire:click="cancelAccountEdit" class="text-gray-400 hover:text-gray-600 p-1" title="{{ __('common.cancel') }}">
                                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        @else
                                            <button wire:click="startEditAccount({{ $account['id'] }})" class="text-gray-400 hover:text-gray-700 p-1" title="{{ __('common.edit_account') }}">
                                                <x-heroicon-o-pencil class="h-4 w-4" />
                                            </button>
                                        @endif
                                        <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $account['status'] === 'active' ? 'bg-[#EDF3EC] text-[#346538]' : 'bg-[#F1F1EF] text-[#6B6B66]' }}">
                                            {{ __('status.' . $account['status']) }}
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900">{{ fmtCurrency($account['total_spend'] ?? 0) }}</span>
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
                                    </div>
                                </div>
                                {{-- Campaigns List --}}
                                <div x-show="open" x-cloak class="border-t border-gray-100">
                                    @if(empty($account['campaigns']))
                                        <div class="empty-state">
                                            <div class="empty-message">{{ __('No campaigns') }}</div>
                                        </div>
                                    @else
                                        <div class="px-4 py-2">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-start">{{ __('Campaign') }}</th>
                                                        <th class="text-start w-28">{{ __('Objective') }}</th>
                                                        <th class="text-start w-20">{{ __('Stage') }}</th>
                                                        <th class="text-start w-28">{{ __('Sub-Category') }}</th>
                                                        <th class="text-end w-24">{{ __('Spend') }}</th>
                                                        <th class="text-end w-20">{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($account['campaigns'] as $campaign)
                                                        <tr>
                                                            <td class="text-gray-700 truncate max-w-[200px]">{{ $campaign['name'] }}</td>
                                                            <td>
                                                                <select wire:change="updateCampaign({{ $campaign['id'] }}, 'objective', $event.target.value)"
                                                                    class="input-field">
                                                                    <option value="" @selected(empty($campaign['objective']))>-</option>
                                                                    <option value="awareness" @selected(($campaign['objective'] ?? '') === 'awareness')>{{ __('objectives.awareness') }}</option>
                                                                    <option value="engagement" @selected(($campaign['objective'] ?? '') === 'engagement')>{{ __('objectives.engagement') }}</option>
                                                                    <option value="leads" @selected(($campaign['objective'] ?? '') === 'leads')>{{ __('objectives.leads') }}</option>
                                                                    <option value="sales" @selected(($campaign['objective'] ?? '') === 'sales')>{{ __('objectives.sales') }}</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select wire:change="updateCampaign({{ $campaign['id'] }}, 'funnel_stage', $event.target.value)"
                                                                    class="input-field">
                                                                    <option value="" @selected(empty($campaign['funnel_stage']))>-</option>
                                                                    <option value="TOF" @selected(($campaign['funnel_stage'] ?? '') === 'TOF')>TOF</option>
                                                                    <option value="MOF" @selected(($campaign['funnel_stage'] ?? '') === 'MOF')>MOF</option>
                                                                    <option value="BOF" @selected(($campaign['funnel_stage'] ?? '') === 'BOF')>BOF</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select wire:change="updateCampaign({{ $campaign['id'] }}, 'sub_industry', $event.target.value)"
                                                                    class="input-field"
                                                                    title="{{ __('tooltips.sub_categories_source') }}">
                                                                    <option value="" @selected(empty($campaign['sub_industry']))>-</option>
                                                                    @if(!empty($campaign['sub_industry']) && !in_array($campaign['sub_industry'], $campaignCategories))
                                                                        <option value="{{ $campaign['sub_industry'] }}" selected>{{ $campaign['sub_industry'] }} (legacy)</option>
                                                                    @endif
                                                                    @foreach($campaignCategories as $cat)
                                                                        <option value="{{ $cat }}" @selected(($campaign['sub_industry'] ?? '') === $cat)>{{ $cat }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td class="text-end text-gray-700 font-medium">{{ fmtCurrency($campaign['spend'] ?? 0) }}</td>
                                                            <td class="text-end">
                                                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ ($campaign['status'] ?? '') === 'active' ? 'bg-[#EDF3EC] text-[#346538]' : 'bg-[#F1F1EF] text-[#6B6B66]' }}">
                                                                    {{ $campaign['status'] ? __('status.' . $campaign['status']) : __('common.unknown') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Platform Deep Dives --}}
            @foreach($pb as $platform)
                @php
                    $platformKey = strtolower($platform['platform']);
                    $platformAds = $topAds[$platformKey] ?? [];
                    $platformLogo = strtoupper(substr($platformKey, 0, 1));
                    $platformColor = 'bg-zinc-900 text-white';
                @endphp
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-lg {{ $platformColor }}">
                            {{ $platformLogo }}
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-gray-900 capitalize">{{ $platform['platform'] }}</h3>
                            <p class="text-xs text-gray-500">{{ $periodLabel }}</p>
                        </div>
                    </div>

                    {{-- Platform KPIs --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @if($platformKey === 'snapchat')
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Spend') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Impressions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Swipes') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['swipes']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Conversions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Revenue') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('ROAS') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('CPA') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpa'], 0) }}</div></div>
                            @else
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Native Leads') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['native_leads']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Conversion Sign Ups') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['sign_ups']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('CPL Snap') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpl'], 1) }}</div></div>
                            @endif
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('CPM Snap') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpm'], 2) }}</div></div>
                        @elseif($platformKey === 'facebook')
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Total spent Meta') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Impressions Meta') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Link clicks Meta') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['link_clicks']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Conversions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Revenue') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('ROAS') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('CPA') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpa'], 0) }}</div></div>
                            @else
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Website Leads Meta') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['website_leads']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('CPM Meta') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpm'], 1) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Cost per On-Facebook leads') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ $platform['on_facebook_leads'] > 0 ? number_format($platform['spend'] / $platform['on_facebook_leads'], 1) : '-' }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('On-Facebook leads') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['on_facebook_leads']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('sign_up') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['sign_ups']) }}</div></div>
                            @endif
                        @elseif($platformKey === 'google')
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Cost') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Impressions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Average cpm') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['cpm'], 0) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Clicks') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['clicks']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Conversions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Revenue') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('ROAS') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                            @endif
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Ctr') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['ctr'], 0) }}%</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Average cpc') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ $platform['clicks'] > 0 ? number_format($platform['spend'] / $platform['clicks'], 0) : '-' }}</div></div>
                        @else
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Spend') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Impressions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Clicks') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['clicks']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Conversions') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Revenue') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('ROAS') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                            @else
                                <div class="bg-[#F7F6F3] rounded-lg p-3"><div class="micro-label">{{ __('Leads') }}</div><div class="text-xl font-semibold tracking-tight text-gray-900">{{ fmtNum($platform['blended_leads']) }}</div></div>
                            @endif
                        @endif
                    </div>

                    {{-- Best Performing Ads --}}
                    @if(!empty($platformAds))
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">{{ ucfirst($platform['platform']) }} {{ __('best performing Ads') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($platformAds as $ad)
                                <div class="card overflow-hidden">
                                    @php
                                        $hasImage = !empty($ad['image_url']) || !empty($ad['thumbnail_url']) || !empty($ad['video_url']);
                                    @endphp
                                    <div class="aspect-video bg-gray-200 flex items-center justify-center relative"
                                         x-data="{ src: '{{ $ad['image_url'] ?? $ad['thumbnail_url'] ?? $ad['video_url'] ?? '' }}', failed: false }">
                                        <template x-if="src && !failed">
                                            <img :src="src" x-on:error="failed = true" alt="" class="w-full h-full object-contain" loading="lazy">
                                        </template>
                                        <template x-if="!src || failed">
                                            @php
                                                $fallbackText = null;
                                                if (!empty($ad['preview_text'])) {
                                                    $fallbackText = $ad['preview_text'];
                                                } elseif (!empty($ad['name'])) {
                                                    $fallbackText = $ad['name'];
                                                } elseif (!empty($ad['campaign_name'])) {
                                                    $fallbackText = $ad['campaign_name'];
                                                }
                                            @endphp
                                            @if($fallbackText)
                                                <div class="px-4 py-3 text-center w-full h-full flex flex-col items-center justify-center">
                                                    <div class="text-gray-800 text-sm font-semibold line-clamp-2 leading-snug">{{ $fallbackText }}</div>
                                                    @if(!empty($ad['campaign_name']) && $ad['campaign_name'] !== $fallbackText)
                                                        <div class="text-gray-500 text-[10px] mt-1 line-clamp-1">{{ $ad['campaign_name'] }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-gray-400 text-xs flex flex-col items-center justify-center h-full">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-1 opacity-50">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                    </svg>
                                                    {{ __('No preview') }}
                                                </div>
                                            @endif
                                        </template>
                                    </div>
                                    <div class="p-3">
                                        @php
                                            $showNameBelow = true;
                                            if (!$hasImage && !empty($ad['preview_text']) && $ad['preview_text'] === $ad['name']) {
                                                $showNameBelow = false;
                                            }
                                        @endphp
                                        @if($showNameBelow)
                                            <div class="text-xs font-semibold text-gray-900 truncate" title="{{ $ad['name'] }}">{{ $ad['name'] }}</div>
                                        @endif
                                        @if(!empty($ad['campaign_name']) && $ad['campaign_name'] !== $ad['name'])
                                            <div class="text-[10px] text-gray-500 truncate {{ $showNameBelow ? '' : 'font-semibold text-gray-900' }}" title="{{ $ad['campaign_name'] }}">{{ $ad['campaign_name'] }}</div>
                                        @endif
                                        <div class="mt-2 grid grid-cols-3 gap-2 text-[10px] text-gray-600">
                                            <div><span class="block text-gray-400">{{ __('common.impr') }}</span>{{ fmtNum($ad['impressions']) }}</div>
                                            <div><span class="block text-gray-400">{{ __('alerts.metrics.clicks') }}</span>{{ fmtNum($ad['clicks']) }}</div>
                                            @if($dashboardData['is_ecommerce'])
                                                <div><span class="block text-gray-400">{{ __('kpis.roas') }}</span>{{ number_format($ad['roas'], 1) }}x</div>
                                            @else
                                                <div><span class="block text-gray-400">{{ __('kpis.cpl') }}</span>{{ number_format($ad['cpl'], 0) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Teads Section --}}
            @if(!empty($teads['rows']))
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-lg bg-zinc-900 text-white">T</div>
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-gray-900">Teads</h3>
                            <p class="text-xs text-gray-500">{{ $periodLabel }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="text-start">{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Impressions') }}</th>
                                    <th class="text-end">{{ __('Clicks') }}</th>
                                    <th class="text-end">{{ __('Amount spent') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teads['rows'] as $row)
                                    <tr>
                                        <td class="text-gray-900">{{ $row->day }}</td>
                                        <td class="text-end text-gray-700">{{ number_format($row->impressions) }}</td>
                                        <td class="text-end text-gray-700">{{ number_format($row->clicks) }}</td>
                                        <td class="text-end text-gray-700">{{ fmtCurrency($row->spend) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-[#F1F1EF] text-gray-900">
                                    <td class="font-semibold">{{ __('Summary') }}</td>
                                    <td class="text-end font-semibold">{{ number_format($teads['summary']['impressions'] ?? 0) }}</td>
                                    <td class="text-end font-semibold">{{ number_format($teads['summary']['clicks'] ?? 0) }}</td>
                                    <td class="text-end font-semibold">{{ fmtCurrency($teads['summary']['spend'] ?? 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Legacy Executive Summary (kept for context) --}}
            <div class="mb-6">
                <h2 class="section-title mb-4">{{ __('Executive Summary') }}</h2>
                @if(!empty($exec['health']))
                    <div class="text-center mb-4">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide {{ $healthClasses[$exec['health']] }}">
                            {{ $healthLabels[$exec['health']] }}
                        </span>
                    </div>
                @endif
                @if(!empty($exec['insights']))
                    <div class="space-y-3">
                        @foreach($exec['insights'] as $insight)
                            @php
                                $insightBorder = match($insight['type']) {
                                    'success' => 'border-s-4 border-[#346538] bg-[#EDF3EC]',
                                    'warning' => 'border-s-4 border-[#956400] bg-[#FBF3DB]',
                                    'danger' => 'border-s-4 border-[#9F2F2D] bg-[#FDEBEC]',
                                    default => 'border-s-4 border-[#1F6C9F] bg-[#E1F3FE]',
                                };
                            @endphp
                            <div class="flex items-center gap-3 p-3 rounded-md {{ $insightBorder }}">
                                <span class="text-lg leading-none">{{ $insight['icon'] }}</span>
                                <span class="text-sm text-gray-700">{{ $insight['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
