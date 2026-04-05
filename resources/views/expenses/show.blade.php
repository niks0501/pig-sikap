<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Expense Details</h1>
                    <p class="text-sm text-gray-500 mt-1">Recorded on Oct 12, 2024</p>
                </div>
            </div>
            <a href="{{ route('expenses.edit', $id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl shadow-sm hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Summary Info -->
            <div class="md:col-span-2 space-y-6">
                <!-- Highlight Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8 relative">
                    <div class="absolute top-0 right-0 p-6 pointer-events-none opacity-10 text-[#0c6d57]">
                        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>

                    <div class="mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-amber-100 text-amber-800 tracking-wide uppercase">
                            Feeds
                        </span>
                    </div>

                    <h2 class="text-4xl font-black text-gray-900 tracking-tight mb-2">₱ 4,500.00</h2>
                    <p class="text-xl text-gray-600 font-medium mb-8">Grower Feeds (3 Sacks)</p>
                    
                    <div class="grid grid-cols-2 gap-6 pt-6 border-t border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Batch / Cycle</p>
                            <p class="text-base text-gray-900 font-semibold">Batch 2024-B</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Recorded By</p>
                            <p class="text-base text-gray-900 font-semibold">Pedro Officer</p>
                        </div>
                    </div>
                </div>

                <!-- Descriptive Panel (Optional notes) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-3">Additional Notes</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Purchased 3 sacks of Grower Feeds from Agri-Vert supplies exactly on the scheduled feed resupply day. Required for the next 2 weeks of portioning.
                    </p>
                </div>
            </div>

            <!-- Receipt Preview -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-4">Official Receipt</h3>
                    
                    <div class="w-full aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden border border-gray-200 relative group">
                        <!-- Placeholder Image -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                            <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-sm font-medium">Receipt Image Attached</span>
                        </div>
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-gray-900/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="bg-white text-gray-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center gap-2 hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                View Full
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>