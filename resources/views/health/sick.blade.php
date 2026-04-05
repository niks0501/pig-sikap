<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('health.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Sick & Isolated Cases</h2>
                    <p class="text-sm text-gray-500 mt-1">Monitor groups requiring immediate care and treatments.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('health.create') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-orange-600 text-white font-bold text-sm rounded-xl hover:bg-orange-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Report Sick Case
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto" x-data="{ showResolveModal: false }">
        
        <!-- Search -->
        <div class="mb-6 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" placeholder="Search sick records..." class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 sm:text-sm shadow-sm transition-colors">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Case Card 1 (Active) -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-orange-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-2 h-full bg-orange-500"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-100 text-orange-800 mb-2">Active Care Underway</span>
                        <h3 class="text-lg font-bold text-gray-900 border-b border-transparent hover:border-gray-900 transition-colors">
                            <a href="{{ route('batches.health', 'BAT-005') }}">BAT-005: Scouring / Diarrhea</a>
                        </h3>
                    </div>
                </div>
                
                <div class="space-y-3 text-sm mb-6">
                    <div class="flex gap-2">
                        <span class="font-bold text-gray-500 w-24">Date Found:</span>
                        <span class="text-gray-900 font-medium">Apr 5, 2026 (Yesterday)</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-bold text-gray-500 w-24">Treatment:</span>
                        <span class="text-gray-900 font-medium">Antibiotics & Electrolytes given.</span>
                    </div>
                    <div class="bg-orange-50 rounded-xl p-3 border border-orange-100">
                        <span class="block font-bold text-orange-800 mb-1">Condition Remarks:</span>
                        <span class="text-orange-900">2 piglets appear weak. Separated to corner pen. Marked with yellow spray.</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-white text-gray-700 font-bold text-sm rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors shadow-sm">
                        Update Notes
                    </button>
                    <button @click="showResolveModal = true" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-orange-100 text-orange-700 font-bold text-sm rounded-xl hover:bg-orange-200 transition-colors shadow-none">
                        Mark Cured
                    </button>
                </div>
            </div>

            <!-- Case Card 2 (Active) -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-red-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-2 h-full bg-red-500"></div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-800 mb-2">Needs Re-Check</span>
                        <h3 class="text-lg font-bold text-gray-900 border-b border-transparent hover:border-gray-900 transition-colors">
                            <a href="{{ route('batches.health', 'BAT-002') }}">BAT-002: Lameness / Joint Issues</a>
                        </h3>
                    </div>
                </div>
                
                <div class="space-y-3 text-sm mb-6">
                    <div class="flex gap-2">
                        <span class="font-bold text-gray-500 w-24">Date Found:</span>
                        <span class="text-gray-900 font-medium">Apr 2, 2026</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-bold text-gray-500 w-24">Treatment:</span>
                        <span class="text-gray-900 font-medium">Anti-inflammatory shot administered.</span>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 border border-red-100">
                        <span class="block font-bold text-red-800 mb-1">Condition Remarks:</span>
                        <span class="text-red-900">1 gilt struggling to stand. Placed on soft bedding alone.</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-white text-gray-700 font-bold text-sm rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors shadow-sm">
                        Update Notes
                    </button>
                    <button @click="showResolveModal = true" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-red-100 text-red-700 font-bold text-sm rounded-xl hover:bg-red-200 transition-colors shadow-none">
                        Mark Cured
                    </button>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>