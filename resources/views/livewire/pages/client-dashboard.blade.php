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
        <a href="{{ route('clients') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-200" wire:navigate>
            <x-heroicon-o-arrow-left class="w-4 h-4 me-1" />
            {{ __('common.back_to_clients') }}
        </a>
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
        </div>
    @elseif($dashboardData)
        {{-- Client Header --}}
        <div class="card p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="micro-label mb-1">{{ __("RB Benchmarks") }}</div>
                    <h1 class="section-title">{{ $dashboardData['client']['name'] }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $periodLabel }}</p>
                </div>
                <div class="flex items-center gap-x-3" x-data="{ open: false }">
                    <div class="relative">
                        <button @click="open = !open" :disabled="$wire.exporting" class="btn-primary">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 me-2" />
                            {{ $exporting ? __('common.exporting') : __('common.export') }}
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute end-0 z-10 mt-2 w-56 rounded-xl bg-gray-900 border border-white/10">
                            <div class="py-1">
                                <button wire:click="exportDashboard('pdf')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                    {{ __('common.export_as_pdf') }}
                                </button>
                                <button wire:click="exportDashboard('csv')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                    {{ __('common.export_as_csv') }}
                                </button>
                                <button wire:click="exportDashboard('excel')" @click="open = false" class="group flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5">
                                    {{ __('common.export_as_excel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-white/10 mb-6">
            <nav class="-mb-px flex gap-x-8" aria-label="{{ __('client_dashboard.tabs.label') }}">
                <button wire:click="setActiveTab(0)" @click="history.replaceState(null, '', window.location.pathname)" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 0 ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5 inline-block me-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.performance') }}
                </button>
                <button wire:click="setActiveTab(1)" @click="history.replaceState(null, '', window.location.pathname + '?seo')" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 1 ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15' }}">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 inline-block me-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.seo_report') }}
                </button>
                <button wire:click="setActiveTab(2)" @click="history.replaceState(null, '', window.location.pathname + '?reports')" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 2 ? 'border-brand-500 text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15' }}">
                    <svg class="w-5 h-5 inline-block me-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                    </svg>
                    Report Designer
                </button>
            </nav>
        </div>


        @include('livewire.pages.client-dashboard.performance-tab', get_defined_vars())

        @include('livewire.pages.client-dashboard.seo-tab', get_defined_vars())

        @include('livewire.pages.client-dashboard.reports-tab', get_defined_vars())

    @else
        {{-- Error State --}}
        <div class="empty-state">
            <p class="empty-message">{{ $error ?: __('Failed to load client dashboard') }}</p>
        </div>
    @endif
</div>
