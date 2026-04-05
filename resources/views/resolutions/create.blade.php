<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('resolutions.index') }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Resolution
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8">
                <form>
                    <div class="space-y-6">
                        <!-- Header Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Resolution Number/Title</label>
                                <input type="text" placeholder="e.g. Res No. 2024-09" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Date Created</label>
                                <input type="date" value="{{ date('Y-m-d') }}" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Subject / Purpose</label>
                            <input type="text" placeholder="Brief description of the resolution" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Related Meeting (Optional)</label>
                            <select class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base text-gray-700">
                                <option value="">-- Select a Meeting --</option>
                                <option value="1">Monthly Assembly - Nov 2, 2024</option>
                                <option value="2">Emergency Session - Oct 15, 2024</option>
                            </select>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Resolution Content</label>
                            <p class="text-sm text-gray-500 mb-3">Copy and paste the official text or body of the resolution below.</p>
                            <textarea rows="10" placeholder="WHEREAS, it is the policy of the association..." class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-4 bg-gray-50 focus:bg-white text-base leading-relaxed"></textarea>
                        </div>

                        <!-- Attachments -->
                        <div class="border-t border-gray-100 pt-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Supporting Attachments</label>
                            <p class="text-sm text-gray-500 mb-3">Upload scanned signatures, photos, or physical documents (JPEG, PNG, PDF).</p>
                            
                            <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed border-gray-300 hover:border-[#0c6d57] rounded-xl transition-colors cursor-pointer bg-gray-50 hover:bg-[#0c6d57]/5">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" />
                                    </svg>
                                    <div class="flex text-base text-gray-600 justify-center">
                                        <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-[#0c6d57] hover:text-[#0a5c49] focus-within:outline-none">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" multiple>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-sm text-gray-500">PNG, JPG, PDF up to 10MB</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-3">
                        <a href="{{ route('resolutions.index') }}" class="w-full sm:w-auto text-center px-5 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                            Cancel
                        </a>
                        <button type="button" class="w-full sm:w-auto text-center px-5 py-3 border border-[#0c6d57] text-[#0c6d57] shadow-sm text-sm font-bold rounded-lg bg-white hover:bg-[#0c6d57]/5 focus:outline-none transition-colors">
                            Save as Draft
                        </button>
                        <button type="button" class="w-full sm:w-auto text-center px-5 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-[#0c6d57] hover:bg-[#0a5c49] focus:outline-none transition-colors">
                            Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>