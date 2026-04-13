@php
    $undoTask = session('undo_task');
    $statusMessage = is_array($undoTask) ? null : session('status');
    $warningMessage = session('warning');
    $errorMessage = $errors->any() ? $errors->first() : session('error');
@endphp

@if ($statusMessage || $warningMessage || $errorMessage || is_array($undoTask))
    <div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4 sm:justify-end sm:px-6">
        <div class="w-full max-w-md space-y-3">
            @if ($statusMessage)
                <div
                    x-data="{ open: true }"
                    x-show="open"
                    x-transition.opacity.duration.250ms
                    x-init="setTimeout(() => open = false, 5000)"
                    class="pointer-events-auto rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-lg"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p>{{ $statusMessage }}</p>
                        <button type="button" class="rounded-md px-1 text-emerald-700 hover:bg-emerald-100" @click="open = false" aria-label="Dismiss message">&times;</button>
                    </div>
                </div>
            @endif

            @if ($warningMessage)
                <div
                    x-data="{ open: true }"
                    x-show="open"
                    x-transition.opacity.duration.250ms
                    x-init="setTimeout(() => open = false, 7000)"
                    class="pointer-events-auto rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 shadow-lg"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p>{{ $warningMessage }}</p>
                        <button type="button" class="rounded-md px-1 text-amber-700 hover:bg-amber-100" @click="open = false" aria-label="Dismiss warning">&times;</button>
                    </div>
                </div>
            @endif

            @if ($errorMessage)
                <div
                    x-data="{ open: true }"
                    x-show="open"
                    x-transition.opacity.duration.250ms
                    x-init="setTimeout(() => open = false, 9000)"
                    class="pointer-events-auto rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 shadow-lg"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p>{{ $errorMessage }}</p>
                        <button type="button" class="rounded-md px-1 text-rose-700 hover:bg-rose-100" @click="open = false" aria-label="Dismiss error">&times;</button>
                    </div>
                </div>
            @endif

            @if (is_array($undoTask))
                <div
                    x-data="{ open: true }"
                    x-show="open"
                    x-transition.opacity.duration.250ms
                    x-init="setTimeout(() => open = false, 12000)"
                    class="pointer-events-auto rounded-2xl border-2 border-[#0c6d57]/30 bg-white px-4 py-3 shadow-2xl"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-base font-bold text-gray-900">{{ $undoTask['message'] ?? 'Task marked as Completed. Undo?' }}</p>
                            <p class="mt-0.5 text-sm text-gray-600">Need to correct this action? Restore the previous task state now.</p>
                        </div>
                        <button type="button" class="rounded-md px-1 text-gray-500 hover:bg-gray-100" @click="open = false" aria-label="Dismiss undo prompt">&times;</button>
                    </div>

                    @if (isset($undoTask['cycle'], $undoTask['task'], $undoTask['token']))
                        <form action="{{ route('health.cycles.tasks.undo', ['cycle' => $undoTask['cycle'], 'healthTask' => $undoTask['task']]) }}" method="POST" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="undo_token" value="{{ $undoTask['token'] }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0a5a48] sm:w-auto">
                                Undo Last Action
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
