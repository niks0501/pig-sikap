<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Mortality Records</h2>
                <p class="text-sm text-gray-500 mt-1">Review documented deaths and incidents for your litters and batches.</p>
            </div>
            <div>
                <a href="{{ route('mortality.create') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-gray-900 text-white font-bold text-sm rounded-xl hover:bg-black transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Report Deceased Pig
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" placeholder="Search by batch ID or cause..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm transition-colors">
                </div>
                <div class="shrink-0">
                    <select class="block w-full py-2.5 px-4 border border-gray-200 rounded-xl leading-5 bg-white text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm transition-colors">
                        <option>All Batches</option>
                        <option>BAT-001</option>
                        <option>BAT-002</option>
                    </select>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date of Death</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Batch ID</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cause of Death</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Notes / Remarks</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Evidence</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        
                        <!-- Record 1 -->
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">Apr 4, 2026</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="#" class="text-sm font-bold text-[#0c6d57] hover:underline">BAT-002</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">Scouring / Severe Diarrhea</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">Piglet was found unresponsive in the morning. Already weak yesterday.</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    1 Photo
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('mortality.show', 1) }}" class="text-[#0c6d57] hover:text-[#0a5a48] font-bold">View Details</a>
                            </td>
                        </tr>

                        <!-- Record 2 -->
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">Mar 22, 2026</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="#" class="text-sm font-bold text-[#0c6d57] hover:underline">BAT-001</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">Crushed by Inahin (Sow)</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">Accidentally laid on during the night in the farrowing pen.</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    1 Video
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('mortality.show', 2) }}" class="text-[#0c6d57] hover:text-[#0a5a48] font-bold">View Details</a>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-gray-100">
                
                <!-- Card 1 -->
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-500 block mb-1">Apr 4, 2026</span>
                            <h3 class="text-base font-bold text-gray-900">BAT-002</h3>
                            <p class="text-sm font-medium text-gray-700 mt-1">Scouring / Severe Diarrhea</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Photo
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">Piglet was found unresponsive in the morning. Already weak yesterday.</p>
                    <a href="{{ route('mortality.show', 1) }}" class="mt-2 inline-flex justify-center items-center px-3 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition-colors">
                        View Details
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-500 block mb-1">Mar 22, 2026</span>
                            <h3 class="text-base font-bold text-gray-900">BAT-001</h3>
                            <p class="text-sm font-medium text-gray-700 mt-1">Crushed by Inahin</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            Video
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">Accidentally laid on during the night in the farrowing pen.</p>
                    <a href="{{ route('mortality.show', 2) }}" class="mt-2 inline-flex justify-center items-center px-3 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition-colors">
                        View Details
                    </a>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>