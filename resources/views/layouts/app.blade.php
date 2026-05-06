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

        <!-- Page Transition Styles -->
        <style>
            .page-transition-enter-active,
            .page-transition-leave-active {
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            .page-transition-enter-from {
                opacity: 0;
                transform: translateY(10px);
            }

            .page-transition-leave-to {
                opacity: 0;
                transform: translateY(-10px);
            }

            .page-transition-enter-to,
            .page-transition-leave-from {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
    </head>
    @php
        $userId = auth()->id() ?? 'guest';
    @endphp
    <body class="font-sans antialiased text-gray-900 bg-gray-50 h-screen flex overflow-hidden"
        x-data="{
            sidebarOpen: false,
            isMobile: window.innerWidth < 768,
            quickActionsOpen: false
        }"
        x-init="
            sidebarOpen = localStorage.getItem('sidebarOpen_{{ $userId }}') === 'true';
            window.addEventListener('resize', () => { isMobile = window.innerWidth < 768 });
            $watch('sidebarOpen', value => localStorage.setItem('sidebarOpen_{{ $userId }}', value));
        "
    >
        <!-- Skip to Content -->
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-[#0c6d57] focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-[#0c6d57] focus:ring-offset-2">
            Skip to content
        </a>

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
            <main class="flex-1" id="main-content">
                <div class="page-transition-enter-active page-transition-leave-active">
                    {{ $slot }}
                </div>
            </main>

            @if (request()->routeIs('health.*') || request()->routeIs('reports.*'))
                <x-health-floating-toast />
            @endif
        </div>

        <!-- Quick Actions Modal -->
        @php
            $modalQuickActions = $navigation['sections']['quick_actions']['items'] ?? [];
            $hasQuickActions = ! empty($modalQuickActions);

            $qaModalIcons = [
                'New Cycle' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
                'Add Expense' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'Record Sale' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>',
                'Health Incident' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
                'New Meeting' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                'New Resolution' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
                'New Canvass' => '<svg class="w-5 h-5 text-[#0c6d57] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>',
            ];
        @endphp

        @if ($hasQuickActions)
        <div
            x-show="quickActionsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
            x-cloak
            @click.self="quickActionsOpen = false"
        >
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4"
            >
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        <button @click="quickActionsOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($modalQuickActions as $action)
                            <a href="{{ route($action['route']) }}" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-2 group-hover:bg-[#0c6d57] transition-colors">
                                    {!! $qaModalIcons[$action['label']] ?? $qaModalIcons['New Cycle'] !!}
                                </div>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57] transition-colors">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-2">Keyboard Shortcuts</p>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Quick Actions</span>
                                <kbd class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-500">Ctrl + K</kbd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Toggle Sidebar</span>
                                <kbd class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-500">Ctrl + B</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Keyboard Shortcuts Script -->
        <script>
            document.addEventListener('keydown', function(e) {
                // Skip if focused inside an editable element
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

                const layoutState = window.Alpine?.$data(document.body);

                // Ctrl/Cmd + K for Quick Actions
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if (layoutState) {
                        layoutState.quickActionsOpen = !layoutState.quickActionsOpen;
                    }
                }

                // Ctrl/Cmd + B for Sidebar Toggle
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    if (layoutState) {
                        layoutState.sidebarOpen = !layoutState.sidebarOpen;
                    }
                }
            });
        </script>
    </body>
</html>
