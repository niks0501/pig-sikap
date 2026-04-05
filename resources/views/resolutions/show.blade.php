<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('resolutions.index') }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Resolution Review
                </h2>
            </div>
            <div class="hidden sm:flex gap-2">
                <button onclick="window.print()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm">
                    Print / Export
                </button>
                <a href="#" class="inline-flex items-center px-4 py-2 border border-[#0c6d57] text-[#0c6d57] rounded-lg text-sm font-medium hover:bg-[#0c6d57]/5 transition-colors shadow-sm">
                    Edit Details
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 sm:px-0">
            <!-- Left Column: Resolution Document -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Summary Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 mb-3 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 uppercase tracking-widest border border-yellow-200">
                                For Approval
                            </span>
                            <h3 class="text-2xl font-extrabold text-gray-900 mb-1">Res No. 2024-08</h3>
                            <p class="text-base text-gray-600 font-medium">Procurement of Additional Feeds</p>
                        </div>
                        <div class="mt-4 sm:mt-0 text-left sm:text-right">
                            <p class="text-sm text-gray-500 font-semibold uppercase tracking-wider mb-1">Date Created</p>
                            <p class="text-lg font-medium text-gray-900">Nov 03, 2024</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-b border-gray-100">
                        <div class="flex items-center gap-2 text-gray-600 text-sm font-medium">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Linked Meeting: <a href="{{ route('minutes.index') }}" class="text-[#0c6d57] underline hover:text-[#0a5c49]">Monthly Assembly (Nov 2)</a>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-6">
                        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Resolution Text</h4>
                        <div class="prose prose-sm sm:prose max-w-none text-gray-800 leading-relaxed font-serif">
                            <p class="whitespace-pre-line text-base">WHEREAS, the association has reviewed the current feed reserves for the upcoming fattening phase of the Batch 2024 cycle;

WHEREAS, there is an anticipated shortage of 50 sacks of starter feeds to sustain the livestock through December;

WHEREAS, the total estimated cost for the procurement is ₱85,000.00;

NOW, THEREFORE, BE IT RESOLVED, that the members authorize the procurement of additional feeds utilizing the association's revolving fund.

RESOLVED FURTHER, that copies of this resolution be furnished to the DSWD focal person for tracking and final approval of funds withdrawal.</p>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="px-6 py-5 bg-white border-t border-gray-100">
                        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Attachments</h4>
                        <ul class="space-y-3">
                            <li class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 border border-gray-200 rounded-lg hover:border-[#0c6d57] transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div class="text-sm">
                                        <p class="font-bold text-gray-900">Signed_Attendance_Nov2.pdf</p>
                                        <p class="text-gray-500">1.2 MB</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-[#0c6d57] hover:text-[#0a5c49] font-medium text-sm p-2 bg-[#0c6d57]/10 rounded-md">View</a>
                                </div>
                            </li>
                            <li class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 border border-gray-200 rounded-lg hover:border-[#0c6d57] transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div class="text-sm">
                                        <p class="font-bold text-gray-900">Feed_Quotation_Supplier.jpg</p>
                                        <p class="text-gray-500">850 KB</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-[#0c6d57] hover:text-[#0a5c49] font-medium text-sm p-2 bg-[#0c6d57]/10 rounded-md">View</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: Tracking & Approvals -->
            <div class="space-y-6">
                <!-- Approval Progress Card -->
                <div class="bg-white rounded-xl shadow-sm border border-[#0c6d57]/20 overflow-hidden">
                    <div class="bg-[#0c6d57]/5 p-5 border-b border-[#0c6d57]/10">
                        <h3 class="text-base font-bold text-[#0c6d57] flex items-center gap-2 uppercase tracking-wide">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Member Approvals
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <span class="text-5xl font-extrabold text-gray-900">18</span>
                            <span class="text-lg font-medium text-gray-500">/ 40 Members</span>
                            <p class="text-sm font-bold text-[#0c6d57] mt-1 tracking-wide">45% COMPLETION</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="relative w-full bg-gray-100 rounded-full h-3 mb-2 shadow-inner">
                            <div class="bg-yellow-400 h-3 rounded-full transition-all duration-500" style="width: 45%"></div>
                            <!-- Target Marker -->
                            <div class="absolute top-0 bottom-0 left-[75%] border-l-2 border-[#0c6d57] z-10" title="75% Required"></div>
                        </div>
                        <div class="flex justify-between text-xs font-semibold text-gray-500 border-t border-gray-100 pt-2">
                            <span>0%</span>
                            <span class="text-[#0c6d57]">75% Required</span>
                            <span>100%</span>
                        </div>

                        <div class="mt-6 flex flex-col gap-3">
                            <button type="button" class="w-full flex items-center justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#0c6d57] hover:bg-[#0a5c49] focus:outline-none transition-colors">
                                Record New Signature
                            </button>
                            <p class="text-xs text-center text-gray-500">
                                12 more signatures needed to submit.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status Tracker -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-50">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest">Document Status</h3>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <!-- Step 1: Draft -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-[#0c6d57]" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-[#0c6d57] flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div><p class="text-sm font-bold text-gray-900">Draft Completed</p></div>
                                                <div class="text-right text-xs whitespace-nowrap text-gray-500">Nov 3</div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- Step 2: For Approval -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full border-2 border-[#0c6d57] bg-white flex items-center justify-center ring-8 ring-white shadow-sm">
                                                    <span class="h-2.5 w-2.5 bg-[#0c6d57] rounded-full"></span>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm font-bold text-gray-900">For Approval</p>
                                                    <p class="text-xs text-gray-500 mt-0.5 font-medium">Gathering signatures</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- Step 3: Approved by Members -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center ring-8 ring-white">
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div><p class="text-sm font-bold text-gray-400">Approved by Members</p></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- Step 4: Submitted to DSWD -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center ring-8 ring-white">
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div><p class="text-sm font-bold text-gray-400">Submitted to DSWD</p></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- Step 5: Approved -->
                                <li>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center ring-8 ring-white">
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div><p class="text-sm font-bold text-gray-400">Final Approval</p></div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-8 border-t border-gray-100 pt-5 text-center">
                            <a href="{{ route('withdrawals.create') }}" class="text-[#0c6d57] font-semibold text-sm hover:text-[#0a5c49] transition-colors border border-[#0c6d57] px-4 py-2 rounded-lg bg-transparent hover:bg-[#0c6d57]/5">
                                Process Funds Withdrawal
                            </a>
                            <p class="text-xs text-gray-400 mt-2 italic">Requires 'Final Approval' to process withdrawals officially.</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Mobile Actions Sticky Footer -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 sm:hidden z-50 flex gap-2">
            <button class="w-1/2 flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white">
                Edit
            </button>
            <button class="w-1/2 flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#0c6d57]">
                Add Sig
            </button>
        </div>
    </div>
</x-app-layout>