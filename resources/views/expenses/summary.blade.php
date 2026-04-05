<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ timeframe: 'this_month' }">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Expense Summary</h1>
                    <p class="text-sm text-gray-500 mt-1">Simplified overview of association expenses</p>
                </div>
            </div>
            
            <div class="w-full sm:w-auto bg-white border border-gray-200 rounded-xl p-1 flex">
                <button @click="timeframe = 'this_month'" class="flex-1 sm:flex-none px-4 py-1.5 text-sm font-semibold rounded-lg transition-colors" :class="timeframe === 'this_month' ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:text-gray-900'">This Month</button>
                <button @click="timeframe = 'last_month'" class="flex-1 sm:flex-none px-4 py-1.5 text-sm font-semibold rounded-lg transition-colors" :class="timeframe === 'last_month' ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:text-gray-900'">Last Month</button>
                <button @click="timeframe = 'all_time'" class="flex-1 sm:flex-none px-4 py-1.5 text-sm font-semibold rounded-lg transition-colors" :class="timeframe === 'all_time' ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:text-gray-900'">All Time</button>
            </div>
        </div>

        <!-- Highlight Total -->
        <div class="bg-[#0c6d57] rounded-2xl shadow-sm overflow-hidden mb-6 relative">
            <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIj48L3JlY3Q+CjxwYXRoIGQ9Ik0wIDBMOCA4Wk04IDBMMCA4WiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utd2lkdGg9IjEiPjwvcGF0aD4KPC9zdmc+')]"></div>
            <div class="px-6 py-10 sm:p-10 relative z-10 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h2 class="text-white/80 font-bold tracking-widest text-sm uppercase mb-2">Total Expenses (Selected Period)</h2>
                    <div class="text-4xl sm:text-6xl font-black text-white tracking-tight">₱ 18,450.00</div>
                </div>
                <a href="{{ route('expenses.create') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-3 bg-white text-[#0c6d57] font-bold rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Entry
                </a>
            </div>
        </div>

        <!-- Breakdown Grid -->
        <h3 class="text-lg font-bold text-gray-900 mb-4 px-1">Expense Breakdown</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Cards -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col hover:border-amber-200 transition-colors group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                </div>
                <p class="text-sm font-bold text-gray-500 tracking-wider">FEEDS</p>
                <p class="text-2xl font-black text-gray-900 mt-1">₱ 14,000.00</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col hover:border-rose-200 transition-colors group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                </div>
                <p class="text-sm font-bold text-gray-500 tracking-wider">MEDICINES & VITS</p>
                <p class="text-2xl font-black text-gray-900 mt-1">₱ 2,150.00</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col hover:border-blue-200 transition-colors group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                </div>
                <p class="text-sm font-bold text-gray-500 tracking-wider">TRANSPORT</p>
                <p class="text-2xl font-black text-gray-900 mt-1">₱ 800.00</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col hover:border-gray-200 transition-colors group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path></svg>
                    </div>
                </div>
                <p class="text-sm font-bold text-gray-500 tracking-wider">OTHERS</p>
                <p class="text-2xl font-black text-gray-900 mt-1">₱ 1,500.00</p>
            </div>
            
        </div>

    </div>
</x-app-layout>