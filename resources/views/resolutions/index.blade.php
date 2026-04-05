<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Resolutions & Documents
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('minutes.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Meeting Minutes
                </a>
                <a href="{{ route('resolutions.create') }}" class="inline-flex items-center px-4 py-2 bg-[#0c6d57] text-white rounded-lg text-sm font-medium hover:bg-[#0a5c49] transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Resolution
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <!-- Quick Nav & Search -->
        <div class="px-4 sm:px-0 mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="w-full sm:w-1/3 relative relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm transition-colors shadow-sm" placeholder="Search resolutions...">
            </div>
            <div class="w-full sm:w-auto flex gap-2">
                <select class="block w-full sm:w-auto pl-3 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm rounded-xl shadow-sm bg-white">
                    <option>All Statuses</option>
                    <option>Draft</option>
                    <option>For Approval</option>
                    <option>Approved by Members</option>
                    <option>Submitted to DSWD</option>
                    <option>Approved</option>
                </select>
                <a href="{{ route('withdrawals.create') }}" class="inline-flex items-center px-4 py-2 border border-[#0c6d57] text-[#0c6d57] rounded-lg text-sm font-medium hover:bg-[#0c6d57]/5 transition-colors shadow-sm">
                    File Withdrawal
                </a>
            </div>
        </div>

        <!-- Desktop Table view -->
        <div class="hidden sm:block bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject & Title</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Created</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <!-- Approved Row -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-bold text-gray-900">Res No. 2024-05</p>
                            <p class="text-sm text-gray-500">Authorization for Fund Withdrawal</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                Approved
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mr-2 max-w-[4rem]">
                                    <div class="bg-green-500 h-1.5 rounded-full" style="width: 100%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600">100%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oct 12, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('resolutions.show', 1) }}" class="text-[#0c6d57] hover:text-[#0a5c49] bg-[#0c6d57]/10 px-3 py-1.5 rounded-lg hover:bg-[#0c6d57]/20 transition-colors">View Details</a>
                        </td>
                    </tr>
                    
                    <!-- Needs Signatures Row -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-bold text-gray-900">Res No. 2024-08</p>
                            <p class="text-sm text-gray-500">Procurement of Additional Feeds</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                For Approval
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="flex items-center mb-1">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mr-2 max-w-[4rem]">
                                        <div class="bg-yellow-500 h-1.5 rounded-full" style="width: 45%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600">45%</span>
                                </div>
                                <span class="text-[10px] text-gray-500 uppercase tracking-wide">Needs 75%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Nov 03, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('resolutions.show', 2) }}" class="text-[#0c6d57] hover:text-[#0a5c49] bg-[#0c6d57]/10 px-3 py-1.5 rounded-lg hover:bg-[#0c6d57]/20 transition-colors">Review Signatures</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card view -->
        <div class="sm:hidden px-4 space-y-4">
            <!-- Card 1 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 relative">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Res No. 2024-05</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Authorization for Fund Withdrawal</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Approved
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Oct 12, 2024
                    </span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 mb-4">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
                <a href="{{ route('resolutions.show', 1) }}" class="block w-full text-center bg-gray-50 hover:bg-gray-100 text-[#0c6d57] border border-gray-200 font-medium px-4 py-2.5 rounded-lg text-sm transition-colors">
                    View Details
                </a>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 relative">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Res No. 2024-08</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Procurement of Additional Feeds</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        For Approval
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Nov 03, 2024
                    </span>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Signatures Collected</span>
                        <span class="font-medium text-gray-900">45% (Needs 75%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 mb-4">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <a href="{{ route('resolutions.show', 2) }}" class="block w-full text-center bg-[#0c6d57] hover:bg-[#0a5c49] text-white font-medium px-4 py-2.5 rounded-lg text-sm transition-colors">
                    Review Signatures
                </a>
            </div>
        </div>

    </div>
</x-app-layout>