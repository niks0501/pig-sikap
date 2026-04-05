<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('reports.generate', ['type' => $type ?? 'inventory']) }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 hidden sm:block">
                    Preview: {{ ucfirst($type ?? 'Inventory') }} Report
                </h2>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 sm:hidden">
                    {{ ucfirst($type ?? 'Inventory') }}
                </h2>
            </div>
            
            <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0 hide-scrollbar">
                <button onclick="window.print()" class="px-4 py-2 border rounded-lg text-gray-600 bg-white hover:bg-gray-50 transition-colors font-medium flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="hidden sm:inline">Print</span>
                </button>
                <button class="px-4 py-2 bg-red-50 text-red-700 border border-red-100 rounded-lg hover:bg-red-100 transition-colors font-medium flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    PDF
                </button>
                <button class="px-4 py-2 bg-[#0c6d57]/10 text-[#0c6d57] border border-[#0c6d57]/20 rounded-lg hover:bg-[#0c6d57]/20 transition-colors font-medium flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    CSV
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Specific Print Styling ensures the report looks good on paper -->
    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        @media print {
            body * {
                visibility: hidden;
            }
            #printable-report, #printable-report * {
                visibility: visible;
            }
            #printable-report {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            /* Hide the browser's default printing header/footer usually */
            @page {
                size: portrait;
                margin: 1cm;
            }
        }
    </style>

    <div class="py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- The Printable Document Container -->
        <div id="printable-report" class="mx-auto max-w-[800px] bg-white shadow-xl sm:rounded-lg overflow-hidden border border-gray-200 min-h-[900px] flex flex-col">
            
            <!-- Report Header (Letterhead styling) -->
            <div class="p-8 pb-4 border-b-2 border-[#0c6d57]">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0c6d57] mb-1">Pig Sikap Agricultural Association</h1>
                        <p class="text-sm text-gray-600">San Jose, IT Park, City</p>
                        <p class="text-sm text-gray-600">Contact: (02) 123-4567 | pigsikap@example.com</p>
                    </div>
                    <div class="text-right">
                        <div class="inline-block p-2 bg-[#0c6d57]/10 rounded font-bold text-[#0c6d57] uppercase tracking-wider text-xl">
                            {{ strtoupper($type ?? 'INVENTORY') }} REPORT
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-8 pt-6 border-t border-gray-100 text-sm">
                    <div>
                        <p><span class="font-semibold text-gray-600 w-24 inline-block">Generated:</span> {{ now()->format('M d, Y, h:i A') }}</p>
                        <p><span class="font-semibold text-gray-600 w-24 inline-block">Cycle:</span> All Active</p>
                    </div>
                    <div>
                        <p><span class="font-semibold text-gray-600 w-24 inline-block">Period:</span> This Quarter</p>
                        <p><span class="font-semibold text-gray-600 w-24 inline-block">Prepared by:</span> System Admin</p>
                    </div>
                </div>
            </div>

            <!-- Report Body -->
            <div class="p-8 flex-grow">
                
                <!-- Summary Metrics Component (Conditional based on report type) -->
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl text-center">
                        <p class="text-sm text-gray-500 font-medium mb-1">Total Records</p>
                        <p class="text-3xl font-bold text-[#0c6d57]">142</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl text-center">
                        <p class="text-sm text-gray-500 font-medium mb-1">Current Value</p>
                        <p class="text-3xl font-bold text-[#0c6d57]">₱ 84,500</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl text-center">
                        <p class="text-sm text-gray-500 font-medium mb-1">Variance</p>
                        <p class="text-3xl font-bold text-green-600">+12%</p>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Detailed Breakdown</h3>
                    
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y overflow-hidden divide-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 tracking-wider">
                                <tr>
                                    <th class="px-6 py-4 text-left">Date</th>
                                    <th class="px-6 py-4 text-left">Category/Description</th>
                                    <th class="px-6 py-4 text-left">Status</th>
                                    <th class="px-6 py-4 text-right">Amount/Qty</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100 text-sm">
                                <!-- Dummy Data Rows -->
                                @for($i = 1; $i <= 8; $i++)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">Oct {{ str_pad($i * 3, 2, '0', STR_PAD_LEFT) }}, 2024</td>
                                    <td class="px-6 py-4 text-gray-800 font-medium">Record Entry #{{ 1000 + $i }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-gray-700 font-medium">
                                        ₱ {{ number_format($i * 1250.50, 2) }}
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-300">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-gray-800 uppercase tracking-wider text-xs">Total</td>
                                    <td class="px-6 py-4 text-right text-[#0c6d57] text-lg">₱ 45,018.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Report Footer / Signature Block -->
            <div class="p-8 pt-0 mt-8">
                <div class="grid grid-cols-2 gap-16 text-center pt-8 border-t">
                    <!-- Prepared By -->
                    <div>
                        <div class="border-b-2 border-gray-800 mb-2 h-10 w-full max-w-[200px] mx-auto"></div>
                        <p class="font-bold text-gray-800 text-sm">System Administrator</p>
                        <p class="text-xs text-gray-500 uppercase tracking-widest mt-1">Prepared By</p>
                    </div>
                    <!-- Approved By -->
                    <div>
                        <div class="border-b-2 border-gray-800 mb-2 h-10 w-full max-w-[200px] mx-auto"></div>
                        <p class="font-bold text-gray-800 text-sm">Association President</p>
                        <p class="text-xs text-gray-500 uppercase tracking-widest mt-1">Noted By</p>
                    </div>
                </div>
            </div>
            
            <!-- Absolute bottom page indicator for print -->
            <div class="text-center p-4 text-xs text-gray-400 bg-gray-50 border-t">
                Report generated by Pig Sikap System. Page 1 of 1
            </div>

        </div>
    </div>
</x-app-layout>