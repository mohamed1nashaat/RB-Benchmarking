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

<header class="sticky top-0 z-50 border-b" style="background-color: #0d0f16; border-color: rgba(255,255,255,0.08);">
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
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-brand-500/15 text-brand-400' : 'text-gray-400 hover:bg-white/8 hover:text-white' }}"
                        >
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-x-2">
                <!-- Theme Toggle -->
                <button
                    onclick="(function(){var h=document.documentElement;var c=h.getAttribute('data-theme')||'dark';var n=c==='light'?'dark':'light';if(n==='dark')h.removeAttribute('data-theme');else h.setAttribute('data-theme','light');localStorage.setItem('rb-theme',n);})()"
                    class="p-2 rounded-lg text-gray-400 hover:text-white transition-colors"
                    title="Toggle light/dark mode"
                >
                    <svg class="h-4 w-4 dark-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <svg class="h-4 w-4 light-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </button>

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
                        class="p-2 rounded-md text-gray-400 hover:bg-white/8 hover:text-white transition-colors"
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
                        class="absolute top-14 inset-x-0 border-t"
                        style="background-color: #0d0f16; border-color: rgba(255,255,255,0.08);"
                        x-cloak
                    >
                        <div class="px-4 py-3 space-y-0.5">
                            @foreach($primaryNavigation as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    @click="mobileMenuOpen = false"
                                    class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-brand-500/15 text-brand-400' : 'text-gray-400 hover:bg-white/8 hover:text-white' }}"
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
