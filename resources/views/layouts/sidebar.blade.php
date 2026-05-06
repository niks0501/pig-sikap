<!-- Sidebar layout navigation -->
@php
    $icons = [
        'dashboard' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>',
        'cycles' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>',
        'health' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>',
        'sales' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'expenses' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
        'profitability' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>',
        'members' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
        'reports' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
        'meetings' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
        'resolutions' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
        'withdrawals' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
        'documents' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>',
        'canvassing' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>',
        'suppliers' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>',
        'penalties' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'settings' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
        'audit' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    ];

    $colors = $navigation['colors'] ?? [];
    $sections = $navigation['sections'] ?? [];
    $mainItems = $sections['main']['items'] ?? [];

    $isActive = function (array $item): bool {
        $pattern = $item['route_pattern'] ?? ($item['route'] . '*');
        $patterns = explode(',', $pattern);

        foreach ($patterns as $p) {
            if (request()->routeIs(trim($p))) {
                return true;
            }
        }

        return false;
    };
@endphp

<aside
    class="flex flex-col bg-white border-r border-gray-100 h-screen shrink-0 relative overflow-hidden transition-all duration-300"
    :class="[
        isMobile
            ? 'fixed inset-y-0 left-0 z-50 w-64 shadow-xl'
            : (sidebarOpen ? 'w-64' : 'w-18')
    ]"
    :style="isMobile ? (sidebarOpen ? 'display:flex' : 'display:none') : ''"
    x-show="isMobile ? sidebarOpen : true"
    x-cloak
    x-data="{ isMobile: window.innerWidth < 768 }"
    x-init="window.addEventListener('resize', () => isMobile = window.innerWidth < 768)"
>
    <!-- Mobile Close Button -->
    <button @click="$parent.sidebarOpen = false" x-show="isMobile" class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 md:hidden">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>

    <!-- Logo Section -->
    <div class="flex items-center h-16 border-b border-gray-50 transition-all duration-300" :class="sidebarOpen ? 'px-6 gap-3' : 'justify-center px-2'">
        <div class="bg-emerald-50 p-1.5 rounded-xl text-[#0c6d57] shrink-0">
            <img src="{{ asset('images/slp_logo.png') }}" alt="Logo" class="w-7 h-7 object-contain">
        </div>
        <div class="flex flex-col overflow-hidden" x-show="sidebarOpen" x-transition.opacity.duration.300ms x-cloak>
            <span class="text-sm font-bold text-gray-900 leading-tight whitespace-nowrap">Pig-Sikap</span>
            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">Pig Farm System</span>
        </div>
    </div>

    <!-- Scrollable Menu -->
    <div class="flex-1 py-6 space-y-8 overflow-y-auto overflow-x-hidden flex flex-col" :class="sidebarOpen ? 'px-4' : 'px-2'">
        <!-- Main Menu -->
        <div>
            <div class="mb-3 transition-all duration-300" :class="sidebarOpen ? 'px-2' : 'flex justify-center'">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide" x-show="sidebarOpen">Main Menu</span>
                <span class="text-xs font-bold text-gray-400" x-show="!sidebarOpen">...</span>
            </div>

            <nav class="space-y-1" aria-label="Main navigation">
                @foreach ($mainItems as $item)
                    @php
                        $active = $isActive($item);
                        $colorScheme = $colors[$item['color']] ?? $colors['gray'];
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors
                              {{ $active ? $colorScheme['bg'] . ' ' . $colorScheme['text'] . ' font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}"
                       :class="sidebarOpen ? 'justify-between' : 'justify-center'"
                       title="{{ $item['label'] }}"
                       @if($active) aria-current="page" @endif>
                        <div class="flex items-center gap-3">
                            <span class="{{ $active ? $colorScheme['icon'] : 'text-gray-400' }}">
                                {!! $icons[$item['icon']] ?? $icons['reports'] !!}
                            </span>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ $item['label'] }}</span>
                        </div>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="border-t border-gray-100 my-2"></div>

        <!-- Account -->
        <div class="mt-auto">
            <div class="mb-3 transition-all duration-300" :class="sidebarOpen ? 'px-2' : 'flex justify-center'">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide" x-show="sidebarOpen">Account</span>
                <span class="text-xs font-bold text-gray-400" x-show="!sidebarOpen">...</span>
            </div>

            <nav class="space-y-1">
                @if (Auth::user()->isSystemAdmin())
                <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center px-3 py-2.5 rounded-xl transition-colors text-[#0c6d57] hover:bg-[#0c6d57]/5 font-medium" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Back to Admin Panel">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Admin Panel</span>
                    </div>
                </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-3 py-2.5 rounded-xl transition-colors text-rose-600 hover:bg-rose-50 font-medium" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Logout">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Logout</span>
                        </div>
                    </button>
                </form>
            </nav>
        </div>
    </div>
</aside>
