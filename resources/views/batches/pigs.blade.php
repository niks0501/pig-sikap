<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Pig Profiles: {{ $batch->batch_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Lightweight identity records inside one batch.</p>
            </div>
            <a href="{{ route('batches.show', $batch) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Batch Detail
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @unless ($batch->isArchived())
            <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="text-base font-bold text-gray-900">Add Pig Profile</h3>
                <form action="{{ route('batches.pigs.store', $batch) }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-3">
                    @csrf
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig No.</span>
                        <input type="number" min="1" name="pig_no" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</span>
                        <input type="text" name="ear_mark_type" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Left/Right cut">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</span>
                        <input type="text" name="ear_mark_value" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="L-1">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</span>
                        <select name="sex" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <option value="">Not set</option>
                            @foreach ($sexOptions as $sex)
                                <option value="{{ $sex }}">{{ $sex }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                        <select name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            @foreach ($pigStatuses as $status)
                                <option value="{{ $status }}" @selected($status === 'Active')>{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                        <input type="text" name="remarks" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    </label>
                    <div class="md:col-span-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Add Pig Profile
                        </button>
                    </div>
                 </form>
             </section>
         @endunless

        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($batch->pigs as $pig)
                            <tr>
                                <form action="{{ route('batches.pigs.update', [$batch, $pig]) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-4 py-2"><input type="number" min="1" name="pig_no" value="{{ $pig->pig_no }}" class="w-20 rounded-lg border border-gray-300 px-2 py-1.5"></td>
                                    <td class="px-4 py-2"><input type="text" name="ear_mark_type" value="{{ $pig->ear_mark_type }}" class="w-full rounded-lg border border-gray-300 px-2 py-1.5"></td>
                                    <td class="px-4 py-2"><input type="text" name="ear_mark_value" value="{{ $pig->ear_mark_value }}" class="w-full rounded-lg border border-gray-300 px-2 py-1.5"></td>
                                    <td class="px-4 py-2">
                                        <select name="sex" class="rounded-lg border border-gray-300 px-2 py-1.5">
                                            <option value="">-</option>
                                            @foreach ($sexOptions as $sex)
                                                <option value="{{ $sex }}" @selected($pig->sex === $sex)>{{ $sex }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <select name="status" class="rounded-lg border border-gray-300 px-2 py-1.5">
                                            @foreach ($pigStatuses as $status)
                                                <option value="{{ $status }}" @selected($pig->status === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2"><input type="text" name="remarks" value="{{ $pig->remarks }}" class="w-full rounded-lg border border-gray-300 px-2 py-1.5"></td>
                                    <td class="px-4 py-2 text-right">
                                        @if ($batch->isArchived())
                                            <span class="text-xs font-medium text-gray-500">Locked</span>
                                        @else
                                            <button type="submit" class="rounded-lg bg-[#0c6d57] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#0a5a48]">Update</button>
                                        @endif
                                    </td>
                                </form>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm font-medium text-gray-500">No pig profiles recorded for this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
