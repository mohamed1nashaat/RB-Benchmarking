<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Client Portal — RB Benchmarks' }}</title>

    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-[#F7F6F3] flex flex-col">

        {{-- Portal Nav --}}
        <header class="bg-white border-b border-neutral-200 sticky top-0 z-50">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14">
                    <div class="flex items-center gap-x-6">
                        <a href="{{ route('portal.dashboard') }}" class="flex-shrink-0">
                            <img src="/logo.svg" alt="Red Bananas" class="h-7 w-auto">
                        </a>
                        <nav class="hidden sm:flex items-center gap-x-0.5">
                            <a href="{{ route('portal.dashboard') }}"
                               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900' }}">
                                Overview
                            </a>
                            <a href="{{ route('portal.reports') }}"
                               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.reports') ? 'bg-primary-50 text-primary-600' : 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900' }}">
                                Reports
                            </a>
                            <a href="{{ route('portal.media-plans') }}"
                               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.media-plans') ? 'bg-primary-50 text-primary-600' : 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900' }}">
                                Media Plans
                            </a>
                        </nav>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <span class="hidden sm:inline text-xs text-neutral-400">
                            {{ auth()->user()?->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-neutral-200 text-neutral-600 hover:bg-neutral-100 transition-colors">
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1">
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-neutral-200 bg-[#F7F6F3] py-4">
            <p class="text-center text-xs text-neutral-400">{{ __('portal.powered_by') }}</p>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
