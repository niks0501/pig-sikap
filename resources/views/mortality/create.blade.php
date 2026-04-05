<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('mortality.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Report Deceased Pig</h2>
                <p class="text-sm text-gray-500 mt-1">Document a mortality event for your records with required evidence.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto" x-data="{ fileName: null }">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="#" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Incident Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            
                            <!-- Target Batch -->
                            <div>
                                <label for="batch_id" class="block text-sm font-bold text-gray-700 mb-1.5">Select Batch / Litter *</label>
                                <select id="batch_id" name="batch_id" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="" selected disabled>Select an active batch...</option>
                                    <option value="BAT-001">BAT-001</option>
                                    <option value="BAT-002">BAT-002</option>
                                    <option value="BAT-003">BAT-003</option>
                                </select>
                            </div>

                            <!-- Date of Death -->
                            <div>
                                <label for="date_of_death" class="block text-sm font-bold text-gray-700 mb-1.5">Date of Death *</label>
                                <input type="date" id="date_of_death" name="date_of_death" value="{{ date('Y-m-d') }}" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>

                            <!-- Cause of Death -->
                            <div class="sm:col-span-2">
                                <label for="cause_of_death" class="block text-sm font-bold text-gray-700 mb-1.5">Cause of Death *</label>
                                <select id="cause_of_death" name="cause_of_death" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                                    <option value="" selected disabled>Select expected cause...</option>
                                    <option value="crushed">Crushed by Inahin (Sow)</option>
                                    <option value="scouring">Scouring / Severe Diarrhea</option>
                                    <option value="respiratory">Respiratory Problem / Pneumonia</option>
                                    <option value="weakness">General Weakness / Starvation</option>
                                    <option value="unknown">Unknown / Sudden Death</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Details & Evidence -->
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-5">Documentation & Evidence</h3>
                        <div class="grid grid-cols-1 gap-5">
                            
                            <!-- Notes / Remarks -->
                            <div>
                                <label for="notes" class="block text-sm font-bold text-gray-700 mb-1.5">Additional Notes / Remarks</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Describe the circumstances discovered, any treatments attempted prior to death, or physical symptoms noticed..." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-700 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] transition-all"></textarea>
                            </div>

                            <!-- File Upload Area -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">Attach Photo or Video Evidence *</label>
                                <div class="mt-1 flex justify-center rounded-2xl border border-dashed border-gray-300 px-6 py-10 bg-gray-50 hover:bg-gray-100 transition-colors relative">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-bold text-[#0c6d57] focus-within:outline-none focus-within:ring-2 focus-within:ring-[#0c6d57] focus-within:ring-offset-2 hover:text-[#0a5a48] px-3 py-1 border border-gray-200 shadow-sm">
                                                <span>Upload a file</span>
                                                <input id="file-upload" name="file-upload" type="file" accept="image/*,video/*" class="sr-only" @change="fileName = $event.target.files[0].name">
                                            </label>
                                        </div>
                                        <p class="text-xs leading-5 text-gray-500 mt-2">PNG, JPG, MP4 up to 10MB</p>
                                    </div>
                                </div>
                                <div x-show="fileName" x-cloak class="mt-3 p-3 bg-[#0c6d57]/5 border border-[#0c6d57]/20 rounded-xl flex justify-between items-center text-sm">
                                    <span class="font-bold text-[#0c6d57] flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <span x-text="fileName"></span>
                                    </span>
                                    <button type="button" @click="fileName = null; document.getElementById('file-upload').value = ''" class="text-red-500 hover:text-red-700 font-bold p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-gray-900 text-white font-bold text-sm rounded-xl hover:bg-black transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                            Save Mortality Record
                        </button>
                        <a href="{{ route('mortality.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>