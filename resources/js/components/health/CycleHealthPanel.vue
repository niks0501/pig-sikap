<script setup>
import { computed, ref } from 'vue';
import CycleHealthIncidentList from './CycleHealthIncidentList.vue';
import CycleHealthTaskList from './CycleHealthTaskList.vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
    },
    healthSummary: {
        type: Object,
        default: () => ({}),
    },
    oralMedicationTask: {
        type: Object,
        default: null,
    },
    timelineItems: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
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

const counts = computed(() => props.healthSummary?.counts ?? {});
const activeMetrics = computed(() => props.healthSummary?.active ?? {});
const lifetimeMetrics = computed(() => props.healthSummary?.lifetime ?? {});
const isArchived = computed(() => props.cycle?.stage === 'Completed' || ['Sold', 'Closed'].includes(props.cycle?.status ?? ''));
const timelineFilter = ref('due_overdue');
const timelineFilterOptions = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'due_overdue', label: 'Due / Overdue' },
    { key: 'completed', label: 'Completed' },
];

const taskTimelineItems = computed(() => props.timelineItems.filter((item) => item.kind === 'task'));

const taskFilterKey = (item) => {
    const task = item.task ?? {};
    const status = String(task.status ?? '');

    if (status === 'completed') {
        return 'completed';
    }

    if (task.is_terminal) {
        return 'completed';
    }

    const plannedDate = task.planned_start_date || item.timeline_date || '';

    if (plannedDate !== '' && plannedDate <= props.todayDate) {
        return 'due_overdue';
    }

    return 'pending';
};

const timelineFilterCounts = computed(() => {
    const counts = {
        all: props.timelineItems.length,
        pending: 0,
        due_overdue: 0,
        completed: 0,
    };

    taskTimelineItems.value.forEach((item) => {
        counts[taskFilterKey(item)] += 1;
    });

    return counts;
});

