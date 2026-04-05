<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Add Treatment / Health Record</h2>
                <p class="text-sm text-gray-500 mt-1">Manually record a medication, vaccination, or treatment for a batch.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="#" method="POST" class="space-y-8">
                    @csrf
                    
                    <!-- Batch Target -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Target Batch</h3>
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label for="batch_id" class="block text-sm font-bold text-gray-700 mb-1.5">Select Batch / Litter *</label>
                                <select id="batch_id" name="batch_id" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="" selected disabled>Select an active batch...</option>
                                    <option value="BAT-001">BAT-001 (12 head, 7 days old)</option>
                                    <option value="BAT-002">BAT-002 (8 head, 14 days old)</option>
                                    <option value="BAT-003">BAT-003 (10 head, 21 days old)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Treatment Details -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Treatment Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Treatment Type -->
                            <div class="sm:col-span-2">
                                <label for="treatment_type" class="block text-sm font-bold text-gray-700 mb-1.5">Type of Health Activity *</label>
                                <select id="treatment_type" name="treatment_type" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="" selected disabled>Select Activity...</option>
                                    <option value="vitamins">Vitamins (A, D, E)</option>
                                    <option value="iron">Iron Injection</option>
                                    <option value="deworming">Deworming</option>
                                    <option value="vaccination_hc">Hog Cholera Vaccine</option>
                                    <option value="illness_treatment">Sick Treatment / Antibiotic</option>
                                    <option value="other">Other Activity</option>
                                </select>
                            </div>

                            <!-- Date fields -->
                            <div>
                                <label for="scheduled_date" class="block text-sm font-bold text-gray-700 mb-1.5">Date Scheduled / Administered *</label>
                                <input type="date" id="scheduled_date" name="scheduled_date" value="{{ date('Y-m-d') }}" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-bold text-gray-700 mb-1.5">Action Status *</label>
                                <select id="status" name="status" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="completed" selected>Already Completed Today</option>
                                    <option value="pending">Schedule for Later (Pending)</option>
                                </select>
                            </div>

                            <!-- Remarks / Color Marks -->
                            <div class="sm:col-span-2">
                                <label for="remarks" class="block text-sm font-bold text-gray-700 mb-1.5">Physical Marks & Remarks</label>
                                <textarea id="remarks" name="remarks" rows="3" placeholder="e.g. Used red spray paint on back to indicate treated pigs. 1 pig skipped due to weakness." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all"></textarea>
                                <p class="text-[10px] text-gray-500 mt-1 font-medium">Record any visual cues you placed on the pigs or any observations about the treatment.</p>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                            Save Treatment Record
                        </button>
                        <a href="javascript:history.back()" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>