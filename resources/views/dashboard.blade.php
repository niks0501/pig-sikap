<x-app-layout>
    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- 1. Welcome Header -->
            <div class="bg-gradient-to-r from-[#0c6d57] to-emerald-600 rounded-2xl p-6 md:p-8 text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <h2 class="text-2xl md:text-3xl font-bold">Welcome back, {{ Auth::user()->name ?? 'User' }}!</h2>
                        <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full font-medium border border-white/30 backdrop-blur-sm shadow-sm">President</span>
                    </div>
                    <p class="text-emerald-50 text-sm md:text-base">Here is today’s overview of your records and operations.</p>
                </div>
                <div class="hidden md:flex bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20 shadow-sm shadow-black/5">
                    <p class="text-sm font-medium flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ now()->format('l, F j, Y') }}
                    </p>
                </div>
            </div>

            <!-- 2. Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Batches -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Batches</span>
                        <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">4</div>
                    <div class="text-xs text-emerald-600 mt-1 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        All active
                    </div>
                </div>

                <!-- Total Pigs -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Total Pigs</span>
                        <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">124</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">+10 this cycle</div>
                </div>

                <!-- Sick Pigs -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Sick Pigs</span>
                        <div class="bg-orange-50 p-2 rounded-lg text-orange-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">3</div>
                    <div class="text-xs text-orange-600 mt-1 font-medium">2 batches affected</div>
                </div>

                <!-- Deceased Pigs -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Deceased</span>
                        <div class="bg-red-50 p-2 rounded-lg text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">1</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">This month</div>
                </div>

                <!-- Total Expenses -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Total Expenses</span>
                        <div class="bg-rose-50 p-2 rounded-lg text-rose-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800 flex items-baseline gap-1">
                        <span class="text-sm font-normal text-gray-500">₱</span>12.5k
                    </div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">Current cycle</div>
                </div>

                <!-- Total Sales -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500 font-bold">Total Sales</span>
                        <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-[#0c6d57] flex items-baseline gap-1">
                        <span class="text-sm font-normal text-emerald-600">₱</span>45.2k
                    </div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">Current cycle</div>
                </div>
            </div>

            <!-- 3. Quick Actions -->
            <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Quick Actions
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <button class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 text-[#0c6d57] rounded-xl hover:bg-[#0c6d57] hover:text-white transition-colors group border border-emerald-100 hover:border-[#0c6d57]">
                        <svg class="w-6 h-6 mb-2 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span class="text-sm font-semibold text-center group-hover:text-white">Add Batch</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 text-[#0c6d57] rounded-xl hover:bg-[#0c6d57] hover:text-white transition-colors group border border-emerald-100 hover:border-[#0c6d57]">
                        <svg class="w-6 h-6 mb-2 text-rose-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span class="text-sm font-semibold text-center group-hover:text-white">Record Expense</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 text-[#0c6d57] rounded-xl hover:bg-[#0c6d57] hover:text-white transition-colors group border border-emerald-100 hover:border-[#0c6d57]">
                        <svg class="w-6 h-6 mb-2 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold text-center group-hover:text-white">Record Sale</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 text-[#0c6d57] rounded-xl hover:bg-[#0c6d57] hover:text-white transition-colors group border border-emerald-100 hover:border-[#0c6d57]">
                        <svg class="w-6 h-6 mb-2 text-blue-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        <span class="text-sm font-semibold text-center group-hover:text-white">Health Record</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 text-[#0c6d57] rounded-xl hover:bg-[#0c6d57] hover:text-white transition-colors group border border-emerald-100 hover:border-[#0c6d57] col-span-2 md:col-span-1">
                        <svg class="w-6 h-6 mb-2 text-purple-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-sm font-semibold text-center group-hover:text-white">Create Resolution</span>
                    </button>
                </div>
            </div>

            <!-- Lower Layout: Alerts & Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column (Reminders & Mini Overview) -->
                <div class="space-y-6 lg:col-span-1">
                    
                    <!-- 5. Alerts and Reminders Section -->
                    <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                Reminders
                            </h3>
                            <span class="bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-0.5 rounded-full">3 New</span>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-orange-50/70 border border-orange-100/80 rounded-xl">
                                <div class="bg-orange-100 p-1.5 rounded-lg text-orange-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Vaccination Due</p>
                                    <p class="text-xs text-gray-600 mt-0.5">Batch 3 requires Iron supplementation starting tomorrow.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3 p-3 bg-red-50/70 border border-red-100/80 rounded-xl">
                                <div class="bg-red-100 p-1.5 rounded-lg text-red-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Attention Needed</p>
                                    <p class="text-xs text-gray-600 mt-0.5">Please update status for 1 sick pig in Batch 2.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-blue-50/70 border border-blue-100/80 rounded-xl">
                                <div class="bg-blue-100 p-1.5 rounded-lg text-blue-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Pending Review</p>
                                    <p class="text-xs text-gray-600 mt-0.5">Monthly summary report is ready for final approval.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Mini Overview -->
                    <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Cycle Overview
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1 line-clamp-1">
                                    <span class="text-gray-600 font-medium">Batch 1 Growth Phase</span>
                                    <span class="text-[#0c6d57] font-bold">85%</span>
                                </div>
                                <div class="w-full bg-emerald-50 rounded-full h-2.5">
                                    <div class="bg-[#0c6d57] h-2.5 rounded-full" style="width: 85%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Nearing market weight in ~2 weeks.</p>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm mb-1 line-clamp-1">
                                    <span class="text-gray-600 font-medium">Batch 2 Weaning Phase</span>
                                    <span class="text-emerald-500 font-bold">30%</span>
                                </div>
                                <div class="w-full bg-emerald-50 rounded-full h-2.5">
                                    <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 30%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Recent Activity) -->
                <div class="lg:col-span-2">
                    
                    <!-- 4. Recent Activity Section -->
                    <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm border border-gray-100 h-full flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Recent Activity
                            </h3>
                            <button class="text-sm text-[#0c6d57] hover:text-emerald-700 font-medium">View All Logs</button>
                        </div>
                        
                        <div class="flex-1">
                            <div class="relative border-l border-gray-200 ml-3 space-y-6 pb-2">
                                
                                <!-- Activity Item 1 -->
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1 bg-[#0c6d57] w-3 h-3 rounded-full ring-4 ring-white"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">New Sale Recorded</p>
                                        <p class="text-sm text-gray-600 mt-0.5">Sold 5 pigs from <span class="font-medium text-gray-800">Batch 1</span> for <span class="font-medium text-emerald-600">₱35,000</span>.</p>
                                        <p class="text-xs text-gray-400 mt-1">Today, 10:24 AM • Recorded by Juan Dela Cruz</p>
                                    </div>
                                </div>

                                <!-- Activity Item 2 -->
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1 bg-rose-500 w-3 h-3 rounded-full ring-4 ring-white"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Expense Logged</p>
                                        <p class="text-sm text-gray-600 mt-0.5">Purchased Feed Supplements. Amount: <span class="font-medium text-rose-600">₱2,500</span>.</p>
                                        <p class="text-xs text-gray-400 mt-1">Yesterday, 3:15 PM • Recorded by Maria Santos</p>
                                    </div>
                                </div>

                                <!-- Activity Item 3 -->
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1 bg-blue-500 w-3 h-3 rounded-full ring-4 ring-white"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Health Record Updated</p>
                                        <p class="text-sm text-gray-600 mt-0.5">Deworming administered for <span class="font-medium text-gray-800">Batch 4</span>.</p>
                                        <p class="text-xs text-gray-400 mt-1">Oct 12, 9:00 AM • Recorded by Dr. Cruz</p>
                                    </div>
                                </div>

                                <!-- Activity Item 4 -->
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1 bg-emerald-500 w-3 h-3 rounded-full ring-4 ring-white"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">New Batch Created</p>
                                        <p class="text-sm text-gray-600 mt-0.5">Created <span class="font-medium text-gray-800">Batch 4</span> with 20 piglets.</p>
                                        <p class="text-xs text-gray-400 mt-1">Oct 10, 1:45 PM • Recorded by Juan Dela Cruz</p>
                                    </div>
                                </div>
                                
                                <!-- Activity Item 5 -->
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1 bg-purple-500 w-3 h-3 rounded-full ring-4 ring-white"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Resolution Created</p>
                                        <p class="text-sm text-gray-600 mt-0.5">Resolution No. 2026-05: Budget Allocation for Expansion.</p>
                                        <p class="text-xs text-gray-400 mt-1">Oct 05, 2:00 PM • Recorded by Secretary</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
