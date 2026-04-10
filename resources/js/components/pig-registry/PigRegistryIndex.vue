<script setup>
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({ data: [], meta: {} }),
    },
    initialFilters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    recentUpdates: {
        type: Array,
        default: () => [],
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    caretakers: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
    statusMessage: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
});

const loading = ref(false);
const cycles = ref(props.initialData?.data ?? []);
const flashStatus = ref(props.statusMessage || '');
const flashError = ref(props.errorMessage || '');
const summary = ref({
    active_cycles: 0,
    total_piglets: 0,
    total_fatteners: 0,
    total_sick: 0,
    total_deceased: 0,
    ready_for_sale_cycles: 0,
    ...props.summary,
});
const recentUpdates = ref(Array.isArray(props.recentUpdates) ? props.recentUpdates : []);

const meta = reactive({
    current_page: props.initialData?.meta?.current_page ?? 1,
    last_page: props.initialData?.meta?.last_page ?? 1,
    total: props.initialData?.meta?.total ?? 0,
    per_page: props.initialData?.meta?.per_page ?? 12,
});

const filters = reactive({
    search: props.initialFilters?.search ?? '',
    scope: props.initialFilters?.scope ?? 'all',
    stage: props.initialFilters?.stage ?? '',
    status: props.initialFilters?.status ?? '',
    caretaker: props.initialFilters?.caretaker ?? '',
});

const scopeOptions = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Archived', value: 'archived' },
];

const stageOptions = computed(() => [
    { label: 'All stages', value: '' },
    ...props.stages.map((stage) => ({ label: stage, value: stage })),
]);

const statusOptions = computed(() => [
    { label: 'All statuses', value: '' },
    ...props.statuses.map((status) => ({ label: status, value: status })),
]);

const selectedScope = ref(scopeOptions.find((item) => item.value === filters.scope) ?? scopeOptions[0]);
const selectedStage = ref(stageOptions.value.find((item) => item.value === filters.stage) ?? stageOptions.value[0]);
const selectedStatus = ref(statusOptions.value.find((item) => item.value === filters.status) ?? statusOptions.value[0]);

watch(selectedScope, (value) => {
    filters.scope = value?.value ?? 'all';
});

watch(selectedStage, (value) => {
    filters.stage = value?.value ?? '';
});

watch(selectedStatus, (value) => {
    filters.status = value?.value ?? '';
});

let debounceTimer = null;

const fetchCycles = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.routes.index, {
            params: {
                search: filters.search,
                scope: filters.scope,
                stage: filters.stage,
                status: filters.status,
                caretaker: filters.caretaker,
                page,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = response.data || {};

        cycles.value = payload.data || [];
        summary.value = payload.summary || summary.value;
        recentUpdates.value = payload.recent_updates || recentUpdates.value;

        meta.current_page = payload.meta?.current_page ?? 1;
        meta.last_page = payload.meta?.last_page ?? 1;
        meta.total = payload.meta?.total ?? 0;
        meta.per_page = payload.meta?.per_page ?? 12;
    } catch (error) {
        flashError.value = 'Unable to refresh cycle records right now.';
    } finally {
        loading.value = false;
    }
};

watch(
    () => [
        filters.search,
        filters.scope,
        filters.stage,
        filters.status,
        filters.caretaker,
    ],
    () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(() => {
            fetchCycles(1);
        }, 300);
    }
);

const resetFilters = () => {
    filters.search = '';
    filters.caretaker = '';

    selectedScope.value = scopeOptions[0];
    selectedStage.value = stageOptions.value[0];
    selectedStatus.value = statusOptions.value[0];
};

const goToPage = (page) => {
    if (page < 1 || page > meta.last_page || loading.value) {
        return;
    }

    fetchCycles(page);
};

const hasPagination = computed(() => meta.last_page > 1);

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.search.trim()) {
        count += 1;
    }

    if (filters.scope !== 'all') {
        count += 1;
    }

    if (filters.stage) {
        count += 1;
    }

    if (filters.status) {
        count += 1;
    }

    if (filters.caretaker) {
        count += 1;
    }

    return count;
});

const showCycleUrl = (cycleCode) => `${props.routes.showBase}/${encodeURIComponent(cycleCode)}`;

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

