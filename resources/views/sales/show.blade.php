<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('sales.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Sale Record #SR-1204</h1>
                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wider">Paid</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Recorded on October 14, 2024</p>
                </div>
            </div>
            <!-- Action (Edit not requested logically but handy) -->
            <a href="#" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl shadow-sm hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit Details
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <!-- Buyer Details Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Buyer Information
                    </h3>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-[#0c6d57]/10 text-[#0c6d57] rounded-full flex items-center justify-center font-black text-xl shrink-0">
                            JD
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">Juan Dela Cruz</h4>
                            <p class="text-sm text-gray-600 mt-1 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                0917-123-4567
                            </p>
                            <p class="text-sm text-gray-600 mt-0.5 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Public Market
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details Card -->
                <div class="bg-[#0c6d57] rounded-2xl shadow-sm border border-[#0a5a48] overflow-hidden relative">
                    <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIj48L3JlY3Q+CjxwYXRoIGQ9Ik0wIDBMOCA4Wk04IDBMMCA4WiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utd2lkdGg9IjEiPjwvcGF0aD4KPC9zdmc+')]"></div>
                    
                    <div class="p-6 sm:p-8 relative z-10 text-white">
                        <div class="mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded bg-white/20 text-white text-xs font-bold uppercase tracking-wider">
                                Live Weight Sale
                            </span>
                        </div>
                        
                        <p class="text-[#0c6d57]-100 font-medium mb-1">Total Sale Amount</p>
                        <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-8">₱ 18,500.00</h2>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-6 border-t border-white/20">
                            <div>
                                <p class="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Batch</p>
                                <p class="text-lg font-semibold">2024-B</p>
                            </div>
                            <div>
                                <p class="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Weight</p>
                                <p class="text-lg font-semibold">92.5 kg</p>
                            </div>
                            <div>
                                <p class="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Price/Kg</p>
                                <p class="text-lg font-semibold">₱ 200.00</p>
                            </div>
                            <div>
                                <p class="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Sale Date</p>
                                <p class="text-lg font-semibold">Oct 14</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Proof Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 h-full">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Temporary Receipt
                    </h3>
                    
                    <div class="w-full aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden border border-gray-200 relative group">
                        <!-- Simulated Uploaded Image -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                            <svg class="w-12 h-12 mb-3 text-[#0c6d57]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-sm font-semibold text-gray-500">handwritten_receipt.jpg</span>
                            <span class="text-xs text-gray-400 mt-1">1.2 MB</span>
                        </div>
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-gray-900/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="bg-white text-gray-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center gap-2 hover:bg-gray-50 uppercase tracking-wide">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                View Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        
        <!-- Mobile Edit Action -->
        <div class="mt-6 sm:hidden">
            <a href="#" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl shadow-sm hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit Transaction Details
            </a>
        </div>

    </div>
</x-app-layout>