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
            @if(!empty($sources))
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 mt-1">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    {{ count($sources) }} sources — see below
                </span>
            @endif
        </div>

        {{-- Filters --}}
        <div class="filter-bar mb-6">
            {{-- Platform Tabs --}}
            <div class="flex items-center gap-1 bg-white/5 rounded-lg p-1 border border-white/8">
                @foreach($this->platforms as $slug => $label)
                    @if($slug === 'blended')
                        <button
                            wire:click="$set('selectedPlatform', '{{ $slug }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-all flex items-center gap-1.5
                                {{ $selectedPlatform === $slug ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-400 hover:text-white hover:bg-white/8' }}"
                        >
                            <svg class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            {{ $label }}
                        </button>
                        <div class="h-4 w-px bg-white/10 mx-0.5"></div>
                    @else
                        <button
                            wire:click="$set('selectedPlatform', '{{ $slug }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $selectedPlatform === $slug ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-400 hover:text-white hover:bg-white/8' }}"
                        >
                            {{ $label }}
                        </button>
                    @endif
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
                                                        <div class="text-sm font-semibold text-gray-200 font-mono">{{ $this->formatValue($metric, $b->percentile_25) }}</div>
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 px-1">
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-[11px] text-yellow-500/70 mb-0.5">{{ __('pages.global_benchmarks.percentile_avg') }}</div>
                                                        <div class="text-sm font-bold text-yellow-400 font-mono">{{ $this->formatValue($metric, $b->percentile_50) }}</div>
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 px-1">
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                        <div class="h-px w-3 bg-white/10"></div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-[11px] text-green-500/70 mb-0.5">{{ __('pages.global_benchmarks.percentile_excellent') }}</div>
                                                        <div class="text-sm font-semibold text-green-400 font-mono">{{ $this->formatValue($metric, $b->percentile_75) }}</div>
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
                <div class="px-5 py-3 border-t border-white/8 flex items-center gap-6 flex-wrap">
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

        {{-- Data Sources Panel --}}
        @if(!empty($sources))
            <div class="mt-6 rounded-xl border border-white/8 bg-white/3 px-5 py-4">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="h-3.5 w-3.5 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('pages.global_benchmarks.sources_label') }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($sources as $index => $source)
                        @php
                            $colors = ['brand', 'teal', 'indigo', 'violet', 'sky'];
                            $color = $colors[$index % count($colors)];
                            $url = $sourceUrlMap[$source] ?? null;
                            $targetPlatform = $sourcePlatformMap[$source] ?? null;
                            $isActive = $targetPlatform && $selectedPlatform === $targetPlatform;
                        @endphp
                        <div class="inline-flex items-stretch rounded-lg border overflow-hidden
                            {{ $isActive
                                ? ($color === 'brand' ? 'border-brand-400' : ($color === 'teal' ? 'border-teal-400' : ($color === 'indigo' ? 'border-indigo-400' : ($color === 'violet' ? 'border-violet-400' : 'border-sky-400'))))
                                : ($color === 'brand' ? 'border-brand-500/20' : ($color === 'teal' ? 'border-teal-500/20' : ($color === 'indigo' ? 'border-indigo-500/20' : ($color === 'violet' ? 'border-violet-500/20' : 'border-sky-500/20'))))
                            }}">
                            {{-- Filter button (switches platform tab) --}}
                            @if($targetPlatform)
                                <button
                                    wire:click="switchToSource('{{ $source }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium transition-all
                                        {{ $isActive
                                            ? ($color === 'brand' ? 'bg-brand-500/25 text-brand-300' : ($color === 'teal' ? 'bg-teal-500/25 text-teal-300' : ($color === 'indigo' ? 'bg-indigo-500/25 text-indigo-300' : ($color === 'violet' ? 'bg-violet-500/25 text-violet-300' : 'bg-sky-500/25 text-sky-300'))))
                                            : ($color === 'brand' ? 'bg-brand-500/10 text-brand-400 hover:bg-brand-500/20' : ($color === 'teal' ? 'bg-teal-500/10 text-teal-400 hover:bg-teal-500/20' : ($color === 'indigo' ? 'bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20' : ($color === 'violet' ? 'bg-violet-500/10 text-violet-400 hover:bg-violet-500/20' : 'bg-sky-500/10 text-sky-400 hover:bg-sky-500/20'))))
                                        }}"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full flex-shrink-0
                                        {{ $color === 'brand' ? 'bg-brand-400' : ($color === 'teal' ? 'bg-teal-400' : ($color === 'indigo' ? 'bg-indigo-400' : ($color === 'violet' ? 'bg-violet-400' : 'bg-sky-400'))) }}
                                    "></span>
                                    {{ $source }}
                                    @if($isActive)
                                        <svg class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    @endif
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                                    {{ $color === 'brand' ? 'bg-brand-500/10 text-brand-400' : ($color === 'teal' ? 'bg-teal-500/10 text-teal-400' : ($color === 'indigo' ? 'bg-indigo-500/10 text-indigo-400' : ($color === 'violet' ? 'bg-violet-500/10 text-violet-400' : 'bg-sky-500/10 text-sky-400'))) }}">
                                    <span class="h-1.5 w-1.5 rounded-full flex-shrink-0
                                        {{ $color === 'brand' ? 'bg-brand-400' : ($color === 'teal' ? 'bg-teal-400' : ($color === 'indigo' ? 'bg-indigo-400' : ($color === 'violet' ? 'bg-violet-400' : 'bg-sky-400'))) }}
                                    "></span>
                                    {{ $source }}
                                </span>
                            @endif
                            {{-- External link icon --}}
                            @if($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                   class="flex items-center px-1.5 border-l transition-opacity hover:opacity-80
                                    {{ $color === 'brand' ? 'border-brand-500/20 bg-brand-500/5 text-brand-500' : ($color === 'teal' ? 'border-teal-500/20 bg-teal-500/5 text-teal-500' : ($color === 'indigo' ? 'border-indigo-500/20 bg-indigo-500/5 text-indigo-500' : ($color === 'violet' ? 'border-violet-500/20 bg-violet-500/5 text-violet-500' : 'border-sky-500/20 bg-sky-500/5 text-sky-500'))) }}"
                                   title="Open source →"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="mt-2.5 text-[11px] text-gray-600">Benchmark values represent aggregated medians across these industry studies. P25 = bottom quartile, P50 = median, P75 = top quartile.</p>
            </div>
        @endif

    </div>
</div>
