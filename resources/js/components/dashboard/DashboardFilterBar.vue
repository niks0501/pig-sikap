<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, default: () => ({}) },
    activeCount: { type: Number, default: 0 },
});

const emit = defineEmits(['clear', 'filter-change']);

const dateRange = reactive({
    start: props.filters.date_from || '',
    end: props.filters.date_to || '',
});

watch(() => [props.filters.date_from, props.filters.date_to], ([from, to]) => {
    dateRange.start = from || '';
    dateRange.end = to || '';
});

watch(dateRange, ({ start, end }) => {
    props.filters.date_from = start;
    props.filters.date_to = end;
    emit('filter-change');
});

function onFilterChange() {
    emit('filter-change');
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
                <span v-if="activeCount > 0" class="bg-[#0c6d57] text-white text-xs px-2 py-0.5 rounded-full">{{ activeCount }} active</span>
            </h3>
            <button
                v-if="activeCount > 0"
                @click="emit('clear')"
                class="text-xs font-medium text-[#0c6d57] hover:text-emerald-700 underline"
            >
                Clear All Filters
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Cycle Filter -->
            <label class="block">
                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Cycle / Batch</span>
                <select
                    v-model="filters.cycle_id"
                    @change="onFilterChange"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option :value="null">All Cycles</option>
                    <option
                        v-for="cycle in filterOptions?.cycles || []"
                        :key="cycle.id"
                        :value="cycle.id"
                    >{{ cycle.label }}</option>
                </select>
            </label>

            <!-- Pig Status Filter -->
            <label class="block">
                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Pig Status</span>
                <select
                    v-model="filters.pig_status"
                    @change="onFilterChange"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">All Statuses</option>
                    <option v-for="s in filterOptions?.pig_statuses || []" :key="s" :value="s">{{ s }}</option>
                </select>
            </label>

            <!-- Pig Sex Filter -->
            <label class="block">
                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Pig Sex</span>
                <select
                    v-model="filters.pig_sex"
                    @change="onFilterChange"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">All</option>
                    <option v-for="s in filterOptions?.pig_sexes || []" :key="s" :value="s">{{ s }}</option>
                </select>
            </label>

            <!-- Date Range -->
            <fieldset class="rounded-xl border border-gray-200 bg-white p-3">
                <legend class="px-1 text-sm font-semibold text-gray-700">Date Range</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Start</span>
                        <input
                            v-model="dateRange.start"
                            type="date"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">End</span>
                        <input
                            v-model="dateRange.end"
                            type="date"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                    </label>
                </div>
            </fieldset>
        </div>

        <!-- Active Filter Chips -->
        <div v-if="activeCount > 0" class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
            <span
                v-if="filters.cycle_id"
                class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs font-medium px-3 py-1 rounded-full border border-emerald-200"
            >
                Cycle: {{ (filterOptions?.cycles || []).find(c => c.id === filters.cycle_id)?.label || filters.cycle_id }}
                <button @click="filters.cycle_id = null" class="hover:text-emerald-900">&times;</button>
            </span>
            <span
                v-if="filters.pig_status"
                class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-xs font-medium px-3 py-1 rounded-full border border-amber-200"
            >
                Status: {{ filters.pig_status }}
                <button @click="filters.pig_status = ''" class="hover:text-amber-900">&times;</button>
            </span>
            <span
                v-if="filters.pig_sex"
                class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs font-medium px-3 py-1 rounded-full border border-blue-200"
            >
                Sex: {{ filters.pig_sex }}
                <button @click="filters.pig_sex = ''" class="hover:text-blue-900">&times;</button>
            </span>
            <span
                v-if="dateRange.start || dateRange.end"
                class="inline-flex items-center gap-1 bg-violet-50 text-violet-700 text-xs font-medium px-3 py-1 rounded-full border border-violet-200"
            >
                Date: {{ dateRange.start || '...' }} - {{ dateRange.end || '...' }}
                <button @click="dateRange.start = ''; dateRange.end = ''" class="hover:text-violet-900">&times;</button>
            </span>
        </div>
    </div>
</template>
