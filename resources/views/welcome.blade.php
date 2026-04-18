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
    <header class="w-full border-b border-gray-100 sticky top-0 bg-[#064e3b] z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/slp_logo.png') }}" alt="SLP Logo" class="h-12 w-12 object-contain rounded-full">
                    <span class="text-white font-bold text-xl text-[#0c6d57]" style="font-family: Fraunces, serif;">Pig-Sikap</span>
                </div>

                <nav class="hidden md:flex items-center bg-[#197c65] px-8 py-2.5 rounded-full border border-white/10 space-x-10" style="font-family: Fraunces, serif;">
                    <a href="#home" class="text-white font-bold text-sm hover:text-emerald-200 transition">Home</a>
                    <a href="#about" class="text-white font-bold text-sm hover:text-emerald-200 transition">About</a>
                    <a href="#features" class="text-white font-bold text-sm hover:text-emerald-200 transition">Features</a>
                    <a href="#how-it-works" class="text-white font-bold text-sm hover:text-emerald-200 transition">How it Works</a>
                    <a href="#who-is-it-for" class="text-white font-bold text-sm hover:text-emerald-200 transition">Who is it for?</a>
                </nav>
                
                <div class="flex items-center">
                @if (Route::has('login'))
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="font-medium text-gray-600 hover:text-[#0c6d57] transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="px-8 py-2.5 border-2 border-white text-white font-bold rounded-full text-sm hover:bg-white hover:text-[#0c6d57] transition" style="font-family: Fraunces, serif;">Log in</a>
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
        <section id="home" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 pr-0 md:pr-12 text-center md:text-left mb-12 md:mb-0">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-[#0c6d57] text-sm font-semibold mb-6 border border-emerald-100">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" pt-20 py-4>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Made for Modern Rural Farming
                </div>
                <h1 class="fade-up fade-up-d1 text-4xl md:text-5xl lg:text-[3.4rem] font-bold text-black leading-[1.1] mb-6" style="font-family: Fraunces, serif;">
                    A digital solution for <span class="text-[#0c6d57]">pig farming records.</span>
                </h1>
                <p class="text-lg text-gray-600 mb-8 max-w-lg mx-auto md:mx-0" style="font-family: Fraunces, serif;">
                    Reduce manual workload, track expenses, and easily compute your profitability. Your digital assistant designed specifically for community livelihood associations.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                
                    <a href="login" class="px-8 py-4 bg-[#0c6d57] text-white font-bold rounded-lg shadow-lg hover:bg-[#095946] transition" style="font-family: Fraunces, serif;">
                        Get Started →
                    </a>
                </div>
            </div>
            
            <div class="md:w-1/2 flex justify-center items-center mt-10 md:mt-0">
                <img src="{{ asset('images/anime-piglets.png') }}" 
                     alt="Pig-Sikap Digital Assistants" 
                     class="w-full max-w-md lg:max-w-xl object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500" />
            </div>
        </section>


        <section id="about"class="py-20 bg-emerald-50 border-b border-emerald-100" style="font-family: Fraunces, serif;">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-[#0c6d57] mb-6">About Pig-Sikap</h1>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-12 lg:gap-20 bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-emerald-100">
                    
                    <div class="w-full md:w-1/2 relative group">
                        <div class="overflow-hidden rounded-[2rem] aspect-[4/3] bg-gray-100 shadow-sm border border-gray-200">
                            <img id="about-carousel" src="{{ asset('images/Photo-1.png') }}" alt="Pig-Sikap Farmers" class="w-full h-full object-cover transition-opacity duration-300 ease-in-out">
                        </div>
                        
                        <button onclick="prevSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/80 hover:bg-white text-[#0c6d57] rounded-full shadow-md transition cursor-pointer z-10 focus:outline-none backdrop-blur-sm border border-emerald-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        
                        <button onclick="nextSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/80 hover:bg-white text-[#0c6d57] rounded-full shadow-md transition cursor-pointer z-10 focus:outline-none backdrop-blur-sm border border-emerald-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>

                    <div class="w-full md:w-1/2">
                        <p class="text-3xl md:text-4xl font-light text-gray-500 leading-relaxed tracking-wide font-mono" style="font-family: Fraunces, serif;">
                            <span class="text-lg text-gray-600 mb-8 max-w-lg mx-auto md:mx-0" style="font-family: Fraunces, serif;">The Elite Visionaries of Humayingan, SLP Association will take traditional swine-raising and elevate it into a data-driven enterprise. Building on our foundation from October 2024, we will meticulously plan every phase of our operations from assigning canvassing tasks and forecasting feed requirements to tracking rearing timelines. Through the Pig-Sikap system, our association will replace manual guesswork with business analytics. We will ensure that every community policy we implement will drive transparency, reduce manual workloads, and maximize profitability for all our members.</span>
                        </p>
                    </div>

                </div>
            <script>
                const slides = [
                    "{{ asset('images/Photo-1.png') }}",
                    "{{ asset('images/Photo-2.png') }}",
                    "{{ asset('images/Photo-3.png') }}",
                    "{{ asset('images/Photo-4.png') }}",
                    "{{ asset('images/Photo-5.png') }}"
                ];
                
                let currentSlide = 0;
                const slideImage = document.getElementById('about-carousel');

                function updateSlide() {
                    slideImage.style.opacity = '0.5'; 
                    setTimeout(() => {
                        slideImage.src = slides[currentSlide];
                        slideImage.style.opacity = '1';
                    }, 150);
                }

                function nextSlide() {
                    currentSlide = (currentSlide === slides.length - 1) ? 0 : currentSlide + 1;
                    updateSlide();
                }

                function prevSlide() {
                    currentSlide = (currentSlide === 0) ? slides.length - 1 : currentSlide - 1;
                    updateSlide();
                }
            </script>
        </section>

        <!-- Features Grid -->
        <section id="features" class="py-20 bg-green-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 py-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4"  style="font-family: Fraunces, serif;">Everything You Need</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto"  style="font-family: Fraunces, serif;">Designed carefully for rural associations, replacing paper completely.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Pig Inventory Management</h3>
                        <p class="text-gray-600">Easily track the number of pigs you have, categorize by batches, and monitor their status.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Health & Vaccination Tracking</h3>
                        <p class="text-gray-600">Keep records of pig checkups, sicknesses, and vaccination schedules safely in one place.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Expense Recording</h3>
                        <p class="text-gray-600">Log feeds, vitamins, materials, and other daily costs to prevent losing financial track.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-[#0c6d57] mb-6">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Sales & Buyer Tracking</h3>
                        <p class="text-gray-600">Organize your sales, track who bought the pigs, and view your revenue accurately.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
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
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition"  style="font-family: Fraunces, serif;">
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
        <section id="how-it-works"class="py-20 bg-white" style="font-family: Fraunces, serif;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 py-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How It Works</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">A seamless transition to digital tools without changing how you farm.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                    <!-- Connector line for desktop -->
                    <div class="hidden md:block absolute top-8 left-[12%] right-[12%] h-[2px] bg-emerald-100"></div>

                    <!-- Step 1 -->
                    <div class="relative text-center group cursor-pointer">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">1</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Record Batches</h4>
                        <p class="text-sm text-gray-600">Register new pig batches and activities into the system.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center group cursor-pointer">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">2</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Track Regularly</h4>
                        <p class="text-sm text-gray-600">Enter daily health logs, expenses, and final sales as they happen.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center group cursor-pointer">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">3</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Compute Output</h4>
                        <p class="text-sm text-gray-600">The system automatically calculates total profit minus total expenses.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative text-center group cursor-pointer">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-[#0c6d57] text-[#0c6d57] group-hover:bg-[#0c6d57] group-hover:text-white transition-colors duration-300 rounded-full flex items-center justify-center text-xl font-bold mb-6 relative z-10 shadow-sm">4</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Generate Reports</h4>
                        <p class="text-sm text-gray-600">Instantly pull up records to show to your association officers.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problem vs Solution -->
        <section class="py-16 bg-gray-50 border-y border-gray-200" style="font-family: Fraunces, serif;">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    <!-- Manual Problems -->
                    <div class="bg-green p-8 rounded-xl border border-red-100 shadow-sm">
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
        <section id="who-is-it-for"class="py-20 bg-white" style="font-family: Fraunces, serif;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-20 py-4">
                <!-- Target Users -->
                <div class="mb-16">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">Who is Pig-Sikap for?</h2>
                    <div class="flex flex-wrap justify-center gap-4 md:gap-8">
                        <div class="px-6 py-4 bg-emerald-50 rounded-lg text-[#0c6d57] font-semibold border border-emerald-100 flex items-center shadow-sm">
                            Association Members
                        </div>
                        <div class="px-6 py-4 bg-emerald-50 rounded-lg text-[#0c6d57] font-semibold border border-emerald-100 flex items-center shadow-sm">
                            President & Officers
                        </div>
                    </div>
            </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12" style="font-family: Fraunces, serif;">
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
