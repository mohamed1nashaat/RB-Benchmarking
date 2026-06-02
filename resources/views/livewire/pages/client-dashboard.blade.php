@php
    function fmtNum($num) {
        if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }
    function fmtCurrency($amount) {
        return 'SAR ' . number_format($amount, 2);
    }
    function countryFlag($name) {
        $map = ['Saudi Arabia'=>'SA','United States'=>'US','United Kingdom'=>'GB','United Arab Emirates'=>'AE','Egypt'=>'EG','Kuwait'=>'KW','Qatar'=>'QA','Bahrain'=>'BH','Oman'=>'OM','Jordan'=>'JO','Lebanon'=>'LB','Iraq'=>'IQ','Morocco'=>'MA','Tunisia'=>'TN','Algeria'=>'DZ','Libya'=>'LY','Sudan'=>'SD','Yemen'=>'YE','Syria'=>'SY','Palestine'=>'PS','India'=>'IN','Pakistan'=>'PK','Bangladesh'=>'BD','Turkey'=>'TR','Iran'=>'IR','Germany'=>'DE','France'=>'FR','Spain'=>'ES','Italy'=>'IT','Netherlands'=>'NL','Belgium'=>'BE','Switzerland'=>'CH','Austria'=>'AT','Sweden'=>'SE','Norway'=>'NO','Denmark'=>'DK','Finland'=>'FI','Poland'=>'PL','Portugal'=>'PT','Greece'=>'GR','Canada'=>'CA','Mexico'=>'MX','Brazil'=>'BR','Argentina'=>'AR','Colombia'=>'CO','Chile'=>'CL','Australia'=>'AU','New Zealand'=>'NZ','Japan'=>'JP','China'=>'CN','South Korea'=>'KR','Indonesia'=>'ID','Malaysia'=>'MY','Philippines'=>'PH','Thailand'=>'TH','Vietnam'=>'VN','Singapore'=>'SG','Russia'=>'RU','Ukraine'=>'UA','South Africa'=>'ZA','Nigeria'=>'NG','Kenya'=>'KE','Israel'=>'IL','Ireland'=>'IE','Romania'=>'RO','Czech Republic'=>'CZ','Hungary'=>'HU'];
        $iso = $map[$name] ?? null;
        if (!$iso) return '';
        return implode('', array_map(fn($c) => mb_chr(0x1F1E6 + ord($c) - ord('A')), str_split($iso)));
    }
    function sparklinePath($data, $w = 80, $h = 24) {
        if (empty($data) || count($data) < 2) return '';
        $min = min($data); $max = max($data); $range = $max - $min ?: 1;
        $step = $w / (count($data) - 1);
        return implode(' ', array_map(fn($i, $v) => round($i * $step, 1) . ',' . round($h - (($v - $min) / $range) * ($h - 4) - 2, 1), array_keys($data), $data));
    }
@endphp

