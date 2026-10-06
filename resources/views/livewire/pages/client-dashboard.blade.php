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
            <x-heroicon-o-arrow-left class="w-4 h-4 me-1" />
            {{ __('common.back_to_clients') }}
        </a>
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
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
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute end-0 z-10 mt-2 w-56 rounded-xl bg-white border border-gray-200">
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
            <nav class="-mb-px flex gap-x-8" aria-label="{{ __('client_dashboard.tabs.label') }}">
                <button wire:click="setActiveTab(0)" @click="history.replaceState(null, '', window.location.pathname)" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 0 ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5 inline-block me-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.performance') }}
                </button>
                <button wire:click="setActiveTab(1)" @click="history.replaceState(null, '', window.location.pathname + '?seo')" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTabIndex === 1 ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 inline-block me-1.5 -mt-0.5" />
                    {{ __('client_dashboard.tabs.seo_report') }}
                </button>
            </nav>
        </div>


        @include('livewire.pages.client-dashboard.performance-tab', get_defined_vars())

        @include('livewire.pages.client-dashboard.seo-tab', get_defined_vars())

    @else
        {{-- Error State --}}
        <div class="empty-state">
            <p class="empty-message">{{ $error ?: __('Failed to load client dashboard') }}</p>
        </div>
    @endif
</div>
