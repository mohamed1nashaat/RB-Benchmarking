<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.global_benchmarks.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('pages.global_benchmarks.description') }}</p>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">

                {{-- Platform Tabs --}}
                <div class="flex items-center gap-1 flex-wrap">
                    @foreach($this->platforms as $slug => $label)
                        <button
                            wire:click="$set('selectedPlatform', '{{ $slug }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $selectedPlatform === $slug ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Industry Filter --}}
                <div class="flex items-center gap-2 ml-auto">
                    <label class="text-sm font-medium text-gray-600">{{ __('filters.industry') }}:</label>
                    <select
                        wire:model.live="selectedIndustry"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">{{ __('filters.all') }}</option>
                        @foreach($availableIndustries as $ind)
                            <option value="{{ $ind }}">{{ $this->industryLabels[$ind] ?? ucwords(str_replace('_', ' ', $ind)) }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>

        {{-- Benchmarks Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            @if(empty($tableData))
                <div class="text-center py-16 text-gray-400">
                    <x-heroicon-o-chart-bar class="h-12 w-12 mx-auto mb-3 opacity-40" />
                    <p class="text-sm">{{ __('pages.global_benchmarks.no_data') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-4 py-3 font-semibold text-gray-700 min-w-[180px]">{{ __('filters.industry') }}</th>
                                @foreach($this->metrics as $metric)
                                    <th class="text-center px-3 py-3 font-semibold text-gray-700 min-w-[130px]">
                                        {{ $this->metricLabels[$metric] ?? strtoupper($metric) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tableData as $industry => $metricData)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $this->industryLabels[$industry] ?? ucwords(str_replace('_', ' ', $industry)) }}
                                    </td>
                                    @foreach($this->metrics as $metric)
                                        <td class="px-3 py-3 text-center">
                                            @if(isset($metricData[$metric]))
                                                @php $b = $metricData[$metric]; @endphp
                                                <div class="flex flex-col items-center gap-0.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        <span class="mr-1 text-gray-400 text-[10px]">{{ __('pages.global_benchmarks.percentile_excellent') }}</span>
                                                        {{ number_format($b->percentile_75, 2) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <span class="mr-1 text-gray-400 text-[10px]">{{ __('pages.global_benchmarks.percentile_avg') }}</span>
                                                        {{ number_format($b->percentile_50, 2) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                                        <span class="mr-1 text-gray-400 text-[10px]">{{ __('pages.global_benchmarks.percentile_good') }}</span>
                                                        {{ number_format($b->percentile_25, 2) }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Sources Panel --}}
        @if(!empty($sources))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('pages.global_benchmarks.sources_label') }}</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($sources as $source)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            <x-heroicon-o-document-text class="h-3 w-3 mr-1.5 opacity-60" />
                            {{ $source }}
                        </span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-gray-400">
                    Values shown are P75 (Excellent) / P50 (Avg) / P25 (Good) percentiles aggregated across the listed sources.
                </p>
            </div>
        @endif

    </div>
</div>
