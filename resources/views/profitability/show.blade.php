<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('profitability.index') }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Batch 2024-A (Fattener) Profitability
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 sm:px-6 lg:px-8">
        <!-- Main Actions -->
        <div class="flex justify-between items-center px-4 sm:px-0 mb-6">
            <div class="text-sm text-gray-500">
                Period: Jan 1, 2024 - Apr 30, 2024
            </div>
            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium transition-colors shadow-sm">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
        </div>

        <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl printable-card">
            <!-- Header section -->
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-gray-500 text-sm font-medium tracking-wide uppercase">Final Net Profit</h3>
                    <p class="text-4xl font-extrabold text-gray-900 mt-2 tracking-tight">₱42,500.00</p>
                </div>
            </div>

            <!-- Breakdown section -->
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                <!-- Sales Side -->
                <div class="p-6">
                    <h4 class="text-lg font-bold items-center gap-2 flex mb-4 text-gray-800">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> Total Sales
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-600">Live Weight Logs</span>
                            <span class="font-medium text-gray-900">₱80,000.00</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-600">Meat Cuts / Mixed</span>
                            <span class="font-medium text-gray-900">₱5,000.00</span>
                        </div>
                        <div class="flex justify-between pt-4">
                            <span class="font-bold text-gray-900">Gross Revenue</span>
                            <span class="font-bold text-gray-900">₱85,000.00</span>
                        </div>
                    </div>
                </div>

                <!-- Expenses Side -->
                <div class="p-6">
                    <h4 class="text-lg font-bold items-center gap-2 flex mb-4 text-gray-800">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Total Expenses
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-600">Feeds & Supplements</span>
                            <span class="font-medium text-gray-900">₱30,000.00</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-600">Veterinary</span>
                            <span class="font-medium text-gray-900">₱5,000.00</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-600">Maintenance & Bills</span>
                            <span class="font-medium text-gray-900">₱7,500.00</span>
                        </div>
                        <div class="flex justify-between pt-4">
                            <span class="font-bold text-gray-900">Total Costs</span>
                            <span class="font-bold text-gray-900 text-red-600">- ₱42,500.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="bg-gray-50 p-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h5 class="font-semibold text-gray-800">Next Step: Profit Distribution</h5>
                    <p class="text-sm text-gray-500 mt-1">Review the 50/25/25 distribution model for this cycle.</p>
                </div>
                <a href="{{ route('profit-sharing', ['id' => $id]) }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-[#0c6d57] text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-[#0a5c49] transition-colors shadow-sm">
                    View Profit Sharing
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            body { background: white !important; }
            .max-w-4xl { max-width: 100% !important; padding: 0 !important; }
            button, a { display: none !important; }
            .printable-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
        }
    </style>
</x-app-layout>