const formatDateTime = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const countLabel = (current, initial) => `${Number(current || 0).toLocaleString()} / ${Number(initial || 0).toLocaleString()}`;
</script>

<template>
    <div class="mx-auto max-w-300 space-y-4">
        <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Cycles</h2>
                <p class="mt-1 text-sm text-gray-500">Cycle-first inventory with focused filters and dedicated profile management.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a :href="props.routes.archived" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Archived
                </a>
                <a :href="props.routes.breeders" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                    Breeder Registry
                </a>
                <a :href="props.routes.create" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                    Create Cycle
                </a>
            </div>
        </section>

        <div v-if="flashStatus" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ flashStatus }}
        </div>

        <div v-if="flashError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ flashError }}
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Active Cycles</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(summary.active_cycles || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Piglets</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(summary.total_piglets || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Fatteners</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(summary.total_fatteners || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Sick Pigs</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ Number(summary.total_sick || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Deceased Pigs</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ Number(summary.total_deceased || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Ready For Sale Cycles</p>
                <p class="mt-2 text-2xl font-bold text-blue-900">{{ Number(summary.ready_for_sale_cycles || 0).toLocaleString() }}</p>
            </article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Filter Cycles</h3>
                    <p class="mt-1 text-xs text-gray-500">Narrow records by search, scope, stage, status, and caretaker.</p>
                </div>
                <span class="inline-flex items-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700">
                    {{ activeFilterCount }} active filter{{ activeFilterCount === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <label class="xl:col-span-2">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Cycle code, caretaker"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                </label>

                <div>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Scope</span>
                    <Listbox v-model="selectedScope">
                        <div class="relative">
                            <ListboxButton class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                {{ selectedScope.label }}
                            </ListboxButton>
                            <ListboxOptions class="absolute z-20 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-lg focus:outline-none">
                                <ListboxOption v-for="option in scopeOptions" :key="option.value" :value="option" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-[#0c6d57]/10 text-[#0c6d57]' : 'text-gray-700', 'cursor-pointer px-3 py-2 text-sm']">
                                        <span :class="selected ? 'font-semibold' : 'font-medium'">{{ option.label }}</span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </div>
                    </Listbox>
                </div>

                <div>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Stage</span>
                    <Listbox v-model="selectedStage">
                        <div class="relative">
                            <ListboxButton class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                {{ selectedStage.label }}
                            </ListboxButton>
                            <ListboxOptions class="absolute z-20 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-lg focus:outline-none">
                                <ListboxOption v-for="option in stageOptions" :key="`stage-${option.value || 'all'}`" :value="option" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-[#0c6d57]/10 text-[#0c6d57]' : 'text-gray-700', 'cursor-pointer px-3 py-2 text-sm']">
                                        <span :class="selected ? 'font-semibold' : 'font-medium'">{{ option.label }}</span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </div>
                    </Listbox>
                </div>

                <div>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                    <Listbox v-model="selectedStatus">
                        <div class="relative">
                            <ListboxButton class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                {{ selectedStatus.label }}
                            </ListboxButton>
                            <ListboxOptions class="absolute z-20 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-lg focus:outline-none">
                                <ListboxOption v-for="option in statusOptions" :key="`status-${option.value || 'all'}`" :value="option" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-[#0c6d57]/10 text-[#0c6d57]' : 'text-gray-700', 'cursor-pointer px-3 py-2 text-sm']">
                                        <span :class="selected ? 'font-semibold' : 'font-medium'">{{ option.label }}</span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </div>
                    </Listbox>
                </div>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Caretaker</span>
                    <select v-model="filters.caretaker" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">All caretakers</option>
                        <option v-for="caretaker in props.caretakers" :key="caretaker.id" :value="String(caretaker.id)">
                            {{ caretaker.name }}
                        </option>
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="activeFilterCount === 0" @click="resetFilters">
                    Reset Filters
                </button>
                <span class="inline-flex items-center rounded-xl bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                    {{ loading ? 'Refreshing records...' : `Showing ${Number(meta.total || 0).toLocaleString()} cycle records` }}
                </span>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-2">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Cycle</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Date of Purchase</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Count</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Stage / Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Caretaker</th>
                                    <th class="px-3 py-2 text-right text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-if="loading && cycles.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">
                                        Loading cycle entries...
                                    </td>
                                </tr>
                                <tr v-for="cycle in cycles" :key="cycle.batch_code" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 align-top">
                                        <p class="text-sm font-bold text-gray-900">{{ cycle.batch_code }}</p>
                                        <p class="text-xs text-gray-500">Cycle #{{ cycle.cycle_number || '-' }}</p>
                                    </td>
                                    <td class="px-3 py-2 align-top text-sm text-gray-700">{{ formatDate(cycle.date_of_purchase) }}</td>
                                    <td class="px-3 py-2 align-top text-sm text-gray-700">{{ countLabel(cycle.current_count, cycle.initial_count) }}</td>
                                    <td class="px-3 py-2 align-top text-xs">
                                        <p><span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-800">{{ cycle.stage }}</span></p>
                                        <p class="mt-2"><span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">{{ cycle.status }}</span></p>
                                    </td>
                                    <td class="px-3 py-2 align-top text-sm text-gray-700">
                                        {{ cycle.caretaker?.name || 'Unassigned' }}
                                    </td>
                                    <td class="px-3 py-2 text-right align-top">
                                        <a :href="showCycleUrl(cycle.batch_code)" class="inline-flex items-center justify-center rounded-lg border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-1.5 text-xs font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="!loading && cycles.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">
                                        <p>No cycle records found for your selected filters.</p>
                                        <button type="button" class="mt-3 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50" @click="resetFilters">
                                            Clear Filters
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-3 p-4 md:hidden">
                        <article v-for="cycle in cycles" :key="`mobile-${cycle.batch_code}`" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ cycle.batch_code }}</h3>
                                    <p class="text-xs text-gray-500">Caretaker: {{ cycle.caretaker?.name || 'Unassigned' }}</p>
                                </div>
                                <a :href="showCycleUrl(cycle.batch_code)" class="rounded-lg bg-[#0c6d57]/10 px-3 py-1.5 text-xs font-semibold text-[#0c6d57]">Open</a>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Count</dt>
                                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ countLabel(cycle.current_count, cycle.initial_count) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Date of Purchase</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-800">{{ formatDate(cycle.date_of_purchase) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Stage</dt>
                                    <dd class="mt-1"><span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-800">{{ cycle.stage }}</span></dd>
                                </div>
                                <div>
                                    <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Status</dt>
                                    <dd class="mt-1"><span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">{{ cycle.status }}</span></dd>
                                </div>
                            </dl>
                        </article>

                        <p v-if="!loading && cycles.length === 0" class="rounded-xl border border-gray-200 bg-white px-4 py-6 text-center text-sm font-medium text-gray-500">
                            No cycle records found for your selected filters.
                            <button type="button" class="mt-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50" @click="resetFilters">
                                Clear Filters
                            </button>
                        </p>
                    </div>

                    <div v-if="hasPagination" class="flex items-center justify-between border-t border-gray-200 px-3 py-2">
                        <p class="text-xs font-medium uppercase tracking-[0.12em] text-gray-500">
                            Page {{ meta.current_page }} of {{ meta.last_page }}
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1 || loading" @click="goToPage(meta.current_page - 1)">
                                Previous
                            </button>
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page || loading" @click="goToPage(meta.current_page + 1)">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h3 class="text-base font-bold text-gray-900">Recent Inventory Updates</h3>
                <p class="mt-1 text-xs text-gray-500">Latest status and count changes from cycle operations.</p>

                <div class="mt-4 max-h-120 space-y-3 overflow-y-auto pr-1">
                    <article v-for="(update, idx) in recentUpdates" :key="`update-${idx}`" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-sm font-semibold text-gray-900">{{ update.cycle_code || update.batch_code || 'Cycle' }}</p>
                        <p class="mt-1 text-xs text-gray-600">{{ update.description }}</p>
                        <p class="mt-2 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-500">
                            {{ update.actor || 'System' }}
                            <template v-if="update.created_at">- {{ formatDateTime(update.created_at) }}</template>
                        </p>
                    </article>

                    <p v-if="recentUpdates.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                        No updates recorded yet.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
