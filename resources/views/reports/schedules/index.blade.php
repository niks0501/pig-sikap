<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-gray-900">Report Schedules</h2>
            <p class="text-sm text-gray-500">Automate monthly and quarterly reports for officers.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Create Schedule</h3>
                <form class="mt-4 grid gap-4 sm:grid-cols-3" action="{{ route('reports.schedules.store') }}" method="POST">
                    @csrf
                    <label class="text-sm font-semibold text-gray-700">
                        Report Type
                        <select name="report_type" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                            <option value="expense">Expense</option>
                            <option value="sales">Sales</option>
                            <option value="monthly">Monthly Financial</option>
                            <option value="quarterly">Quarterly Financial</option>
                            <option value="profitability">Profitability</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Format
                        <select name="format" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Frequency
                        <select name="frequency" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Day of Month
                        <input type="number" name="day_of_month" min="1" max="28" value="1" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Run At
                        <input type="time" name="run_at" value="08:00" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Cycle
                        <select name="cycle_id" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                            <option value="">All active cycles</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->batch_code }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="sm:col-span-3 flex justify-end">
                        <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-6 text-sm font-semibold text-white">
                            Save Schedule
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Existing Schedules</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3 text-left">Type</th>
                                <th class="px-3 py-3 text-left">Format</th>
                                <th class="px-3 py-3 text-left">Frequency</th>
                                <th class="px-3 py-3 text-left">Day</th>
                                <th class="px-3 py-3 text-left">Run At</th>
                                <th class="px-3 py-3 text-left">Next Run</th>
                                <th class="px-3 py-3 text-left">Status</th>
                                <th class="px-3 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($schedules as $schedule)
                                <tr>
                                    <td class="px-3 py-3 font-semibold text-gray-800">{{ ucfirst($schedule->report_type) }}</td>
                                    <td class="px-3 py-3">{{ strtoupper($schedule->format) }}</td>
                                    <td class="px-3 py-3">{{ ucfirst($schedule->frequency) }}</td>
                                    <td class="px-3 py-3">{{ $schedule->day_of_month ?? '-' }}</td>
                                    <td class="px-3 py-3">{{ $schedule->run_at ?? '-' }}</td>
                                    <td class="px-3 py-3">{{ $schedule->next_run_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                    <td class="px-3 py-3">{{ ucfirst($schedule->status) }}</td>
                                    <td class="px-3 py-3">
                                        <form action="{{ route('reports.schedules.destroy', $schedule) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-red-600">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">No schedules yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