<div class="py-6">
    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('clients') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700" wire:navigate>
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-1" />
            {{ __('common.back_to_clients') }}
        </a>
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
    @elseif($dashboardData)
        {{-- Client Header --}}
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white rounded-lg p-6 mb-6 shadow-lg">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="text-[#F93549] text-xs font-semibold uppercase tracking-widest mb-1">RB Benchmarks</div>
                    <h1 class="text-2xl font-bold">{{ $dashboardData['client']['name'] }}</h1>
                    <p class="text-sm text-gray-400 mt-1">{{ $periodLabel }}</p>
                </div>
                <div class="flex items-center space-x-3" x-data="{ open: false }">
                    <div class="relative">
                        <button @click="open = !open" :disabled="$wire.exporting" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#F93549] hover:bg-red-600 disabled:opacity-50">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-2" />
                            {{ $exporting ? __('common.exporting') : __('common.export') }}
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <button wire:click="exportDashboard('pdf')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ __('common.export_as_pdf') }}
                                </button>
                                <button wire:click="exportDashboard('csv')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ __('common.export_as_csv') }}
                                </button>
                                <button wire:click="exportDashboard('excel')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ __('common.export_as_excel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="setActiveTab(0)" @click="history.replaceState(null, '', window.location.pathname)" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 0 ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5 inline-block mr-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.performance') }}
                </button>
                <button wire:click="setActiveTab(1)" @click="history.replaceState(null, '', window.location.pathname + '?seo')" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 1 ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 inline-block mr-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.seo_report') }}
                </button>
            </nav>
        </div>

        {{-- Ad Performance Tab --}}
        @if($activeTabIndex === 0)
            @php
                $sm = $dashboardData['summary_metrics'] ?? [];
                $pb = $dashboardData['platform_breakdown'] ?? [];
                $topAds = $dashboardData['top_ads'] ?? [];
                $teads = $dashboardData['teads_daily'] ?? [];
                $exec = $dashboardData['executive_summary'] ?? [];
                $healthLabels = ['healthy' => 'Healthy Performance', 'needs_attention' => 'Needs Attention', 'critical' => 'Critical - Action Required'];
                $healthClasses = ['healthy' => 'bg-green-100 text-green-800', 'needs_attention' => 'bg-yellow-100 text-yellow-800', 'critical' => 'bg-red-100 text-red-800'];
            @endphp

            {{-- Filters Panel --}}
            <div class="bg-white shadow rounded-lg p-4 mb-6 border border-gray-200">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('Period') }}:</span>
                        <div class="inline-flex rounded-lg bg-gray-100 p-1">
                            @foreach([7, 30, 90, 365, 0] as $period)
                                <button wire:click="$set('selectedPeriod', {{ $period }})" class="px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200 {{ $selectedPeriod === $period ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                    {{ $period === 0 ? 'All' : ($period === 365 ? '1Y' : $period . 'D') }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="hidden md:block h-8 w-px bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('Custom') }}:</span>
                        <input wire:model.live="dateFrom" type="date" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                        <span class="text-gray-400">-</span>
                        <input wire:model.live="dateTo" type="date" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                    </div>
                    <div class="hidden md:block h-8 w-px bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('Platform') }}:</span>
                        <select wire:model.live="selectedPlatform" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8">
                            <option value="">{{ __('All Platforms') }}</option>
                            <option value="facebook">Facebook</option>
                            <option value="google">Google</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="snapchat">Snapchat</option>
                        </select>
                    </div>
                    @if($selectedPeriod !== 30 || $selectedPlatform || $dateFrom || $dateTo)
                        <button wire:click="resetFilters" class="ml-auto inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                            <x-heroicon-o-x-mark class="w-4 h-4 mr-1" />
                            {{ __('Reset') }}
                        </button>
                    @endif
                    <div wire:loading class="ml-auto flex items-center text-sm text-gray-500">
                        <svg class="animate-spin h-4 w-4 mr-2 text-[#F93549]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ __('Loading...') }}
                    </div>
                </div>
            </div>

            {{-- Performance Summary Title --}}
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Performance Summary') }}</h2>
            </div>

            {{-- Top KPI Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Blended Spent') }} {{ $dashboardData['client']['name'] }}</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_blended_spent'] ?? 0, 0) }}</div>
                </div>
                @if($dashboardData['is_ecommerce'])
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Conversions') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_conversions'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_campaigns'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Revenue') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmtCurrency($sm['total_revenue'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Imp') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($sm['total_impressions'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('ROAS') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['roas'] ?? 0, 1) }}x</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('AOV') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmtCurrency($sm['aov'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('CPA SAR') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['cpa'] ?? 0, 0) }}</div>
                    </div>
                @else
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total blended leads') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_blended_leads'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_campaigns'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Instant leads') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_instant_leads'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Imp') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($sm['total_impressions'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('CPL SAR') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['cpl'] ?? 0, 1) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('CPM SAR') }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['cpm'] ?? 0, 2) }}</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Total Blended Link Clicks') }} {{ $dashboardData['client']['name'] }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($sm['total_link_clicks'] ?? 0) }}</div>
                    </div>
                @endif
            </div>

            {{-- Funnel + Charts Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                @if($dashboardData['is_ecommerce'])
                    {{-- E-Commerce Funnel --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
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
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Revenue VS ROAS') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.revenueRoasCanvas, @js($revenueVsRoasChartConfig))" wire:ignore>
                            <canvas x-ref="revenueRoasCanvas"></canvas>
                        </div>
                    </div>

                    {{-- Conversions VS CPA --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Conversions VS CPA') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.conversionsCpaCanvas, @js($conversionsVsCpaChartConfig))" wire:ignore>
                            <canvas x-ref="conversionsCpaCanvas"></canvas>
                        </div>
                    </div>
                @else
                    {{-- Conversion Funnel --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
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
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ __('Lead VS CPL') }}</h3>
                        <div class="h-64" x-data="{ chart: null }" x-init="chart = new Chart($refs.leadCplCanvas, @js($leadVsCplChartConfig))" wire:ignore>
                            <canvas x-ref="leadCplCanvas"></canvas>
                        </div>
                    </div>

                    {{-- Lead VS Spent --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
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
                    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('Platforms') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                        @php
                            $platformGroups = collect($accounts)->groupBy('platform')->sortKeys();
                        @endphp
                        @foreach($platformGroups as $platform => $platformAccounts)
                            @php
                                $platformSpend = $platformAccounts->sum('total_spend');
                                $platformCampaigns = $platformAccounts->sum(fn($a) => count($a['campaigns'] ?? []));
                                $platformColor = match(strtolower($platform)) {
                                    'facebook' => 'bg-blue-50 border-blue-200 text-blue-800',
                                    'google' => 'bg-red-50 border-red-200 text-red-800',
                                    'tiktok' => 'bg-gray-900 border-gray-800 text-white',
                                    'snapchat' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                                    'linkedin' => 'bg-sky-50 border-sky-200 text-sky-800',
                                    default => 'bg-gray-50 border-gray-200 text-gray-800',
                                };
                            @endphp
                            <div class="border rounded-lg p-3 {{ $platformColor }}">
                                <div class="text-xs font-medium uppercase tracking-wide opacity-75">{{ ucfirst($platform) }}</div>
                                <div class="text-lg font-bold mt-1">{{ fmtCurrency($platformSpend) }}</div>
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
                    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('Ad Accounts & Campaigns') }}</h2>
                    <div class="space-y-3">
                        @foreach($accounts as $account)
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden" x-data="{ open: false }">
                                {{-- Account Header --}}
                                <div class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                                     @click="open = !open">
                                    <div class="flex items-center justify-center w-8 h-8 rounded bg-gray-50 border border-gray-100 mr-3">
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
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $account['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ ucfirst($account['status']) }}
                                        </span>
                                        <span class="text-sm font-bold text-gray-900">{{ fmtCurrency($account['total_spend'] ?? 0) }}</span>
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
                                    </div>
                                </div>
                                {{-- Campaigns List --}}
                                <div x-show="open" x-cloak class="border-t border-gray-100">
                                    @if(empty($account['campaigns']))
                                        <div class="px-4 py-3 text-sm text-gray-400 italic">{{ __('No campaigns') }}</div>
                                    @else
                                        <div class="px-4 py-2">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="text-xs text-gray-500 uppercase tracking-wider">
                                                        <th class="text-left py-1.5 font-medium">{{ __('Campaign') }}</th>
                                                        <th class="text-left py-1.5 font-medium w-24">{{ __('Objective') }}</th>
                                                        <th class="text-left py-1.5 font-medium w-20">{{ __('Stage') }}</th>
                                                        <th class="text-right py-1.5 font-medium w-24">{{ __('Spend') }}</th>
                                                        <th class="text-right py-1.5 font-medium w-20">{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach($account['campaigns'] as $campaign)
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="py-1.5 text-gray-700 truncate max-w-[250px]">{{ $campaign['name'] }}</td>
                                                            <td class="py-1.5 text-gray-500 capitalize">{{ $campaign['objective'] ?? '-' }}</td>
                                                            <td class="py-1.5">
                                                                @if($campaign['funnel_stage'])
                                                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600">{{ $campaign['funnel_stage'] }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="py-1.5 text-right text-gray-700 font-medium">{{ fmtCurrency($campaign['spend'] ?? 0) }}</td>
                                                            <td class="py-1.5 text-right">
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium
                                                                    {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                                    {{ ucfirst($campaign['status'] ?? 'unknown') }}
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
                    $platformLogo = match($platformKey) {
                        'snapchat' => '👻',
                        'facebook' => 'ⓕ',
                        'google' => 'G',
                        'tiktok' => '♪',
                        'linkedin' => 'in',
                        'twitter' => '𝕏',
                        default => '●',
                    };
                    $platformColor = match($platformKey) {
                        'snapchat' => 'text-yellow-500',
                        'facebook' => 'bg-[#1877f2] text-white',
                        'google' => 'bg-white text-gray-800 border border-gray-200',
                        'tiktok' => 'bg-black text-white',
                        'linkedin' => 'bg-[#0a66c2] text-white',
                        'twitter' => 'bg-black text-white',
                        default => 'bg-gray-200 text-gray-800',
                    };
                @endphp
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg {{ $platformColor }}">
                            {{ $platformLogo }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 capitalize">{{ $platform['platform'] }}</h3>
                            <p class="text-xs text-gray-500">{{ $periodLabel }}</p>
                        </div>
                    </div>

                    {{-- Platform KPIs --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @if($platformKey === 'snapchat')
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Spend') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Impressions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Swipes') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['swipes']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Conversions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Revenue') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('ROAS') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('CPA') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpa'], 0) }}</div></div>
                            @else
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Native Leads') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['native_leads']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Conversion Sign Ups') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['sign_ups']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('CPL Snap') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpl'], 1) }}</div></div>
                            @endif
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('CPM Snap') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpm'], 2) }}</div></div>
                        @elseif($platformKey === 'facebook')
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Total spent Meta') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Impressions Meta') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Link clicks Meta') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['link_clicks']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Conversions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Revenue') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('ROAS') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('CPA') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpa'], 0) }}</div></div>
                            @else
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Website Leads Meta') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['website_leads']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('CPM Meta') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpm'], 1) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Cost per On-Facebook leads') }}</div><div class="text-xl font-bold text-gray-900">{{ $platform['on_facebook_leads'] > 0 ? number_format($platform['spend'] / $platform['on_facebook_leads'], 1) : '-' }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('On-Facebook leads') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['on_facebook_leads']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('sign_up') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['sign_ups']) }}</div></div>
                            @endif
                        @elseif($platformKey === 'google')
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Cost') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Impressions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Average cpm') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['cpm'], 0) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Clicks') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['clicks']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Conversions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Revenue') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('ROAS') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                            @endif
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Ctr') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['ctr'], 0) }}%</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Average cpc') }}</div><div class="text-xl font-bold text-gray-900">{{ $platform['clicks'] > 0 ? number_format($platform['spend'] / $platform['clicks'], 0) : '-' }}</div></div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Spend') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['spend']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Impressions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['impressions']) }}</div></div>
                            <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Clicks') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['clicks']) }}</div></div>
                            @if($dashboardData['is_ecommerce'])
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Conversions') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['conversions']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Revenue') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtCurrency($platform['revenue']) }}</div></div>
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('ROAS') }}</div><div class="text-xl font-bold text-gray-900">{{ number_format($platform['roas'], 1) }}x</div></div>
                            @else
                                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-500 uppercase">{{ __('Leads') }}</div><div class="text-xl font-bold text-gray-900">{{ fmtNum($platform['blended_leads']) }}</div></div>
                            @endif
                        @endif
                    </div>

                    {{-- Best Performing Ads --}}
                    @if(!empty($platformAds))
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">{{ ucfirst($platform['platform']) }} {{ __('best performing Ads') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($platformAds as $ad)
                                <div class="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
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
                                            <div><span class="block text-gray-400">Impr</span>{{ fmtNum($ad['impressions']) }}</div>
                                            <div><span class="block text-gray-400">Clicks</span>{{ fmtNum($ad['clicks']) }}</div>
                                            @if($dashboardData['is_ecommerce'])
                                                <div><span class="block text-gray-400">ROAS</span>{{ number_format($ad['roas'], 1) }}x</div>
                                            @else
                                                <div><span class="block text-gray-400">CPL</span>{{ number_format($ad['cpl'], 0) }}</div>
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
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg bg-blue-600 text-white">T</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Teads</h3>
                            <p class="text-xs text-gray-500">{{ $periodLabel }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#F93549] to-red-600 text-white">
                                    <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide">{{ __('Date') }}</th>
                                    <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">{{ __('Impressions') }}</th>
                                    <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">{{ __('Clicks') }}</th>
                                    <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">{{ __('Amount spent') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teads['rows'] as $row)
                                    <tr class="even:bg-red-50 border-b border-gray-100">
                                        <td class="px-3 py-2 text-gray-900">{{ $row->day }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700">{{ number_format($row->impressions) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700">{{ number_format($row->clicks) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700">{{ fmtCurrency($row->spend) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-900 text-white">
                                    <td class="px-3 py-2 font-bold">{{ __('Summary') }}</td>
                                    <td class="px-3 py-2 text-right font-bold">{{ number_format($teads['summary']['impressions'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right font-bold">{{ number_format($teads['summary']['clicks'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right font-bold">{{ fmtCurrency($teads['summary']['spend'] ?? 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Legacy Executive Summary (kept for context) --}}
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 pl-3 border-l-4 border-[#F93549]">{{ __('Executive Summary') }}</h2>
                @if(!empty($exec['health']))
                    <div class="text-center mb-4">
                        <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide {{ $healthClasses[$exec['health']] }}">
                            {{ $healthLabels[$exec['health']] }}
                        </span>
                    </div>
                @endif
                @if(!empty($exec['insights']))
                    <div class="space-y-3">
                        @foreach($exec['insights'] as $insight)
                            @php
                                $insightBorder = match($insight['type']) {
                                    'success' => 'border-l-4 border-green-500 bg-green-50',
                                    'warning' => 'border-l-4 border-yellow-500 bg-yellow-50',
                                    'danger' => 'border-l-4 border-red-500 bg-red-50',
                                    default => 'border-l-4 border-blue-500 bg-blue-50',
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


        {{-- SEO Report Tab --}}
        @if($activeTabIndex === 1)
            {{-- SEO Settings Panel --}}
            <div class="bg-white shadow rounded-lg mb-6" x-data>
                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <button wire:click="toggleSeoSettings" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $seoSettingsOpen ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                            {{ __('SEO Properties') }}
                            <x-heroicon-o-chevron-down class="w-3 h-3 transition-transform {{ $seoSettingsOpen ? 'rotate-180' : '' }}" />
                        </button>
                        @if($seoSaved)
                            <span class="inline-flex items-center text-xs text-green-600 font-medium">
                                <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                                {{ __('Saved') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- View Full Report Button --}}
                        <a href="{{ route('client-seo-report', $clientId) }}" target="_blank"
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors">
                            <x-heroicon-o-document-chart-bar class="w-4 h-4 mr-1.5" />
                            {{ __('View Full Report') }}
                        </a>
                    </div>
                </div>

                @if($seoSettingsOpen)
                    <div class="p-4 bg-gray-50 border-b border-gray-100">
                        @if(!$seoHasIntegration)
                            <div class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                <div class="text-sm text-yellow-700">
                                    {{ __('No active Google integration found.') }}
                                    <a href="{{ route('integrations') }}" class="font-medium underline hover:text-yellow-800" wire:navigate>{{ __('Connect Google') }}</a>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Search Console Site --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Search Console Site') }}</label>
                                <div class="relative">
                                    <select wire:model="seoSearchConsoleSite"
                                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-2 pr-8"
                                            {{ !$seoHasIntegration ? 'disabled' : '' }}>
                                        <option value="">{{ __('-- Select Site --') }}</option>
                                        @foreach($seoSearchConsoleSites as $site)
                                            <option value="{{ $site['site_url'] }}">{{ $site['site_url'] }}</option>
                                        @endforeach
                                    </select>
                                    @if($seoLoadingSites)
                                        <div class="absolute right-8 top-2.5">
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
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('GA4 Property') }}</label>
                                <div class="relative">
                                    <select wire:model="seoGa4PropertyId"
                                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-2 pr-8"
                                            {{ !$seoHasIntegration ? 'disabled' : '' }}>
                                        <option value="">{{ __('-- Select Property --') }}</option>
                                        @foreach($seoGa4Properties as $prop)
                                            <option value="{{ $prop['property_id'] }}">{{ $prop['display_name'] }} ({{ $prop['account_name'] ?? '' }})</option>
                                        @endforeach
                                    </select>
                                    @if($seoLoadingProperties)
                                        <div class="absolute right-8 top-2.5">
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
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('PageSpeed URL') }}</label>
                                <input wire:model="seoPagespeedUrl" type="url" placeholder="https://example.com"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-2" />
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <button wire:click="saveSeoProperties" wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md shadow-sm transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="saveSeoProperties">{{ __('Save Properties') }}</span>
                                <span wire:loading wire:target="saveSeoProperties" class="inline-flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
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
            <div class="bg-white shadow rounded-lg border border-gray-200 p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Period Preset Dropdown --}}
                        <select wire:model.live="seoPeriod" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8">
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
                                class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                            <span class="text-xs text-gray-400">–</span>
                            <input wire:model.live.debounce.500ms="seoDateTo" type="date" max="{{ now()->format('Y-m-d') }}"
                                class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                        </div>

                        {{-- Compare Toggle --}}
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <span class="text-sm font-medium text-gray-600">{{ __('Compare') }}</span>
                            <button wire:click="$toggle('seoCompareEnabled')" type="button"
                                class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $seoCompareEnabled ? 'bg-primary-600' : 'bg-gray-200' }}"
                                role="switch" aria-checked="{{ $seoCompareEnabled ? 'true' : 'false' }}">
                                <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $seoCompareEnabled ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        </label>

                        {{-- Comparison Period Label --}}
                        @if($seoCompareEnabled && $seoDateFrom && $seoDateTo)
                            @php
                                $daysDiff = \Carbon\Carbon::parse($seoDateFrom)->diffInDays(\Carbon\Carbon::parse($seoDateTo));
                                $cmpStart = \Carbon\Carbon::parse($seoDateFrom)->subDays($daysDiff);
                                $cmpEnd = \Carbon\Carbon::parse($seoDateFrom)->subDay();
                            @endphp
                            <span class="text-xs text-gray-400">vs {{ $cmpStart->format('M d') }} – {{ $cmpEnd->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($seoLoading)
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="animate-spin h-4 w-4 mr-2 text-primary-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('common.loading') }}
                            </div>
                        @endif
                        {{-- Export Dropdown --}}
                        <div class="relative" x-data="{ exportOpen: false }">
                            <button @click="exportOpen = !exportOpen" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1.5" />
                                {{ __('common.export') }}
                            </button>
                            <div x-show="exportOpen" @click.outside="exportOpen = false"
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 z-20 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="{{ route('client-seo-report-pdf', $clientId) }}?period={{ $seoPeriod }}" target="_blank" @click="exportOpen = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <x-heroicon-o-document class="w-4 h-4 mr-2 text-red-500" /> {{ __('Download PDF') }}
                                    </a>
                                    <a href="{{ route('client-seo-report-excel', $clientId) }}?period={{ $seoPeriod }}" target="_blank" @click="exportOpen = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <x-heroicon-o-table-cells class="w-4 h-4 mr-2 text-green-600" /> {{ __('Export Excel') }}
                                    </a>
                                    <button @click="window.print(); exportOpen = false" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <x-heroicon-o-printer class="w-4 h-4 mr-2 text-gray-500" /> {{ __('Print') }}
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
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-2 flex-1">
                                        <div class="h-3 bg-gray-200 rounded w-24"></div>
                                        <div class="h-7 bg-gray-200 rounded w-16"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-gray-200 rounded-lg"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="h-4 bg-gray-200 rounded w-48 mb-4"></div>
                        <div class="h-48 bg-gray-100 rounded"></div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="h-4 bg-gray-200 rounded w-32 mb-4"></div>
                        <div class="space-y-3">
                            @for($i = 0; $i < 5; $i++)
                                <div class="h-3 bg-gray-100 rounded"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            @elseif($seoReportData)
                @if(isset($seoReportData['error']))
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                        <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-yellow-400" />
                        <p class="mt-2 text-sm text-yellow-700">{{ $seoReportData['error'] }}</p>
                    </div>
                @else
                    @php
                        $channelIcons = [
                            'Organic Search' => ['icon' => 'heroicon-o-magnifying-glass', 'color' => 'text-green-600', 'bg' => 'bg-green-100'],
                            'Direct'         => ['icon' => 'heroicon-o-globe-alt', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
                            'Social'         => ['icon' => 'heroicon-o-share', 'color' => 'text-pink-600', 'bg' => 'bg-pink-100'],
                            'Referral'       => ['icon' => 'heroicon-o-link', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100'],
                            'Paid Search'    => ['icon' => 'heroicon-o-currency-dollar', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-100'],
                            'Email'          => ['icon' => 'heroicon-o-envelope', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100'],
                            'Paid Social'    => ['icon' => 'heroicon-o-megaphone', 'color' => 'text-rose-600', 'bg' => 'bg-rose-100'],
                            'Display'        => ['icon' => 'heroicon-o-tv', 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100'],
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
                        <nav class="border-b border-gray-200 mb-6 -mb-px flex space-x-6 overflow-x-auto">
                            <button @click="subTab = 'overview'" :class="subTab === 'overview' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                {{ __('client_dashboard.seo.tab_overview') }}
                            </button>
                            @if($hasSc)
                                <button @click="subTab = 'search_console'" :class="subTab === 'search_console' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_search_console') }}
                                </button>
                            @endif
                            @if($hasGa4)
                                <button @click="subTab = 'analytics'" :class="subTab === 'analytics' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_analytics') }}
                                </button>
                            @endif
                            @if($hasPs)
                                <button @click="subTab = 'pagespeed'" :class="subTab === 'pagespeed' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_pagespeed') }}
                                </button>
                            @endif
                            @if($hasEcommerce)
                                <button @click="subTab = 'ecommerce'" :class="subTab === 'ecommerce' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                    {{ __('client_dashboard.seo.tab_ecommerce') }}
                                </button>
                            @endif
                            <button @click="subTab = 'audit'" :class="subTab === 'audit' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                                {{ __('client_dashboard.seo.tab_audit') }}
                            </button>
                        </nav>

                    {{-- ═══ OVERVIEW SUB-TAB ═══ --}}
                    <div x-show="subTab === 'overview'">
                    {{-- SEO Key Metrics --}}
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-4">
                            <x-heroicon-o-chart-bar-square class="w-5 h-5 text-gray-400" />
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Key Metrics') }}</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @php $cmp = $seoReportData['comparison'] ?? []; $spk = $seoReportData['sparklines'] ?? []; @endphp
                            @if(isset($seoReportData['ga4']['summary']['active_users']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.active_users') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['active_users']) }}</p>
                                            @if(isset($cmp['active_users']))
                                                <span class="kpi-change {{ $cmp['active_users']['direction'] }}">
                                                    @if($cmp['active_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['active_users']['value']) }}%
                                                </span>
                                            @endif
                                            @if(!empty($spk['active_users']))
                                                <svg class="mt-1.5 w-20 h-6"><polyline points="{{ sparklinePath($spk['active_users']) }}" fill="none" stroke="#3b82f6" stroke-width="1.5"/></svg>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-blue-100 rounded-lg">
                                            <x-heroicon-o-users class="w-5 h-5 text-blue-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['search_console']['summary']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_impressions') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['search_console']['summary']['total_impressions'] ?? 0) }}</p>
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
                                        <div class="p-2.5 bg-purple-100 rounded-lg">
                                            <x-heroicon-o-eye class="w-5 h-5 text-purple-600" />
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.avg_position') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $seoReportData['search_console']['summary']['avg_position'] ?? '-' }}</p>
                                            @if(isset($cmp['avg_position']))
                                                <span class="kpi-change {{ $cmp['avg_position']['direction'] }}">
                                                    @if($cmp['avg_position']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['avg_position']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-teal-100 rounded-lg">
                                            <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-teal-600" />
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_clicks') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['search_console']['summary']['total_clicks'] ?? 0) }}</p>
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
                                        <div class="p-2.5 bg-indigo-100 rounded-lg">
                                            <x-heroicon-o-cursor-arrow-rays class="w-5 h-5 text-indigo-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['pagespeed_mobile']['scores']['performance']) && $seoReportData['pagespeed_mobile']['scores']['performance'] > 0)
                                @php
                                    $score = $seoReportData['pagespeed_mobile']['scores']['performance'];
                                    $scoreColor = $score >= 90 ? 'text-green-600' : ($score >= 50 ? 'text-orange-600' : 'text-red-600');
                                @endphp
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.mobile_speed') }}</p>
                                            <p class="text-2xl font-bold {{ $scoreColor }} mt-1">{{ $score }}</p>
                                        </div>
                                        <div class="p-2.5 bg-orange-100 rounded-lg">
                                            <x-heroicon-o-bolt class="w-5 h-5 text-orange-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['sessions']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.sessions') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['sessions']) }}</p>
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
                                        <div class="p-2.5 bg-green-100 rounded-lg">
                                            <x-heroicon-o-clock class="w-5 h-5 text-green-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['page_views']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.page_views') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['page_views']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-sky-100 rounded-lg">
                                            <x-heroicon-o-document-text class="w-5 h-5 text-sky-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['total_users']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.total_users') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['total_users']) }}</p>
                                            @if(isset($cmp['total_users']))
                                                <span class="kpi-change {{ $cmp['total_users']['direction'] }}">
                                                    @if($cmp['total_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_users']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-violet-100 rounded-lg">
                                            <x-heroicon-o-user-group class="w-5 h-5 text-violet-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['new_users']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.new_users') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['new_users']) }}</p>
                                            @if(isset($cmp['new_users']))
                                                <span class="kpi-change {{ $cmp['new_users']['direction'] }}">
                                                    @if($cmp['new_users']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['new_users']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-emerald-100 rounded-lg">
                                            <x-heroicon-o-user-plus class="w-5 h-5 text-emerald-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['conversions']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.conversions') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($seoReportData['ga4']['summary']['conversions']) }}</p>
                                            @if(isset($cmp['conversions']))
                                                <span class="kpi-change {{ $cmp['conversions']['direction'] }}">
                                                    @if($cmp['conversions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['conversions']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-primary-100 rounded-lg">
                                            <x-heroicon-o-check-circle class="w-5 h-5 text-primary-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(($seoReportData['ga4']['summary']['total_revenue'] ?? 0) > 0)
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.total_revenue') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtCurrency($seoReportData['ga4']['summary']['total_revenue']) }}</p>
                                            @if(isset($cmp['total_revenue']))
                                                <span class="kpi-change {{ $cmp['total_revenue']['direction'] }}">
                                                    @if($cmp['total_revenue']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['total_revenue']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-yellow-100 rounded-lg">
                                            <x-heroicon-o-banknotes class="w-5 h-5 text-yellow-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['bounce_rate']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.bounce_rate') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($seoReportData['ga4']['summary']['bounce_rate'], 1) }}%</p>
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
                                        <div class="p-2.5 bg-rose-100 rounded-lg">
                                            <x-heroicon-o-arrow-uturn-left class="w-5 h-5 text-rose-600" />
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
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.avg_engagement_time') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $mins }}m {{ $secs }}s</p>
                                        </div>
                                        <div class="p-2.5 bg-cyan-100 rounded-lg">
                                            <x-heroicon-o-clock class="w-5 h-5 text-cyan-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['ga4']['summary']['engagement_rate']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.engagement_rate') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($seoReportData['ga4']['summary']['engagement_rate'], 1) }}%</p>
                                            @if(isset($cmp['engagement_rate']))
                                                <span class="kpi-change {{ $cmp['engagement_rate']['direction'] }}">
                                                    @if($cmp['engagement_rate']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['engagement_rate']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-amber-100 rounded-lg">
                                            <x-heroicon-o-fire class="w-5 h-5 text-amber-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['ga4']['traffic_sources']))
                                @php
                                    $organicSource = collect($seoReportData['ga4']['traffic_sources'])->firstWhere('channel', 'Organic Search');
                                    $organicShare = $organicSource['share'] ?? 0;
                                @endphp
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.organic_share') }}</p>
                                            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($organicShare, 1) }}%</p>
                                        </div>
                                        <div class="p-2.5 bg-lime-100 rounded-lg">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-lime-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($seoReportData['search_console']['summary']['avg_ctr']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_ctr') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($seoReportData['search_console']['summary']['avg_ctr'], 2) }}%</p>
                                            @if(isset($cmp['avg_ctr']))
                                                <span class="kpi-change {{ $cmp['avg_ctr']['direction'] }}">
                                                    @if($cmp['avg_ctr']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                                    {{ abs($cmp['avg_ctr']['value']) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-2.5 bg-blue-100 rounded-lg">
                                            <x-heroicon-o-arrow-up-right class="w-5 h-5 text-blue-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['search_console']['pages']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.unique_pages_ranking') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($seoReportData['search_console']['pages']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-slate-100 rounded-lg">
                                            <x-heroicon-o-document-duplicate class="w-5 h-5 text-slate-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($seoReportData['search_console']['queries']))
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.unique_keywords') }}</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($seoReportData['search_console']['queries']) }}</p>
                                        </div>
                                        <div class="p-2.5 bg-fuchsia-100 rounded-lg">
                                            <x-heroicon-o-key class="w-5 h-5 text-fuchsia-600" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Search Console Trend Chart --}}
                    @if(!empty($seoReportData['search_console']['timeseries']))
                        <div class="bg-white shadow rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.search_performance_trend') }}</h3>
                            <div class="h-64 sm:h-72 md:h-80" x-data="{ chart: null }" x-init="chart = new Chart($refs.scTrendCanvas, @js($scTrendChartConfig))" wire:ignore>
                                <canvas x-ref="scTrendCanvas"></canvas>
                            </div>
                        </div>
                    @endif

                    {{-- GA4 Analytics Trend Chart --}}
                    @if(!empty($seoReportData['ga4']['timeseries']))
                        <div class="bg-white shadow rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.ga4_analytics_trend') }}</h3>
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('client_dashboard.seo.pagespeed_title') }}</h3>
                                <div class="flex items-center gap-3">
                                    <button wire:click.stop="runPageSpeed" wire:loading.attr="disabled" wire:target="runPageSpeed"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50"
                                            @if(($seoReportData['pagespeed_mobile']['_error'] ?? null) === 'quota_exceeded') disabled @endif>
                                        <span wire:loading.remove wire:target="runPageSpeed">
                                            <x-heroicon-o-bolt class="w-4 h-4 mr-1.5 inline-block -mt-0.5" />
                                            {{ __('client_dashboard.seo.run_pagespeed') }}
                                        </span>
                                        <span wire:loading wire:target="runPageSpeed" class="inline-flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24">
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
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-3 capitalize">{{ $type }}</h4>
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
                                                                    <circle cx="32" cy="32" r="28" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="5"/>
                                                                    <circle cx="32" cy="32" r="28" fill="none" stroke="{{ $ringColor }}" stroke-width="5"
                                                                            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"/>
                                                                </svg>
                                                                <span class="absolute inset-0 flex items-center justify-center text-lg font-bold" style="color: {{ $ringColor }}">{{ $val }}</span>
                                                            </div>
                                                            <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                {{-- Core Web Vitals --}}
                                                @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                                        <h5 class="text-xs font-medium text-gray-600 mb-2">{{ __('client_dashboard.seo.core_web_vitals') }}</h5>
                                                        <div class="grid grid-cols-3 gap-2">
                                                            @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                                @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                                    @php $cwv = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                                    <div class="text-center p-2 rounded {{ ($cwv['score'] ?? '') === 'good' ? 'bg-green-50' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'bg-yellow-50' : 'bg-red-50') }}">
                                                                        <div class="text-sm font-semibold {{ ($cwv['score'] ?? '') === 'good' ? 'text-green-700' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'text-yellow-700' : 'text-red-700') }}">
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
                                <div class="text-center py-6">
                                    <x-heroicon-o-clock class="mx-auto h-10 w-10 text-orange-300" />
                                    <p class="mt-2 text-sm text-orange-600">{{ __('client_dashboard.seo.pagespeed_quota_exceeded') }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('client_dashboard.seo.pagespeed_quota_hint') }}</p>
                                </div>
                            @else
                                <div class="text-center py-6">
                                    <x-heroicon-o-bolt class="mx-auto h-10 w-10 text-gray-300" />
                                    <p class="mt-2 text-sm text-gray-500">{{ __('client_dashboard.seo.pagespeed_prompt') }}</p>
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Traffic Sources') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Channel') }}</th>
                                            <th class="text-right">{{ __('Sessions') }}</th>
                                            <th class="text-right">{{ __('Share') }}</th>
                                            <th class="text-right">{{ __('Revenue') }}</th>
                                            <th class="text-right">{{ __('client_dashboard.seo.rev_share') }}</th>
                                            <th class="text-right">{{ __('client_dashboard.seo.rev_per_session') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Sessions') }}</th>@endif
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
                                                <td class="text-right">{{ fmtNum($source['sessions'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($source['share'] ?? 0, 1) }}%</td>
                                                <td class="text-right">{{ fmtCurrency($source['revenue'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($source['rev_share'] ?? 0, 1) }}%</td>
                                                <td class="text-right">{{ fmtCurrency($source['rev_per_session'] ?? 0) }}</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_traffic_sources'][$source['channel']]))
                                                        @php $prev = $cmp['ga4_traffic_sources'][$source['channel']]; $d = $prev['sessions'] > 0 ? round((($source['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($source['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                                <td>
                                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min($source['share'] ?? 0, 100) }}%"></div>
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
                                <div class="bg-white shadow rounded-lg mb-0" x-data="{ open: true, expanded: false }">
                                    <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Top Pages (Search Console)') }}</h3>
                                        <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                                    </div>
                                    <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                    <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Page') }}</th>
                                                    <th class="text-right">{{ __('Clicks') }}</th>
                                                    <th class="text-right">{{ __('Impressions') }}</th>
                                                    <th class="text-right">{{ __('CTR') }}</th>
                                                    <th class="text-right">{{ __('Position') }}</th>
                                                    @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Clicks') }}</th>@endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                @foreach($seoReportData['search_console']['pages'] as $idx => $page)
                                                    <tr x-show="expanded || {{ $idx }} < 10">
                                                        <td class="truncate max-w-[200px]" title="{{ $page['key'] ?? '' }}">
                                                            @if(!empty($page['key']))
                                                                <a href="{{ $page['key'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $page['key'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                        <td class="text-right">{{ fmtNum($page['clicks'] ?? 0) }}</td>
                                                        <td class="text-right">{{ fmtNum($page['impressions'] ?? 0) }}</td>
                                                        <td class="text-right">{{ number_format($page['ctr'] ?? 0, 1) }}%</td>
                                                        <td class="text-right">
                                                            @php $pos = $page['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-100 text-green-800' : ($pos <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'); @endphp
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                        </td>
                                                        @if($seoCompareEnabled)
                                                            @if(isset($cmp['sc_pages'][$page['key']]))
                                                                @php $prev = $cmp['sc_pages'][$page['key']]; $d = $prev['clicks'] > 0 ? round((($page['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($page['clicks'] > 0 ? 100 : 0); @endphp
                                                                <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                            @else
                                                                <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                            @endif
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(count($seoReportData['search_console']['pages']) > 10)
                                        <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
                                            <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['pages']) }}'"></span>
                                        </button>
                                    @endif
                                    </div>
                                </div>
                            @endif
                            {{-- GA4 Top Pages --}}
                            @if(!empty($seoReportData['ga4']['top_pages']))
                                <div class="bg-white shadow rounded-lg mb-0" x-data="{ open: true, expanded: false }">
                                    <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Top Pages (GA4)') }}</h3>
                                        <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                                    </div>
                                    <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                    <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Page') }}</th>
                                                    <th class="text-right">{{ __('Views') }}</th>
                                                    <th class="text-right">{{ __('Users') }}</th>
                                                    <th class="text-right">{{ __('Sessions') }}</th>
                                                    @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Views') }}</th>@endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                @foreach($seoReportData['ga4']['top_pages'] as $idx => $page)
                                                    <tr x-show="expanded || {{ $idx }} < 10">
                                                        <td class="truncate max-w-[200px]" title="{{ $page['page_path'] ?? '' }}">
                                                            @if(!empty($page['page_path']) && !empty($dashboardData['client']['website']))
                                                                <a href="{{ rtrim($dashboardData['client']['website'], '/') }}{{ $page['page_path'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $page['page_path'] }}</a>
                                                            @else
                                                                {{ $page['page_path'] ?? '-' }}
                                                            @endif
                                                        </td>
                                                        <td class="text-right">{{ fmtNum($page['page_views'] ?? 0) }}</td>
                                                        <td class="text-right">{{ fmtNum($page['users'] ?? 0) }}</td>
                                                        <td class="text-right">{{ fmtNum($page['sessions'] ?? 0) }}</td>
                                                        @if($seoCompareEnabled)
                                                            @if(isset($cmp['ga4_pages'][$page['page_path']]))
                                                                @php $prev = $cmp['ga4_pages'][$page['page_path']]; $d = $prev['page_views'] > 0 ? round((($page['page_views'] - $prev['page_views']) / $prev['page_views']) * 100, 1) : ($page['page_views'] > 0 ? 100 : 0); @endphp
                                                                <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                            @else
                                                                <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                            @endif
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(count($seoReportData['ga4']['top_pages']) > 10)
                                        <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Top Search Queries') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Query') }}</th>
                                            <th class="text-right">{{ __('Clicks') }}</th>
                                            <th class="text-right">{{ __('Impressions') }}</th>
                                            <th class="text-right">{{ __('CTR') }}</th>
                                            <th class="text-right">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['queries'] as $idx => $query)
                                            <tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">
                                                    @if(!empty($query['key']))
                                                        <a href="https://www.google.com/search?q={{ urlencode($query['key']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $query['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-right">{{ fmtNum($query['clicks'] ?? 0) }}</td>
                                                <td class="text-right">{{ fmtNum($query['impressions'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($query['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-right">
                                                    @php $pos = $query['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-100 text-green-800' : ($pos <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'); @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_queries'][$query['key']]))
                                                        @php $prev = $cmp['sc_queries'][$query['key']]; $d = $prev['clicks'] > 0 ? round((($query['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($query['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['queries']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['queries']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif

                    {{-- Devices (Collapsible + Device Icons) --}}
                    @if(!empty($seoReportData['ga4']['devices']))
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Devices') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Device') }}</th>
                                            <th class="text-right">{{ __('Users') }}</th>
                                            <th class="text-right">{{ __('Sessions') }}</th>
                                            <th class="text-right">{{ __('Share') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Sessions') }}</th>@endif
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
                                                <td class="text-right">{{ fmtNum($device['users'] ?? 0) }}</td>
                                                <td class="text-right">{{ fmtNum($device['sessions'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($device['share'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_devices'][$device['device']]))
                                                        @php $prev = $cmp['ga4_devices'][$device['device']]; $d = $prev['sessions'] > 0 ? round((($device['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($device['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Geographic Data') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Country') }}</th>
                                            <th class="text-right">{{ __('Sessions') }}</th>
                                            <th class="text-right">{{ __('Users') }}</th>
                                            <th class="text-right">{{ __('Share') }}</th>
                                            <th class="text-right">{{ __('Engagement Rate') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Sessions') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['geo'] as $idx => $geo)
                                            <tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">{{ countryFlag($geo['country'] ?? '') }} {{ $geo['country'] ?? '-' }}</td>
                                                <td class="text-right">{{ fmtNum($geo['sessions'] ?? 0) }}</td>
                                                <td class="text-right">{{ fmtNum($geo['users'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($geo['share'] ?? 0, 1) }}%</td>
                                                <td class="text-right">{{ number_format($geo['engagement_rate'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_geo'][$geo['country']]))
                                                        @php $prev = $cmp['ga4_geo'][$geo['country']]; $d = $prev['sessions'] > 0 ? round((($geo['sessions'] - $prev['sessions']) / $prev['sessions']) * 100, 1) : ($geo['sessions'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['ga4']['geo']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
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
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_clicks') }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($scSum['total_clicks'] ?? 0) }}</p>
                                    @if(isset($cmp['total_clicks']))
                                        <span class="kpi-change {{ $cmp['total_clicks']['direction'] }}">
                                            @if($cmp['total_clicks']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['total_clicks']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_impressions') }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtNum($scSum['total_impressions'] ?? 0) }}</p>
                                    @if(isset($cmp['total_impressions']))
                                        <span class="kpi-change {{ $cmp['total_impressions']['direction'] }}">
                                            @if($cmp['total_impressions']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['total_impressions']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.search_ctr') }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($scSum['avg_ctr'] ?? 0, 2) }}%</p>
                                    @if(isset($cmp['avg_ctr']))
                                        <span class="kpi-change {{ $cmp['avg_ctr']['direction'] }}">
                                            @if($cmp['avg_ctr']['value'] >= 0) <x-heroicon-s-arrow-trending-up class="w-3 h-3 inline -mt-0.5" /> @else <x-heroicon-s-arrow-trending-down class="w-3 h-3 inline -mt-0.5" /> @endif
                                            {{ abs($cmp['avg_ctr']['value']) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('client_dashboard.seo.avg_position') }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $scSum['avg_position'] ?? '-' }}</p>
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
                            <div class="bg-white shadow rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.search_performance_trend') }}</h3>
                                <div class="h-64 sm:h-72 md:h-80" x-data="{ chart: null }" x-init="chart = new Chart($refs.scTrendCanvas2, @js($scTrendChartConfig))" wire:ignore>
                                    <canvas x-ref="scTrendCanvas2"></canvas>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- SC Pages --}}
                    @if(!empty($seoReportData['search_console']['pages']))
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Top Pages (Search Console)') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Page') }}</th>
                                            <th class="text-right">{{ __('Clicks') }}</th>
                                            <th class="text-right">{{ __('Impressions') }}</th>
                                            <th class="text-right">{{ __('CTR') }}</th>
                                            <th class="text-right">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['pages'] as $idx => $page)
                                            <tr x-show="expanded || {{ $idx }} < 10">
                                                <td class="truncate max-w-[200px]" title="{{ $page['key'] ?? '' }}">
                                                    @if(!empty($page['key']))
                                                        <a href="{{ $page['key'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $page['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-right">{{ fmtNum($page['clicks'] ?? 0) }}</td>
                                                <td class="text-right">{{ fmtNum($page['impressions'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($page['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-right">
                                                    @php $pos = $page['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-100 text-green-800' : ($pos <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'); @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_pages'][$page['key']]))
                                                        @php $prev = $cmp['sc_pages'][$page['key']]; $d = $prev['clicks'] > 0 ? round((($page['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($page['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['pages']) > 10)
                                <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['pages']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif
                    {{-- SC Queries --}}
                    @if(!empty($seoReportData['search_console']['queries']))
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true, expanded: false }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Top Search Queries') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Query') }}</th>
                                            <th class="text-right">{{ __('Clicks') }}</th>
                                            <th class="text-right">{{ __('Impressions') }}</th>
                                            <th class="text-right">{{ __('CTR') }}</th>
                                            <th class="text-right">{{ __('Position') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Clicks') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['search_console']['queries'] as $idx => $query)
                                            <tr x-show="expanded || {{ $idx }} < 20">
                                                <td class="font-medium">
                                                    @if(!empty($query['key']))
                                                        <a href="https://www.google.com/search?q={{ urlencode($query['key']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $query['key'] }}</a>
                                                    @else - @endif
                                                </td>
                                                <td class="text-right">{{ fmtNum($query['clicks'] ?? 0) }}</td>
                                                <td class="text-right">{{ fmtNum($query['impressions'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($query['ctr'] ?? 0, 1) }}%</td>
                                                <td class="text-right">
                                                    @php $pos = $query['position'] ?? 0; $posBadge = $pos <= 3 ? 'bg-green-100 text-green-800' : ($pos <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'); @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $posBadge }}">{{ number_format($pos, 1) }}</span>
                                                </td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['sc_queries'][$query['key']]))
                                                        @php $prev = $cmp['sc_queries'][$query['key']]; $d = $prev['clicks'] > 0 ? round((($query['clicks'] - $prev['clicks']) / $prev['clicks']) * 100, 1) : ($query['clicks'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($seoReportData['search_console']['queries']) > 20)
                                <button @click="expanded = !expanded" class="mt-2 w-full text-center text-xs font-medium text-primary-600 hover:text-primary-800 py-2">
                                    <span x-text="expanded ? '{{ __('common.show_less') }}' : '{{ __('common.show_all') }} {{ count($seoReportData['search_console']['queries']) }}'"></span>
                                </button>
                            @endif
                            </div>
                        </div>
                    @endif
                    {{-- SC Coverage in search_console tab --}}
                    @if(!empty($seoReportData['search_console']['coverage']) && !empty($seoReportData['search_console']['coverage']['sitemaps']))
                        @php $cov2 = $seoReportData['search_console']['coverage']; @endphp
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                    <div class="text-center border border-gray-200 rounded-lg p-4"><p class="text-2xl font-bold text-gray-900">{{ fmtNum($cov2['total_submitted']) }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p></div>
                                    <div class="text-center border border-gray-200 rounded-lg p-4"><p class="text-2xl font-bold text-green-600">{{ fmtNum($cov2['total_indexed']) }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p></div>
                                    <div class="text-center border border-gray-200 rounded-lg p-4">@php $rc = $cov2['index_rate'] >= 90 ? 'text-green-600' : ($cov2['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp<p class="text-2xl font-bold {{ $rc }}">{{ $cov2['index_rate'] }}%</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p></div>
                                    <div class="text-center border border-gray-200 rounded-lg p-4"><p class="text-2xl font-bold text-red-600">{{ $cov2['total_errors'] }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p></div>
                                    <div class="text-center border border-gray-200 rounded-lg p-4"><p class="text-2xl font-bold text-orange-600">{{ $cov2['total_warnings'] }}</p><p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p></div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if(empty($seoReportData['search_console']))
                        <div class="bg-white shadow rounded-lg p-12 text-center">
                            <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No Search Console data') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Configure a Search Console property in SEO Properties to view data.') }}</p>
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
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Ecommerce') }}</h3>
                            <div class="flex-1 h-px bg-gray-200"></div>
                        </div>

                        {{-- Top Products by Revenue --}}
                        @php
                            $ecomPages = $seoReportData['ga4']['top_ecommerce_pages'] ?? [];
                        @endphp
                        @if(!empty($ecomPages))
                            <div class="bg-white shadow rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.top_ecommerce_pages') }}</h3>
                                <div class="overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('client_dashboard.seo.product') }}</th>
                                                <th class="text-right">{{ __('Views') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.purchases') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.revenue_sar') }}</th>
                                                @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Views') }}</th>@endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                            @foreach($ecomPages as $ePage)
                                                <tr>
                                                    <td class="truncate max-w-[200px]" title="{{ $ePage['name'] ?? '' }}">
                                                        @if(!empty($ePage['name']))
                                                            <a href="https://www.google.com/search?q={{ urlencode($ePage['name']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $ePage['name'] }}</a>
                                                        @else - @endif
                                                    </td>
                                                    <td class="text-right">{{ fmtNum($ePage['views'] ?? 0) }}</td>
                                                    <td class="text-right">{{ fmtNum($ePage['purchases'] ?? 0) }}</td>
                                                    <td class="text-right">{{ fmtCurrency($ePage['revenue'] ?? 0) }}</td>
                                                    @if($seoCompareEnabled)
                                                        @if(isset($cmp['ga4_ecom_pages'][$ePage['name']]))
                                                            @php $prev = $cmp['ga4_ecom_pages'][$ePage['name']]; $d = $prev['views'] > 0 ? round((($ePage['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($ePage['views'] > 0 ? 100 : 0); @endphp
                                                            <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                        @else
                                                            <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
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
                                    <div class="bg-white shadow rounded-lg p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.top_products') }}</h3>
                                        <div class="overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th class="w-10">#</th>
                                                        <th>{{ __('client_dashboard.seo.product') }}</th>
                                                        <th class="text-right">{{ __('Views') }}</th>
                                                        <th class="text-right">{{ __('Users') }}</th>
                                                        @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Views') }}</th>@endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                    @foreach($seoReportData['ga4']['top_products'] as $prod)
                                                        <tr>
                                                            <td class="text-gray-400">{{ $loop->iteration }}</td>
                                                            <td class="font-medium truncate max-w-[200px]">
                                                            @if(!empty($prod['name']))
                                                                <a href="https://www.google.com/search?q={{ urlencode($prod['name']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $prod['name'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                            <td class="text-right">{{ fmtNum($prod['views'] ?? 0) }}</td>
                                                            <td class="text-right">{{ fmtNum($prod['users'] ?? 0) }}</td>
                                                            @if($seoCompareEnabled)
                                                                @if(isset($cmp['ga4_top_products'][$prod['name']]))
                                                                    @php $prev = $cmp['ga4_top_products'][$prod['name']]; $d = $prev['views'] > 0 ? round((($prod['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($prod['views'] > 0 ? 100 : 0); @endphp
                                                                    <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                                @else
                                                                    <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
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
                                    <div class="bg-white shadow rounded-lg p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('client_dashboard.seo.least_products') }}</h3>
                                        <div class="overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th class="w-10">#</th>
                                                        <th>{{ __('client_dashboard.seo.product') }}</th>
                                                        <th class="text-right">{{ __('Views') }}</th>
                                                        <th class="text-right">{{ __('Users') }}</th>
                                                        <th class="text-right">{{ __('Engagement Rate') }}</th>
                                                        @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Views') }}</th>@endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                                    @foreach($seoReportData['ga4']['least_products'] as $prod)
                                                        <tr>
                                                            <td class="text-gray-400">{{ $loop->iteration }}</td>
                                                            <td class="font-medium truncate max-w-[200px]">
                                                            @if(!empty($prod['name']))
                                                                <a href="https://www.google.com/search?q={{ urlencode($prod['name']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $prod['name'] }}</a>
                                                            @else - @endif
                                                        </td>
                                                            <td class="text-right">{{ fmtNum($prod['views'] ?? 0) }}</td>
                                                            <td class="text-right">{{ fmtNum($prod['users'] ?? 0) }}</td>
                                                            <td class="text-right">{{ number_format($prod['engagement_rate'] ?? 0, 1) }}%</td>
                                                            @if($seoCompareEnabled)
                                                                @if(isset($cmp['ga4_least_products'][$prod['name']]))
                                                                    @php $prev = $cmp['ga4_least_products'][$prod['name']]; $d = $prev['views'] > 0 ? round((($prod['views'] - $prev['views']) / $prev['views']) * 100, 1) : ($prod['views'] > 0 ? 100 : 0); @endphp
                                                                    <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                                @else
                                                                    <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('client_dashboard.seo.browser_technology') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="overflow-auto max-h-[32rem] ring-1 ring-gray-200 rounded-lg">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('client_dashboard.seo.browser') }}</th>
                                            <th class="text-right">{{ __('Users') }}</th>
                                            <th class="text-right">{{ __('Share') }}</th>
                                            @if($browserTotalRevenue > 0)
                                                <th class="text-right">{{ __('client_dashboard.seo.revenue_sar') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.rev_share') }}</th>
                                            @endif
                                            <th class="text-right">{{ __('Engagement Rate') }}</th>
                                            @if($seoCompareEnabled)<th class="text-right">{{ __('Δ Users') }}</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $cmp = $seoReportData['comparison'] ?? []; @endphp
                                        @foreach($seoReportData['ga4']['browsers'] as $browser)
                                            <tr>
                                                <td class="font-medium">{{ $browser['browser'] ?? '-' }}</td>
                                                <td class="text-right">{{ fmtNum($browser['users'] ?? 0) }}</td>
                                                <td class="text-right">{{ number_format($browser['share'] ?? 0, 1) }}%</td>
                                                @if($browserTotalRevenue > 0)
                                                    <td class="text-right">{{ fmtCurrency($browser['revenue'] ?? 0) }}</td>
                                                    <td class="text-right">{{ number_format($browser['rev_share'] ?? 0, 1) }}%</td>
                                                @endif
                                                <td class="text-right">{{ number_format($browser['engagement_rate'] ?? 0, 1) }}%</td>
                                                @if($seoCompareEnabled)
                                                    @if(isset($cmp['ga4_browsers'][$browser['browser']]))
                                                        @php $prev = $cmp['ga4_browsers'][$browser['browser']]; $d = $prev['users'] > 0 ? round((($browser['users'] - $prev['users']) / $prev['users']) * 100, 1) : ($browser['users'] > 0 ? 100 : 0); @endphp
                                                        <td class="text-right"><span class="text-xs font-medium {{ $d >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $d >= 0 ? '+' : '' }}{{ $d }}%</span></td>
                                                    @else
                                                        <td class="text-right"><span class="text-xs text-gray-400">&mdash;</span></td>
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
                        <div class="bg-white shadow rounded-lg mb-6" x-data="{ open: true }">
                            <div class="flex items-center justify-between p-6 cursor-pointer select-none" @click="open = !open">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                            <div x-show="open" x-transition.duration.200ms class="px-6 pb-6">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ fmtNum($cov['total_submitted']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-green-600">{{ fmtNum($cov['total_indexed']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    @php $rateColor = $cov['index_rate'] >= 90 ? 'text-green-600' : ($cov['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp
                                    <p class="text-2xl font-bold {{ $rateColor }}">{{ $cov['index_rate'] }}%</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-red-600">{{ $cov['total_errors'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-orange-600">{{ $cov['total_warnings'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p>
                                </div>
                            </div>
                            @if(count($cov['sitemaps']) > 1)
                                <div class="mt-4 overflow-hidden ring-1 ring-gray-200 rounded-lg">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('client_dashboard.seo.sitemap') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.submitted_pages') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.indexed_pages') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.errors') }}</th>
                                                <th class="text-right">{{ __('client_dashboard.seo.warnings') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cov['sitemaps'] as $sm)
                                                <tr>
                                                    <td class="truncate max-w-[300px]" title="{{ $sm['path'] }}">
                                                        <a href="{{ $sm['path'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline">{{ $sm['path'] }}</a>
                                                    </td>
                                                    <td class="text-right">{{ fmtNum($sm['submitted']) }}</td>
                                                    <td class="text-right">{{ fmtNum($sm['indexed']) }}</td>
                                                    <td class="text-right {{ $sm['errors'] > 0 ? 'text-red-600 font-medium' : '' }}">{{ $sm['errors'] }}</td>
                                                    <td class="text-right {{ $sm['warnings'] > 0 ? 'text-orange-600 font-medium' : '' }}">{{ $sm['warnings'] }}</td>
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
                    <div class="bg-white shadow rounded-lg mb-6 p-6">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400" />
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('client_dashboard.seo.audit_health_score') }}</h3>
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
                                    <circle cx="48" cy="48" r="42" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="6"/>
                                    <circle cx="48" cy="48" r="42" fill="none" stroke="{{ $healthColor }}" stroke-width="6"
                                            stroke-dasharray="{{ $healthCircumference }}" stroke-dashoffset="{{ $healthOffset }}" stroke-linecap="round"/>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-2xl font-bold" style="color: {{ $healthColor }}">{{ $healthScore }}</span>
                            </div>

                            {{-- Breakdown badges --}}
                            <div class="flex flex-wrap gap-3">
                                @foreach($auditComponents as $compKey => $comp)
                                    @php
                                        $badgeColor = $comp['value'] >= 80 ? 'bg-green-100 text-green-700' : ($comp['value'] >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                    @endphp
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $badgeColor }}">
                                        <span class="text-lg font-bold">{{ $comp['value'] }}</span>
                                        <span class="text-xs font-medium">{{ $comp['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: PageSpeed Audit Summary --}}
                    @if(isset($seoReportData['pagespeed_mobile']['scores']) || isset($seoReportData['pagespeed_desktop']['scores']))
                        <div class="bg-white shadow rounded-lg mb-6 p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-bolt class="w-5 h-5 text-gray-400" />
                                <h3 class="text-base font-semibold text-gray-900">{{ __('client_dashboard.seo.pagespeed_title') }}</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach(['mobile' => $seoReportData['pagespeed_mobile'] ?? null, 'desktop' => $seoReportData['pagespeed_desktop'] ?? null] as $type => $ps)
                                    @if($ps && isset($ps['scores']))
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-gray-700 mb-3 capitalize">{{ $type }}</h4>
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
                                                                <circle cx="32" cy="32" r="28" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="5"/>
                                                                <circle cx="32" cy="32" r="28" fill="none" stroke="{{ $ringColor }}" stroke-width="5"
                                                                        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"/>
                                                            </svg>
                                                            <span class="absolute inset-0 flex items-center justify-center text-lg font-bold" style="color: {{ $ringColor }}">{{ $val }}</span>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                                <div class="mt-4 pt-4 border-t border-gray-200">
                                                    <h5 class="text-xs font-medium text-gray-600 mb-2">{{ __('client_dashboard.seo.core_web_vitals') }}</h5>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                            @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                                @php $cwv = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                                <div class="text-center p-2 rounded {{ ($cwv['score'] ?? '') === 'good' ? 'bg-green-50' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'bg-yellow-50' : 'bg-red-50') }}">
                                                                    <div class="text-sm font-semibold {{ ($cwv['score'] ?? '') === 'good' ? 'text-green-700' : (($cwv['score'] ?? '') === 'needs_improvement' ? 'text-yellow-700' : 'text-red-700') }}">
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
                        <div class="bg-white shadow rounded-lg mb-6 p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-globe-alt class="w-5 h-5 text-gray-400" />
                                <h3 class="text-base font-semibold text-gray-900">{{ __('client_dashboard.seo.sc_coverage') }}</h3>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ fmtNum($auditCovData['total_submitted']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.submitted_pages') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-green-600">{{ fmtNum($auditCovData['total_indexed']) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.indexed_pages') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    @php $auditRateColor = $auditCovData['index_rate'] >= 90 ? 'text-green-600' : ($auditCovData['index_rate'] >= 50 ? 'text-orange-600' : 'text-red-600'); @endphp
                                    <p class="text-2xl font-bold {{ $auditRateColor }}">{{ $auditCovData['index_rate'] }}%</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.index_rate') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-red-600">{{ $auditCovData['total_errors'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.errors') }}</p>
                                </div>
                                <div class="text-center border border-gray-200 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-orange-600">{{ $auditCovData['total_warnings'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('client_dashboard.seo.warnings') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Section 4: Technical Issues Table --}}
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-6 gap-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('client_dashboard.seo.technical_issues') }}</h3>
                            <div class="flex items-center gap-3">
                                <select wire:model.live="auditStatusFilter" class="text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    <option value="all">{{ __('client_dashboard.seo.all_statuses') }}</option>
                                    <option value="open">{{ __('client_dashboard.seo.status_open') }}</option>
                                    <option value="in_progress">{{ __('client_dashboard.seo.status_in_progress') }}</option>
                                    <option value="resolved">{{ __('client_dashboard.seo.status_resolved') }}</option>
                                    <option value="wont_fix">{{ __('client_dashboard.seo.status_wont_fix') }}</option>
                                </select>
                                <select wire:model.live="auditSeverityFilter" class="text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
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
                                                        $sevColors = ['critical' => 'bg-red-100 text-red-700', 'high' => 'bg-orange-100 text-orange-700', 'medium' => 'bg-yellow-100 text-yellow-700', 'low' => 'bg-blue-100 text-blue-700'];
                                                        $sevClass = $sevColors[$issue['severity']] ?? 'bg-gray-100 text-gray-700';
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sevClass }}">
                                                        {{ __('client_dashboard.seo.severity_' . $issue['severity']) }}
                                                    </span>
                                                </td>
                                                <td class="text-sm text-gray-700">{{ $issue['readable_type'] ?? $issue['issue_type'] ?? '-' }}</td>
                                                <td class="text-sm text-gray-600 max-w-[250px] truncate" title="{{ app()->getLocale() === 'ar' ? ($issue['description_ar'] ?? $issue['description_en'] ?? '') : ($issue['description_en'] ?? '') }}">
                                                    {{ app()->getLocale() === 'ar' ? ($issue['description_ar'] ?? $issue['description_en'] ?? '-') : ($issue['description_en'] ?? '-') }}
                                                </td>
                                                <td class="text-sm max-w-[200px] truncate">
                                                    @if($issue['page_url'])
                                                        <a href="{{ $issue['page_url'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-800 hover:underline" title="{{ $issue['page_url'] }}">{{ $issue['page_url'] }}</a>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($editingIssueId === $issue['id'])
                                                        <select wire:model="editingIssueStatus" class="text-xs border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500">
                                                            <option value="open">{{ __('client_dashboard.seo.status_open') }}</option>
                                                            <option value="in_progress">{{ __('client_dashboard.seo.status_in_progress') }}</option>
                                                            <option value="resolved">{{ __('client_dashboard.seo.status_resolved') }}</option>
                                                            <option value="wont_fix">{{ __('client_dashboard.seo.status_wont_fix') }}</option>
                                                        </select>
                                                    @else
                                                        @php
                                                            $statusColors = ['open' => 'bg-red-100 text-red-700', 'in_progress' => 'bg-yellow-100 text-yellow-700', 'resolved' => 'bg-green-100 text-green-700', 'wont_fix' => 'bg-gray-100 text-gray-700'];
                                                            $statusClass = $statusColors[$issue['current_status']] ?? 'bg-gray-100 text-gray-700';
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
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
                                                            <button wire:click="cancelEditIssue" class="text-gray-400 hover:text-gray-600" title="{{ __('client_dashboard.seo.audit_cancel') }}">
                                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    @else
                                                        <button wire:click="startEditIssue({{ $issue['id'] }})" class="text-gray-400 hover:text-primary-600" title="{{ __('buttons.edit') }}">
                                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            {{-- Inline editing expanded row --}}
                                            @if($editingIssueId === $issue['id'])
                                                <tr class="bg-gray-50">
                                                    <td colspan="7" class="px-6 py-3">
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('client_dashboard.seo.notes') }}</label>
                                                        <textarea wire:model="editingIssueNotes" rows="2" class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500" placeholder="{{ __('client_dashboard.seo.notes') }}..."></textarea>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-10 px-6">
                                <x-heroicon-o-check-badge class="mx-auto h-10 w-10 text-green-300" />
                                <p class="mt-2 text-sm text-gray-500">{{ __('client_dashboard.seo.no_issues') }}</p>
                            </div>
                        @endif
                    </div>

                    </div>{{-- end audit sub-tab --}}

                    </div>{{-- end sub-tab x-data wrapper --}}
                @endif
            @else
                <div class="bg-white shadow rounded-lg p-12 text-center">
                    <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No SEO data available') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Configure SEO properties to view reports') }}</p>
                </div>
            @endif
        @endif

    @else
        {{-- Error State --}}
        <div class="text-center py-12">
            <p class="text-gray-500">{{ $error ?: __('Failed to load client dashboard') }}</p>
        </div>
    @endif
</div>
