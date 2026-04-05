<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ 
        weight: '', 
        price: '', 
        headCount: 1,
        saleType: 'Live Weight',
        get total() { 
            if (this.saleType === 'Per Head') return (this.headCount * this.price) || 0;
            return (this.weight * this.price) || 0; 
        },
        fileName: null 
    }">
        
        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('sales.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Record Sale Transaction</h1>
                <p class="text-sm text-gray-500 mt-1">Add buyer info and sale details with live weight computation</p>
            </div>
        </div>

        <form class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" action="{{ route('sales.index') }}" method="GET">
            
            <!-- Buyer Information Section -->
            <div class="p-6 sm:p-8 border-b border-gray-50 bg-gray-50/30">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">1. Buyer Information</h3>
                
                <div class="grid grid-cols-1 gap-y-5 sm:grid-cols-2 sm:gap-x-6">
                    <div class="sm:col-span-2">
                        <label for="buyer_name" class="block text-sm font-semibold text-gray-700 mb-1">Buyer Name <span class="text-red-500">*</span></label>
                        <input type="text" name="buyer_name" id="buyer_name" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="e.g. Juan Dela Cruz or Maria's Meat Shop" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="contact_details" class="block text-sm font-semibold text-gray-700 mb-1">Contact Number / Address <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <input type="text" name="contact_details" id="contact_details" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="0917-123-4567 / Public Market Stall 4">
                    </div>
                </div>
            </div>

            <!-- Sale Details Section -->
            <div class="p-6 sm:p-8 border-b border-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">2. Transaction Details</h3>

                <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-6">
                    <div>
                        <label for="batch" class="block text-sm font-semibold text-gray-700 mb-1">Select Batch / Litter <span class="text-red-500">*</span></label>
                        <select id="batch" name="batch" class="block w-full py-2.5 px-3 border border-gray-200 bg-white rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" required>
                            <option value="">Select Target Batch...</option>
                            <option value="2024-B">Batch 2024-B</option>
                            <option value="2024-A">Batch 2024-A</option>
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-semibold text-gray-700 mb-1">Sale Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date" id="date" value="{{ date('Y-m-d') }}" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" required>
                    </div>

                    <div>
                        <label for="sale_type" class="block text-sm font-semibold text-gray-700 mb-1">Sale Method / Basis <span class="text-red-500">*</span></label>
                        <select id="sale_type" name="sale_type" x-model="saleType" class="block w-full py-2.5 px-3 border border-gray-200 bg-white rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" required>
                            <option value="Live Weight">Live Weight</option>
                            <option value="Per Head">Per Head</option>
                            <option value="Carcass">Carcass (Meat)</option>
                        </select>
                    </div>

                    <div class="hidden sm:block"></div> <!-- Spacer -->

                    <!-- Dynamic Fields (Live Weight / Carcass) -->
                    <div x-show="saleType !== 'Per Head'" x-collapse class="sm:col-span-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-6">
                            <div>
                                <label for="weight" class="block text-sm font-semibold text-gray-700 mb-1">Total Weight (kg) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" step="0.1" name="weight" id="weight" x-model.number="weight" class="block w-full pr-12 pl-3 py-2.5 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="e.g. 95.5" :disabled="saleType === 'Per Head'">
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold">kg</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="price" class="block text-sm font-semibold text-gray-700 mb-1">Price per Kilo (₱) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="price" id="price" x-model.number="price" class="block w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="200.00" :disabled="saleType === 'Per Head'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields (Per Head) -->
                    <div x-show="saleType === 'Per Head'" x-collapse x-cloak class="sm:col-span-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-6">
                            <div>
                                <label for="headCount" class="block text-sm font-semibold text-gray-700 mb-1">Number of Heads <span class="text-red-500">*</span></label>
                                <input type="number" step="1" name="headCount" id="headCount" x-model.number="headCount" class="block w-full py-2.5 px-3 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="e.g. 5" :disabled="saleType !== 'Per Head'">
                            </div>
                            <div>
                                <label for="priceHead" class="block text-sm font-semibold text-gray-700 mb-1">Price per Head (₱) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="priceHead" id="priceHead" x-model.number="price" class="block w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-[#0c6d57] focus:border-[#0c6d57]" placeholder="e.g. 5000.00" :disabled="saleType !== 'Per Head'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Calculation Display -->
                    <div class="sm:col-span-2 bg-[#0c6d57]/5 border border-[#0c6d57]/20 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between mt-2">
                        <div>
                            <span class="block text-sm text-[#0c6d57] font-semibold mb-1 uppercase tracking-wider">Computed Total Amount</span>
                            <span class="text-xs text-gray-500" x-show="saleType !== 'Per Head'">Weight × Price per Kilo</span>
                            <span class="text-xs text-gray-500" x-show="saleType === 'Per Head'" x-cloak>Heads × Price per Head</span>
                        </div>
                        <div class="text-3xl font-black text-gray-900 mt-2 sm:mt-0">
                            ₱ <span x-text="total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})">0.00</span>
                        </div>
                        <input type="hidden" name="total_amount" :value="total">
                    </div>
                </div>
            </div>

            <!-- Receipt & Payment Status -->
            <div class="p-6 sm:p-8">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">3. Payment & Documentation</h3>

                <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-6">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Status <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="relative flex cursor-pointer rounded-xl border bg-white p-3 focus:outline-none flex-col items-center justify-center border-gray-200 hover:bg-green-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50 has-[:checked]:ring-1 has-[:checked]:ring-green-500 text-center transition-colors">
                                <input type="radio" name="payment_status" value="paid" class="sr-only" checked>
                                <span class="text-green-600 font-bold block mb-1">Paid</span>
                                <span class="text-xs text-gray-500">Fully settled</span>
                            </label>
                            <label class="relative flex cursor-pointer rounded-xl border bg-white p-3 focus:outline-none flex-col items-center justify-center border-gray-200 hover:bg-blue-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:ring-1 has-[:checked]:ring-blue-500 text-center transition-colors">
                                <input type="radio" name="payment_status" value="partial" class="sr-only">
                                <span class="text-blue-600 font-bold block mb-1">Partial</span>
                                <span class="text-xs text-gray-500">Downpayment provided</span>
                            </label>
                            <label class="relative flex cursor-pointer rounded-xl border bg-white p-3 focus:outline-none flex-col items-center justify-center border-gray-200 hover:bg-amber-50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500 text-center transition-colors">
                                <input type="radio" name="payment_status" value="pending" class="sr-only">
                                <span class="text-amber-600 font-bold block mb-1">Pending</span>
                                <span class="text-xs text-gray-500">No payment yet</span>
                            </label>
                        </div>
                    </div>

                    <!-- Receipt Upload -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Temporary Receipt Proof <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <p class="text-xs text-gray-500 mb-3">Upload a photo of the handwritten temporary receipt or weighing scale reading.</p>
                        
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-50 transition-colors relative cursor-pointer" :class="fileName ? 'bg-[#0c6d57]/5 border-[#0c6d57]/30' : ''">
                            <div class="space-y-1 text-center flex flex-col items-center">
                                <div x-show="!fileName" class="mb-2">
                                    <div class="mx-auto w-12 h-12 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm">
                                        <svg class="h-6 w-6 text-[#0c6d57]" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    </div>
                                </div>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="receipt-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-[#0c6d57] hover:text-[#0a5a48] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#0c6d57]">
                                        <span x-text="fileName ? 'Change file' : 'Upload a photo'"></span>
                                        <input id="receipt-upload" name="receipt" type="file" class="sr-only" accept="image/*" @change="fileName = $event.target.files[0].name">
                                    </label>
                                    <p class="pl-1" x-show="!fileName">or take a picture</p>
                                </div>
                                
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
                <a href="{{ route('sales.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 border border-gray-300 shadow-sm text-sm font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57] transition-colors">
                    Cancel
                </a>
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white bg-[#0c6d57] hover:bg-[#0a5a48] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57] transition-colors">
                    Record Sale
                </button>
            </div>
        </form>
    </div>
</x-app-layout>