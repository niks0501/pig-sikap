<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    initialFilters: {
        type: Object,
        default: () => ({
            search: '',
            category: '',
            cycle_id: '',
            month: '',
            date_from: '',
            date_to: '',
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    baseUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['filters-changed']);

const filters = reactive({
    search: props.initialFilters.search || '',
    category: props.initialFilters.category || '',
    cycle_id: props.initialFilters.cycle_id || '',
    month: props.initialFilters.month || '',
    date_from: props.initialFilters.date_from || '',
    date_to: props.initialFilters.date_to || '',
});

const hasActiveFilters = computed(() => {
    return filters.search !== '' ||
        filters.category !== '' ||
        filters.cycle_id !== '' ||
        filters.month !== '' ||
        filters.date_from !== '' ||
        filters.date_to !== '';
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (filters.search !== '') count++;
    if (filters.category !== '') count++;
    if (filters.cycle_id !== '') count++;
    if (filters.month !== '') count++;
    if (filters.date_from !== '' || filters.date_to !== '') count++;
    return count;
});

const sortBy = reactive({
    column: 'expense_date',
    direction: 'desc',
});

const datePresets = [
    { label: 'Today', value: 'today' },
    { label: 'This Week', value: 'this_week' },
    { label: 'This Month', value: 'this_month' },
];

const selectedDatePreset = computed(() => {
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    if (filters.date_from === todayStr && filters.date_to === todayStr) {
        return 'today';
    }

    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay());
    const startOfWeekStr = startOfWeek.toISOString().split('T')[0];

    if (filters.date_from === startOfWeekStr && filters.date_to === todayStr) {
        return 'this_week';
    }

    const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const firstOfMonthStr = firstOfMonth.toISOString().split('T')[0];

    if (filters.date_from === firstOfMonthStr && filters.date_to === todayStr) {
        return 'this_month';
    }

    return '';
});

const applyDatePreset = (preset) => {
    const today = new Date();

    switch (preset) {
        case 'today':
            filters.month = '';
            filters.date_from = today.toISOString().split('T')[0];
            filters.date_to = filters.date_from;
            break;
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            filters.month = '';
            filters.date_from = startOfWeek.toISOString().split('T')[0];
            filters.date_to = today.toISOString().split('T')[0];
            break;
        case 'this_month':
            const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            filters.month = '';
            filters.date_from = firstOfMonth.toISOString().split('T')[0];
            filters.date_to = today.toISOString().split('T')[0];
            break;
    }
};

const clearFilters = () => {
    filters.search = '';
    filters.category = '';
    filters.cycle_id = '';
    filters.month = '';
    filters.date_from = '';
    filters.date_to = '';
};

const formatCategoryLabel = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1);
};

const applyFilters = () => {
    emit('apply-filters', { ...filters });
};

const clearAndApply = () => {
    clearFilters();
    emit('apply-filters', { ...filters });
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span class="text-sm font-semibold text-gray-700">Filters</span>
                <span v-if="activeFilterCount > 0" class="inline-flex items-center justify-center rounded-full bg-[#0c6d57] px-2 py-0.5 text-xs font-bold text-white">
                    {{ activeFilterCount }}
                </span>
            </div>

            <button
                v-if="hasActiveFilters"
                type="button"
                class="text-xs font-semibold text-[#0c6d57] hover:text-[#0a5a48]"
                @click="clearAndApply"
            >
                Clear all
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
            <div>
                <label for="filter-search" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    Search
                </label>
<input
id="filter-search"
v-model="filters.search"
type="text"
placeholder="Description or cycle"
class="w-full rounded-lg border-gray-200 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
>
            </div>

            <div>
                <label for="filter-category" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    Category
                </label>
<select
id="filter-category"
v-model="filters.category"
class="w-full rounded-lg border-gray-200 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
>
                    <option value="">All categories</option>
                    <option
                        v-for="category in props.categories"
                        :key="category"
                        :value="category"
                    >
                        {{ formatCategoryLabel(category) }}
                    </option>
                </select>
            </div>

            <div>
                <label for="filter-cycle" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    Cycle
                </label>
<select
id="filter-cycle"
v-model="filters.cycle_id"
class="w-full rounded-lg border-gray-200 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
>
                    <option value="">All cycles</option>
                    <option
                        v-for="cycle in props.cycles"
                        :key="cycle.id"
                        :value="String(cycle.id)"
                    >
                        {{ cycle.batch_code }}{{ cycle.isArchived ? ' (Archived)' : '' }}
                    </option>
                </select>
            </div>

            <div>
                <label for="filter-date-from" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    From
                </label>
<input
id="filter-date-from"
v-model="filters.date_from"
type="date"
class="w-full rounded-lg border-gray-200 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
>
            </div>

            <div>
                <label for="filter-date-to" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    To
                </label>
<input
id="filter-date-to"
v-model="filters.date_to"
type="date"
class="w-full rounded-lg border-gray-200 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <button
                type="button"
                class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 rounded-lg bg-[#0c6d57] text-white text-sm font-semibold hover:bg-[#0a5a48] transition-colors"
                @click="applyFilters"
            >
                Apply Filters
            </button>

            <div class="flex flex-wrap gap-1">
                <button
                    v-for="preset in datePresets"
                    :key="preset.value"
                    type="button"
                    :class="[
                        'px-3 py-2 rounded-lg text-xs font-semibold transition-colors',
                        selectedDatePreset === preset.value
                            ? 'bg-[#0c6d57] text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                    ]"
                    @click="applyDatePreset(preset.value)"
                >
                    {{ preset.label }}
                </button>
            </div>
        </div>
    </div>
</template>
