<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    type: { type: String, required: true },
    rows: { type: Array, default: () => [] },
});

const viewMode = ref('table');

const columns = computed(() => {
    switch (props.type) {
        case 'inventory':
            return ['Cycle', 'Stage', 'Status', 'Caretaker', 'Initial', 'Current', 'Active', 'Sold', 'Deceased'];
        case 'health':
            return ['Cycle', 'Due Today', 'Overdue', 'Completed', 'Affected', 'Incidents', 'Mortality'];
        case 'mortality':
            return ['Date', 'Cycle', 'Affected', 'Cause', 'Reported By'];
        case 'expense':
            return ['Date', 'Cycle', 'Category', 'Amount', 'Notes'];
        case 'sales':
            return ['Date', 'Cycle', 'Buyer', 'Pigs Sold', 'Amount', 'Paid', 'Status'];
        case 'profitability':
            return ['Cycle', 'Status', 'Caretaker', 'Gross Income', 'Expenses', 'Net', 'Finalized'];
        default:
            return ['Metric', 'Value'];
    }
});

const rowsForTable = computed(() => props.rows || []);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Detailed Breakdown</h3>
            <div class="inline-flex rounded-full border border-gray-200 bg-white p-1">
                <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="viewMode === 'table' ? 'bg-[#0c6d57]/10 text-[#0c6d57]' : 'text-gray-500'"
                    @click="viewMode = 'table'"
                >
                    Table
                </button>
                <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="viewMode === 'card' ? 'bg-[#0c6d57]/10 text-[#0c6d57]' : 'text-gray-500'"
                    @click="viewMode = 'card'"
                >
                    Card
                </button>
            </div>
        </div>

        <div v-if="viewMode === 'table'" class="overflow-x-auto rounded-2xl border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th v-for="col in columns" :key="col" class="px-4 py-3 text-left">{{ col }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-for="(row, index) in rowsForTable" :key="index">
                        <td v-for="(value, key, colIndex) in row" :key="colIndex" class="px-4 py-3 text-gray-700">
                            {{ value }}
                        </td>
                    </tr>
                    <tr v-if="rowsForTable.length === 0">
                        <td :colspan="columns.length" class="px-4 py-6 text-center text-sm text-gray-500">
                            No data available for the selected filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <div v-for="(row, index) in rowsForTable" :key="index" class="rounded-2xl border border-gray-200 bg-white p-4">
                <div v-for="(value, key) in row" :key="key" class="flex items-center justify-between text-sm text-gray-600">
                    <span class="font-semibold text-gray-500">{{ key }}</span>
                    <span class="text-gray-900">{{ value }}</span>
                </div>
            </div>
            <div v-if="rowsForTable.length === 0" class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                No data available for the selected filters.
            </div>
        </div>
    </div>
</template>
