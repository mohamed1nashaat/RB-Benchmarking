<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-neutral-900">{{ __('billing.title') }}</h1>
        <p class="mt-1 text-sm text-neutral-500">{{ __('billing.subtitle') }}</p>
    </div>

    {{-- Flash messages --}}
    @if(session('info'))
        <div class="flex items-center gap-x-3 px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
            </svg>
            {{ session('info') }}
        </div>
    @endif

    @if(request('subscribed'))
        <div class="flex items-center gap-x-3 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            Subscription activated successfully. Welcome aboard!
        </div>
    @endif

    {{-- Current Plan Status --}}
    <div class="bg-white border border-neutral-200 rounded-xl p-5 flex items-center justify-between gap-x-4">
        <div class="flex items-center gap-x-3">
            <div class="h-9 w-9 rounded-lg bg-primary-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900">{{ __('billing.current_plan') }}</p>
                @if($this->activePlan)
                    <p class="text-xs text-green-600 mt-0.5">
                        Active · {{ ucfirst($this->activePlan->stripe_status ?? 'active') }}
                    </p>
                @else
                    <p class="text-xs text-neutral-400 mt-0.5">{{ __('billing.free_trial') }}</p>
                @endif
            </div>
        </div>
        @if($this->activePlan)
            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full border border-green-200 bg-green-50 text-green-700">
                Subscribed
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full border border-neutral-200 bg-neutral-50 text-neutral-500">
                No active plan
            </span>
        @endif
    </div>

    {{-- Billing interval toggle --}}
    <div class="flex items-center justify-center gap-x-3">
        <span class="text-sm font-medium {{ $billingInterval === 'monthly' ? 'text-neutral-900' : 'text-neutral-400' }}">Monthly</span>
        <button wire:click="$set('billingInterval', $billingInterval === 'monthly' ? 'annual' : 'monthly')"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $billingInterval === 'annual' ? 'bg-primary-600' : 'bg-neutral-200' }}">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $billingInterval === 'annual' ? 'translate-x-6' : 'translate-x-1' }}"></span>
        </button>
        <span class="text-sm font-medium {{ $billingInterval === 'annual' ? 'text-neutral-900' : 'text-neutral-400' }}">
            Annual
            <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 text-xs rounded-full bg-green-100 text-green-700 font-medium">Save 20%</span>
        </span>
    </div>

    {{-- Plan cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($this->plans as $plan)
        @php
            $isSelected   = $selectedPlan === $plan->slug;
            $isRecommended = $plan->slug === 'growth';
            $annualPrice  = round($plan->price_sar * 12 * 0.8, 0);
            $displayPrice = $billingInterval === 'annual' ? $annualPrice / 12 : $plan->price_sar;
            $displayUsd   = $billingInterval === 'annual' ? round($plan->price_usd * 0.8, 0) : $plan->price_usd;
        @endphp
        <div class="relative bg-white rounded-xl border-2 transition-all
            {{ $isRecommended ? 'border-primary-500' : ($isSelected ? 'border-primary-400' : 'border-neutral-200') }}
            {{ $isSelected ? 'ring-4 ring-primary-100' : '' }}">

            @if($isRecommended)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="inline-flex items-center px-3 py-0.5 text-xs font-semibold rounded-full bg-primary-600 text-white whitespace-nowrap">
                        {{ __('billing.most_popular') }}
                    </span>
                </div>
            @endif

            <div class="p-5 {{ $isRecommended ? 'pt-6' : '' }}">
                {{-- Plan name --}}
                <h3 class="text-base font-semibold text-neutral-900">{{ $plan->name }}</h3>

                {{-- Price --}}
                <div class="mt-3 mb-4">
                    <div class="flex items-baseline gap-x-1">
                        <span class="text-3xl font-bold font-mono text-neutral-900">{{ number_format($displayPrice, 0) }}</span>
                        <span class="text-sm text-neutral-500">SAR</span>
                    </div>
                    <p class="text-xs text-neutral-400 mt-0.5">
                        ${{ number_format($displayUsd, 0) }} USD · {{ __('billing.per_month') }}
                        @if($billingInterval === 'annual')
                            <span class="text-green-600"> (billed annually)</span>
                        @endif
                    </p>
                </div>

                {{-- Features --}}
                <ul class="space-y-2 mb-5">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-start gap-x-2 text-sm text-neutral-600">
                        <svg class="h-4 w-4 text-primary-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                {{-- Limits --}}
                <div class="mb-5 px-3 py-2 bg-neutral-50 rounded-lg space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Ad accounts</span>
                        <span class="font-medium text-neutral-700">{{ $plan->limitLabel('ad_accounts') }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">AI reports / mo</span>
                        <span class="font-medium text-neutral-700">{{ $plan->limitLabel('reports_per_month') }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Team members</span>
                        <span class="font-medium text-neutral-700">{{ $plan->limitLabel('users') }}</span>
                    </div>
                </div>

                {{-- CTA --}}
                <button wire:click="selectPlan('{{ $plan->slug }}')"
                        class="w-full py-2 text-sm font-medium rounded-lg border transition-colors
                    @if($isSelected)
                        bg-primary-600 border-primary-600 text-white hover:bg-primary-700
                    @elseif($isRecommended)
                        bg-primary-50 border-primary-500 text-primary-700 hover:bg-primary-100
                    @else
                        bg-white border-neutral-200 text-neutral-700 hover:bg-neutral-50
                    @endif">
                    {{ $isSelected ? '✓ ' . __('billing.current') : __('billing.select_plan') }}
                </button>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Subscribe CTA (shown when plan selected) --}}
    @if($selectedPlan)
    <div class="bg-white border border-neutral-200 rounded-xl px-6 py-5 flex items-center justify-between gap-x-4">
        <div>
            <p class="text-sm font-medium text-neutral-900">
                Subscribe to
                <span class="text-primary-600">{{ $this->plans->firstWhere('slug', $selectedPlan)?->name }}</span>
            </p>
            <p class="text-xs text-neutral-500 mt-0.5">
                @php
                    $selPlan = $this->plans->firstWhere('slug', $selectedPlan);
                    $price = $billingInterval === 'annual'
                        ? number_format(round($selPlan?->price_sar * 12 * 0.8, 0))
                        : number_format($selPlan?->price_sar, 0);
                    $period = $billingInterval === 'annual' ? '/year' : '/month';
                @endphp
                {{ $price }} SAR {{ $period }} · Cancel anytime
            </p>
        </div>
        <button wire:click="subscribe"
                wire:loading.attr="disabled"
                class="px-6 py-2.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors disabled:opacity-60 flex items-center gap-x-2">
            <span wire:loading wire:target="subscribe" class="inline-block h-3.5 w-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Proceed to Checkout →
        </button>
    </div>
    @endif

    {{-- Enterprise CTA --}}
    <div class="text-center py-4 border-t border-neutral-100">
        <p class="text-sm text-neutral-500">
            {{ __('billing.contact_us') }}
            <a href="mailto:billing@redbananas.com" class="text-primary-600 hover:underline">billing@redbananas.com →</a>
        </p>
    </div>

</div>
