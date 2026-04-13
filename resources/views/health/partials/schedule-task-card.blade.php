<article class="rounded-2xl border p-4 shadow-sm {{ $toneClass }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            @if ($cycle)
                <a href="{{ route('health.cycles.show', $cycle) }}" class="text-sm font-bold text-[#0c6d57] hover:underline">
                    {{ $cycle->batch_code }}
                </a>
            @else
                <p class="text-sm font-semibold text-gray-500">Unknown cycle</p>
            @endif
            <h4 class="mt-1 text-lg font-bold text-gray-900">{{ $task->task_name }}</h4>
            <p class="mt-1 text-sm text-gray-600">
                {{ str_replace('_', ' ', (string) $task->task_type) }}
                • Planned: {{ $plannedDate ?: '-' }}
            </p>
            @if ($task->remarks)
                <p class="mt-2 rounded-lg border border-gray-200 bg-white/80 px-3 py-2 text-sm text-gray-600">
                    {{ $task->remarks }}
                </p>
            @endif
        </div>

        @if ($cycle)
            <form action="{{ route('health.cycles.tasks.update', [$cycle, $task]) }}" method="POST" class="flex flex-col gap-2 sm:min-w-48">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="complete_all">
                <input type="hidden" name="actual_date" value="{{ now()->toDateString() }}">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0a5a48]">
                    Mark Completed
                </button>
                <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Open Cycle Actions
                </a>
            </form>
        @endif
    </div>
</article>
