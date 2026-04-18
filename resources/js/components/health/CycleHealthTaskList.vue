<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    cycleArchived: {
        type: Boolean,
        default: false,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    todayDate: {
        type: String,
        required: true,
    },
});

const task = computed(() => props.item?.task ?? {});
const timelineDateLabel = computed(() => props.item?.timeline_date_label ?? 'Timeline Date');
const timelineDateText = computed(() => formatDate(props.item?.timeline_date));

const isTerminal = computed(() => Boolean(task.value.is_terminal));
const isOralMedication = computed(() => Boolean(task.value.is_oral_medication));

const statusClass = computed(() => {
    switch (task.value.status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-800';
        case 'partially_completed':
            return 'bg-amber-100 text-amber-800';
        case 'overdue':
            return 'bg-red-100 text-red-800';
        case 'rescheduled':
            return 'bg-blue-100 text-blue-800';
        case 'skipped':
        case 'not_applicable':
            return 'bg-gray-200 text-gray-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <article class="relative rounded-2xl border p-4 shadow-sm sm:p-5" :class="isOralMedication ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200 bg-gray-50'">
        <span class="absolute left-3 top-5 h-2.5 w-2.5 rounded-full" :class="isOralMedication ? 'bg-emerald-500' : 'bg-[#0c6d57]'" />

        <div class="pl-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-base font-bold text-gray-900">{{ task.task_name }}</h4>
                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold" :class="statusClass">
                        {{ task.formatted_status || task.status }}
                    </span>
                    <span v-if="isOralMedication" class="inline-flex rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                        Oral Medication
                    </span>
                </div>

                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ timelineDateLabel }}: {{ timelineDateText }}
                </span>
            </div>

            <p class="mt-1 text-sm text-gray-600">
                {{ task.task_type_label }}
                <template v-if="task.planned_start_date"> • Planned {{ formatDate(task.planned_start_date) }}</template>
                <template v-if="task.planned_end_date"> to {{ formatDate(task.planned_end_date) }}</template>
                <template v-if="task.follow_up_date"> • Follow-up {{ formatDate(task.follow_up_date) }}</template>
            </p>

            <p class="mt-1 text-sm text-gray-700">
                Coverage {{ Number(task.completed_count || 0).toLocaleString() }} / {{ Number(task.target_count || 0).toLocaleString() }}
            </p>

            <form v-if="!cycleArchived && !isTerminal" :action="task.update_url" method="POST" class="mt-3 space-y-3">
                <input type="hidden" name="_token" :value="csrfToken">
                <input type="hidden" name="_method" value="PATCH">

                <div class="grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Completed Count</span>
                        <input
                            type="number"
                            name="completed_count"
                            min="0"
                            :max="Number(task.target_count || 0)"
                            :placeholder="`0 - ${Number(task.target_count || 0)}`"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>

                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Actual Date</span>
                        <input
                            type="date"
                            name="actual_date"
                            :value="todayDate"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>

                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Follow-up Date</span>
                        <input
                            type="date"
                            name="follow_up_date"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>

                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Reschedule Planned Date</span>
                        <input
                            type="date"
                            name="planned_start_date"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>
                </div>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Remarks</span>
                    <textarea
                        name="remarks"
                        rows="2"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    />
                </label>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" name="action" value="complete_all" class="rounded-lg bg-[#0c6d57] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#0a5a48]">
                        Complete All
                    </button>
                    <button type="submit" name="action" value="partial" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600">
                        Partial
                    </button>
                    <button type="submit" name="action" value="reschedule" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-700">
                        Reschedule
                    </button>
                    <template v-if="task.is_optional">
                        <button type="submit" name="action" value="skip" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                            Skip
                        </button>
                        <button type="submit" name="action" value="not_applicable" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                            Not Applicable
                        </button>
                    </template>
                </div>
            </form>
            <p v-else class="mt-3 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-500">
                Task is terminal or cycle is archived.
            </p>

            <p v-if="task.remarks" class="mt-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                {{ task.remarks }}
            </p>
        </div>
    </article>
</template>
