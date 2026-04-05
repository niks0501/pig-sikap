<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('batches.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Batch B-001</h2>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Piglets</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">From Inahin A • Registered Sep 12, 2024</p>
                </div>
            </div>
            <div class="hidden sm:flex sm:items-center gap-3">
                <a href="{{ route('batches.edit', 'B-001') }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit
                </a>
                <button type="button" x-data @click="$dispatch('open-modal', 'status-update')" class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57] gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Update Batch Status
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6 relative">
        
        <!-- Mobile Actions (Hidden on desktop) -->
        <div class="sm:hidden grid grid-cols-2 gap-3 mb-6">
            <button type="button" x-data @click="$dispatch('open-modal', 'status-update')" class="w-full inline-flex justify-center items-center px-4 py-3 bg-[#0c6d57] text-white font-bold text-sm rounded-xl shadow-sm focus:outline-none">
                Update Status
            </button>
            <a href="{{ route('batches.edit', 'B-001') }}" class="w-full inline-flex justify-center items-center px-4 py-3 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl shadow-sm focus:outline-none">
                Edit Record
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:items-start" x-data="{ showStatusModal: false }">
            
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Data Highlight Cards -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-6">Batch Profile Overview</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                        <!-- Stat 1 -->
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Total Pigs
                            </p>
                            <p class="text-3xl font-bold text-gray-900 mb-0.5">8 <span class="text-sm text-gray-400">/ 8</span></p>
                            <p class="text-xs text-emerald-600 font-medium">Original count</p>
                        </div>
                        
                        <!-- Stat 2 -->
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Age Group
                            </p>
                            <p class="text-3xl font-bold text-gray-900 mb-0.5">Weaners</p>
                            <p class="text-xs text-gray-500 font-medium">8 weeks old</p>
                        </div>

                        <!-- Stat 3 (Spans 2 cols on mobile) -->
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Caretaker
                            </p>
                            <div class="flex items-center gap-2.5 mt-2">
                                <div class="bg-emerald-50 text-[#0c6d57] font-bold w-10 h-10 rounded-full flex items-center justify-center shrink-0">JD</div>
                                <div>
                                    <p class="text-sm text-gray-900 font-bold leading-tight">Juan Dela Cruz</p>
                                    <p class="text-xs text-gray-500 font-medium">Since Registration</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes and Information -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Initial Remarks & Notes</h3>
                    <div class="prose prose-sm text-gray-600 max-w-none">
                        <p>Batch sourced internally from Inahin A's third parity. Healthy and active upon inspection. Assigned immediately to Juan's block.</p>
                        <p class="mb-0"><strong>Feeding Plan:</strong> Booster pellets assigned for Month 1. Iron injection given on Day 3.</p>
                    </div>
                </div>

                <!-- Modals -->
                <!-- Status Update Modal -->
                <!-- Assuming standard Alpine modal or Breeze default components here. Using raw markup for illustration as x-show / dialog. -->
                <x-modal name="status-update" focusable>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Update Batch Status</h2>
                        
                        <form class="space-y-6">
                            @csrf
                            
                            <div>
                                <label for="new_status" class="block text-sm font-bold text-gray-700 mb-1.5">New Livestock Phase/Status</label>
                                <select id="new_status" name="status" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="piglets" selected>Piglets (Current)</option>
                                    <option value="fatteners">Promote to Fatteners</option>
                                    <option value="breeders">Select as Breeders/Inahin candidate</option>
                                    <option disabled>──────────</option>
                                    <option value="sick">Mark batch as Sick / Recovering</option>
                                    <option value="deceased">Report Mortality (-1 Head)</option>
                                    <option value="sold">Report Sales / Complete Cycle</option>
                                </select>
                            </div>

                            <div>
                                <label for="update_date" class="block text-sm font-bold text-gray-700 mb-1.5">Date of Update</label>
                                <input type="date" id="update_date" value="{{ date('Y-m-d') }}" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>

                            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-xl border border-yellow-100 text-sm hidden">
                                <strong>Note:</strong> Reducing headcount. Please specify the reason below.
                            </div>

                            <div>
                                <label for="reason" class="block text-sm font-bold text-gray-700 mb-1.5">Reason or Remarks <span class="text-gray-400 font-medium">(Required for Status changes)</span></label>
                                <textarea id="reason" rows="3" placeholder="Explain the status change..." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]"></textarea>
                            </div>

                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" x-on:click="$dispatch('close')" class="px-5 py-3 border border-gray-200 rounded-xl text-gray-700 font-bold bg-white hover:bg-gray-50 transition-colors">Cancel</button>
                                <button type="button" class="px-5 py-3 rounded-xl text-white font-bold bg-[#0c6d57] hover:bg-[#0a5a48] transition-colors">Save Update</button>
                            </div>
                        </form>
                    </div>
                </x-modal>

            </div>

            <!-- Right Column: Timeline / History -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8 lg:sticky lg:top-24">
                    <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-6">Status History</h3>
                    
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            <!-- Timeline Item 2 -->
                            <li>
                                <div class="relative pb-8">
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center ring-8 ring-white z-10 shrink-0">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-900 font-medium">Head count updated <strong>(+1 recovered from illness)</strong></p>
                                                <p class="text-sm text-gray-500 mt-1">Total count adjusted to 8</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-[11px] font-medium text-gray-400">
                                                <time datetime="2024-10-15">Oct 15</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Timeline Item 3 -->
                            <li>
                                <div class="relative pb-8">
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-[#0c6d57]/10 flex items-center justify-center ring-8 ring-white z-10 shrink-0">
                                                <svg class="w-4 h-4 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-[#0c6d57] font-bold">Batch Registered</p>
                                                <p class="text-sm text-gray-500 mt-1">Created by admin</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-[11px] font-medium text-gray-400">
                                                <time datetime="2024-09-12">Sep 12</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>