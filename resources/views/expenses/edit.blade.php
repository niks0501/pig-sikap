<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('expenses.show', $id) }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Expense</h1>
                <p class="text-sm text-gray-500 mt-1">Update financial outgoing records</p>
            </div>
        </div>

        <!-- Form Card -->
        <form class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" action="{{ route('expenses.show', $id) }}" method="GET">
            <div class="p-6 sm:p-8">
                
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Update Details
                </h3>

                <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-6">
                    
                    <!-- Amount -->
                    <div class="sm:col-span-2">
                        <label for="amount" class="block text-sm font-semibold text-gray-700 mb-1">Total Amount (₱) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold text-lg">₱</span>
                            </div>
                            <input type="number" step="0.01" name="amount" id="amount" value="4500.00" class="block w-full pl-10 pr-4 py-3 text-lg font-bold border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" required>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category" name="category" class="block w-full py-2.5 px-3 border border-gray-200 bg-white rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm" required>
                            <option value="feeds" selected>Feeds</option>
                            <option value="medicines">Medicines</option>
                            <option value="vitamins">Vitamins</option>
                            <option value="transport">Transport</option>
                            <option value="emergency">Emergency</option>
                            <option value="others">Others</option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="date" class="block text-sm font-semibold text-gray-700 mb-1">Date of Expense <span class="text-red-500">*</span></label>
                        <input type="date" name="date" id="date" value="2024-10-12" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm" required>
                    </div>

                    <!-- Batch / Cycle -->
                    <div class="sm:col-span-2">
                        <label for="batch" class="block text-sm font-semibold text-gray-700 mb-1">Batch or Cycle <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <select id="batch" name="batch" class="block w-full py-2.5 px-3 border border-gray-200 bg-white rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm">
                            <option value="">General Association Expense</option>
                            <option value="2024-B" selected>Batch 2024-B</option>
                            <option value="2024-A">Batch 2024-A</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Short Description <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="3" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57] sm:text-sm resize-none" required>Grower Feeds (3 Sacks)</textarea>
                    </div>

                    <!-- Receipt Upload -->
                    <div class="sm:col-span-2" x-data="{ fileName: null }">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Receipt / Proof of Expense</label>
                        
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-50 transition-colors relative cursor-pointer" :class="fileName ? 'bg-[#0c6d57]/5 border-[#0c6d57]/30' : ''">
                            <div class="space-y-1 text-center flex flex-col items-center">
                                <div x-show="!fileName" class="mb-2">
                                    <div class="mx-auto w-12 h-12 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm">
                                        <svg class="h-6 w-6 text-[#0c6d57]" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    </div>
                                </div>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="receipt-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-[#0c6d57] hover:text-[#0a5a48] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#0c6d57]">
                                        <span x-text="fileName ? 'Change file' : 'Replace Receipt Image'"></span>
                                        <input id="receipt-upload" name="receipt" type="file" class="sr-only" accept="image/*" @change="fileName = $event.target.files[0].name">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-show="!fileName">Current receipt attached. Upload to replace.</p>
                                
                                <div x-show="fileName" class="mt-2 flex items-center gap-2 text-sm text-gray-700 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm" x-cloak>
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="fileName" class="font-medium truncate max-w-[200px] sm:max-w-xs"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('expenses.show', $id) }}" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 border border-gray-300 shadow-sm text-sm font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57] transition-colors">
                    Cancel
                </a>
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white bg-[#0c6d57] hover:bg-[#0a5a48] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57] transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-app-layout>