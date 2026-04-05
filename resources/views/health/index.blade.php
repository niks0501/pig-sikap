<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Health Records</h2>
                <p class="text-sm text-gray-500 mt-1">Manage health, vaccination, and treatments by batch.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('health.schedule') }}" class="inline-flex justify-center items-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    View Schedule
                </a>
                <a href="{{ route('health.create') }}" class="inline-flex justify-center items-center px-4 py-2.5 bg-[#0c6d57] text-white font-bold text-sm rounded-xl hover:bg-[#0a5a48] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0c6d57]">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Record
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ activeTab: 'all', showCompleteModal: false }">
        
        <!-- Summary Cards for Quick Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Upcoming</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">12</p>
                </div>
                <div class="p-2 bg-yellow-50 rounded-xl text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">3</p>
                </div>
                <div class="p-2 bg-red-50 rounded-xl text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Completed</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">45</p>
                </div>
                <div class="p-2 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <a href="{{ route('health.sick') }}" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between hover:border-orange-300 transition-colors">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Sick Cases</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">2</p>
                </div>
                <div class="p-2 bg-orange-50 rounded-xl text-orange-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" placeholder="Search by batch ID or treatment..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm transition-colors">
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-100">
                <nav class="flex -mb-px overflow-x-auto">
                    <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'border-[#0c6d57] text-[#0c6d57]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-colors focus:outline-none">
                        All Records
                    </button>
                    <button @click="activeTab = 'upcoming'" :class="activeTab === 'upcoming' ? 'border-[#0c6d57] text-[#0c6d57]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-colors focus:outline-none">
                        Upcoming & Pending
                    </button>
                    <button @click="activeTab = 'completed'" :class="activeTab === 'completed' ? 'border-[#0c6d57] text-[#0c6d57]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-colors focus:outline-none">
                        Completed
                    </button>
                </nav>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Batch ID</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Activity / Treatment</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        
                        <!-- Record 1 (Overdue) -->
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('batches.health', 'BAT-001') }}" class="text-sm font-bold text-gray-900 border-b border-transparent hover:border-gray-900 transition-colors">BAT-001</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">Iron Injection</div>
                                <div class="text-xs text-gray-500">Day 3 Schedule</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">Apr 2, 2026</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 bg-red-600 rounded-full mr-1.5"></span> Overdue
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">No marks yet.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="showCompleteModal = true" class="text-[#0c6d57] hover:text-[#0a5a48] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 px-3 py-1.5 rounded-lg transition-colors font-bold">
                                    Mark Done
                                </button>
                            </td>
                        </tr>

                        <!-- Record 2 (Upcoming) -->
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('batches.health', 'BAT-002') }}" class="text-sm font-bold text-gray-900 border-b border-transparent hover:border-gray-900 transition-colors">BAT-002</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">Deworming</div>
                                <div class="text-xs text-gray-500">Regular Schedule</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">Apr 8, 2026</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5"></span> Pending
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">To be marked on 2nd schedule.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="showCompleteModal = true" class="text-[#0c6d57] hover:text-[#0a5a48] bg-[#0c6d57]/10 hover:bg-[#0c6d57]/20 px-3 py-1.5 rounded-lg transition-colors font-bold">
                                    Mark Done
                                </button>
                            </td>
                        </tr>

                        <!-- Record 3 (Completed) -->
                        <tr class="hover:bg-gray-50/50 transition-colors" x-show="activeTab === 'all' || activeTab === 'completed'">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('batches.health', 'BAT-003') }}" class="text-sm font-bold text-gray-900 border-b border-transparent hover:border-gray-900 transition-colors">BAT-003</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">Vitamins (A,D,E)</div>
                                <div class="text-xs text-gray-500">Day 14 Schedule</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">Mar 25, 2026</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800">
                                    <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full mr-1.5"></span> Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">Marked red on back. All 12 treated.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <span class="text-gray-400 font-bold px-3 py-1.5">Done</span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-gray-100">
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-800 mb-2">Overdue</span>
                            <h3 class="text-sm font-bold text-gray-900"><a href="{{ route('batches.health', 'BAT-001') }}">BAT-001: Iron Injection</a></h3>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-gray-500 block">Due Date</span>
                            <span class="text-sm font-bold text-red-600">Apr 2, 2026</span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-bold text-gray-900">Remarks:</span> No marks yet.
                    </div>
                    <button @click="showCompleteModal = true" class="w-full mt-2 inline-flex justify-center items-center px-3 py-2.5 bg-[#0c6d57]/10 text-[#0c6d57] font-bold text-sm rounded-xl hover:bg-[#0c6d57]/20 transition-colors">
                        Mark as Completed
                    </button>
                </div>

                <div class="p-4 flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 mb-2">Pending</span>
                            <h3 class="text-sm font-bold text-gray-900"><a href="{{ route('batches.health', 'BAT-002') }}">BAT-002: Deworming</a></h3>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-gray-500 block">Due Date</span>
                            <span class="text-sm font-bold text-gray-900">Apr 8, 2026</span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-bold text-gray-900">Remarks:</span> To be marked on 2nd schedule.
                    </div>
                    <button @click="showCompleteModal = true" class="w-full mt-2 inline-flex justify-center items-center px-3 py-2.5 bg-[#0c6d57]/10 text-[#0c6d57] font-bold text-sm rounded-xl hover:bg-[#0c6d57]/20 transition-colors">
                        Mark as Completed
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Completion Modal (Mark as Completed) -->
        <div x-show="showCompleteModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
            <div x-show="showCompleteModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showCompleteModal" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         class="relative transform overflow-hidden rounded-3xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <div>
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="mt-5 text-center sm:mt-6">
                                <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Mark Treatment as Completed</h3>
                                <p class="text-sm text-gray-500 mt-2">Confirm that <span class="font-bold text-gray-900">BAT-001</span> received <span class="font-bold text-gray-900">Iron Injection</span>.</p>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4 text-left">
                            <div>
                                <label for="completion_date" class="block text-sm font-bold text-gray-700 mb-1.5">Date Completed</label>
                                <input type="date" id="completion_date" name="completion_date" value="2026-04-06" class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 font-medium sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]">
                            </div>
                            <div>
                                <label for="remarks" class="block text-sm font-bold text-gray-700 mb-1.5">Remarks / Visual Markings</label>
                                <textarea id="remarks" name="remarks" rows="2" placeholder="e.g. Marked red on the back, all pigs treated..." class="block w-full py-3 px-4 border border-gray-200 rounded-xl bg-white text-gray-900 sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57]"></textarea>
                            </div>
                        </div>

                        <div class="mt-8 sm:mt-8 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" @click="showCompleteModal = false" class="inline-flex w-full justify-center rounded-xl bg-[#0c6d57] px-3 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-[#0a5a48] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0c6d57] sm:col-start-2">
                                Confirm Completed
                            </button>
                            <button type="button" @click="showCompleteModal = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-3.5 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>