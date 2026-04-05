<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('mortality.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Mortality Record Details</h2>
                <p class="text-sm text-gray-500 mt-1">Review the documented evidence and notes for this incident.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Summary Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6 pb-6 border-b border-gray-100">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800 mb-2 border border-gray-200">Date of Incident</span>
                            <h3 class="text-2xl sm:text-3xl font-bold text-gray-900">Apr 4, 2026</h3>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-sm font-bold text-gray-500 block mb-1">Target Batch</span>
                            <a href="#" class="text-lg font-bold text-[#0c6d57] hover:underline">BAT-002</a>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Cause of Death</h4>
                            <p class="text-lg font-bold text-gray-900">Scouring / Severe Diarrhea</p>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Notes & Remarks</h4>
                            <div class="bg-gray-50 rounded-2xl p-4 sm:p-5 border border-gray-200">
                                <p class="text-gray-700 leading-relaxed">
                                    Piglet was found unresponsive in the morning during feeding time. It appeared severely dehydrated. Already noticed weakness yesterday and had separated it to the corner pen, but it did not survive the night.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-full text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Recorded on Apr 4, 2026</p>
                            <p class="text-xs text-gray-500">By Association Officer</p>
                        </div>
                    </div>
                    <div class="w-full sm:w-auto">
                        <button class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Edit Record
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Media Preview -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 sticky top-6">
                    <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Photographic Evidence
                    </h4>
                    
                    <!-- Evidence Placeholder Container -->
                    <div class="bg-gray-100 rounded-2xl aspect-[4/3] flex flex-col items-center justify-center text-gray-400 border border-gray-200 relative overflow-hidden group cursor-pointer">
                        <svg class="w-12 h-12 mb-2 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2v12h16V6H4zm8 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z"/>
                        </svg>
                        <span class="text-xs font-bold">Image Preview Here</span>
                        
                        <!-- Overlay on hover to view full screen -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white font-bold text-sm flex items-center bg-black/40 px-3 py-1.5 rounded-lg backdrop-blur-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                View Full Size
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-500 truncate mr-2" title="IMG_4821_dead_piglet.jpg">IMG_4821_dead_piglet.jpg</span>
                        <a href="#" class="font-bold text-[#0c6d57] shrink-0 hover:underline">Download</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>