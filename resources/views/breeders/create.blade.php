<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Register New Inahin / Breeder</h2>
                <p class="text-sm text-gray-500 mt-1">Record a new breeding cycle or sow registry.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="#" method="POST" class="space-y-8">
                    @csrf
                    
                    <!-- Breeder Identification -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Breeder Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Identifier / Ear Tag -->
                            <div>
                                <label for="breeder_name" class="block text-sm font-bold text-gray-700 mb-1.5">Breeder ID / Tag Name *</label>
                                <input type="text" id="breeder_name" name="breeder_name" placeholder="e.g. Inahin A" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>

                            <!-- Source Batch -->
                            <div>
                                <label for="source_batch" class="block text-sm font-bold text-gray-700 mb-1.5">Original Batch (If applicable)</label>
                                <select id="source_batch" name="source_batch" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="" selected>Not from an internal batch</option>
                                    <option value="B-001">Batch B-001</option>
                                    <option value="B-002">Batch B-002</option>
                                    <option value="B-003">Batch B-003</option>
                                </select>
                                <p class="text-[10px] text-gray-500 mt-1 font-medium">Link if this breeder came from one of your own litters.</p>
                            </div>

                        </div>
                    </div>

                    <!-- Reproductive Data -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Current Reproductive Status</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Date of Breeding / Insemination ->
                            <div>
                                <label for="breeding_date" class="block text-sm font-bold text-gray-700 mb-1.5">Date of Breeding / Insemination</label>
                                <input type="date" id="breeding_date" name="breeding_date" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>

                            <!-- Expected Farrowing -->
                            <div>
                                <label for="farrowing_date" class="block text-sm font-bold text-gray-700 mb-1.5">Expected Farrowing Date</label>
                                <input type="date" id="farrowing_date" name="farrowing_date" readonly class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 font-medium sm:text-sm cursor-not-allowed">
                                <p class="text-[10px] text-gray-500 mt-1 font-medium">Auto-calculated (114 days from breeding).</p>
                            </div>

                            <!-- Current Status -->
                            <div class="sm:col-span-2">
                                <label for="status" class="block text-sm font-bold text-gray-700 mb-1.5">Pregnancy / Cycle Status *</label>
                                <select id="status" name="status" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="open">Open / Ready to Breed</option>
                                    <option value="inseminated" selected>Bred / Inseminated (Pending Check)</option>
                                    <option value="pregnant">Confirmed Pregnant</option>
                                    <option value="farrowed">Farrowed (Lactating)</option>
                                    <option value="weaned">Weaned</option>
                                    <option value="cull">Cull / Retire</option>
                                </select>
                            </div>

                            <!-- Notes / Remarks -->
                            <div class="sm:col-span-2">
                                <label for="notes" class="block text-sm font-bold text-gray-700 mb-1.5">Breeder Notes & Remarks</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Add information regarding boar used, method, parity number, general health prior to breeding..." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all"></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                            Save Breeder Record
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