const filteredTimelineItems = computed(() => {
    if (timelineFilter.value === 'all') {
        return props.timelineItems;
    }

    return taskTimelineItems.value.filter((item) => taskFilterKey(item) === timelineFilter.value);
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
    <section class="relative overflow-hidden rounded-3xl bg-[#0c6d57] p-6 text-white shadow-md">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Purchase Date</p>
                <p class="mt-1 text-lg font-bold">{{ formatDate(props.cycle.date_of_purchase) }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Days Since Acquisition</p>
                <p class="mt-1 text-lg font-bold">{{ props.cycle.days_since_acquisition ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Current Count</p>
                <p class="mt-1 text-lg font-bold">{{ Number(props.cycle.current_count || 0).toLocaleString() }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Overdue Tasks</p>
                <p class="mt-1 text-lg font-bold">{{ Number(counts.overdue || 0).toLocaleString() }}</p>
            </div>
            <div>
                <a :href="props.routes.cycleShow" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0c6d57] transition-colors hover:bg-gray-50">
                    Open Cycle Detail
                </a>
            </div>
        </div>
    </section>

    <!-- Spacer -->
    <div class="h-4"></div>

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Post-Purchase Health Plan</p>
            <p class="mt-2 text-lg font-bold text-gray-900">
                {{ props.cycle.health_template?.name || 'No assigned template' }}
            </p>
            <p v-if="props.cycle.health_template?.code" class="mt-1 text-sm text-gray-600">Template Code: {{ props.cycle.health_template.code }}</p>
        </article>

        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Oral Medication Period</p>
            <template v-if="props.oralMedicationTask">
                <p class="mt-2 text-lg font-bold text-emerald-900">{{ props.oralMedicationTask.task_name }}</p>
                <p class="mt-1 text-sm text-emerald-800">
                    {{ formatDate(props.oralMedicationTask.planned_start_date) }}
                    <template v-if="props.oralMedicationTask.planned_end_date"> to {{ formatDate(props.oralMedicationTask.planned_end_date) }}</template>
                </p>
            </template>
            <p v-else class="mt-2 text-sm font-semibold text-emerald-900">No oral medication period task configured for this cycle.</p>
        </article>
    </section>

    <!-- Spacer -->
    <div class="h-4"></div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Due Today</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(counts.due_today || 0).toLocaleString() }}</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700">Overdue</p>
            <p class="mt-2 text-2xl font-bold text-red-900">{{ Number(counts.overdue || 0).toLocaleString() }}</p>
        </article>
        <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Upcoming</p>
            <p class="mt-2 text-2xl font-bold text-blue-900">{{ Number(counts.upcoming || 0).toLocaleString() }}</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Completed Recently</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ Number(counts.completed_recently || 0).toLocaleString() }}</p>
        </article>
    </section>

    <!-- Spacer -->
    <div class="h-4"></div>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Current Active State</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-white/80 bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Healthy Now</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ Number(activeMetrics.healthy_now ?? counts.healthy_now ?? 0).toLocaleString() }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Currently Sick</p>
                    <p class="mt-1 text-xl font-bold text-amber-900">{{ Number(activeMetrics.currently_sick ?? counts.currently_sick ?? 0).toLocaleString() }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Currently Isolated</p>
                    <p class="mt-1 text-xl font-bold text-orange-900">{{ Number(activeMetrics.currently_isolated ?? counts.currently_isolated ?? 0).toLocaleString() }}</p>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-600">Historical Totals</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-white bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Recovered</p>
                    <p class="mt-1 text-xl font-bold text-emerald-800">{{ Number(lifetimeMetrics.total_recovered_reported ?? counts.total_recovered_reported ?? 0).toLocaleString() }}</p>
                </div>
                <div class="rounded-xl border border-white bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Deceased</p>
                    <p class="mt-1 text-xl font-bold text-rose-800">{{ Number(lifetimeMetrics.total_deceased_reported ?? counts.total_deceased_reported ?? counts.mortality ?? 0).toLocaleString() }}</p>
                </div>
                <div class="rounded-xl border border-white bg-white p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Incident Events</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ Number(counts.incidents || 0).toLocaleString() }}</p>
                </div>
            </div>
        </article>
    </section>

    <!-- Spacer -->
    <div class="h-4"></div>

    <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Cycle Timeline</h3>
                <p class="mt-1 text-sm text-gray-500">A single chronological view of scheduled tasks, treatment actions, and incidents.</p>
            </div>
            <div v-if="!isArchived" class="flex flex-wrap gap-2">
                <a :href="props.routes.recordIncident" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-2 text-xs font-bold text-[#0c6d57] hover:bg-[#0c6d57]/10">
                    Record Incident
                </a>
                <a v-if="props.routes.recordMortality" :href="props.routes.recordMortality" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700">
                    Record Mortality
                </a>
            </div>
            <span v-else class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-600">
                Archived cycle: incident recording disabled
            </span>
        </div>

        <div class="mt-3 grid gap-2" aria-live="polite">
            <p class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-900">
                Completed health tasks stay visible here as historical proof. Use the filters below if the timeline becomes long.
            </p>
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">
                Deceased incidents deduct cycle current count immediately.
            </p>
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                Isolated incidents remain health records only and do not deduct cycle current count.
            </p>
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">
                Recovered events close one active bucket per entry, and deceased events can close a bucket when a resolution target is provided.
            </p>
        </div>

        <div class="mt-4 flex flex-wrap gap-2" aria-label="Timeline filters">
            <button
                v-for="option in timelineFilterOptions"
                :key="option.key"
                type="button"
                class="inline-flex min-h-10 items-center justify-center rounded-xl border px-3 py-2 text-xs font-bold transition"
                :class="timelineFilter === option.key ? 'border-[#0c6d57] bg-[#0c6d57] text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="timelineFilter = option.key"
            >
                {{ option.label }} ({{ Number(timelineFilterCounts[option.key] || 0).toLocaleString() }})
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <template v-for="item in filteredTimelineItems" :key="`${item.kind}-${item.id}`">
                <CycleHealthTaskList
                    v-if="item.kind === 'task'"
                    :item="item"
                    :cycle-archived="isArchived"
                    :csrf-token="props.csrfToken"
                    :today-date="props.todayDate"
                    :cycle-current-count="Number(props.cycle.current_count || 0)"
                />
                <CycleHealthIncidentList v-else :item="item" />
            </template>

            <p v-if="filteredTimelineItems.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-sm text-gray-500">
                No timeline records match this filter.
            </p>
        </div>
    </section>
</template>
