<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'RB Benchmarks') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="alternate icon" href="/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-950 text-white">
    <div class="min-h-screen flex flex-col" style="background-color: #0d0f16;">
        <!-- Header -->
        <x-header />

        <!-- Page Content -->
        <main class="flex-1">
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <x-footer />
    </div>

    <livewire:components.ai-chat />
    @livewireScripts
</body>
</html>
