<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-semibold text-neutral-900">{{ __('portal.dashboard.title') }}</h1>
        <p class="mt-1 text-sm text-neutral-500">{{ __('portal.dashboard.subtitle') }}</p>
    </div>

    @if(empty($stats))
        <div class="bg-white border border-neutral-200 rounded-xl p-10 text-center">
            <p class="text-sm text-neutral-500">No campaign data linked to your account yet. Contact your account manager.</p>
        </div>
    @else
        {{-- KPI Tiles --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white border border-neutral-200 rounded-xl p-5">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Spend</p>
                <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['spend'], 0) }}</p>
                <p class="text-xs text-neutral-400 mt-0.5">SAR · last 30 days</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Impressions</p>
                <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['impressions'] / 1000, 1) }}K</p>
                <p class="text-xs text-neutral-400 mt-0.5">last 30 days</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">CTR</p>
                <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ $stats['ctr'] }}%</p>
                <p class="text-xs text-neutral-400 mt-0.5">click-through rate</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">CPL</p>
                <p class="mt-1 text-2xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['cpl'], 2) }}</p>
                <p class="text-xs text-neutral-400 mt-0.5">SAR / lead</p>
            </div>
        </div>

        {{-- Secondary metrics --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white border border-neutral-200 rounded-xl p-4">
                <p class="text-xs text-neutral-500 uppercase tracking-wide font-medium">Clicks</p>
                <p class="mt-1 text-xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['clicks']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-4">
                <p class="text-xs text-neutral-500 uppercase tracking-wide font-medium">Leads</p>
                <p class="mt-1 text-xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['leads']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-4">
                <p class="text-xs text-neutral-500 uppercase tracking-wide font-medium">CPC</p>
                <p class="mt-1 text-xl font-semibold text-neutral-900 font-mono">{{ number_format($stats['cpc'], 2) }} <span class="text-sm font-normal text-neutral-400">SAR</span></p>
            </div>
        </div>
    @endif

    {{-- Quick links --}}
    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('portal.reports') }}"
           class="flex items-center gap-x-4 bg-white border border-neutral-200 rounded-xl p-5 hover:border-primary-300 transition-colors group">
            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900 group-hover:text-primary-700 transition-colors">Your Reports</p>
                <p class="text-xs text-neutral-400 mt-0.5">AI-generated performance reports</p>
            </div>
        </a>
        <a href="{{ route('portal.media-plans') }}"
           class="flex items-center gap-x-4 bg-white border border-neutral-200 rounded-xl p-5 hover:border-primary-300 transition-colors group">
            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900 group-hover:text-primary-700 transition-colors">Media Plans</p>
                <p class="text-xs text-neutral-400 mt-0.5">Approved campaign plans</p>
            </div>
        </a>
    </div>

</div>
