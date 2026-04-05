<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Reports & Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 border-b border-gray-100">

                    <!-- Header Section -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Available Reports</h3>
                            <p class="text-sm text-gray-500 mt-1">Generate required reports for meetings and documentation.</p>
                        </div>
                    </div>

                    <!-- Prebuilt Report Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        
                        <!-- Pig Inventory Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Pig Inventory</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Summary of current livestock counts, stages, and cycle tracking.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'inventory']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Health Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Health</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Vaccination records, medicine usage, and overall herd health status.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'health']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Mortality Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Mortality</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Records of lost livestock with recorded causes and timestamps.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'mortality']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Expense Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Expenses</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Breakdown of feeds, vitmains, operations, and other costs incurred.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'expense']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Sales Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Sales</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Detailing pigs sold, average kg weight, price per kg, and totals.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'sales']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Profitability Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Profitability</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Return on Investment (ROI) and net profit calculation per cycle.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'profitability']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                        <!-- Monthly Summary Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Monthly Summary</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Compiled overview of operations, cashflow, and events for a selected month.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'monthly']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>
                        
                        <!-- Quarterly Summary Report -->
                        <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md hover:border-[#0c6d57]/30 transition-all group flex flex-col h-full">
                            <div class="p-5 flex-grow">
                                <div class="w-10 h-10 rounded-lg bg-[#0c6d57]/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1">Quarterly Summary</h4>
                                <p class="text-xs text-gray-500 line-clamp-2">Comprehensive 3-month aggregated business presentation report.</p>
                            </div>
                            <div class="p-3 border-t">
                                <a href="{{ route('reports.generate', ['type' => 'quarterly']) }}" class="flex items-center justify-center w-full py-2 px-4 rounded-lg bg-gray-50 text-sm font-medium text-[#0c6d57] hover:bg-[#0c6d57]/10 transition-colors">
                                    Generate Report
                                </a>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>