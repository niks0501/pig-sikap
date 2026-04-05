<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased" style="background-color: #0c6d57;">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
        <div class="text-center mb-8 flex flex-col items-center">
            <a href="/" aria-label="Pig Sikap home" class="inline-block hover:opacity-90 transition-opacity">
                <img src="{{ asset('images/slp_logo.png') }}" alt="SLP Logo" class="w-20 h-20 object-contain rounded-full shadow-lg" />
            </a>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-white">Elite Visionaries SLP Association</h1>
            <p class="mt-1 text-sm text-slate-50">Humayingan, Lian, Batangas</p>
        </div>

        <div class="w-full sm:max-w-md bg-white shadow-2xl rounded-xl px-6 py-8 sm:px-10">
            {{ $slot }}
        </div>

        <div class="mt-8 text-center">
            <p class="text-xs text-slate-50">Secure management system for association members</p>
        </div>
    </div>
</body>

</html>