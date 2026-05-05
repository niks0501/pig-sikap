<script setup>
import { computed, reactive } from 'vue';
import DateRangePicker from '../common/DateRangePicker.vue';

const props = defineProps({
    type: { type: String, required: true },
    cycles: { type: Array, default: () => [] },
    initialFilters: { type: Object, default: () => ({}) },
    showChartsToggle: { type: Boolean, default: true },
    submitLabel: { type: String, default: 'Generate & Preview' },
    actionUrl: { type: String, required: true },
});

const filters = reactive({
    cycle_id: props.initialFilters.cycle_id || '',
    date_range: props.initialFilters.date_range || 'this_month',
    start_date: props.initialFilters.start_date || '',
    end_date: props.initialFilters.end_date || '',
    month: props.initialFilters.month || '',
    quarter: props.initialFilters.quarter || '',
    year: props.initialFilters.year || new Date().getFullYear(),
    category: props.initialFilters.category || '',
    payment_status: props.initialFilters.payment_status || '',
    include_details: props.initialFilters.include_details ?? true,
    include_charts: props.initialFilters.include_charts ?? false,
});

const submittableParams = computed(() => {
    const params = new URLSearchParams();
    if (filters.cycle_id) params.set('cycle_id', filters.cycle_id);
    if (filters.date_range) params.set('date_range', filters.date_range);
    if (filters.start_date) params.set('start_date', filters.start_date);
    if (filters.end_date) params.set('end_date', filters.end_date);
    if (filters.month) params.set('month', String(filters.month));
    if (filters.quarter) params.set('quarter', String(filters.quarter));
    if (filters.year) params.set('year', String(filters.year));
    if (filters.category) params.set('category', filters.category);
    if (filters.payment_status) params.set('payment_status', filters.payment_status);
    if (filters.include_details) params.set('include_details', '1');
    if (filters.include_charts) params.set('include_charts', '1');
    return params.toString();
});

const dateRangePresets = [
    { label: 'This month', value: 'this_month' },
    { label: 'Last month', value: 'last_month' },
    { label: 'This quarter', value: 'this_quarter' },
    { label: 'This year', value: 'this_year' },
    { label: 'Custom', value: 'custom' },
];

const showMonthQuarter = computed(() => ['monthly', 'quarterly'].includes(props.type));
const showExpenseCategory = computed(() => props.type === 'expense');
const showPaymentStatus = computed(() => props.type === 'sales');

const isCustomRange = computed(() => filters.date_range === 'custom');

const submitDisabled = computed(() => {
    if (showMonthQuarter.value) {
        return !filters.year || (props.type === 'monthly' && !filters.month) || (props.type === 'quarterly' && !filters.quarter);
    }

    return !filters.cycle_id && !filters.date_range;
});

const onDateRangeChange = (value) => {
    filters.date_range = value;
    if (value !== 'custom') {
        filters.start_date = '';
        filters.end_date = '';
    }
};

const onDateRangeSelect = (value) => {
    filters.start_date = value.start;
    filters.end_date = value.end;
};

const handleSubmit = () => {
    if (submitDisabled.value) return;
    const query = submittableParams.value;
    window.location.href = query ? `${props.actionUrl}?${query}` : props.actionUrl;
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <h3 class="mb-4 border-b pb-2 text-sm font-bold uppercase tracking-wider text-gray-600">Report Parameters</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold text-gray-700">
                    Pig Cycle
                    <select v-model="filters.cycle_id" name="cycle_id" class="mt-2 w-full rounded-xl border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                        <option value="">All Active Cycles</option>
                        <option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">
                            {{ cycle.batch_code }} — {{ cycle.stage }} ({{ cycle.status }})
                        </option>
                    </select>
                </label>

                <label class="text-sm font-semibold text-gray-700">
                    Date Range
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="preset in dateRangePresets"
                            :key="preset.value"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                                filters.date_range === preset.value
                                    ? 'border-[#0c6d57]/30 bg-[#0c6d57]/10 text-[#0c6d57]'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-[#0c6d57]/40',
                            ]"
                            @click="onDateRangeChange(preset.value)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                    <input type="hidden" name="date_range" :value="filters.date_range">
                </label>
            </div>

            <div v-if="isCustomRange" class="mt-4">
                <date-range-picker
                    :model-value="{ start: filters.start_date, end: filters.end_date }"
                    label="Custom range"
                    :presets="[]"
                    @update:model-value="onDateRangeSelect"
                />
                <input type="hidden" name="start_date" :value="filters.start_date">
                <input type="hidden" name="end_date" :value="filters.end_date">
            </div>

            <div v-if="showMonthQuarter" class="mt-4 grid gap-4 sm:grid-cols-3">
                <label class="text-sm font-semibold text-gray-700">
                    Year
                    <input v-model="filters.year" type="number" min="2020" max="2099" name="year" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                </label>
                <label v-if="type === 'monthly'" class="text-sm font-semibold text-gray-700">
                    Month
                    <select v-model="filters.month" name="month" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option value="">Select month</option>
                        <option v-for="month in 12" :key="month" :value="month">{{ month }}</option>
                    </select>
                </label>
                <label v-if="type === 'quarterly'" class="text-sm font-semibold text-gray-700">
                    Quarter
                    <select v-model="filters.quarter" name="quarter" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option value="">Select quarter</option>
                        <option value="1">Q1</option>
                        <option value="2">Q2</option>
                        <option value="3">Q3</option>
                        <option value="4">Q4</option>
                    </select>
                </label>
            </div>

            <div v-if="showExpenseCategory || showPaymentStatus" class="mt-4 grid gap-4 sm:grid-cols-2">
                <label v-if="showExpenseCategory" class="text-sm font-semibold text-gray-700">
                    Expense Category
                    <select v-model="filters.category" name="category" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option value="">All categories</option>
                        <option value="acquisition">Acquisition</option>
                        <option value="feed">Feed</option>
                        <option value="medicine">Medicine</option>
                        <option value="transport">Transport</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </label>
                <label v-if="showPaymentStatus" class="text-sm font-semibold text-gray-700">
                    Payment Status
                    <select v-model="filters.payment_status" name="payment_status" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option value="">All statuses</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                        <option value="pending">Pending</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 border-b pb-2 text-sm font-bold uppercase tracking-wider text-gray-600">Display Options</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="filters.include_details" name="include_details" type="checkbox" class="rounded border-gray-300 text-[#0c6d57]">
                    Include detailed line items
                </label>
                <label v-if="showChartsToggle" class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="filters.include_charts" name="include_charts" type="checkbox" class="rounded border-gray-300 text-[#0c6d57]">
                    Include summary charts
                </label>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:justify-end">
            <a :href="actionUrl.replace('/preview', '/generate')" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-600">
                Cancel
            </a>
            <button
                type="button"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-6 text-sm font-semibold text-white transition hover:bg-[#0a5a48]"
                :disabled="submitDisabled"
                @click="handleSubmit"
            >
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
