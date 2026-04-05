<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ showFilters: false }">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Expense Records</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and track your association's daily expenses</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('expenses.summary') }}" class="flex-1 sm:flex-none justify-center inline-flex items-center gap-2 px-4 py-2.5 bg-white border-2 border-[#0c6d57] text-[#0c6d57] font-semibold rounded-xl hover:bg-[#0c6d57]/5 transition-colors focus:ring-2 focus:ring-[#0c6d57]/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    View Summary
                </a>
                <a href="{{ route('expenses.create') }}" class="flex-1 sm:flex-none justify-center inline-flex items-center gap-2 px-4 py-2.5 bg-[#0c6d57] border border-transparent text-white font-semibold rounded-xl shadow-sm hover:bg-[#0a5a48] transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Expense
                </a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 relative z-10">
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm transition-colors" placeholder="Search description...">
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
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                        <select class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#0c6d57] focus:border-[#0c6d57] py-2">
                            <option>All Categories</option>
                            <option>Feeds</option>
                            <option>Medicines</option>
                            <option>Vitamins</option>
                            <option>Transport</option>
                            <option>Emergency</option>
                            <option>Others</option>
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
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Date Range</label>
                        <input type="month" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#0c6d57] focus:border-[#0c6d57] py-2">
                    </div>
                </div>
            </div>
        </div>

        <!-- List/Table View content inside -->
        <div class="grid grid-cols-1 gap-4 sm:hidden">
            <!-- Mobile Card 1 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between hover:border-[#0c6d57]/30 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800">
                        Feeds
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold items-center text-gray-900 block leading-none">₱ 4,500.00</span>
                        <span class="text-xs text-gray-500">Oct 12, 2024</span>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-900">Grower Feeds (3 Sacks)</p>
                    <p class="text-xs text-gray-500 mt-0.5">Batch 2024-B</p>
                </div>
                <a href="{{ route('expenses.show', 1) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Details
                </a>
            </div>

            <!-- Mobile Card 2 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between hover:border-[#0c6d57]/30 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-100 text-rose-800">
                        Medicines
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold items-center text-gray-900 block leading-none">₱ 850.00</span>
                        <span class="text-xs text-gray-500">Oct 10, 2024</span>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-900">Antibiotics for Fever</p>
                    <p class="text-xs text-gray-500 mt-0.5">Batch 2024-B</p>
                </div>
                <a href="{{ route('expenses.show', 2) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Details
                </a>
            </div>
            
            <!-- Mobile Card 3 -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between hover:border-[#0c6d57]/30 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800">
                        Transport
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold items-center text-gray-900 block leading-none">₱ 300.00</span>
                        <span class="text-xs text-gray-500">Oct 08, 2024</span>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-900">Gasoline for Feeds Pickup</p>
                    <p class="text-xs text-gray-500 mt-0.5">General</p>
                </div>
                <a href="{{ route('expenses.show', 3) }}" class="w-full inline-flex justify-center text-sm font-medium text-[#0c6d57] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 py-2 rounded-lg transition-colors">
                    View Details
                </a>
            </div>
        </div>

        <!-- Desktop Table view -->
        <div class="hidden sm:block bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Batch/Cycle</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="relative px-6 py-4"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 12, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800">Feeds</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">Grower Feeds (3 Sacks)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Batch 2024-B</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₱ 4,500.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('expenses.show', 1) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 10, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-100 text-rose-800">Medicines</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">Antibiotics for Fever</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Batch 2024-B</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₱ 850.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('expenses.show', 2) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 08, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800">Transport</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">Gasoline for Feeds Pickup</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">General</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₱ 300.00</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('expenses.show', 3) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48] transition-colors">Details &rarr;</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Static representation) -->
        <div class="mt-6 flex justify-between items-center bg-transparent">
            <span class="text-sm text-gray-500">Showing 3 of 32 records</span>
            <div class="flex gap-2">
                <button class="px-3 py-1.5 border border-gray-200 bg-white text-gray-500 rounded-lg hover:bg-gray-50 transition shadow-sm text-sm" disabled>Prev</button>
                <button class="px-3 py-1.5 border border-gray-200 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition shadow-sm text-sm font-medium">Next</button>
            </div>
        </div>

    </div>
</x-app-layout>