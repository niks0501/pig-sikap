<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('batches.show', $id) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Edit Batch {{ $id }}</h2>
                <p class="text-sm text-gray-500 mt-1">Make corrections to the original batch registry information.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="#" method="POST" class="space-y-8">
                    @csrf
                    @method('PUT') <!-- Laravel standard put for edits -->

                    <!-- Alert message for editing -->
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex gap-3 text-blue-800 text-sm items-start">
                        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p><strong>Note:</strong> To change the batch's current phase (like promoting to a Fattener) or updating head counts due to sales or sickness, please use the <strong><a href="{{ route('batches.show', $id) }}" class="underline font-bold hover:text-blue-900">Update Status</a></strong> button on the batch details page instead to keep the timeline history intact.</p>
                    </div>
                    
                    <!-- Batch Origin Info -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Batch Origin Information (Correction)</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Batch ID -->
                            <div>
                                <label for="batch_id" class="block text-sm font-bold text-gray-700 mb-1.5">Batch ID</label>
                                <input type="text" id="batch_id" name="batch_id" value="{{ $id }}" readonly class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-gray-600 sm:text-sm font-medium focus:outline-none focus:ring-0">
                                <p class="text-[11px] text-gray-500 mt-1.5 font-medium">Identifier cannot be changed.</p>
                            </div>

                            <!-- Source / Category -->
                            <div>
                                <label for="source" class="block text-sm font-bold text-gray-700 mb-1.5">Source / Origin</label>
                                <select id="source" name="source" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                                    <optgroup label="Internal Breeding">
                                        <option value="inahin_a" selected>Litter from Inahin A</option>
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
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Birth Date / Acquisition Date -->
                            <div>
                                <label for="birth_date" class="block text-sm font-bold text-gray-700 mb-1.5">Birth / Acquired Date *</label>
                                <input type="date" id="birth_date" name="birth_date" value="2024-09-12" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                            </div>

                            <!-- Initial Head Count -->
                            <div>
                                <label for="head_count" class="block text-sm font-bold text-gray-700 mb-1.5">Initial Head Count at Start *</label>
                                <input type="number" id="head_count" name="head_count" min="1" value="8" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                            </div>

                            <!-- Caretaker -->
                            <div class="sm:col-span-2">
                                <label for="caretaker" class="block text-sm font-bold text-gray-700 mb-1.5">Assigned Caretaker *</label>
                                <select id="caretaker" name="caretaker" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">
                                    <option value="1" selected>Juan Dela Cruz</option>
                                    <option value="2">Maria Reyes</option>
                                    <option value="3">Pedro Penduko</option>
                                </select>
                            </div>
                            
                            <!-- Remarks -->
                            <div class="sm:col-span-2">
                                <label for="remarks" class="block text-sm font-bold text-gray-700 mb-1.5">Initial Remarks / Notes</label>
                                <textarea id="remarks" name="remarks" rows="4" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all">Batch sourced internally from Inahin A's third parity. Healthy and active upon inspection. Assigned immediately to Juan's block.

Feeding Plan: Booster pellets assigned for Month 1. Iron injection given on Day 3.</textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                            Save Changes
                        </button>
                        <a href="{{ route('batches.show', $id) }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>