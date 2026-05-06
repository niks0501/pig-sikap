<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pig-Sikap — Livelihood Monitoring & Profitability System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .hero-gradient { background: linear-gradient(135deg, #0c6d57 0%, #0a5a48 50%, #074d3b 100%); }
        .card-ring { box-shadow: 0 1px 3px rgba(12,109,87,.06), 0 1px 2px rgba(12,109,87,.04); }
        .step-dot::after { content: ''; position: absolute; top: 50%; left: 100%; width: 100%; height: 2px; background: #d1fae5; transform: translateY(-50%); }
        .step-dot:last-child::after { display: none; }
    </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-white">

    <!-- Navbar -->
    <header class="hero-gradient sticky top-0 z-50 backdrop-blur-sm bg-opacity-95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-18 items-center">
                <div class="flex items-center gap-3 shrink-0">
                    <img src="{{ asset('images/slp_logo.png') }}" alt="SLP Logo" class="h-10 w-10 object-contain rounded-xl bg-white/10 p-1">
                    <span class="text-white font-bold text-xl tracking-tight">Pig-Sikap</span>
                </div>

                <nav class="hidden md:flex items-center gap-1 bg-white/10 backdrop-blur px-2 py-1.5 rounded-full">
                    <a href="#home" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white transition rounded-full hover:bg-white/10">Home</a>
                    <a href="#how-to-join" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white transition rounded-full hover:bg-white/10">How to Join</a>
                    <a href="#features" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white transition rounded-full hover:bg-white/10">Features</a>
                    <a href="#how-it-works" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white transition rounded-full hover:bg-white/10">How it Works</a>
                    <a href="#who-is-it-for" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white transition rounded-full hover:bg-white/10">Who is it for?</a>
                </nav>

                <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 rounded-xl transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 text-sm font-semibold bg-white text-[#0c6d57] rounded-xl hover:bg-emerald-50 transition shadow-lg shadow-black/10">Log in</a>
                    @endauth
                @endif
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero-gradient overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28 lg:py-36">
                <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                    <div class="lg:w-3/5 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/15 text-white/90 text-sm font-medium mb-8">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Built for Philippine Rural Associations
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-[1.08] tracking-tight mb-6">
                            Smarter pig farming<br class="hidden sm:block">
                            <span class="text-emerald-200">starts with clear records.</span>
                        </h1>
                        <p class="text-lg text-emerald-100/80 leading-relaxed mb-10 max-w-xl mx-auto lg:mx-0">
                            Replace paper logbooks with a digital system that tracks your pigs, expenses, sales, and profitability — designed for community livelihood associations.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('login') }}" class="px-8 py-3.5 bg-white text-[#0c6d57] font-semibold rounded-xl hover:bg-emerald-50 transition shadow-xl shadow-black/10 text-center">
                                Log in
                            </a>
                            <a href="#features" class="px-8 py-3.5 border border-white/25 text-white font-semibold rounded-xl hover:bg-white/10 transition text-center">
                                See Features
                            </a>
                        </div>
                    </div>
                    <div class="lg:w-2/5 flex justify-center">
                        <img src="{{ asset('images/anime-piglets.png') }}"
                             alt="Pig-Sikap Digital Assistants"
                             class="w-full max-w-sm lg:max-w-md drop-shadow-2xl hover:scale-105 transition-transform duration-500" />
                    </div>
                </div>
            </div>
            <!-- Wave divider -->
            <div class="h-16 bg-white" style="clip-path: ellipse(60% 100% at 50% 100%);"></div>
        </section>

        <!-- Trusted / Stats bar -->
        <section class="py-12 bg-white border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                    <div><p class="text-3xl font-bold text-[#0c6d57]">100%</p><p class="text-sm text-gray-500 mt-1">Digital Records</p></div>
                    <div><p class="text-3xl font-bold text-[#0c6d57]">24/7</p><p class="text-sm text-gray-500 mt-1">Access Anywhere</p></div>
                    <div><p class="text-3xl font-bold text-[#0c6d57]">Auto</p><p class="text-sm text-gray-500 mt-1">Profit Calculation</p></div>
                    <div><p class="text-3xl font-bold text-[#0c6d57]">Secure</p><p class="text-sm text-gray-500 mt-1">Data Backup</p></div>
                </div>
            </div>
        </section>

        <!-- How to Join -->
        <section id="how-to-join" class="py-20 bg-gray-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <p class="text-sm font-semibold text-[#0c6d57] uppercase tracking-wider mb-3">Membership</p>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How to Join Elite Visionaries Association</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">Simple informational guide. No sensitive documents are uploaded here.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-3 mb-10">
                    <div class="bg-white rounded-2xl p-6 card-ring">
                        <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg mb-5">1</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Coordinate With Officers</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">Talk to current officers of Elite Visionaries Association for the latest meeting schedule, requirements, and profiling instructions.</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 card-ring">
                        <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg mb-5">2</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Prepare Requirements</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">Prepare paper copies only. Do not upload IDs or certificates here — this page is informational.</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 card-ring">
                        <div class="w-12 h-12 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57] font-bold text-lg mb-5">3</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">DSWD SLP Profiling</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">The SLP Focal Person checks if the applicant qualifies under program criteria such as Listahanan or 4Ps.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 card-ring">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div>
                            <p class="text-xs font-semibold text-[#0c6d57] uppercase tracking-wider">Checklist</p>
                            <h3 class="text-xl font-bold text-gray-900 mt-1">Membership File Requirements</h3>
                        </div>
                        <span class="self-start inline-flex items-center px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-amber-700 text-xs font-semibold">Paper documents only</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                            <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                            <span>Photocopy of Valid ID</span>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                            <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                            <span>Barangay Certificate of Residency</span>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer sm:col-span-2">
                            <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                            <span>Barangay Certificate of Indigency, if required for DSWD profiling</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                    <h3 class="text-sm font-bold text-amber-900 mb-1">Privacy Reminder</h3>
                    <p class="text-sm text-amber-700 leading-relaxed">Keep valid IDs and certificates in the official paper membership file. Only add digital document uploads later if the system has a secure approved pattern for sensitive files.</p>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <p class="text-sm font-semibold text-[#0c6d57] uppercase tracking-wider mb-3">Features</p>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything your association needs</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">Designed carefully for rural associations, replacing paper logbooks completely.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $features = [
                        ['icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'title' => 'Pig Inventory', 'desc' => 'Track pig counts by cycle, monitor status, and manage batches with ease.'],
                        ['icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'title' => 'Health Records', 'desc' => 'Log checkups, vaccinations, and sickness incidents — all in one organized place.'],
                        ['icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z', 'title' => 'Expense Tracking', 'desc' => 'Log feeds, vitamins, materials, and daily costs. Never lose track of your spending.'],
                        ['icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'title' => 'Sales & Buyers', 'desc' => 'Organize sales, track buyers, issue digital receipts, and view revenue accurately.'],
                        ['icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z', 'title' => 'Profit Analytics', 'desc' => 'Automatic profit computation. Enter expenses and sales — the system does the math.'],
                        ['icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title' => 'Instant Reports', 'desc' => 'Generate clean, professional reports for meetings, DSWD submissions, and audits.'],
                    ]; @endphp
                    @foreach ($features as $f)
                    <div class="group bg-white rounded-2xl p-6 card-ring hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                        <div class="w-11 h-11 rounded-xl bg-[#0c6d57]/8 flex items-center justify-center text-[#0c6d57] mb-5 group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/></svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="py-20 bg-gray-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <p class="text-sm font-semibold text-[#0c6d57] uppercase tracking-wider mb-3">Process</p>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How It Works</h2>
                    <p class="text-gray-500">A seamless transition to digital — without changing how you farm.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @php $steps = [
                        ['num' => 1, 'title' => 'Record Cycles', 'desc' => 'Register new pig cycles and activities into the system.'],
                        ['num' => 2, 'title' => 'Track Daily', 'desc' => 'Enter health logs, expenses, and sales as they happen.'],
                        ['num' => 3, 'title' => 'Auto Compute', 'desc' => 'The system calculates total profit minus total expenses.'],
                        ['num' => 4, 'title' => 'Generate Reports', 'desc' => 'Pull up records instantly for your association officers.'],
                    ]; @endphp
                    @foreach ($steps as $step)
                    <div class="relative text-center group">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-white border-2 border-[#0c6d57]/15 text-[#0c6d57] group-hover:bg-[#0c6d57] group-hover:text-white group-hover:border-[#0c6d57] transition-all duration-300 flex items-center justify-center text-lg font-bold mb-5 shadow-sm">
                            {{ $step['num'] }}
                        </div>
                        <h4 class="text-base font-semibold text-gray-900 mb-2">{{ $step['title'] }}</h4>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Problem vs Solution -->
        <section class="py-20 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-rose-500">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-rose-700">Without Pig-Sikap</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-2.5 text-sm text-gray-700"><span class="text-rose-400 font-bold shrink-0 mt-0.5">&times;</span> Messy handwritten logbooks</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-700"><span class="text-rose-400 font-bold shrink-0 mt-0.5">&times;</span> Time-consuming report creation</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-700"><span class="text-rose-400 font-bold shrink-0 mt-0.5">&times;</span> Difficult expense & profit calculation</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-700"><span class="text-rose-400 font-bold shrink-0 mt-0.5">&times;</span> Lost, damaged, or wet records</li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-[#0c6d57]/15 bg-[#0c6d57]/3 p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-[#0c6d57]/15 flex items-center justify-center text-[#0c6d57]">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-[#0c6d57]">With Pig-Sikap</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-2.5 text-sm text-gray-800"><span class="text-[#0c6d57] font-bold shrink-0 mt-0.5">&#10003;</span> Clear digital record keeping</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-800"><span class="text-[#0c6d57] font-bold shrink-0 mt-0.5">&#10003;</span> Automatic one-click report generation</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-800"><span class="text-[#0c6d57] font-bold shrink-0 mt-0.5">&#10003;</span> Instant, accurate profit computation</li>
                            <li class="flex items-start gap-2.5 text-sm text-gray-800"><span class="text-[#0c6d57] font-bold shrink-0 mt-0.5">&#10003;</span> Organized, backed-up, and secure data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Who is it for -->
        <section id="who-is-it-for" class="py-20 bg-gray-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-sm font-semibold text-[#0c6d57] uppercase tracking-wider mb-3">Audience</p>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Who is Pig-Sikap for?</h2>
                <p class="text-gray-500 mb-10">Built for the people who make community livelihoods work every day.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <div class="px-8 py-4 bg-white rounded-2xl card-ring hover:shadow-md transition-all duration-300">
                        <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">Association Members</p>
                    </div>
                    <div class="px-8 py-4 bg-white rounded-2xl card-ring hover:shadow-md transition-all duration-300">
                        <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-[#0c6d57]/10 flex items-center justify-center text-[#0c6d57]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">President & Officers</p>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-16 bg-[#0c6d57] rounded-3xl p-10 sm:p-12 text-white">
                    <h3 class="text-2xl font-bold mb-3">Ready to go digital?</h3>
                    <p class="text-emerald-100/80 mb-8 max-w-md mx-auto">Start tracking your pig farming operation with clear, organized digital records today.</p>
                    <div class="flex justify-center">
                        <a href="{{ route('login') }}" class="px-10 py-3 bg-white text-[#0c6d57] font-semibold rounded-xl hover:bg-emerald-50 transition shadow-lg">Log in</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="hero-gradient text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <!-- Brand -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/slp_logo.png') }}" alt="SLP Logo" class="h-9 w-9 object-contain rounded-xl bg-white/10 p-1">
                        <span class="font-bold text-lg tracking-tight">Pig-Sikap</span>
                    </div>
                    <p class="text-sm text-emerald-100/70 leading-relaxed mb-4">A Web-Based Livelihood Monitoring and Profitability Analytics System for the Elite Visionaries of Humayingan SLP Association.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 text-emerald-200">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm text-emerald-100/70">
                        <li><a href="#home" class="hover:text-white transition">Home</a></li>
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#how-it-works" class="hover:text-white transition">How it Works</a></li>
                        <li><a href="#who-is-it-for" class="hover:text-white transition">Who is it for?</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 text-emerald-200">Resources</h4>
                    <ul class="space-y-2.5 text-sm text-emerald-100/70">
                        <li><a href="#how-to-join" class="hover:text-white transition">How to Join</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Member Login</a></li>
                    </ul>
                </div>

                <!-- Association -->
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 text-emerald-200">Association</h4>
                    <ul class="space-y-2.5 text-sm text-emerald-100/70">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Elite Visionaries of Humayingan SLP Association</span>
                        </li>
                        <li>Barangay Humayingan</li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-white/10 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-emerald-100/50">
                <p>&copy; {{ date('Y') }} Pig-Sikap. All rights reserved.</p>
                <p>Crafted for proper association management.</p>
            </div>
        </div>
    </footer>
</body>
</html>
