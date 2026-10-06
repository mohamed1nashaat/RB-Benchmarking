<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-x-3">
        <a href="{{ route('media-plans') }}" class="p-1.5 rounded-md text-gray-400 hover:bg-white/8 hover:text-gray-200 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-semibold text-white">{{ __('media_planner.builder_title') }}</h1>
            <p class="mt-0.5 text-sm text-gray-400">{{ __('media_planner.builder_subtitle') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-x-2 px-4 py-3 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Step indicator --}}
    <div class="flex items-center gap-x-0">
        @php $steps = [1 => __('media_planner.step1'), 2 => __('media_planner.step2'), 3 => __('media_planner.step3')]; @endphp
        @foreach($steps as $num => $label)
            <div class="flex items-center {{ !$loop->first ? 'flex-1' : '' }}">
                @if(!$loop->first)
                    <div class="flex-1 h-px {{ $step > $num - 1 ? 'bg-brand-500' : 'bg-white/10' }}"></div>
                @endif
                <div class="flex items-center gap-x-2">
                    <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium
                        {{ $step > $num ? 'bg-brand-600 text-white' : ($step === $num ? 'bg-brand-600 text-white ring-4 ring-brand-500/20' : 'bg-white/8 text-gray-500') }}">
                        @if($step > $num)
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-sm font-medium {{ $step === $num ? 'text-white' : 'text-gray-500' }}">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ───── STEP 1: Campaign Setup ───── --}}
    @if($step === 1)
    <div class="card p-6 space-y-5">
        <h2 class="text-base font-semibold text-white">{{ __('media_planner.step1_heading') }}</h2>

        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('media_planner.plan_name') }}</label>
            <input type="text" wire:model="name"
                   class="input-field w-full"
                   placeholder="{{ __('media_planner.plan_name_placeholder') }}">
            @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Period --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('common.period_start') }}</label>
                <input type="date" wire:model="periodStart" class="input-field w-full">
                @error('periodStart') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('common.period_end') }}</label>
                <input type="date" wire:model="periodEnd" class="input-field w-full">
                @error('periodEnd') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Budget + Currency --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('media_planner.total_budget') }}</label>
                <input type="number" wire:model="totalBudget" min="1000" step="1000"
                       class="input-field w-full font-mono"
                       placeholder="50000">
                @error('totalBudget') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('media_planner.currency') }}</label>
                <select wire:model="currency" class="input-field w-full">
                    <option value="SAR">SAR</option>
                    <option value="AED">AED</option>
                    <option value="USD">USD</option>
                    <option value="KWD">KWD</option>
                    <option value="QAR">QAR</option>
                </select>
            </div>
        </div>

        {{-- Objective + Industry --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('media_planner.objective') }}</label>
                <select wire:model="objective" class="input-field w-full">
                    <option value="awareness">Awareness</option>
                    <option value="traffic">Traffic</option>
                    <option value="leads">Leads</option>
                    <option value="conversions">Conversions</option>
                </select>
                @error('objective') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1.5">{{ __('common.industry') }}</label>
                <select wire:model="industry" class="input-field w-full">
                    <option value="">{{ __('common.select_industry') }}</option>
                    @foreach($this->industries as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('industry') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button wire:click="nextStep" class="btn-primary">
                {{ __('common.next') }} →
            </button>
        </div>
    </div>
    @endif

    {{-- ───── STEP 2: Platform Selection ───── --}}
    @if($step === 2)
    <div class="card p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold text-white">{{ __('media_planner.step2_heading') }}</h2>
            <p class="mt-1 text-sm text-gray-400">{{ __('media_planner.step2_hint') }}</p>
        </div>

        @error('selectedPlatforms') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($this->availablePlatforms as $key => $platform)
                <label class="relative flex items-center gap-x-3 p-4 rounded-xl border-2 cursor-pointer transition-colors
                    {{ in_array($key, $selectedPlatforms) ? 'border-brand-500 bg-brand-500/10' : 'border-white/10 bg-white/3 hover:border-white/20' }}">
                    <input type="checkbox" wire:model="selectedPlatforms" value="{{ $key }}" class="sr-only">
                    <span class="text-2xl">{{ $platform['icon'] }}</span>
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $platform['label'] }}</p>
                        @if(in_array($key, $selectedPlatforms))
                            <p class="text-xs text-brand-400 mt-0.5">✓ Selected</p>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Budget Preview --}}
        @if(count($selectedPlatforms) > 0)
        <div class="bg-white/5 rounded-lg p-4 border border-white/8">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-3">Budget estimate per platform</p>
            <div class="space-y-2">
                @php $equalShare = $totalBudget / max(1, count($selectedPlatforms)); @endphp
                @foreach($selectedPlatforms as $p)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-300 capitalize">{{ $this->availablePlatforms[$p]['label'] ?? ucfirst($p) }}</span>
                    <span class="font-mono text-white">~{{ number_format($equalShare, 0) }} {{ $currency }}</span>
                </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-500">AI will optimize the actual split based on benchmarks</p>
        </div>
        @endif

        <div class="flex items-center justify-between pt-2">
            <button wire:click="prevStep" class="btn-secondary">
                ← {{ __('common.back') }}
            </button>
            <button wire:click="nextStep"
                    wire:loading.attr="disabled"
                    class="btn-primary disabled:opacity-60 flex items-center gap-x-2">
                <span wire:loading wire:target="nextStep" class="inline-block h-3.5 w-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                {{ __('media_planner.generate_plan') }}
            </button>
        </div>
    </div>
    @endif

    {{-- ───── STEP 3: AI Allocation Results ───── --}}
    @if($step === 3)
    <div class="space-y-5">

        {{-- Allocating spinner --}}
        @if($allocating)
        <div class="card p-12 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-brand-500/10 mb-4">
                <div class="h-6 w-6 border-2 border-brand-500/20 border-t-brand-500 rounded-full animate-spin"></div>
            </div>
            <p class="text-sm font-medium text-gray-200">Analyzing benchmarks and optimizing allocation...</p>
            <p class="text-xs text-gray-500 mt-1">Using {{ count($selectedPlatforms) }} platforms · {{ $industry }}</p>
        </div>

        @elseif($plan && $plan->scenarios)

        {{-- Plan approved banner --}}
        @if($plan->isApproved())
        <div class="flex items-center gap-x-3 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/20">
            <svg class="h-5 w-5 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-green-300">Plan Approved</p>
                <p class="text-xs text-green-500">Approved {{ $plan->approved_at?->diffForHumans() }}</p>
            </div>
        </div>
        @endif

        {{-- 3 Scenarios --}}
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="text-base font-semibold text-white">3-Scenario Comparison</h2>
                <p class="text-sm text-gray-400 mt-0.5">Select the budget scenario that fits your goals</p>
            </div>
            <div class="grid grid-cols-3 divide-x divide-white/8">
                @foreach(['conservative', 'recommended', 'aggressive'] as $scenarioKey)
                @php $scenario = $plan->scenarios[$scenarioKey] ?? []; @endphp
                <button wire:click="selectScenario('{{ $scenarioKey }}')"
                        class="p-5 text-left transition-colors {{ $selectedScenario === $scenarioKey ? 'bg-brand-500/8' : 'hover:bg-white/3' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide {{ $selectedScenario === $scenarioKey ? 'text-brand-400' : 'text-gray-400' }}">
                                {{ $scenarioKey }}
                            </p>
                            @if($scenarioKey === 'recommended')
                                <span class="inline-block mt-1 px-1.5 py-0.5 text-xs rounded bg-brand-500/10 text-brand-300 font-medium">Recommended</span>
                            @endif
                        </div>
                        @if($selectedScenario === $scenarioKey)
                        <div class="h-5 w-5 rounded-full bg-brand-600 flex items-center justify-center flex-shrink-0">
                            <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                    <p class="text-xl font-semibold font-mono text-white">
                        {{ number_format($scenario['budget'] ?? 0, 0) }}
                        <span class="text-sm font-normal text-gray-400">{{ $plan->currency }}</span>
                    </p>
                    @php $proj = $scenario['projections'] ?? []; @endphp
                    <div class="mt-3 space-y-1.5 text-xs">
                        <div class="flex justify-between text-gray-400">
                            <span>Est. Leads</span>
                            <span class="font-mono font-medium text-white">{{ number_format($proj['leads'] ?? 0, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Est. Clicks</span>
                            <span class="font-mono font-medium text-white">{{ number_format($proj['clicks'] ?? 0, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Impressions</span>
                            <span class="font-mono font-medium text-white">{{ number_format(($proj['impressions'] ?? 0) / 1000, 0) }}K</span>
                        </div>
                    </div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Platform Allocation for Selected Scenario --}}
        @php
            $activeScenario   = $plan->scenarios[$selectedScenario] ?? [];
            $activeAllocations = $activeScenario['allocations'] ?? [];
            $maxBudget = $activeAllocations ? max(array_column($activeAllocations, 'budget')) : 1;
        @endphp
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="text-base font-semibold text-white">Platform Allocation</h2>
                <p class="text-xs text-gray-400 mt-0.5 capitalize">{{ $selectedScenario }} scenario · {{ number_format($activeScenario['budget'] ?? 0, 0) }} {{ $plan->currency }}</p>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $platformLabels = ['facebook'=>'Meta / Facebook','google'=>'Google Ads','tiktok'=>'TikTok','snapchat'=>'Snapchat','linkedin'=>'LinkedIn'];
                @endphp
                @foreach($activeAllocations as $platform => $alloc)
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-100">{{ $platformLabels[$platform] ?? ucfirst($platform) }}</span>
                        <div class="flex items-center gap-x-3">
                            <span class="text-gray-500 text-xs">{{ $alloc['pct'] }}%</span>
                            <span class="font-mono font-medium text-white">{{ number_format($alloc['budget'], 0) }} {{ $plan->currency }}</span>
                        </div>
                    </div>
                    <div class="h-2 bg-white/8 rounded-full overflow-hidden">
                        <div class="h-full bg-brand-500 rounded-full transition-all"
                             style="width: {{ $maxBudget > 0 ? round(($alloc['budget'] / $maxBudget) * 100) : 0 }}%"></div>
                    </div>
                    @if($alloc['cpl'] || $alloc['cpc'])
                    <div class="flex gap-x-4 text-xs text-gray-500">
                        @if($alloc['cpl']) <span>CPL: <span class="text-gray-300 font-mono">{{ number_format($alloc['cpl'], 1) }}</span></span> @endif
                        @if($alloc['cpc']) <span>CPC: <span class="text-gray-300 font-mono">{{ number_format($alloc['cpc'], 2) }}</span></span> @endif
                        @if($alloc['cpm']) <span>CPM: <span class="text-gray-300 font-mono">{{ number_format($alloc['cpm'], 1) }}</span></span> @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Funnel Split --}}
        @if($plan->funnel_split)
        <div class="card p-6">
            <h2 class="text-base font-semibold text-white mb-4">Funnel Allocation</h2>
            <div class="grid grid-cols-3 gap-4">
                @php $funnel = $plan->funnel_split; @endphp
                @foreach(['tof' => ['label'=>'Top of Funnel','color'=>'bg-blue-500','desc'=>'Awareness & Reach'], 'mof' => ['label'=>'Mid Funnel','color'=>'bg-amber-500','desc'=>'Consideration & Engagement'], 'bof' => ['label'=>'Bottom of Funnel','color'=>'bg-green-500','desc'=>'Conversion & Retargeting']] as $key => $meta)
                <div class="text-center p-4 rounded-xl bg-white/5 border border-white/8">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full {{ $meta['color'] }}/10 mb-2">
                        <span class="text-lg font-bold text-white font-mono">{{ $funnel[$key] ?? 0 }}%</span>
                    </div>
                    <p class="text-sm font-medium text-gray-100">{{ $meta['label'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $meta['desc'] }}</p>
                    <div class="mt-2 h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full {{ $meta['color'] }} rounded-full" style="width: {{ $funnel[$key] ?? 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Monthly Calendar / Gantt --}}
        @if($plan->calendar)
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="text-base font-semibold text-white">Monthly Budget Calendar</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $plan->periodLabel() }} · {{ count($plan->calendar) }} months</p>
            </div>
            <div class="p-6">
                @php $maxMonthBudget = max(array_column($plan->calendar, 'budget') ?: [1]); @endphp
                <div class="space-y-3">
                    @foreach($plan->calendar as $month)
                    <div class="flex items-center gap-x-4">
                        <div class="w-20 text-sm text-gray-300 flex-shrink-0 flex items-center gap-x-1.5">
                            {{ $month['label'] }}
                            @if($month['is_ramadan'])
                                <span class="text-xs text-amber-400 font-medium" title="Ramadan">☪</span>
                            @endif
                        </div>
                        <div class="flex-1 h-6 bg-white/8 rounded-md overflow-hidden relative">
                            <div class="h-full rounded-md transition-all {{ $month['is_ramadan'] ? 'bg-amber-400' : 'bg-brand-500' }}"
                                 style="width: {{ $maxMonthBudget > 0 ? round(($month['budget'] / $maxMonthBudget) * 100) : 0 }}%">
                            </div>
                        </div>
                        <div class="w-28 text-right text-sm font-mono text-gray-200 flex-shrink-0">
                            {{ number_format($month['budget'], 0) }}
                            <span class="text-xs text-gray-500">{{ $plan->currency }}</span>
                        </div>
                        <div class="w-10 text-right text-xs text-gray-500 flex-shrink-0">
                            {{ $month['weight'] }}%
                        </div>
                    </div>
                    @endforeach
                </div>
                @if(collect($plan->calendar)->contains('is_ramadan', true))
                <div class="mt-4 flex items-center gap-x-2 text-xs text-amber-400">
                    <div class="h-3 w-3 rounded bg-amber-400"></div>
                    <span>Ramadan month — social media budgets boosted, search reduced</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Approve / Action bar --}}
        @if(!$plan->isApproved())
        <div class="flex items-center justify-between card px-6 py-4">
            <div>
                <p class="text-sm font-medium text-gray-100">Ready to finalize?</p>
                <p class="text-xs text-gray-400">Approve the plan to lock the allocation and share with your client</p>
            </div>
            <button wire:click="approvePlan"
                    class="px-5 py-2 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
                ✓ Approve Plan
            </button>
        </div>
        @endif

        @endif {{-- end $plan && scenarios --}}
    </div>
    @endif

</div>
