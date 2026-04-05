<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('resolutions.index') }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Minutes of Meeting
                </h2>
            </div>
            
            <a href="{{ route('resolutions.create') }}" class="inline-flex items-center px-4 py-2 border border-[#0c6d57] text-[#0c6d57] rounded-lg text-sm font-medium hover:bg-[#0c6d57]/5 transition-colors shadow-sm bg-white">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Draft Resolution
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-[#0c6d57]/20 overflow-hidden mb-8">
            <div class="bg-[#0c6d57] p-5 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg tracking-wide">Record New Minutes</h3>
            </div>
            <div class="p-6">
                <form>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Meeting Title / Topic</label>
                                <input type="text" placeholder="e.g. Monthly Assembly" class="block w-full rounded-lg border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Meeting Date</label>
                                <input type="date" value="{{ date('Y-m-d') }}" class="block w-full rounded-lg border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Attendees</label>
                            <input type="text" placeholder="Who was present during the meeting?" class="block w-full rounded-lg border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-3 bg-gray-50 focus:bg-white text-base">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Minutes Details</label>
                            <textarea rows="6" placeholder="Record the key discussions, outcomes, and agreed action items here." class="block w-full rounded-lg border-gray-200 shadow-sm focus:border-[#0c6d57] focus:ring focus:ring-[#0c6d57] focus:ring-opacity-50 transition-colors p-4 bg-gray-50 focus:bg-white text-base leading-relaxed"></textarea>
                        </div>

                        <div class="border-t border-gray-100 pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="w-full sm:w-auto">
                                <label for="minutes-file" class="flex items-center gap-2 cursor-pointer text-[#0c6d57] hover:text-[#0a5c49] font-bold text-sm bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 sm:py-2 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    Attach Photo / Signatures 
                                    <input id="minutes-file" type="file" class="sr-only">
                                </label>
                            </div>
                            <button type="button" class="w-full sm:w-auto text-center px-6 py-3 sm:py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-[#0c6d57] hover:bg-[#0a5c49] focus:outline-none transition-colors tracking-wide">
                                Save Meeting Minutes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h3 class="text-lg font-bold text-gray-900 mb-4 px-1">Recent Meeting Records</h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
            <!-- Row 1 -->
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex flex-col sm:flex-row justify-between sm:items-start gap-4">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Monthly Assembly - Association Hall</h4>
                        <p class="text-sm text-gray-500 font-medium">Nov 2, 2024 • 18 Attendees</p>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                            Discussion regarding the upcoming fattening phase. Caretaker reported missing 50 sacks of starter feeds. Agreed to utilize revolving fund for procurement up to P85k. Resolution to follow.
                        </p>
                    </div>
                    <div class="flex flex-col shrink-0 items-start sm:items-end gap-2">
                        <a href="{{ route('resolutions.show', 2) }}" class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-lg uppercase tracking-wider border border-green-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Resolution Pending
                        </a>
                        <button class="text-[#0c6d57] text-sm font-bold hover:underline">View Full Record</button>
                    </div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex flex-col sm:flex-row justify-between sm:items-start gap-4">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Emergency Session - Brgy. Hall</h4>
                        <p class="text-sm text-gray-500 font-medium">Oct 15, 2024 • 25 Attendees</p>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                            Agreed to withdraw the funds for the maintenance of Batch 2024-C pig pen fencing due to recent typhoon damages. Res 2024-05 approved.
                        </p>
                    </div>
                    <div class="flex flex-col shrink-0 items-start sm:items-end gap-2">
                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-lg uppercase tracking-wider border border-blue-200">
                            Resolution Completed
                        </span>
                        <button class="text-[#0c6d57] text-sm font-bold hover:underline">View Full Record</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>