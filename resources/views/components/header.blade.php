@php
    $primaryNavigation = [
        ['name' => 'benchmarks', 'href' => '/benchmarks', 'label' => 'navigation.benchmarks'],
        ['name' => 'global-benchmarks', 'href' => '/global-benchmarks', 'label' => 'navigation.global_benchmarks'],
        ['name' => 'calculator', 'href' => '/calculator', 'label' => 'navigation.calculator'],
        ['name' => 'reports', 'href' => '/reports', 'label' => 'navigation.reports'],
        ['name' => 'media-planner', 'href' => '/media-planner', 'label' => 'navigation.media_planner'],
    ];

    $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();

    $adminNavigation = [
        ['name' => 'industry-overview',  'href' => '/industry-overview',  'label' => 'navigation.industry_overview',  'icon' => 'chart-bar',     'desc' => 'navigation.industry_overview_desc'],
        ['name' => 'spend-verification', 'href' => '/spend-verification', 'label' => 'navigation.spend_verification', 'icon' => 'shield-check',  'desc' => 'navigation.spend_verification_desc'],
        ['name' => 'industry-management','href' => '/industry-management','label' => 'navigation.industry_management','icon' => 'building-office','desc' => 'navigation.industry_management_desc'],
        ['name' => 'clients',            'href' => '/clients',            'label' => 'navigation.clients',            'icon' => 'user-group',    'desc' => 'navigation.clients_desc'],
    ];

    $isAdminPage = $isSuperAdmin && collect($adminNavigation)->contains(fn($i) => request()->is(ltrim($i['href'], '/')));
@endphp

<header class="sticky top-0 z-50 border-b" style="background-color: #0d0f16; border-color: rgba(255,255,255,0.08);">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <!-- Left: Logo + Primary Nav -->
            <div class="flex items-center gap-x-8">
                <!-- Logo -->
                <a href="/benchmarks" class="flex-shrink-0">
                    <img src="/logo-dark.svg" alt="{{ __('app.name') }}" class="h-7 w-auto dark-logo">
                    <img src="/logo.svg" alt="{{ __('app.name') }}" class="h-7 w-auto light-logo hidden">
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

                    {{-- Admin dropdown --}}
                    @if($isSuperAdmin)
                        <div x-data="{ adminOpen: false }" @click.outside="adminOpen = false" class="relative">
                            <button
                                @click="adminOpen = !adminOpen"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                       {{ $isAdminPage ? 'bg-red-500/15 text-red-400' : 'text-red-400/70 hover:bg-red-500/10 hover:text-red-400' }}"
                            >
                                <x-heroicon-s-shield-check class="h-3.5 w-3.5" />
                                Admin
                                <x-heroicon-s-chevron-down class="h-3 w-3 transition-transform" x-bind:class="adminOpen ? 'rotate-180' : ''" />
                            </button>

                            <div
                                x-show="adminOpen"
                                x-transition:enter="transition duration-100 ease-out"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition duration-75 ease-in"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                class="absolute start-0 top-full mt-2 w-72 rounded-xl border border-white/10 bg-gray-900 shadow-xl z-50"
                                x-cloak
                            >
                                {{-- Tenant switcher --}}
                                <div class="px-3 pt-3 pb-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 mb-2 px-1">Viewing tenant</p>
                                    <livewire:components.tenant-selector />
                                </div>

                                <div class="border-t border-white/8 py-1.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 mb-1 px-4 pt-1">Admin Tools</p>
                                    @foreach($adminNavigation as $item)
                                        <a
                                            href="{{ $item['href'] }}"
                                            @click="adminOpen = false"
                                            class="flex items-start gap-3 px-4 py-2.5 hover:bg-white/5 transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-red-500/8' : '' }}"
                                        >
                                            <div class="mt-0.5 h-7 w-7 rounded-lg bg-red-500/10 flex items-center justify-center flex-shrink-0">
                                                @if($item['icon'] === 'chart-bar') <x-heroicon-s-chart-bar class="h-3.5 w-3.5 text-red-400" />
                                                @elseif($item['icon'] === 'shield-check') <x-heroicon-s-shield-check class="h-3.5 w-3.5 text-red-400" />
                                                @elseif($item['icon'] === 'building-office') <x-heroicon-s-building-office class="h-3.5 w-3.5 text-red-400" />
                                                @elseif($item['icon'] === 'user-group') <x-heroicon-s-user-group class="h-3.5 w-3.5 text-red-400" />
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium {{ request()->is(ltrim($item['href'], '/') . '*') ? 'text-red-400' : 'text-white' }}">{{ __($item['label']) }}</p>
                                                <p class="text-xs text-gray-500 leading-tight">{{ __($item['desc']) }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
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
                            @if($isSuperAdmin)
                                <div class="pt-2 mt-2 border-t border-white/8">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-red-400/60 px-3 mb-1">Admin</p>
                                    @foreach($adminNavigation as $item)
                                        <a
                                            href="{{ $item['href'] }}"
                                            @click="mobileMenuOpen = false"
                                            class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->is(ltrim($item['href'], '/') . '*') ? 'bg-red-500/15 text-red-400' : 'text-red-400/70 hover:bg-red-500/10 hover:text-red-400' }}"
                                        >
                                            {{ __($item['label']) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
