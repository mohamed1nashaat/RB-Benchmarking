<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RB Benchmarks') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="alternate icon" href="/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- English Fonts: Montserrat (body) + Cormorant Garamond (headings) -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Arabic Fonts: IBM Plex Sans Arabic (body) + Noto Naskh Arabic (headings) -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
