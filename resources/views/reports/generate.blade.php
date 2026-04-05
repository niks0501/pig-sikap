<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('reports.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Generate {{ ucfirst(request('type')) }} Report
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('reports.preview', ['type' => request('type') ?? 'inventory']) }}" method="GET" class="space-y-6">
                        
                        <!-- Report Parameters -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Report Parameters</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- Cycle Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pig Cycle</label>
                                    <select name="cycle" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                                        <option value="all">All Active Cycles</option>
                                        <option value="1">Cycle 1 (Started Jan 2024)</option>
                                        <option value="2">Cycle 2 (Started June 2024)</option>
                                    </select>
                                </div>

                                <!-- Date Range Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                    <select name="date_range" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                                        <option value="this_month">This Month</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="this_quarter">This Quarter</option>
                                        <option value="this_year">This Year</option>
                                        <option value="custom">Custom Date Range</option>
                                    </select>
                                </div>
                                
                                <!-- Custom Date Start -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date (Optional)</label>
                                    <input type="date" name="start_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                                </div>

                                <!-- Custom Date End -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date (Optional)</label>
                                    <input type="date" name="end_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                                </div>
                            </div>
                        </div>

                        <!-- Include Details Toggles -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                            <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Display Options</h3>

                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" checked class="rounded border-gray-300 text-[#0c6d57] shadow-sm focus:ring-[#0c6d57]">
                                    <span class="ml-2 text-sm text-gray-700">Include summary charts and graphs</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" checked class="rounded border-gray-300 text-[#0c6d57] shadow-sm focus:ring-[#0c6d57]">
                                    <span class="ml-2 text-sm text-gray-700">Include detailed line items / transaction logs</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" class="rounded border-gray-300 text-[#0c6d57] shadow-sm focus:ring-[#0c6d57]">
                                    <span class="ml-2 text-sm text-gray-700">Add association letterhead and signature blocks</span>
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <a href="{{ route('reports.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-[#0c6d57] text-white rounded-lg hover:bg-[#0a5c4a] transition-colors font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Generate & Preview
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>