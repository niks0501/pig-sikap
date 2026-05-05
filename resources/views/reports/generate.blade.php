<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('reports.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Generate {{ ucfirst($type ?? 'inventory') }} Report</h2>
                <p class="text-sm text-gray-500">Select filters and preview before downloading.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="p-6">
                    @php
                    $generatorProps = json_encode([
                        'type' => $type,
                        'cycles' => $cycles,
                        'actionUrl' => $actionUrl,
                    ]);
                    @endphp
                    <div data-vue-component="report-generator" data-props="{{ $generatorProps }}"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
