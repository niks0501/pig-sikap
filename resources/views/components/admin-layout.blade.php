@props([
    'title' => 'System Administrator',
    'subtitle' => 'Technical administration panel',
    'breadcrumb' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pig-Sikap') }} - System Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=lexend:400,500,600,700|source-sans-3:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased" style="font-family: 'Source Sans 3', sans-serif;" x-data="{ mobileMenuOpen: false, profileOpen: false }">
        <div class="min-h-screen lg:flex">
            <div
                x-show="mobileMenuOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
                @click="mobileMenuOpen = false"
                x-cloak
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 px-4 py-6 transform transition-transform duration-200 lg:translate-x-0 lg:static lg:inset-auto"
                :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="flex items-center gap-3 px-3 pb-6 border-b border-slate-100">
                    <img src="{{ asset('images/slp_logo.png') }}" alt="Pig-Sikap logo" class="h-10 w-10 rounded-lg object-contain bg-emerald-50 p-1">
                    <div>
                        <p class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Pig-Sikap</p>
                        <p class="text-xs text-slate-500">System Administrator</p>
                    </div>
                </div>

                <nav class="mt-6 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-50 text-[#0c6d57]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-emerald-50 text-[#0c6d57]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Users</span>
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.roles.*') ? 'bg-emerald-50 text-[#0c6d57]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Roles</span>
                    </a>
                    <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.activity-logs.*') ? 'bg-emerald-50 text-[#0c6d57]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Activity Logs</span>
                    </a>
                    <a href="{{ route('admin.profile') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.profile*') ? 'bg-emerald-50 text-[#0c6d57]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Profile</span>
                    </a>
                </nav>

                <div class="mt-8 border-t border-slate-100 pt-6">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 lg:ml-0">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden"
                            @click="mobileMenuOpen = true"
                            aria-label="Open menu"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="hidden sm:block">
                            <p class="text-xs uppercase tracking-wide text-slate-500">System Administrator Module</p>
                            <p class="text-sm font-semibold text-slate-800" style="font-family: 'Lexend', sans-serif;">Elite Visionaries Association</p>
                        </div>

                        <div class="relative">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700"
                                @click="profileOpen = !profileOpen"
                                @keydown.escape.window="profileOpen = false"
                            >
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="profileOpen"
                                x-transition
                                @click.outside="profileOpen = false"
                                class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-lg"
                                x-cloak
                            >
                                <a href="{{ route('admin.profile') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="mt-1 block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="px-4 py-5 sm:px-6 lg:px-8">
                    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">System Admin</p>
                        <h1 class="mt-1 text-2xl font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">{{ $title }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
                        @if ($breadcrumb)
                            <p class="mt-2 text-xs font-medium text-slate-500">{{ $breadcrumb }}</p>
                        @endif
                    </div>

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
