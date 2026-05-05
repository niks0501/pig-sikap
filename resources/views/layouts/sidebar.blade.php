<!-- Sidebar layout navigation -->
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
            <!-- Ensure img exists in public/images/ -->
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

            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#0c6d57]/10 text-[#0c6d57] font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Dashboard">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-[#0c6d57]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Dashboard</span>
                    </div>
                </a>

                <a href="{{ route('cycles.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('cycles.*') ? 'bg-[#0c6d57]/10 text-[#0c6d57] font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Cycles">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('cycles.*') ? 'text-[#0c6d57]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Cycles</span>
                    </div>

                </a>

                <a href="{{ route('health.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('health.*') ? 'bg-amber-100 text-amber-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Health & Treatments">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('health.*') ? 'text-amber-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Health & Treatments</span>
                    </div>
                </a>

                <!-- Sales -->
                <a href="{{ route('sales.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('sales.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Sales Transactions">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sales.*') ? 'text-blue-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Sales Log</span>
                    </div>
                </a>

                <a href="{{ route('expenses.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('expenses.*') ? 'bg-violet-100 text-violet-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Expenses">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('expenses.*') ? 'text-violet-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Expenses</span>
                    </div>
                </a>

    <a href="{{ route('profitability.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('profitability.*') || request()->routeIs('profit-sharing') ? 'bg-emerald-100 text-emerald-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Profitability">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('profitability.*') || request()->routeIs('profit-sharing') ? 'text-emerald-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Profitability</span>
                    </div>
                </a>

                <a href="{{ route('membership.how-to-join') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('membership.*') ? 'bg-[#0c6d57]/10 text-[#0c6d57] font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Members">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('membership.*') ? 'text-[#0c6d57]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Members</span>
                    </div>
                </a>

    <a href="{{ route('reports.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('reports.*') ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Reports">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('reports.*') ? 'text-gray-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Reports</span>
                    </div>
                </a>

                <a href="{{ route('workflow.meetings.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('workflow.meetings*') || request()->routeIs('workflow.resolutions*') || request()->routeIs('workflow.withdrawals*') ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Docs & Approvals">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('workflow.meetings*') || request()->routeIs('workflow.resolutions*') || request()->routeIs('workflow.withdrawals*') ? 'text-gray-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Docs & Approvals</span>
                    </div>
                </a>

                <a href="{{ route('workflow.canvasses.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('workflow.canvasses*') ? 'bg-emerald-100 text-emerald-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Canvassing">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('workflow.canvasses*') ? 'text-emerald-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Canvassing</span>
                    </div>
                </a>

                <a href="{{ route('workflow.suppliers.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('workflow.suppliers*') ? 'bg-emerald-100 text-emerald-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Suppliers">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('workflow.suppliers*') ? 'text-emerald-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Suppliers</span>
                    </div>
                </a>

                <a href="{{ route('workflow.penalties.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('workflow.penalties*') ? 'bg-amber-100 text-amber-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Penalties">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('workflow.penalties*') ? 'text-amber-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Penalties</span>
                    </div>
                </a>

                @can('manage', \App\Models\AssociationPolicySetting::class)
                <a href="{{ route('workflow.settings.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('workflow.settings.*') ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Policy Settings">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('workflow.settings.*') ? 'text-gray-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Policy Settings</span>
                    </div>
                </a>
                @endcan

                <a href="{{ route('audit-trails.index') }}" class="flex items-center px-3 py-2.5 rounded-xl mb-1 transition-colors {{ request()->routeIs('audit-trails.*') ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium' }}" :class="sidebarOpen ? 'justify-between' : 'justify-center'" title="Audit Trails">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('audit-trails.*') ? 'text-gray-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">Audit Trails</span>
                    </div>
                </a>
            </nav>
        </div>

        <div class="border-t border-gray-100 my-2"></div>

        <!-- Quick Actions -->
        <div x-show="sidebarOpen" x-transition.opacity.duration.300ms x-cloak>
            <div class="mb-3 px-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Quick Actions</span>
            </div>

            <div class="space-y-1">
                <a href="{{ route('cycles.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <span class="whitespace-nowrap">New Cycle</span>
                </a>

                <a href="{{ route('expenses.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="whitespace-nowrap">Add Expense</span>
                </a>

                <a href="{{ route('sales.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <span class="whitespace-nowrap">Record Sale</span>
                </a>

                <a href="{{ route('health.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <span class="whitespace-nowrap">Health Incident</span>
                </a>
            </div>
        </div>

        <div class="border-t border-gray-100 my-2"></div>

        <!-- Management -->
        <div class="mt-auto">
            <div class="mb-3 transition-all duration-300" :class="sidebarOpen ? 'px-2' : 'flex justify-center'">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide" x-show="sidebarOpen">Account</span>
                <span class="text-xs font-bold text-gray-400" x-show="!sidebarOpen">...</span>
            </div>

            <nav class="space-y-1">
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
