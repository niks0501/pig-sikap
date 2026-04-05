<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ showFilters: false }">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sales Transactions</h1>
                <p class="text-sm text-gray-500 mt-1">Track pig sales, buyer details, and payment statuses</p>
            </div>
            <div class="w-full sm:w-auto">
                <a href="{{ route('sales.create') }}" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-4 py-2.5 bg-[#0c6d57] border border-transparent text-white font-semibold rounded-xl shadow-sm hover:bg-[#0a5a48] transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Record Sale
                </a>
            </div>
        </div>

        <!-- Totals Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Total Sales (This Month)</p>
                    <p class="text-2xl font-black text-gray-900">₱ 45,200.00</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Pigs Sold</p>
                    <p class="text-2xl font-black text-gray-900">12 Heads</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Pending Payments</p>
                    <p class="text-2xl font-black text-amber-600">₱ 3,500.00</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 relative z-10">
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm transition-colors" placeholder="Search by buyer name or batch...">
                </div>
                <button @click="showFilters = !showFilters" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filters
                    <svg class="w-4 h-4 transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </div>

            <!-- Expandable Filters -->
            <div x-show="showFilters" x-collapse x-cloak>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50/50 rounded-b-xl">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Payment Status</label>
                        <select class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#0c6d57] focus:border-[#0c6d57] py-2">
                            <option>All Statuses</option>
                            <option>Paid</option>
                            <option>Partial</option>
                            <option>Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Sale Type</label>
                        <select class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#0c6d57] focus:border-[#0c6d57] py-2">
                            <option>All Types</option>
                            <option>Live Weight</option>
                            <option>Per Head</option>
                            <option>Carcass</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Batch / Cycle</label>
                        <select class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#0c6d57] focus:border-[#0c6d57] py-2">
                            <option>All Batches</option>
                            <option>Batch 2024-A</option>
                            <option>Batch 2024-B</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- List/Table View content inside -->
        <div class="grid grid-cols-1 gap-4 sm:hidden">
            <!-- Mobile Card 1 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">Juan Dela Cruz</p>
                        <p class="text-xs text-gray-500 mt-0.5">Batch 2024-B • Live Weight</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wider mb-1">
                            Paid
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-end mb-4 border-t border-gray-50 pt-3">
                    <span class="text-xs text-gray-500">Oct 14, 2024</span>
                    <span class="text-lg font-black text-[#0c6d57]">₱ 18,500.00</span>
                </div>
                <a href="{{ route('sales.show', 1) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Transaction
                </a>
            </div>

            <!-- Mobile Card 2 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">Maria's Meat Shop</p>
                        <p class="text-xs text-gray-500 mt-0.5">Batch 2024-B • Live Weight</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 uppercase tracking-wider mb-1">
                            Pending
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-end mb-4 border-t border-gray-50 pt-3">
                    <span class="text-xs text-gray-500">Oct 12, 2024</span>
                    <span class="text-lg font-black text-[#0c6d57]">₱ 3,500.00</span>
                </div>
                <a href="{{ route('sales.show', 2) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Transaction
                </a>
            </div>
            
            <!-- Mobile Card 3 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">Local Market Vendor</p>
                        <p class="text-xs text-gray-500 mt-0.5">Batch 2024-A • Per Head</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 uppercase tracking-wider mb-1">
                            Partial
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-end mb-4 border-t border-gray-50 pt-3">
                    <span class="text-xs text-gray-500">Sep 28, 2024</span>
                    <span class="text-lg font-black text-[#0c6d57]">₱ 23,200.00</span>
                </div>
                <a href="{{ route('sales.show', 3) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Transaction
                </a>
            </div>
        </div>

        <!-- Desktop Table view -->
        <div class="hidden sm:block bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Buyer</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Batch</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sale Type</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Payment Status</th>
                        <th scope="col" class="relative px-6 py-4"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <!-- Row 1 -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 14, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 font-bold block">Juan Dela Cruz</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Batch 2024-B</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Live Weight</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">₱ 18,500.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wider">Paid</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('sales.show', 1) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                    <!-- Row 2 -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 12, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 font-bold block">Maria's Meat Shop</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Batch 2024-B</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Live Weight</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">₱ 3,500.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Pending</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('sales.show', 2) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                    <!-- Row 3 -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Sep 28, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 font-bold block">Local Market Vendor</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Batch 2024-A</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Per Head</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">₱ 23,200.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">Partial</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('sales.show', 3) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Static representation) -->
        <div class="mt-6 flex justify-between items-center bg-transparent">
            <span class="text-sm text-gray-500">Showing 3 of 12 sales</span>
            <div class="flex gap-2">
                <button class="px-3 py-1.5 border border-gray-200 bg-white text-gray-500 rounded-lg hover:bg-gray-50 transition shadow-sm text-sm" disabled>Prev</button>
                <button class="px-3 py-1.5 border border-gray-200 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition shadow-sm text-sm font-medium">Next</button>
            </div>
        </div>

    </div>
</x-app-layout>