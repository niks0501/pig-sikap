<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('batches.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Create New Batch</h2>
                <p class="text-sm text-gray-500 mt-1">Record a new pig litter or batch assignment.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="#" method="POST" class="space-y-8">
                    @csrf
                    
                    <!-- Batch Origin Info -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Batch Origin Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Batch ID -->
                            <div>
                                <label for="batch_id" class="block text-sm font-bold text-gray-700 mb-1.5">Batch ID</label>
                                <input type="text" id="batch_id" name="batch_id" value="B-025" readonly class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-gray-600 sm:text-sm font-medium focus:outline-none focus:ring-0">
                                <p class="text-[11px] text-gray-500 mt-1.5 font-medium">Auto-generated identifier.</p>
                            </div>

                            <!-- Source / Category -->
                            <div>
                                <label for="source" class="block text-sm font-bold text-gray-700 mb-1.5">Source / Origin</label>
                                <select id="source" name="source" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                                    <option value="">Select origin...</option>
                                    <optgroup label="Internal Breeding">
                                        <option value="inahin_a">Litter from Inahin A</option>
                                        <option value="inahin_b">Litter from Inahin B</option>
                                    </optgroup>
                                    <optgroup label="External">
                                        <option value="purchased">Purchased Piglets</option>
                                        <option value="donated">Donated / Grant</option>
                                    </optgroup>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Batch Details -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Batch Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Birth Date / Acquisition Date -->
                            <div>
                                <label for="birth_date" class="block text-sm font-bold text-gray-700 mb-1.5">Birth / Acquired Date *</label>
                                <input type="date" id="birth_date" name="birth_date" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                            </div>

                            <!-- Head Count -->
                            <div>
                                <label for="head_count" class="block text-sm font-bold text-gray-700 mb-1.5">Initial Head Count *</label>
                                <input type="number" id="head_count" name="head_count" min="1" placeholder="e.g. 8" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                            </div>

                            <!-- Caretaker -->
                            <div class="sm:col-span-2">
                                <label for="caretaker" class="block text-sm font-bold text-gray-700 mb-1.5">Assigned Caretaker *</label>
                                <select id="caretaker" name="caretaker" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                                    <option value="">Select an association member...</option>
                                    <option value="1">Juan Dela Cruz</option>
                                    <option value="2">Maria Reyes</option>
                                    <option value="3">Pedro Penduko</option>
                                </select>
                            </div>

                            <!-- Initial Status -->
                            <div class="sm:col-span-2">
                                <label for="status" class="block text-sm font-bold text-gray-700 mb-1.5">Initial Status *</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <!-- Radio Options styled as buttons -->
                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#0c6d57] focus-within:ring-2 focus-within:ring-[#0c6d57] transition-all has-[:checked]:border-[#0c6d57] has-[:checked]:bg-[#0c6d57]/5">
                                        <input type="radio" name="status" value="piglets" class="sr-only" checked>
                                        <span class="text-sm font-bold text-gray-900 text-center w-full">Piglets <span class="block text-[10px] text-gray-500 font-medium mt-1">Suckling/Weaners</span></span>
                                    </label>

                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#0c6d57] focus-within:ring-2 focus-within:ring-[#0c6d57] transition-all has-[:checked]:border-[#0c6d57] has-[:checked]:bg-[#0c6d57]/5">
                                        <input type="radio" name="status" value="fatteners" class="sr-only">
                                        <span class="text-sm font-bold text-gray-900 text-center w-full">Fatteners <span class="block text-[10px] text-gray-500 font-medium mt-1">Growers</span></span>
                                    </label>
                                    
                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#0c6d57] focus-within:ring-2 focus-within:ring-[#0c6d57] transition-all has-[:checked]:border-[#0c6d57] has-[:checked]:bg-[#0c6d57]/5">
                                        <input type="radio" name="status" value="breeders" class="sr-only">
                                        <span class="text-sm font-bold text-gray-900 text-center w-full">Breeders <span class="block text-[10px] text-gray-500 font-medium mt-1">Future Inahin</span></span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Remarks -->
                            <div class="sm:col-span-2">
                                <label for="remarks" class="block text-sm font-bold text-gray-700 mb-1.5">Initial Remarks / Notes <span class="text-gray-400 font-medium">(Optional)</span></label>
                                <textarea id="remarks" name="remarks" rows="3" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all" placeholder="Add any details about the batch's health, feed initially used, etc."></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                            Save Batch Record
                        </button>
                        <a href="{{ route('batches.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>