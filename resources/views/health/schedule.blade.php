<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Vaccination & Treatment Schedule</h2>
                <p class="text-sm text-gray-500 mt-1">View required health activities based on batch birth dates.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto" x-data="{ showCompleteModal: false }">
        
        <!-- Filters -->
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Filter by Status</label>
                <select class="block w-full py-2.5 px-4 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                    <option>All Due Soon & Overdue</option>
                    <option>Upcoming Only</option>
                    <option>Overdue Only</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Filter by Batch</label>
                <select class="block w-full py-2.5 px-4 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                    <option>All Active Batches</option>
                    <option>BAT-001</option>
                    <option>BAT-002</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm">
                    Filter
                </button>
            </div>
        </div>

        <!-- Schedule List -->
        <div class="space-y-8">
            
            <!-- Overdue Section -->
            <div>
                <h3 class="text-base font-bold text-red-600 flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Overdue Actions Needed
                </h3>
                <div class="bg-red-50/50 rounded-3xl border border-red-100 overflow-hidden divide-y divide-red-100">
                    <!-- Schedule Item -->
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex flex-col items-center justify-center shrink-0">
                                <span class="text-xs font-bold uppercase leading-none">APR</span>
                                <span class="text-lg font-bold leading-none mt-0.5">02</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">Iron Injection (Day 3)</h4>
                                <div class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <a href="{{ route('batches.health', 'BAT-001') }}" class="font-bold border-b border-transparent hover:border-gray-600">BAT-001</a>
                                    <span class="text-gray-300">•</span>
                                    <span>Birth Date: Mar 30, 2026</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="font-bold text-red-600">4 days overdue</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 flex gap-2">
                            <button @click="showCompleteModal = true" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-red-600 text-white font-bold text-sm rounded-xl hover:bg-red-700 transition-colors shadow-sm">
                                Mark Completed
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Week Section -->
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Due This Week
                </h3>
                <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden divide-y divide-gray-100 shadow-sm">
                    <!-- Schedule Item -->
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex flex-col items-center justify-center shrink-0">
                                <span class="text-xs font-bold uppercase leading-none">APR</span>
                                <span class="text-lg font-bold leading-none mt-0.5">08</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">1st Deworming (Day 10)</h4>
                                <div class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <a href="{{ route('batches.health', 'BAT-002') }}" class="font-bold border-b border-transparent hover:border-gray-600">BAT-002</a>
                                    <span class="text-gray-300">•</span>
                                    <span>Birth Date: Mar 29, 2026</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-2 bg-gray-50 p-2 rounded-lg inline-block">Use Blue Mark on back.</p>
                            </div>
                        </div>
                        <div class="shrink-0 flex gap-2">
                            <button @click="showCompleteModal = true" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-[#0c6d57]/10 text-[#0c6d57] font-bold text-sm rounded-xl hover:bg-[#0c6d57]/20 transition-colors shadow-none">
                                Mark Completed
                            </button>
                        </div>
                    </div>
                    
                    <!-- Schedule Item -->
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex flex-col items-center justify-center shrink-0">
                                <span class="text-xs font-bold uppercase leading-none">APR</span>
                                <span class="text-lg font-bold leading-none mt-0.5">10</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">Vitamins A,D,E (Day 14)</h4>
                                <div class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <a href="{{ route('batches.health', 'BAT-004') }}" class="font-bold border-b border-transparent hover:border-gray-600">BAT-004</a>
                                    <span class="text-gray-300">•</span>
                                    <span>Birth Date: Mar 27, 2026</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 flex gap-2">
                            <button @click="showCompleteModal = true" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-[#0c6d57]/10 text-[#0c6d57] font-bold text-sm rounded-xl hover:bg-[#0c6d57]/20 transition-colors shadow-none">
                                Mark Completed
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Reuse Modal From Index -->
        <div x-show="showCompleteModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
            <div x-show="showCompleteModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showCompleteModal" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         class="relative transform overflow-hidden rounded-3xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <div>
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="mt-5 text-center sm:mt-6">
                                <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Confirm Completed Activity</h3>
                                <p class="text-sm text-gray-500 mt-2">Was this activity completed for the batch?</p>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4 text-left">
                            <div>
                                <label for="completion_date" class="block text-sm font-bold text-gray-700 mb-1.5">Date Completed</label>
                                <input type="date" id="completion_date" name="completion_date" value="2026-04-06" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>
                            <div>
                                <label for="remarks" class="block text-sm font-bold text-gray-700 mb-1.5">Remarks / Color Markings Used</label>
                                <textarea id="remarks" name="remarks" rows="2" placeholder="e.g. Marked blue..." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]"></textarea>
                            </div>
                        </div>

                        <div class="mt-8 sm:mt-8 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" @click="showCompleteModal = false" class="inline-flex w-full justify-center rounded-xl bg-[#0c6d57] px-3 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-[#0a5a48] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0c6d57] sm:col-start-2">
                                Save Completion
                            </button>
                            <button type="button" @click="showCompleteModal = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-3.5 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>