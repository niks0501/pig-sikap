<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Batch Inventory</h2>
                <p class="text-sm text-gray-500 mt-1">Manage associative pig litters and batch records.</p>
            </div>
            <a href="{{ route('batches.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-[#0c6d57] text-white font-medium text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Batch
            </a>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ activeTab: 'all' }">
        
        <!-- Controls & Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <!-- Search bar -->
            <div class="relative w-full sm:w-96 shrink-0">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm shadow-sm transition-colors" placeholder="Search by batch ID or caretaker...">
            </div>

            <!-- Date Filter (Optional mock) -->
            <div class="flex items-center gap-2">
                <select class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 rounded-xl bg-white text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] shadow-sm">
                    <option>All Time</option>
                    <option>This Quarter</option>
                    <option>This Year</option>
                </select>
            </div>
        </div>

        <!-- Status Tabs (Scrollable on mobile) -->
        <div class="mb-6 -mx-4 sm:mx-0 overflow-x-auto hide-scrollbar">
            <div class="flex gap-2 px-4 sm:px-0 min-w-max pb-1">
                @php
                    $tabs = [
                        'all' => 'All Batches',
                        'piglets' => 'Piglets',
                        'breeders' => 'Breeders',
                        'fatteners' => 'Fatteners',
                        'sick' => 'Sick',
                        'deceased' => 'Deceased'
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <button @click="activeTab = '{{ $key }}'" 
                            :class="activeTab === '{{ $key }}' ? 'bg-[#0c6d57] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                            class="px-4 py-2 font-medium text-sm rounded-full transition-all duration-200 shrink-0">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Desktop Table (Hidden on small screens) -->
        <div class="hidden sm:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Batch Info</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Birth Date</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Head Count</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Caretaker</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <!-- Mock Row 1: Piglets -->
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="bg-emerald-50 text-[#0c6d57] font-bold w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                        P1
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Batch B-001</p>
                                        <p class="text-xs text-gray-500">From Inahin A</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Sep 12, 2024<br>
                                <span class="text-xs text-gray-400">2 months ago</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">
                                8
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">JD</div>
                                    <span class="text-sm text-gray-700 font-medium">Juan Dela Cruz</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    Piglets
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('batches.show', 1) }}" class="text-[#0c6d57] hover:text-[#0a5a48] bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                    View
                                </a>
                            </td>
                        </tr>

                        <!-- Mock Row 2: Fatteners -->
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="bg-amber-50 text-amber-700 font-bold w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                        F2
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Batch B-002</p>
                                        <p class="text-xs text-gray-500">From Inahin B</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Jun 05, 2024<br>
                                <span class="text-xs text-gray-400">5 months ago</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">
                                10
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">MR</div>
                                    <span class="text-sm text-gray-700 font-medium">Maria Reyes</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    Fatteners
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('batches.show', 2) }}" class="text-[#0c6d57] hover:text-[#0a5a48] bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                    View
                                </a>
                            </td>
                        </tr>

                        <!-- Mock Row 3: Sick -->
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="bg-red-50 text-red-700 font-bold w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                        S3
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Batch B-003</p>
                                        <p class="text-xs text-gray-500">Purchased</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Aug 20, 2024<br>
                                <span class="text-xs text-gray-400">3 months ago</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">
                                5
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">PP</div>
                                    <span class="text-sm text-gray-700 font-medium">Pedro Penduko</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    Sick (2 heads)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('batches.show', 3) }}" class="text-[#0c6d57] hover:text-[#0a5a48] bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                    View
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 text-sm text-gray-500">
                Showing 3 of 24 batches
            </div>
        </div>

        <!-- Mobile Card Layout (Hidden on screens sm and up) -->
        <div class="sm:hidden space-y-4">
            <!-- Mobile Card 1 -->
            <a href="{{ route('batches.show', 1) }}" class="block bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative active:scale-[0.98] transition-transform">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-50 text-[#0c6d57] font-bold w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                            P1
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Batch B-001</h3>
                            <p class="text-xs text-gray-500">From Inahin A</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        Piglets
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-4 bg-gray-50 rounded-xl p-3">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Head Count</p>
                        <p class="text-lg font-bold text-gray-900 leading-none mt-1">8</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] uppercase font-bold text-gray-400">Birth Date</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">Sep 12, 2024</p>
                    </div>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">JD</div>
                        <span class="text-gray-700 font-medium">Juan Dela Cruz</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Mobile Card 2 -->
            <a href="{{ route('batches.show', 2) }}" class="block bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative active:scale-[0.98] transition-transform">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-50 text-amber-700 font-bold w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                            F2
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Batch B-002</h3>
                            <p class="text-xs text-gray-500">From Inahin B</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                        Fatteners
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-4 bg-gray-50 rounded-xl p-3">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Head Count</p>
                        <p class="text-lg font-bold text-gray-900 leading-none mt-1">10</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] uppercase font-bold text-gray-400">Birth Date</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">Jun 05, 2024</p>
                    </div>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">MR</div>
                        <span class="text-gray-700 font-medium">Maria Reyes</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>
        </div>

    </div>
</x-app-layout>