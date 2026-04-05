<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('profitability.show', ['id' => $id]) }}" class="text-gray-500 hover:text-[#0c6d57] transition-colors rounded-lg p-1.5 hover:bg-[#0c6d57]/10" aria-label="Back">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Profit Sharing: Batch 2024-A
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center px-4 sm:px-0 mb-6">
            <div class="text-sm text-gray-500">
                Net Profit Available for Distribution: <strong>₱42,500.00</strong>
            </div>
            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium transition-colors shadow-sm">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Distribution Report
            </button>
        </div>

        <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl printable-element">
            <div class="p-6 md:p-8">
                <div class="text-center mb-10">
                    <h3 class="text-3xl font-extrabold text-[#0c6d57]">SIKAP Distribution Model</h3>
                    <p class="text-gray-500 mt-2">Standard 50% Caretaker / 25% Member / 25% Association breakdown</p>
                </div>

                <!-- Visual Segmented Bar -->
                <div class="mb-12">
                    <div class="w-full h-8 rounded-full overflow-hidden flex shadow-inner">
                        <div class="bg-[#0c6d57] h-full" style="width: 50%;" title="Caretaker 50%"></div>
                        <div class="bg-[#0c6d57]/80 h-full border-l border-white/20" style="width: 25%;" title="Member 25%"></div>
                        <div class="bg-[#0c6d57]/60 h-full border-l border-white/20" style="width: 25%;" title="Association 25%"></div>
                    </div>
                    <div class="flex justify-between mt-3 text-xs md:text-sm font-medium text-gray-500">
                        <div class="w-1/2 text-left pl-2">50%</div>
                        <div class="w-1/4 text-center">25%</div>
                        <div class="w-1/4 text-right pr-2">25%</div>
                    </div>
                </div>

                <!-- Breakdown Calculation -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Caretaker Share -->
                    <div class="border border-[#0c6d57]/20 bg-[#0c6d57]/5 rounded-xl p-6 text-center transform transition duration-200 hover:-translate-y-1">
                        <div class="w-12 h-12 bg-[#0c6d57] rounded-full flex items-center justify-center text-white mx-auto mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-[#0c6d57] uppercase tracking-wide">Caretaker Share</p>
                        <p class="text-xs text-gray-500 mt-1 mb-3">Labor & Maintenance</p>
                        <p class="text-3xl font-extrabold text-gray-900 border-t border-[#0c6d57]/20 pt-4">₱21,250.00</p>
                    </div>

                    <!-- Member Share -->
                    <div class="border border-[#0c6d57]/20 bg-white rounded-xl p-6 text-center transform transition duration-200 hover:-translate-y-1">
                        <div class="w-12 h-12 bg-[#0c6d57]/80 rounded-full flex items-center justify-center text-white mx-auto mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-[#0c6d57] uppercase tracking-wide">Member Share</p>
                        <p class="text-xs text-gray-500 mt-1 mb-3">Investment Return</p>
                        <p class="text-3xl font-extrabold text-gray-900 border-t border-gray-100 pt-4">₱10,625.00</p>
                    </div>

                    <!-- Association Share -->
                    <div class="border border-[#0c6d57]/20 bg-white rounded-xl p-6 text-center transform transition duration-200 hover:-translate-y-1">
                        <div class="w-12 h-12 bg-[#0c6d57]/60 rounded-full flex items-center justify-center text-white mx-auto mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-[#0c6d57] uppercase tracking-wide">Association Fund</p>
                        <p class="text-xs text-gray-500 mt-1 mb-3">Reinvestment & Ops</p>
                        <p class="text-3xl font-extrabold text-gray-900 border-t border-gray-100 pt-4">₱10,625.00</p>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="bg-gray-50 p-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Total Validated</p>
                        <p class="text-xs text-gray-500 tracking-tight">₱21,250 + ₱10,625 + ₱10,625 = ₱42,500.00</p>
                    </div>
                </div>
                <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                    <button class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-white text-gray-700 hover:bg-gray-50 px-5 py-2.5 border border-gray-200 rounded-lg text-sm font-medium transition-colors shadow-sm">
                        Export as PDF
                    </button>
                    <a href="{{ route('profitability.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-[#0c6d57] text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-[#0a5c49] transition-colors shadow-sm">
                        Finish & Close
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            body { background: white !important; }
            .max-w-4xl { max-width: 100% !important; padding: 0 !important; }
            button, a { display: none !important; }
            .printable-element { box-shadow: none !important; border: none !important; }
            .text-center.mb-10 { margin-bottom: 2rem !important; }
            /* Make sure colors show when printing */
            .bg-\[\#0c6d57\] { background-color: #0c6d57 !important; -webkit-print-color-adjust: exact; }
            .bg-\[\#0c6d57\]\/80 { background-color: rgba(12, 109, 87, 0.8) !important; -webkit-print-color-adjust: exact; }
            .bg-\[\#0c6d57\]\/60 { background-color: rgba(12, 109, 87, 0.6) !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</x-app-layout>