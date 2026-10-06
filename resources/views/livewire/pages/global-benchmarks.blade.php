<div class="min-h-screen">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Page Header --}}
        <div class="mb-8 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-widest bg-brand-500/15 text-brand-400 border border-brand-500/20">
                        {{ __('pages.global_benchmarks.sources_label') }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ __('pages.global_benchmarks.title') }}</h1>
                <p class="mt-1 text-sm text-gray-400 max-w-lg">{{ __('pages.global_benchmarks.description') }}</p>
            </div>
            {{-- Source chips --}}
            @if(!empty($sources))
                <div class="flex flex-wrap gap-1.5 max-w-xs">
                    @foreach($sources as $source)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-medium bg-white/5 text-gray-400 border border-white/8">
                            <span class="h-1.5 w-1.5 rounded-full bg-brand-500 flex-shrink-0"></span>
                            {{ $source }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Filters --}}
        <div class="filter-bar mb-6">
            {{-- Platform Tabs --}}
            <div class="flex items-center gap-1 bg-white/5 rounded-lg p-1 border border-white/8">
                @foreach($this->platforms as $slug => $label)
                    <button
                        wire:click="$set('selectedPlatform', '{{ $slug }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $selectedPlatform === $slug ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-400 hover:text-white hover:bg-white/8' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Industry Filter --}}
            <div x-data="{ open: false }" class="relative ms-auto">
                <button
                    @click="open = !open"
                    :class="open ? 'is-active' : ''"
                    class="filter-trigger w-48"
                >
                    <span class="truncate">
                        @if($selectedIndustry)
                            {{ $this->industryLabels[$selectedIndustry] ?? ucwords(str_replace('_', ' ', $selectedIndustry)) }}
                        @else
                            {{ __('filters.all') }} {{ __('filters.industry') }}
                        @endif
                    </span>
                    <svg :class="{'rotate-180': open}" class="h-3.5 w-3.5 flex-shrink-0 transition-transform duration-150 text-gray-500 ms-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="dropdown-menu absolute end-0 top-full mt-1 z-30 w-56">
                    <button wire:click="$set('selectedIndustry', '')" @click="open = false"
                            class="dropdown-item w-full text-start {{ !$selectedIndustry ? 'text-white' : '' }}">
                        {{ __('filters.all') }} {{ __('filters.industry') }}
                    </button>
                    @foreach($availableIndustries as $ind)
                        <button wire:click="$set('selectedIndustry', '{{ $ind }}')" @click="open = false"
                                class="dropdown-item w-full text-start {{ $selectedIndustry === $ind ? 'text-white' : '' }}">
                            {{ $this->industryLabels[$ind] ?? ucwords(str_replace('_', ' ', $ind)) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Benchmarks Table --}}
        <div class="card overflow-hidden">
            @if(empty($tableData))
                <div class="text-center py-20 text-gray-400">
                    <div class="h-14 w-14 rounded-xl bg-white/5 flex items-center justify-center mx-auto mb-4">
                        <x-heroicon-o-chart-bar class="h-7 w-7 opacity-40" />
                    </div>
                    <p class="text-sm font-medium">{{ __('pages.global_benchmarks.no_data') }}</p>
                    <p class="text-xs text-gray-500 mt-1">Try selecting a different platform</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <th class="text-start px-5 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-400 min-w-[180px]">{{ __('filters.industry') }}</th>
                                @foreach($this->metrics as $metric)
                                    <th class="text-center px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-400 min-w-[120px]">
                                        {{ $this->metricLabels[$metric] ?? strtoupper($metric) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tableData as $industry => $metricData)
                                <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors last:border-0">
                                    <td class="px-5 py-4 font-medium text-white">
                                        {{ $this->industryLabels[$industry] ?? ucwords(str_replace('_', ' ', $industry)) }}
                                    </td>
                                    @foreach($this->metrics as $metric)
                                        <td class="px-4 py-4 text-center">
                                            @if(isset($metricData[$metric]))
                                                @php $b = $metricData[$metric]; @endphp
                                                <div class="inline-flex items-center gap-1.5">
                                                    <div class="text-center">
                                                        <div class="text-[11px] text-gray-500 mb-0.5">{{ __('pages.global_benchmarks.percentile_good') }}</div>
                                                        <div class="text-sm font-semibold text-gray-200">{{ number_format($b->percentile_25, 2) }}</div>
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 px-1">
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-[11px] text-yellow-500/70 mb-0.5">{{ __('pages.global_benchmarks.percentile_avg') }}</div>
                                                        <div class="text-sm font-bold text-yellow-400">{{ number_format($b->percentile_50, 2) }}</div>
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 px-1">
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-[11px] text-green-500/70 mb-0.5">{{ __('pages.global_benchmarks.percentile_excellent') }}</div>
                                                        <div class="text-sm font-semibold text-green-400">{{ number_format($b->percentile_75, 2) }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-600">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="px-5 py-3 border-t border-white/8 flex items-center gap-6">
                    <span class="text-xs text-gray-500">Reading each cell left → right:</span>
                    <div class="flex items-center gap-1.5">
                        <div class="h-1.5 w-1.5 rounded-full bg-gray-400"></div>
                        <span class="text-xs text-gray-400">{{ __('pages.global_benchmarks.percentile_good') }} <span class="text-gray-600">(P25)</span></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-1.5 w-1.5 rounded-full bg-yellow-400"></div>
                        <span class="text-xs text-gray-400">{{ __('pages.global_benchmarks.percentile_avg') }} <span class="text-gray-600">(P50)</span></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-1.5 w-1.5 rounded-full bg-green-400"></div>
                        <span class="text-xs text-gray-400">{{ __('pages.global_benchmarks.percentile_excellent') }} <span class="text-gray-600">(P75)</span></span>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
