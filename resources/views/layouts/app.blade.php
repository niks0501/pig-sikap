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
    @php
        $userId = auth()->id() ?? 'guest';
    @endphp
    <body class="font-sans antialiased text-gray-900 bg-gray-50 h-screen flex overflow-hidden"
        x-data="{ 
            sidebarOpen: false,
            isMobile: window.innerWidth < 768
        }"
        x-init="
            sidebarOpen = localStorage.getItem('sidebarOpen_{{ $userId }}') === 'true';
            window.addEventListener('resize', () => { isMobile = window.innerWidth < 768 });
            $watch('sidebarOpen', value => localStorage.setItem('sidebarOpen_{{ $userId }}', value));
        "
    >
        <!-- Mobile Overlay -->
        <div 
            x-show="sidebarOpen && isMobile"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden"
            x-cloak
        ></div>

        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-y-auto">
            <!-- Top Navigation Bar -->
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-b border-gray-50 shrink-0">
                    <div class="px-6 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            @if (request()->routeIs('health.*'))
                <x-health-floating-toast />
            @endif
        </div>
    </body>
</html>
