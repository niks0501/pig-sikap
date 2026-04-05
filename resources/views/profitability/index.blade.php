<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                Profitability Overview
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 px-4 sm:px-0">
            <!-- Overall Sales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Sales (YTD)</p>
                    <p class="text-2xl font-bold text-gray-900">₱85,000.00</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Overall Expenses -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Expenses (YTD)</p>
                    <p class="text-2xl font-bold text-gray-900">₱42,500.00</p>
                </div>
                <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
            </div>

            <!-- Net Profit -->
            <div class="bg-[#0c6d57] rounded-xl shadow-sm border border-[#0c6d57] p-6 flex items-center justify-between text-white">
                <div>
                    <p class="text-sm font-medium text-green-100 mb-1">Net Profit (YTD)</p>
                    <p class="text-2xl font-bold">₱42,500.00</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
        </div>

        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Farming Cycles</h3>
            
            <!-- Cycle List -->
            <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl">
                <div class="divide-y divide-gray-100">
                    <!-- Cycle 1 (Profitable) -->
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Batch 2024-A (Fattener)</h4>
                                    <p class="text-sm text-gray-500">Jan 1, 2024 - Apr 30, 2024 • Completed</p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-800">
                                            Sales: ₱85,000
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-800">
                                            Expenses: ₱42,500
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-left sm:text-right flex flex-row sm:flex-col items-center sm:items-end justify-between gap-4 sm:gap-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-0.5">Net Profit</p>
                                    <p class="text-xl font-bold text-green-600">+₱42,500.00</p>
                                </div>
                                <a href="{{ route('profitability.show', 1) }}" class="inline-flex items-center text-sm font-medium text-[#0c6d57] hover:text-[#0a5c49]">
                                    View Breakdown
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cycle 2 (Loss) -->
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">Batch 2023-C (Breeder)</h4>
                                    <p class="text-sm text-gray-500">Sep 1, 2023 - Dec 15, 2023 • Completed</p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-800">
                                            Sales: ₱15,000
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-800">
                                            Expenses: ₱20,000
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-left sm:text-right flex flex-row sm:flex-col items-center sm:items-end justify-between gap-4 sm:gap-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-0.5">Net Loss</p>
                                    <p class="text-xl font-bold text-red-600">-₱5,000.00</p>
                                </div>
                                <a href="{{ route('profitability.show', 2) }}" class="inline-flex items-center text-sm font-medium text-[#0c6d57] hover:text-[#0a5c49]">
                                    View Summary
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Showing records of past completed farming cycles.</p>
            </div>
        </div>
    </div>
</x-app-layout>