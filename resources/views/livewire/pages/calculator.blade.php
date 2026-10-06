<div class="min-h-screen bg-gray-950"
     x-data="{
         budget: @entangle('monthlyBudget'),
         init() {
             this.budget = this.budget ?? '';
         }
     }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Page Header + Mode Toggle --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-white">{{ __('pages.calculator.hero_title') }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ __('pages.calculator.hero_description') }}</p>
            </div>
            {{-- Simple / Advanced toggle --}}
            <div class="flex items-center gap-1 bg-white/5 rounded-lg p-1 self-start sm:self-auto">
                <button type="button"
                        wire:click="$set('viewMode', 'simple')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors {{ $viewMode === 'simple' ? 'bg-gray-900 text-white border border-white/10' : 'text-gray-500 hover:text-gray-200' }}">
                    {{ __('pages.calculator.mode_simple') }}
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'advanced')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors {{ $viewMode === 'advanced' ? 'bg-gray-900 text-white border border-white/10' : 'text-gray-500 hover:text-gray-200' }}">
                    {{ __('pages.calculator.mode_advanced') }}
                </button>
            </div>
        </div>

        @if($viewMode === 'simple')
            {{-- ═══════════════════════════════════════════════════════
                 SIMPLE MODE
            ═══════════════════════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                {{-- Input Card --}}
                <div class="lg:col-span-4">
                    <div class="card">
                        <div class="px-5 pt-5 pb-4 border-b border-white/10">
                            <h3 class="text-base font-semibold text-white">{{ __('pages.calculator.inputs_title') }}</h3>
                        </div>
                        <div class="px-5 py-5">
                            <form wire:submit="calculate" class="space-y-5">

                                {{-- Industry --}}
                                <x-select2
                                    wire-model="industry"
                                    :label="__('labels.industry')"
                                    :placeholder="__('filters.select_industry')"
                                    :options="$this->availableIndustries"
                                    :allowClear="false"
                                    :searchable="true"
                                />
                                @error('industry') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

                                {{-- Objective --}}
                                <x-select2
                                    wire-model="objective"
                                    :label="__('labels.objective')"
                                    :placeholder="__('filters.select_objective')"
                                    :options="$availableObjectives"
                                    :allowClear="false"
                                    :searchable="true"
                                />
                                @error('objective') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

                                {{-- Budget or Results toggle --}}
                                <div>
                                    <label class="micro-label block mb-2">{{ __('pages.calculator.calculation_mode') }}</label>
                                    <div class="flex">
                                        @foreach(array_keys($availableModes) as $mode)
                                            <button type="button"
                                                    wire:click="$set('calculationMode', '{{ $mode }}')"
                                                    class="flex-1 px-3 py-2 text-sm border focus:outline-none focus:ring-2 focus:ring-gray-900/10
                                                           {{ $calculationMode === $mode ? 'bg-gray-900 border-gray-900 text-white font-semibold' : 'bg-gray-900 border-white/10 text-gray-200 font-medium hover:bg-gray-950' }}
                                                           {{ $loop->first ? 'rounded-s-md' : '' }}
                                                           {{ $loop->last ? 'rounded-e-md' : '' }}
                                                           {{ !$loop->first ? '-ms-px' : '' }}">
                                                {{ __('pages.calculator.modes.' . $mode) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Budget amount --}}
                                @if($calculationMode === 'target_to_budget')
                                    <div>
                                        <label class="micro-label block mb-1">{{ __('pages.calculator.target_results') }}</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-semibold">#</span>
                                            <input type="number" wire:model.live.debounce.300ms="targetResults" min="1"
                                                   class="input-field pl-10" placeholder="500">
                                        </div>
                                        @error('targetResults') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @else
                                    <div>
                                        <label class="micro-label block mb-1">{{ __('pages.calculator.monthly_budget') }}</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-semibold">SAR</span>
                                            <input type="number" min="1"
                                                   x-model.number="budget"
                                                   class="input-field pl-12" placeholder="10000">
                                        </div>
                                        <div class="mt-3">
                                            <input type="range" min="1000" max="1000000" step="1000"
                                                   x-model.number="budget"
                                                   class="w-full h-2 bg-white/8 rounded-lg appearance-none cursor-pointer accent-brand-600">
                                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                                <span>1K</span><span>250K</span><span>500K</span><span>1M</span>
                                            </div>
                                        </div>
                                        @error('monthlyBudget') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <button type="submit" wire:loading.attr="disabled" wire:target="calculate"
                                        class="btn-primary w-full">
                                    <x-heroicon-o-sparkles class="h-4 w-4 me-2" wire:loading.remove wire:target="calculate" />
                                    <svg wire:loading wire:target="calculate" class="animate-spin -ms-1 me-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="calculate">{{ __('pages.calculator.forecast_cta') }}</span>
                                    <span wire:loading wire:target="calculate">{{ __('pages.calculator.calculating') }}...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Simple Results --}}
                <div class="lg:col-span-8">

                    @if($loading)
                        <div class="card p-10 flex items-center justify-center">
                            <svg class="animate-spin h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    @elseif($error)
                        <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-4">
                            <div class="flex">
                                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400 flex-shrink-0" />
                                <div class="ms-3">
                                    <p class="text-sm font-medium text-red-800">{{ __('pages.calculator.calculation_error') }}</p>
                                    <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                                </div>
                            </div>
                        </div>
                    @elseif($hasResult && !empty($result['predictions']))
                        @php
                            $poorPrediction      = $result['predictions']['poor'] ?? [];
                            $excellentPrediction = $result['predictions']['excellent'] ?? [];
                            $averagePrediction   = $result['predictions']['average'] ?? [];
                            $primaryLabel        = ($averagePrediction['primary_result']['label'] ?? null)
                                                    ?: $this->getObjectivePrimaryLabel($result['input']['objective'] ?? $objective);
                            $resultMode          = $result['input']['mode'] ?? $calculationMode;
                        @endphp

                        {{-- Estimated Budget Range (target mode) --}}
                        @if(!empty($result['budget']))
                            <div class="card p-6 mb-4">
                                <p class="text-sm text-gray-500 mb-1">{{ __('pages.calculator.estimated_budget') }}</p>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-2xl font-bold text-white">{{ $this->formatCurrency($result['budget']['poor'] ?? 0) }}</span>
                                    <span class="text-gray-400 text-xl">→</span>
                                    <span class="text-2xl font-bold text-white">{{ $this->formatCurrency($result['budget']['excellent'] ?? 0) }}</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">{{ __('pages.calculator.budget_range') }} ({{ __('pages.calculator.scenarios.poor') }} → {{ __('pages.calculator.scenarios.excellent') }})</p>
                            </div>
                        @endif

                        {{-- Results Range Cards --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                            {{-- Primary Result Range --}}
                            <div class="card p-5 sm:col-span-3">
                                <p class="text-sm font-medium text-gray-500 mb-3">{{ $primaryLabel }} {{ __('pages.calculator.range') }}</p>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-center">
                                        <p class="text-xs text-red-500 font-medium mb-1">{{ __('pages.calculator.scenarios.poor') }}</p>
                                        <p class="text-2xl font-bold text-white">{{ $this->formatNumber($poorPrediction['primary_result']['value'] ?? 0) }}</p>
                                    </div>
                                    <div class="flex-1 h-2 bg-gradient-to-r from-red-200 via-yellow-200 to-green-200 rounded-full"></div>
                                    <div class="text-center">
                                        <p class="text-xs text-amber-500 font-medium mb-1">{{ __('pages.calculator.scenarios.average') }}</p>
                                        <p class="text-2xl font-bold text-white">{{ $this->formatNumber($averagePrediction['primary_result']['value'] ?? 0) }}</p>
                                    </div>
                                    <div class="flex-1 h-2 bg-gradient-to-r from-yellow-200 to-green-200 rounded-full"></div>
                                    <div class="text-center">
                                        <p class="text-xs text-green-600 font-medium mb-1">{{ __('pages.calculator.scenarios.excellent') }}</p>
                                        <p class="text-2xl font-bold text-white">{{ $this->formatNumber($excellentPrediction['primary_result']['value'] ?? 0) }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Impressions --}}
                            <div class="card p-4">
                                <p class="text-xs text-gray-500 mb-1">{{ __('kpis.impressions') }}</p>
                                <p class="text-sm font-semibold text-gray-500">{{ $this->formatNumber($poorPrediction['impressions']['value'] ?? 0) }}</p>
                                <p class="text-xs text-gray-400">→</p>
                                <p class="text-lg font-bold text-white">{{ $this->formatNumber($excellentPrediction['impressions']['value'] ?? 0) }}</p>
                            </div>

                            {{-- Clicks --}}
                            <div class="card p-4">
                                <p class="text-xs text-gray-500 mb-1">{{ __('kpis.clicks') }}</p>
                                <p class="text-sm font-semibold text-gray-500">{{ $this->formatNumber($poorPrediction['clicks']['value'] ?? 0) }}</p>
                                <p class="text-xs text-gray-400">→</p>
                                <p class="text-lg font-bold text-white">{{ $this->formatNumber($excellentPrediction['clicks']['value'] ?? 0) }}</p>
                            </div>

                            {{-- Cost per result --}}
                            <div class="card p-4">
                                <p class="text-xs text-gray-500 mb-1">{{ __('pages.calculator.cost_per_result') }}</p>
                                <p class="text-sm font-semibold text-gray-500">{{ $excellentPrediction['cost_per_result']['cost_per_conversion'] ?? '-' }}</p>
                                <p class="text-xs text-gray-400">→</p>
                                <p class="text-lg font-bold text-white">{{ $poorPrediction['cost_per_result']['cost_per_conversion'] ?? '-' }}</p>
                            </div>
                        </div>

                        {{-- Hint to switch to advanced --}}
                        <p class="text-xs text-gray-400 text-center">
                            {{ __('pages.calculator.advanced_hint') }}
                            <button type="button" wire:click="$set('viewMode', 'advanced')" class="text-brand-400 hover:underline font-medium">{{ __('pages.calculator.mode_advanced') }}</button>
                        </p>
                    @else
                        <div class="card">
                            <div class="empty-state">
                                <div class="mx-auto h-12 w-12 rounded-xl bg-white/5 flex items-center justify-center mb-4">
                                    <x-heroicon-o-calculator class="h-6 w-6 text-gray-300" />
                                </div>
                                <h3 class="empty-title">{{ __('pages.calculator.empty_title') }}</h3>
                                <p class="empty-message">{{ __('pages.calculator.empty_description') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        @else
            {{-- ═══════════════════════════════════════════════════════
                 ADVANCED MODE (full experience)
            ═══════════════════════════════════════════════════════ --}}
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

                {{-- Input Panel --}}
                <div class="xl:col-span-4">
                    <div class="card">
                        <div class="px-5 pt-5 pb-4 border-b border-white/10">
                            <h3 class="text-lg font-semibold tracking-tight text-white">
                                {{ __('pages.calculator.inputs_title') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('pages.calculator.description') }}
                            </p>
                        </div>

                        <div class="px-5 py-5">
                            <form wire:submit="calculate" class="space-y-6">

                                {{-- Mode Toggle --}}
                                <div>
                                    <label class="micro-label block mb-2">
                                        {{ __('pages.calculator.calculation_mode') }}
                                    </label>
                                    <div class="flex">
                                        @foreach(array_keys($availableModes) as $mode)
                                            <button type="button"
                                                    wire:click="$set('calculationMode', '{{ $mode }}')"
                                                    class="flex-1 px-4 py-2 text-sm border focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:z-10
                                                           {{ $calculationMode === $mode ? 'bg-gray-900 border-gray-900 text-white font-semibold' : 'bg-gray-900 border-white/10 text-gray-200 font-medium hover:bg-gray-950' }}
                                                           {{ $loop->first ? 'rounded-s-md' : '' }}
                                                           {{ $loop->last ? 'rounded-e-md' : '' }}
                                                           {{ !$loop->first ? '-ms-px' : '' }}">
                                                {{ __('pages.calculator.modes.' . $mode) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Campaign Settings --}}
                                <div class="space-y-4">
                                    <h4 class="micro-label">{{ __('pages.calculator.section_campaign') }}</h4>
                                    <div class="space-y-4">
                                        <x-select2
                                            wire-model="industry"
                                            :label="__('labels.industry')"
                                            :placeholder="__('filters.select_industry')"
                                            :options="$this->availableIndustries"
                                            :allowClear="false"
                                            :searchable="true"
                                        />
                                        @error('industry') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

                                        <x-select2
                                            wire-model="objective"
                                            :label="__('labels.objective')"
                                            :placeholder="__('filters.select_objective')"
                                            :options="$availableObjectives"
                                            :allowClear="false"
                                            :searchable="true"
                                        />
                                        @error('objective') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

                                        <x-select2-multi
                                            wireModel="platform"
                                            :label="__('labels.platform')"
                                            :placeholder="__('filters.all_platforms')"
                                            :options="$availablePlatforms"
                                        />
                                        @error('platform') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Targeting & Funnel --}}
                                <div class="space-y-4">
                                    <h4 class="micro-label">{{ __('pages.calculator.section_targeting') }}</h4>
                                    <div class="space-y-4">
                                        <x-select2
                                            wire-model="country"
                                            :label="__('labels.country')"
                                            :placeholder="__('filters.all_countries')"
                                            :options="$this->availableCountries"
                                            :allowClear="true"
                                            :searchable="true"
                                        />
                                        @error('country') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

                                        <div class="grid grid-cols-2 gap-4">
                                            <x-select2
                                                wire-model="funnelStage"
                                                :label="__('funnel_stages.label')"
                                                :placeholder="__('filters.all_stages')"
                                                :options="$availableFunnelStages"
                                                :allowClear="true"
                                                :searchable="false"
                                            />
                                            <x-select2
                                                wire-model="userJourney"
                                                :label="__('pages.calculator.user_journey')"
                                                :placeholder="__('filters.all_journeys')"
                                                :options="$availableUserJourneys"
                                                :allowClear="true"
                                                :searchable="false"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- Budget / Target --}}
                                <div class="space-y-4">
                                    <h4 class="micro-label">
                                        {{ $calculationMode === 'target_to_budget' ? __('pages.calculator.section_target_budget') : __('pages.calculator.section_budget') }}
                                    </h4>

                                    @if($calculationMode === 'target_to_budget')
                                        <div>
                                            <label class="micro-label block mb-1">{{ __('pages.calculator.target_results') }}</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-semibold">#</span>
                                                <input type="number" wire:model.live.debounce.300ms="targetResults" min="1"
                                                       class="input-field pl-10" placeholder="500">
                                            </div>
                                            @error('targetResults') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <div>
                                            <label class="micro-label block mb-1">{{ __('pages.calculator.monthly_budget') }}</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-semibold">SAR</span>
                                                <input type="number" min="1"
                                                       x-model.number="budget"
                                                       class="input-field pl-12" placeholder="10000">
                                            </div>
                                            <div class="mt-4">
                                                <input type="range" min="1000" max="1000000" step="1000"
                                                       x-model.number="budget"
                                                       class="w-full h-2 bg-white/8 rounded-lg appearance-none cursor-pointer accent-brand-600">
                                                <div class="flex justify-between text-xs text-gray-500 mt-1.5">
                                                    <span>1K</span><span>250K</span><span>500K</span><span>750K</span><span>1M</span>
                                                </div>
                                            </div>
                                            @error('monthlyBudget') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>

                                {{-- CTA --}}
                                <button type="submit" wire:loading.attr="disabled" wire:target="calculate"
                                        class="btn-primary w-full">
                                    <x-heroicon-o-sparkles class="h-4 w-4 me-2" wire:loading.remove wire:target="calculate" />
                                    <svg wire:loading wire:target="calculate" class="animate-spin -ms-1 me-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="calculate">{{ __('pages.calculator.forecast_cta') }}</span>
                                    <span wire:loading wire:target="calculate">{{ __('pages.calculator.calculating') }}...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Results --}}
                <div class="xl:col-span-8 space-y-6">

                    @if($loading)
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="card">
                                        <div class="p-5 animate-pulse">
                                            <div class="flex items-center">
                                                <div class="h-12 w-12 bg-white/8 rounded-lg"></div>
                                                <div class="ms-5 flex-1 space-y-2">
                                                    <div class="h-3 bg-white/8 rounded w-20"></div>
                                                    <div class="h-6 bg-white/8 rounded w-24"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endif

                    @if($error)
                        <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                                </div>
                                <div class="ms-3">
                                    <h3 class="text-sm font-medium text-red-800">{{ __('pages.calculator.calculation_error') }}</h3>
                                    <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                                    <p class="text-sm text-red-600 mt-2">{{ __('pages.calculator.try_another') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($hasResult && !empty($result['predictions']) && !$loading)
                        @php
                            $averagePrediction = $result['predictions']['average'] ?? [];
                            $primaryResult = $averagePrediction['primary_result'] ?? [];
                            $primaryLabel = $primaryResult['label'] ?? $this->getObjectivePrimaryLabel($result['input']['objective'] ?? $objective);
                            $costPerResult = $averagePrediction['cost_per_result']['cost_per_conversion'] ?? null;
                            $resultMode = $result['input']['mode'] ?? $calculationMode;
                        @endphp

                        {{-- Input Summary --}}
                        <div class="card p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                    <x-heroicon-o-building-office class="h-4 w-4" />
                                    {{ $this->getIndustryLabel($result['input']['industry'] ?? $industry) }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                    <x-heroicon-o-flag class="h-4 w-4" />
                                    {{ $availableObjectives[$result['input']['objective'] ?? $objective] ?? ucfirst($result['input']['objective'] ?? $objective) }}
                                </span>
                                @php
                                    $resultPlatforms = $result['input']['filters']['platform'] ?? $platform ?? [];
                                    $resultCountry   = $result['input']['filters']['country'][0] ?? $country ?? null;
                                    $resultFunnel    = $result['input']['filters']['funnel_stage'][0] ?? $funnelStage ?? null;
                                    $resultJourney   = $result['input']['filters']['user_journey'][0] ?? $userJourney ?? null;
                                @endphp
                                @foreach((array) $resultPlatforms as $resultPlatform)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        <x-heroicon-o-computer-desktop class="h-4 w-4" />
                                        {{ $availablePlatforms[$resultPlatform] ?? ucfirst($resultPlatform) }}
                                    </span>
                                @endforeach
                                @if($resultCountry)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        <x-heroicon-o-globe-alt class="h-4 w-4" />
                                        {{ $resultCountry }}
                                    </span>
                                @endif
                                @if($resultFunnel)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        <x-heroicon-o-funnel class="h-4 w-4" />
                                        {{ $availableFunnelStages[$resultFunnel] ?? $resultFunnel }}
                                    </span>
                                @endif
                                @if($resultJourney)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        <x-heroicon-o-user-group class="h-4 w-4" />
                                        {{ $availableUserJourneys[$resultJourney] ?? ucfirst(str_replace('_', ' ', $resultJourney)) }}
                                    </span>
                                @endif
                                @if($resultMode === 'target_to_budget')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-400">
                                        <x-heroicon-o-flag class="h-4 w-4" />
                                        {{ $this->formatNumber($result['input']['target'] ?? ($targetResults ?? 0)) }} {{ $primaryLabel }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-400">
                                        <x-heroicon-o-currency-dollar class="h-4 w-4" />
                                        {{ $this->formatCurrency($result['input']['spend'] ?? ($monthlyBudget ?? 0)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Recommended Budget (target mode) --}}
                        @if(!empty($result['budget']))
                            <div class="kpi-card">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-xl bg-white/5 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-currency-dollar class="h-6 w-6 text-gray-300" />
                                    </div>
                                    <div class="w-0 flex-1 min-w-0">
                                        <p class="kpi-label truncate">{{ __('pages.calculator.estimated_budget') }}</p>
                                        <p class="kpi-value">{{ $this->formatCurrency($result['budget']['recommended']) }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-x-2 text-sm text-gray-500">
                                    <span>{{ __('pages.calculator.budget_range') }}:</span>
                                    <span class="font-medium text-white">{{ $this->formatCurrency($result['budget']['poor'] ?? 0) }}</span>
                                    <span>&rarr;</span>
                                    <span class="font-medium text-white">{{ $this->formatCurrency($result['budget']['excellent'] ?? 0) }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- KPI Summary --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"
                             x-data="{
                                 counters: [0, 0, 0, 0],
                                 targets: [
                                    {{ $averagePrediction['impressions']['value'] ?? 0 }},
                                    {{ $averagePrediction['clicks']['value'] ?? 0 }},
                                    {{ $averagePrediction['primary_result']['value'] ?? 0 }},
                                    {{ is_numeric($costPerResult) ? (float) str_replace(['SAR ', ','], '', $costPerResult) : 0 }}
                                 ],
                                 duration: 900,
                                 init() { this.animate(); },
                                 animate() {
                                     const start = performance.now();
                                     const step = (now) => {
                                         const progress = Math.min((now - start) / this.duration, 1);
                                         const ease = 1 - Math.pow(1 - progress, 3);
                                         this.counters = this.targets.map(t => Math.round(t * ease));
                                         if (progress < 1) requestAnimationFrame(step);
                                     };
                                     requestAnimationFrame(step);
                                 }
                             }"
                             x-init="init()">
                            @php
                                $kpis = [
                                    ['icon' => 'eye', 'label' => __('kpis.impressions'), 'value' => $averagePrediction['impressions']['value'] ?? 0, 'format' => 'number'],
                                    ['icon' => 'cursor-arrow-rays', 'label' => __('kpis.clicks'), 'value' => $averagePrediction['clicks']['value'] ?? 0, 'format' => 'number'],
                                    ['icon' => 'check-circle', 'label' => $primaryLabel, 'value' => $averagePrediction['primary_result']['value'] ?? 0, 'format' => 'number'],
                                    ['icon' => 'banknotes', 'label' => __('pages.calculator.cost_per_result'), 'value' => is_numeric($costPerResult) ? (float) str_replace(['SAR ', ','], '', $costPerResult) : 0, 'format' => 'currency'],
                                ];
                            @endphp
                            @foreach($kpis as $index => $kpi)
                                <div class="kpi-card">
                                    <div class="flex items-center gap-4">
                                        <div class="h-11 w-11 rounded-xl bg-white/5 flex items-center justify-center flex-shrink-0">
                                            @if($kpi['icon'] === 'eye') <x-heroicon-o-eye class="h-6 w-6 text-gray-300" />
                                            @elseif($kpi['icon'] === 'cursor-arrow-rays') <x-heroicon-o-cursor-arrow-rays class="h-6 w-6 text-gray-300" />
                                            @elseif($kpi['icon'] === 'check-circle') <x-heroicon-o-check-circle class="h-6 w-6 text-gray-300" />
                                            @else <x-heroicon-o-banknotes class="h-6 w-6 text-gray-300" />
                                            @endif
                                        </div>
                                        <div class="w-0 flex-1 min-w-0">
                                            <p class="kpi-label truncate">{{ $kpi['label'] }}</p>
                                            <p class="kpi-value">
                                                @if($kpi['format'] === 'currency')
                                                    <span x-text="'SAR ' + counters[{{ $index }}].toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                                @else
                                                    <span x-text="counters[{{ $index }}].toLocaleString('en-US')"></span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Chart --}}
                        @php $chartData = $this->chartData; @endphp
                        @if(!empty($chartData))
                            <div class="card"
                                 x-data="{
                                     chart: null,
                                     init() {
                                         const ctx = this.$refs.scenarioChart.getContext('2d');
                                         this.chart = new Chart(ctx, {
                                             type: 'bar',
                                             data: @js($chartData),
                                             options: {
                                                 responsive: true,
                                                 maintainAspectRatio: false,
                                                 plugins: {
                                                     legend: { position: 'top' },
                                                     tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + c.parsed.y.toLocaleString('en-US'); } } }
                                                 },
                                                 scales: {
                                                     y: { beginAtZero: true, ticks: { callback: (v) => v >= 1000 ? (v/1000)+'K' : v } }
                                                 }
                                             }
                                         });
                                     },
                                     destroy() { if (this.chart) { this.chart.destroy(); this.chart = null; } }
                                 }"
                                 x-init="init()">
                                <div class="px-5 py-4 border-b border-white/10">
                                    <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.calculator.scenario_comparison') }}</h3>
                                </div>
                                <div class="p-5">
                                    <div class="relative h-80">
                                        <canvas x-ref="scenarioChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Scenario Cards --}}
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-white mb-4">{{ __('pages.calculator.scenario_forecasts') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                                @php
                                    $scenarioConfig = [
                                        'poor'      => ['label' => __('pages.calculator.scenarios.poor'), 'icon' => 'arrow-trending-down'],
                                        'average'   => ['label' => __('pages.calculator.scenarios.average'), 'icon' => 'minus'],
                                        'good'      => ['label' => __('pages.calculator.scenarios.good'), 'icon' => 'arrow-trending-up'],
                                        'excellent' => ['label' => __('pages.calculator.scenarios.excellent'), 'icon' => 'rocket-launch'],
                                    ];
                                @endphp
                                @foreach($result['predictions'] as $scenario => $prediction)
                                    @php $config = $scenarioConfig[$scenario] ?? ['label' => ucfirst($scenario), 'icon' => 'chart-bar']; @endphp
                                    <div class="card cursor-pointer {{ $selectedScenario === $scenario ? 'ring-2 ring-gray-900' : '' }}"
                                         wire:click="$set('selectedScenario', '{{ $scenario }}')">
                                        <div class="px-4 py-3 bg-gray-950 border-b border-white/10 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                @if($config['icon'] === 'arrow-trending-down') <x-heroicon-o-arrow-trending-down class="h-5 w-5 text-gray-300" />
                                                @elseif($config['icon'] === 'minus') <x-heroicon-o-minus class="h-5 w-5 text-gray-300" />
                                                @elseif($config['icon'] === 'arrow-trending-up') <x-heroicon-o-arrow-trending-up class="h-5 w-5 text-gray-300" />
                                                @elseif($config['icon'] === 'rocket-launch') <x-heroicon-o-rocket-launch class="h-5 w-5 text-gray-300" />
                                                @else <x-heroicon-o-chart-bar class="h-5 w-5 text-gray-300" />
                                                @endif
                                                <span class="font-semibold text-sm text-white">{{ $config['label'] }}</span>
                                            </div>
                                            @if($selectedScenario === $scenario)
                                                <span class="inline-flex items-center rounded-full bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('pages.calculator.selected') }}</span>
                                            @endif
                                        </div>
                                        <div class="p-5 space-y-4">
                                            <div class="text-center">
                                                <p class="text-sm text-gray-500">{{ $prediction['primary_result']['label'] ?? $primaryLabel }}</p>
                                                <p class="stat-num">{{ $this->formatNumber($prediction['primary_result']['value'] ?? 0) }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">{{ __('kpis.impressions') }}</p>
                                                    <p class="font-semibold text-white">{{ $this->formatNumber($prediction['impressions']['value'] ?? 0) }}</p>
                                                </div>
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">{{ __('kpis.clicks') }}</p>
                                                    <p class="font-semibold text-white">{{ $this->formatNumber($prediction['clicks']['value'] ?? 0) }}</p>
                                                </div>
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">CTR</p>
                                                    <p class="font-semibold text-white">{{ $prediction['metrics']['ctr'] ?? '-' }}</p>
                                                </div>
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">CPC</p>
                                                    <p class="font-semibold text-white">{{ $prediction['metrics']['cpc'] ?? '-' }}</p>
                                                </div>
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">CPM</p>
                                                    <p class="font-semibold text-white">{{ $prediction['metrics']['cpm'] ?? '-' }}</p>
                                                </div>
                                                <div class="bg-gray-950 rounded-lg p-2">
                                                    <p class="text-xs text-gray-500">CVR</p>
                                                    <p class="font-semibold text-white">{{ $prediction['metrics']['cvr'] ?? '-' }}</p>
                                                </div>
                                            </div>
                                            <div class="pt-3 border-t border-white/10">
                                                <p class="text-xs text-gray-500">{{ __('pages.calculator.cost_per_result') }}</p>
                                                <p class="text-lg font-semibold text-white">{{ $prediction['cost_per_result']['cost_per_conversion'] ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Global Benchmark Comparison --}}
                        @if(!empty($result['global_predictions']))
                            @php
                                $rbAverage     = $result['predictions']['average'] ?? [];
                                $globalAverage = $result['global_predictions']['average'] ?? [];
                            @endphp
                            <div class="card">
                                <div class="px-5 py-4 border-b border-white/10">
                                    <h3 class="text-base font-semibold tracking-tight text-white flex items-center gap-2">
                                        <x-heroicon-o-globe-alt class="h-5 w-5 text-gray-300" />
                                        {{ __('pages.calculator.global_comparison') }}
                                    </h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="data-table min-w-full">
                                        <thead>
                                            <tr>
                                                <th>{{ __('pages.calculator.metric') }}</th>
                                                <th>{{ __('pages.calculator.redbananas_benchmarks') }}</th>
                                                <th>{{ __('pages.calculator.global_benchmarks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="font-medium text-white">{{ $primaryLabel }}</td>
                                                <td class="font-semibold text-white">{{ $this->formatNumber($rbAverage['primary_result']['value'] ?? 0) }}</td>
                                                <td class="font-semibold text-white">{{ $this->formatNumber($globalAverage['primary_result']['value'] ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('kpis.impressions') }}</td>
                                                <td>{{ $this->formatNumber($rbAverage['impressions']['value'] ?? 0) }}</td>
                                                <td>{{ $this->formatNumber($globalAverage['impressions']['value'] ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('kpis.clicks') }}</td>
                                                <td>{{ $this->formatNumber($rbAverage['clicks']['value'] ?? 0) }}</td>
                                                <td>{{ $this->formatNumber($globalAverage['clicks']['value'] ?? 0) }}</td>
                                            </tr>
                                            <tr><td>CTR</td><td>{{ $rbAverage['metrics']['ctr'] ?? '-' }}</td><td>{{ $globalAverage['metrics']['ctr'] ?? '-' }}</td></tr>
                                            <tr><td>CPC</td><td>{{ $rbAverage['metrics']['cpc'] ?? '-' }}</td><td>{{ $globalAverage['metrics']['cpc'] ?? '-' }}</td></tr>
                                            <tr><td>CPM</td><td>{{ $rbAverage['metrics']['cpm'] ?? '-' }}</td><td>{{ $globalAverage['metrics']['cpm'] ?? '-' }}</td></tr>
                                            <tr><td>CVR</td><td>{{ $rbAverage['metrics']['cvr'] ?? '-' }}</td><td>{{ $globalAverage['metrics']['cvr'] ?? '-' }}</td></tr>
                                            <tr>
                                                <td>{{ __('pages.calculator.cost_per_result') }}</td>
                                                <td>{{ $rbAverage['cost_per_result']['cost_per_conversion'] ?? '-' }}</td>
                                                <td>{{ $globalAverage['cost_per_result']['cost_per_conversion'] ?? '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Media Plan --}}
                        @if(!empty($result['media_plan']))
                            <div class="card">
                                <div class="px-5 py-4 border-b border-white/10">
                                    <h3 class="text-base font-semibold tracking-tight text-white flex items-center gap-2">
                                        <x-heroicon-o-table-cells class="h-5 w-5 text-gray-300" />
                                        {{ __('pages.calculator.media_plan') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.calculator.media_plan_description') }}</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="data-table min-w-full">
                                        <thead>
                                            <tr>
                                                <th>{{ __('funnel_stages.label') }}</th>
                                                <th>{{ __('pages.calculator.allocation') }}</th>
                                                <th>{{ __('pages.calculator.budget') }}</th>
                                                <th>{{ __('kpis.impressions') }}</th>
                                                <th>{{ __('kpis.clicks') }}</th>
                                                <th>{{ __('pages.calculator.primary_result') }}</th>
                                                <th>{{ __('pages.calculator.cost_per_result') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($result['media_plan'] as $row)
                                                <tr>
                                                    <td class="font-medium text-white">{{ $availableFunnelStages[$row['stage']] ?? $row['stage'] }}</td>
                                                    <td>{{ $row['percentage'] }}%</td>
                                                    <td class="text-white">{{ $this->formatCurrency($row['budget']) }}</td>
                                                    <td>{{ $this->formatNumber($row['impressions']) }}</td>
                                                    <td>{{ $this->formatNumber($row['clicks']) }}</td>
                                                    <td>{{ $this->formatNumber($row['primary_result']) }} {{ $row['primary_label'] }}</td>
                                                    <td>{{ $row['cost_per_result'] ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Benchmark Transparency --}}
                        @if(!empty($result['benchmark_info']))
                            <div class="card" x-data="{ open: false }">
                                <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between hover:bg-gray-950 focus:outline-none">
                                    <div class="flex items-center gap-3">
                                        <x-heroicon-o-information-circle class="h-5 w-5 text-gray-300" />
                                        <span class="text-base font-semibold tracking-tight text-white">{{ __('pages.calculator.how_calculated') }}</span>
                                    </div>
                                    <x-heroicon-o-chevron-down class="h-5 w-5 text-gray-400 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                                </button>
                                <div x-show="open" x-transition class="border-t border-white/10">
                                    <div class="px-5 py-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between py-2 border-b border-white/10">
                                                <span class="text-sm text-gray-500">{{ __('pages.calculator.data_points') }}</span>
                                                <span class="font-medium text-white">{{ $result['benchmark_info']['data_points'] ?? 0 }}</span>
                                            </div>
                                            <div class="flex items-center justify-between py-2 border-b border-white/10">
                                                <span class="text-sm text-gray-500">{{ __('pages.calculator.date_range') }}</span>
                                                <span class="font-medium text-white">{{ $result['benchmark_info']['date_range']['from'] ?? '-' }} &rarr; {{ $result['benchmark_info']['date_range']['to'] ?? '-' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between py-2 border-b border-white/10">
                                                <span class="text-sm text-gray-500">{{ __('pages.calculator.method') }}</span>
                                                <span class="font-medium text-white capitalize">{{ $result['benchmark_info']['calculation_method'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="bg-gray-950 rounded-xl p-4">
                                            <h4 class="text-sm font-semibold text-white mb-2">{{ __('pages.calculator.objective_explanation') }}</h4>
                                            <p class="text-sm text-gray-300">{{ $this->getObjectiveDescription($result['input']['objective'] ?? $objective) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Disclaimers --}}
                        @if(!empty($result['disclaimers']))
                            <div class="rounded-lg bg-gray-950 border border-white/10 p-4">
                                <div class="flex">
                                    <x-heroicon-o-information-circle class="h-5 w-5 text-gray-300 flex-shrink-0" />
                                    <ul class="ms-3 list-disc list-inside text-sm text-gray-200 space-y-1">
                                        @foreach($result['disclaimers'] as $disclaimer)
                                            <li>{{ $disclaimer }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if(!$hasResult && !$error && !$loading)
                        <div class="card">
                            <div class="empty-state">
                                <div class="mx-auto h-12 w-12 rounded-xl bg-white/5 flex items-center justify-center mb-4">
                                    <x-heroicon-o-calculator class="h-6 w-6 text-gray-300" />
                                </div>
                                <h3 class="empty-title">{{ __('pages.calculator.empty_title') }}</h3>
                                <p class="empty-message mb-8">{{ __('pages.calculator.empty_description') }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-2xl mx-auto">
                                    <div class="bg-gray-950 rounded-xl p-4">
                                        <div class="h-8 w-8 bg-gray-900 border border-white/10 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-semibold text-sm">1</span>
                                        </div>
                                        <p class="text-sm font-medium text-white">{{ __('pages.calculator.step_1') }}</p>
                                    </div>
                                    <div class="bg-gray-950 rounded-xl p-4">
                                        <div class="h-8 w-8 bg-gray-900 border border-white/10 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-semibold text-sm">2</span>
                                        </div>
                                        <p class="text-sm font-medium text-white">{{ __('pages.calculator.step_2') }}</p>
                                    </div>
                                    <div class="bg-gray-950 rounded-xl p-4">
                                        <div class="h-8 w-8 bg-gray-900 border border-white/10 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-semibold text-sm">3</span>
                                        </div>
                                        <p class="text-sm font-medium text-white">{{ __('pages.calculator.step_3') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
