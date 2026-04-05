<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('resolutions.index') }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Process New Fund Withdrawal
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 flex gap-3 text-yellow-800">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="text-sm font-medium">
                Withdrawals from the association fund require an <strong>approved resolution</strong> and must be validated by the DSWD focal person before release.
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form>
                    <div class="space-y-6">
                        <!-- Required Resolution Link -->
                        <div class="border-b border-gray-100 pb-6">
                            <label class="block text-sm font-bold text-[#0c6d57] mb-2 uppercase tracking-wider">Step 1: Link Resolution</label>
                            <p class="text-sm text-gray-500 mb-3">Please select the formally approved resolution authorizing this specific withdrawal.</p>
                            <select class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base text-gray-900 font-medium">
                                <option value="">-- Select Approved Resolution --</option>
                                <option value="1">Res No. 2024-05: Authorization for Fund Withdrawal (Oct 12)</option>
                            </select>
                        </div>

                        <!-- Withdrawal Details -->
                        <div class="pt-2">
                            <label class="block text-sm font-bold text-[#0c6d57] mb-2 uppercase tracking-wider">Step 2: Withdrawal Details</label>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Request Title / Purpose</label>
                                <input type="text" placeholder="e.g. Pen Repair Contingency Fund" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Requested Amount (₱)</label>
                                <input type="number" placeholder="0.00" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base font-bold text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Request Date</label>
                            <input type="date" value="{{ date('Y-m-d') }}" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Description / Justification</label>
                            <textarea rows="4" placeholder="Briefly describe what this specific withdrawal covers." class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-4 bg-gray-50 focus:bg-white text-base leading-relaxed"></textarea>
                        </div>

                        <!-- Attachments -->
                        <div class="border-t border-gray-100 pt-6">
                            <label class="block text-sm font-bold text-[#0c6d57] mb-2 uppercase tracking-wider">Step 3: Attach Proof / Vouchers</label>
                            <p class="text-sm text-gray-500 mb-3">Upload quotations, invoices, or signed vouchers.</p>
                            
                            <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed border-gray-300 hover:border-[#0c6d57] rounded-xl transition-colors cursor-pointer bg-gray-50 hover:bg-[#0c6d57]/5">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 28v-8a4 4 0 014-4h16a4 4 0 014 4v8M24 24v16m-8-8h16" />
                                    </svg>
                                    <div class="flex text-base text-gray-600 justify-center">
                                        <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-[#0c6d57] hover:text-[#0a5c49] focus-within:outline-none">
                                            <span>Upload a document</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" multiple>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-sm text-gray-500">Bank slips or quotations (IMG, PDF)</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-3">
                        <a href="{{ route('resolutions.index') }}" class="w-full sm:w-auto text-center px-5 py-3 border border-gray-300 shadow-sm text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                            Cancel
                        </a>
                        <button type="button" class="w-full sm:w-auto text-center px-5 py-3 border border-[#0c6d57] text-[#0c6d57] shadow-sm text-sm font-bold rounded-lg bg-white hover:bg-[#0c6d57]/5 focus:outline-none transition-colors">
                            Save Request
                        </button>
                        <button type="button" class="w-full sm:w-auto text-center px-5 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-[#0c6d57] hover:bg-[#0a5c49] focus:outline-none transition-colors">
                            Submit for DSWD Validation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>