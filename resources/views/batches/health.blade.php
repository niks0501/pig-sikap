<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('batches.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Batch {{ $id }} Health History</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">
                        Healthy
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Review the complete health and treatment timeline.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto" x-data="{ showCompleteModal: false }">
        
        <!-- Batch Summary Banner -->
        <div class="bg-[#0c6d57] rounded-3xl p-6 mb-8 text-white shadow-md relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
                <svg class="w-48 h-48 -mt-8 -mr-8" fill="currentColor" viewBox="0 0 24 24"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
            </div>
            <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-[#86d4c1] text-xs font-bold uppercase tracking-wider mb-1">Birth Date</p>
                    <p class="text-lg font-bold">Mar 15, 2026</p>
                </div>
                <div>
                    <p class="text-[#86d4c1] text-xs font-bold uppercase tracking-wider mb-1">Current Age</p>
                    <p class="text-lg font-bold">22 Days (Weanling)</p>
                </div>
                <div>
                    <p class="text-[#86d4c1] text-xs font-bold uppercase tracking-wider mb-1">Total Head</p>
                    <p class="text-lg font-bold">12 Pigs</p>
                </div>
                <div>
                    <a href="{{ route('health.create') }}" class="inline-flex w-full justify-center items-center px-4 py-2.5 bg-white text-[#0c6d57] font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                        Add Treatment
                    </a>
                </div>
            </div>
        </div>

        <!-- Health Timeline -->
        <h3 class="text-lg font-bold text-gray-900 mb-6">Treatment Timeline</h3>
        
        <div class="relative border-l-2 border-gray-200 pl-4 sm:pl-6 ml-2 sm:ml-4 space-y-8">
            
            <!-- Future / Pending Record -->
            <div class="relative">
                <div class="absolute -left-[23px] sm:-left-[35px] top-1 w-5 h-5 rounded-full bg-white border-4 border-amber-400 mt-0.5"></div>
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-amber-100">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-md mb-2 inline-block">Scheduled: Apr 14, 2026</span>
                            <h4 class="text-lg font-bold text-gray-900">2nd Deworming (Day 30)</h4>
                        </div>
                        <button @click="showCompleteModal = true" class="px-3 py-1.5 bg-[#0c6d57]/10 text-[#0c6d57] font-bold text-xs rounded-lg hover:bg-[#0c6d57]/20 transition-colors">
                            Mark Done
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">Standard routine. To be followed up after weaning period transition.</p>
                </div>
            </div>

            <!-- Completed Record -->
            <div class="relative">
                <div class="absolute -left-[23px] sm:-left-[35px] top-1 w-5 h-5 rounded-full bg-emerald-500 ring-4 ring-white mt-0.5 flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 opacity-80">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-xs font-bold text-gray-500 mb-2 inline-block">Completed: Mar 29, 2026</span>
                            <h4 class="text-lg font-bold text-gray-900">Vitamins A,D,E (Day 14)</h4>
                        </div>
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">Done</span>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-sm text-gray-600 border border-gray-100">
                        <strong class="text-gray-900 block mb-1">Remarks & Markings:</strong>
                        Marked with blue paint on the back. All 12 piglets successfully ingested.
                    </div>
                </div>
            </div>

            <!-- Completed Record 2 -->
            <div class="relative">
                <div class="absolute -left-[23px] sm:-left-[35px] top-1 w-5 h-5 rounded-full bg-emerald-500 ring-4 ring-white mt-0.5 flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 opacity-80">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-xs font-bold text-gray-500 mb-2 inline-block">Completed: Mar 18, 2026</span>
                            <h4 class="text-lg font-bold text-gray-900">Iron Injection (Day 3)</h4>
                        </div>
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">Done</span>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-sm text-gray-600 border border-gray-100">
                        <strong class="text-gray-900 block mb-1">Remarks & Markings:</strong>
                        Standard procedure done inside farrowing crate. Red mark placed behind the ears.
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>