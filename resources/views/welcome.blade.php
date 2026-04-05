<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pig-Sikap System</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-800 bg-white">

    <!-- Navbar -->
    <header class="w-full border-b border-gray-100 sticky top-0 bg-white z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center">
                    <img src="{{ asset('images/slp_logo.png') }}" alt="SLP Logo" class="h-12 w-12 object-contain rounded-full">
                    <span class="ml-3 font-bold text-xl text-[#0c6d57]">Pig-Sikap</span>
                </div>
                
                @if (Route::has('login'))
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="font-medium text-gray-600 hover:text-[#0c6d57] transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-[#0c6d57] transition">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-5 py-2.5 bg-[#0c6d57] text-white font-medium rounded-lg hover:bg-[#095946] transition shadow-sm">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 pr-0 md:pr-12 text-center md:text-left mb-12 md:mb-0">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-[#0c6d57] text-sm font-semibold mb-6 border border-emerald-100">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Made for Modern Rural Farming
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                    A simple digital solution for <span class="text-[#0c6d57]">pig farming records.</span>
                </h1>
                <p class="text-lg text-gray-600 mb-8 max-w-lg mx-auto md:mx-0">
                    Reduce manual workload, track expenses, and easily compute your profitability. Your digital assistant designed specifically for community livelihood associations.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-[#0c6d57] text-white text-lg font-medium rounded-lg hover:bg-[#095946] transition shadow-lg text-center">
                        Get Started
                    </a>
                    <a href="#about" class="px-8 py-4 bg-gray-50 text-gray-700 border border-gray-200 text-lg font-medium rounded-lg hover:bg-gray-100 transition text-center">
                        Learn More
                    </a>
                </div>
            </div>
            
            <div class="md:w-1/2">
                <!-- Clean, agricultural friendly abstract illustration placeholder -->
                <div class="bg-emerald-50 rounded-2xl p-8 border border-emerald-100 shadow-sm relative overflow-hidden flex items-center justify-center min-h-[400px]">
                    <div class="absolute -right-10 -bottom-10 bg-[#0c6d57] opacity-10 w-64 h-64 rounded-full blur-3xl"></div>
                    <div class="absolute top-10 -left-10 bg-emerald-400 opacity-20 w-48 h-48 rounded-full blur-2xl"></div>
                    <div class="z-10 bg-white p-6 rounded-xl shadow-lg border border-gray-100 w-full max-w-md">
                        <div class="h-4 w-1/3 bg-gray-200 rounded mb-4"></div>
                        <div class="h-3 w-1/2 bg-gray-100 rounded mb-6"></div>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">✓</div>
                                <div class="flex-1 border-b border-gray-100 pb-2">
                                    <div class="h-3 w-1/4 bg-[#0c6d57] rounded mb-2"></div>
                                    <div class="h-2 w-3/4 bg-gray-100 rounded"></div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">✓</div>
                                <div class="flex-1 border-b border-gray-100 pb-2">
                                    <div class="h-3 w-1/3 bg-[#0c6d57] rounded mb-2"></div>
                                    <div class="h-2 w-1/2 bg-gray-100 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="bg-[#0c6d57] text-white py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">From manual notebooks to organized digital records</h2>
                <p class="text-xl text-emerald-100 max-w-2xl mx-auto">
                    Pig-Sikap digitizes your traditional logbooks to preserve your associations' practices, making data safe, accessible, and simple to report.
                </p>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything You Need</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">Designed carefully for rural associations, replacing paper completely.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Pig Inventory Management</h3>
                        <p class="text-gray-600">Easily track the number of pigs you have, categorize by batches, and monitor their status.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Health & Vaccination Tracking</h3>
                        <p class="text-gray-600">Keep records of pig checkups, sicknesses, and vaccination schedules safely in one place.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Expense Recording</h3>
                        <p class="text-gray-600">Log feeds, vitamins, materials, and other daily costs to prevent losing financial track.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Sales & Buyer Tracking</h3>
                        <p class="text-gray-600">Organize your sales, track who bought the pigs, and view your revenue accurately.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Profit Computation</h3>
                        <p class="text-gray-600">Never miscalculate again. Simply enter your expenses and sales, and let the system compute profit automatically.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Reports Generation</h3>
                        <p class="text-gray-600">Print or view instant, clean reports suitable for association meetings and reviews.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How It Works</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">A seamless transition to digital tools without changing how you farm.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                    <!-- Connector line for desktop -->
                    <div class="hidden md:block absolute top-8 left-[12%] right-[12%] h-[2px] bg-emerald-100"></div>

                    <!-- Step 1 -->
                    <div class="relative text-center">
                        <div class="w-16 h-16 mx-auto bg-[#0c6d57] text-white rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-md">1</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Record Batches</h4>
                        <p class="text-sm text-gray-600">Register new pig batches and activities into the system.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">2</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Track Regularly</h4>
                        <p class="text-sm text-gray-600">Enter daily health logs, expenses, and final sales as they happen.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">3</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Compute Output</h4>
                        <p class="text-sm text-gray-600">The system automatically calculates total profit minus total expenses.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative text-center">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">4</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Generate Reports</h4>
                        <p class="text-sm text-gray-600">Instantly pull up records to show to your association officers.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problem vs Solution -->
        <section class="py-16 bg-gray-50 border-y border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    <!-- Manual Problems -->
                    <div class="bg-white p-8 rounded-xl border border-red-100 shadow-sm">
                        <h3 class="text-xl font-bold text-red-600 mb-6 flex items-center">
                            <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Manual Problems
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start text-gray-600"><span class="mr-2 text-red-500 font-bold">✗</span> Logbooks and messy handwritten records</li>
                            <li class="flex items-start text-gray-600"><span class="mr-2 text-red-500 font-bold">✗</span> Time-consuming report creation</li>
                            <li class="flex items-start text-gray-600"><span class="mr-2 text-red-500 font-bold">✗</span> Difficult expense and profit calculation</li>
                            <li class="flex items-start text-gray-600"><span class="mr-2 text-red-500 font-bold">✗</span> High risk of lost, damaged, or wet records</li>
                        </ul>
                    </div>

                    <!-- Pig-Sikap Solution -->
                    <div class="bg-white p-8 rounded-xl border border-emerald-200 shadow-sm ring-1 ring-emerald-50">
                        <h3 class="text-xl font-bold text-[#0c6d57] mb-6 flex items-center">
                            <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pig-Sikap Solution
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start text-gray-800"><span class="mr-2 text-[#0c6d57] font-bold">✓</span> Clear digital record keeping</li>
                            <li class="flex items-start text-gray-800"><span class="mr-2 text-[#0c6d57] font-bold">✓</span> Automatic, one-click report generation</li>
                            <li class="flex items-start text-gray-800"><span class="mr-2 text-[#0c6d57] font-bold">✓</span> Instant, accurate profit computation</li>
                            <li class="flex items-start text-gray-800"><span class="mr-2 text-[#0c6d57] font-bold">✓</span> Organized, backed-up, and secure data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Target Users & Benefits Combined Area -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Target Users -->
                <div class="mb-16">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">Who is Pig-Sikap for?</h2>
                    <div class="flex flex-wrap justify-center gap-4 md:gap-8">
                        <div class="px-6 py-4 bg-emerald-50 rounded-lg text-[#0c6d57] font-semibold border border-emerald-100 flex items-center shadow-sm">
                            Association Members (Farmers)
                        </div>
                        <div class="px-6 py-4 bg-emerald-50 rounded-lg text-[#0c6d57] font-semibold border border-emerald-100 flex items-center shadow-sm">
                            President & Officers
                        </div>
                        <div class="px-6 py-4 bg-emerald-50 rounded-lg text-[#0c6d57] font-semibold border border-emerald-100 flex items-center shadow-sm">
                            Treasurer & Secretary
                        </div>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="pt-8 border-t border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-900 mb-10">Why use the system?</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left max-w-5xl mx-auto">
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Faster record updates</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Organized financial records</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Reduced manual workload</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Improved transparency</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Easier report generation</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="text-[#0c6d57] text-xl">✦</div><span class="text-gray-700 font-medium">Better decision-making</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="bg-[#0c6d57] text-white py-16 md:py-20 border-b-8 border-emerald-400">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight">Start managing your pig farming records the easier way.</h2>
                <div class="mt-8">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-block px-10 py-5 bg-white text-[#0c6d57] text-xl font-bold rounded-xl shadow-lg hover:bg-gray-50 transition transform hover:-translate-y-1">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-block px-10 py-5 bg-white text-[#0c6d57] text-xl font-bold rounded-xl shadow-lg hover:bg-gray-50 transition transform hover:-translate-y-1">
                            Get Started Now
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-8 text-center md:text-left">
                <div>
                    <div class="flex items-center justify-center md:justify-start mb-4">
                        <span class="font-bold text-xl text-white">Pig-Sikap</span>
                    </div>
                    <p class="text-sm text-gray-400 max-w-xs">
                        A Web-Based Livelihood Monitoring and Profitability Analytics System.
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-8 text-sm">
                    <a href="#" class="hover:text-white transition">Home</a>
                    <a href="#about" class="hover:text-white transition">About</a>
                    <a href="#" class="hover:text-white transition">Features</a>
                    <a href="{{ route('login') }}" class="hover:text-white transition">Member Login</a>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-sm text-center text-gray-500">
                &copy; {{ date('Y') }} Pig-Sikap. Designed for proper association management.
            </div>
        </div>
    </footer>

</body>
</html>
