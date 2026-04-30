<script setup>
import { computed, ref } from 'vue';
import StatusBadge from '../common/StatusBadge.vue';

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Treatment Schedule',
    },
});

const selectedDate = ref(new Date().toISOString().slice(0, 10));

const groupedTasks = computed(() => {
    return props.tasks.reduce((groups, task) => {
        const key = task.planned_start_date || task.actual_date || 'unscheduled';
        groups[key] = groups[key] || [];
        groups[key].push(task);
        return groups;
    }, {});
});

const visibleTasks = computed(() => groupedTasks.value[selectedDate.value] || []);

const orderedDates = computed(() => {
    return Object.keys(groupedTasks.value)
        .filter((date) => date !== 'unscheduled')
        .sort()
        .slice(0, 14);
});
</script>

<template>
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
                <p class="mt-1 text-sm text-gray-500">Review planned treatments by date.</p>
            </div>
            <label class="sm:w-48">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date</span>
                <input
                    v-model="selectedDate"
                    type="date"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
            </label>
        </div>

        <div v-if="orderedDates.length > 0" class="mt-4 flex gap-2 overflow-x-auto pb-1">
            <button
                v-for="date in orderedDates"
                :key="date"
                type="button"
                :class="[
                    'shrink-0 rounded-xl border px-3 py-2 text-left text-xs font-semibold transition',
                    selectedDate === date ? 'border-[#0c6d57] bg-[#0c6d57]/10 text-[#0c6d57]' : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100',
                ]"
                @click="selectedDate = date"
            >
                {{ new Date(date).toLocaleDateString(undefined, { month: 'short', day: '2-digit' }) }}
            </button>
        </div>

        <div class="mt-4 space-y-3">
            <article
                v-for="task in visibleTasks"
                :key="task.id"
                class="rounded-xl border border-gray-100 bg-gray-50 p-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">{{ task.task_name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ task.cycle?.batch_code || 'No cycle assigned' }}</p>
                    </div>
                    <StatusBadge :status="task.status === 'completed' ? 'success' : 'pending'" size="sm">
                        {{ task.formatted_status || task.status || 'Pending' }}
                    </StatusBadge>
                </div>
            </article>

            <p v-if="visibleTasks.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-6 text-center text-sm font-medium text-gray-500">
                No treatments scheduled for this date.
            </p>
        </div>
    </section>
</template>
