@php
    $primaryNavigation = [
        ['name' => 'benchmarks', 'href' => '/benchmarks', 'label' => 'navigation.benchmarks'],
        ['name' => 'global-benchmarks', 'href' => '/global-benchmarks', 'label' => 'navigation.global_benchmarks'],
        ['name' => 'calculator', 'href' => '/calculator', 'label' => 'navigation.calculator'],
        ['name' => 'reports', 'href' => '/reports', 'label' => 'navigation.reports'],
        ['name' => 'media-planner', 'href' => '/media-planner', 'label' => 'navigation.media_planner'],
    ];

    if (auth()->check() && auth()->user()->isSuperAdmin()) {
        $primaryNavigation[] = ['name' => 'industry-overview', 'href' => '/industry-overview', 'label' => 'navigation.industry_overview'];
        $primaryNavigation[] = ['name' => 'spend-verification', 'href' => '/spend-verification', 'label' => 'navigation.spend_verification'];
    }
@endphp

<header class="bg-white border-b border-neutral-200 sticky top-0 z-50">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <!-- Left: Logo + Primary Nav -->
            <div class="flex items-center gap-x-8">
                <!-- Logo -->
                <a href="/benchmarks" class="flex-shrink-0">
                    <img src="/logo.svg" alt="{{ __('app.name') }}" class="h-7 w-auto">
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-x-0.5">
                    @foreach($primaryNavigation as $item)
                        <a
                            href="{{ $item['href'] }}"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900' }}"
                        >
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-x-2">
                <!-- Language Switcher -->
                <livewire:components.language-switcher />

                <!-- User Menu -->
                @if(auth()->user())
                    <livewire:components.user-menu />
                @endif

                <!-- Mobile menu button -->
                <div x-data="{ mobileMenuOpen: false }" class="lg:hidden">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-700"
                    >
                        <x-heroicon-o-bars-3 x-show="!mobileMenuOpen" class="h-5 w-5" />
                        <x-heroicon-o-x-mark x-show="mobileMenuOpen" class="h-5 w-5" x-cloak />
                    </button>

                    <!-- Mobile Navigation Menu -->
                    <div
                        x-show="mobileMenuOpen"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition duration-150 ease-in"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute top-14 inset-x-0 border-t border-neutral-200 bg-white"
                        x-cloak
                    >
                        <div class="px-4 py-3 space-y-0.5">
                            @foreach($primaryNavigation as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    @click="mobileMenuOpen = false"
                                    class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900' }}"
                                >
                                    {{ __($item['label']) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
