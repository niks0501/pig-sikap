<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    reports: { type: Array, default: () => [] },
    hasPages: { type: Boolean, default: false },
    currentPage: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    filters: { type: Object, default: () => ({}) },
});

const activeFilters = ref({
    type: props.filters.type || '',
    format: props.filters.format || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
});

const reportTypes = [
    { value: '', label: 'All Types' },
    { value: 'inventory', label: 'Inventory' },
    { value: 'health', label: 'Health' },
    { value: 'mortality', label: 'Mortality' },
    { value: 'expense', label: 'Expense' },
    { value: 'sales', label: 'Sales' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'profitability', label: 'Profitability' },
];

const formatOptions = [
    { value: '', label: 'All Formats' },
    { value: 'pdf', label: 'PDF' },
    { value: 'csv', label: 'CSV' },
];

const formatBadge = (format) => {
    return format === 'pdf'
        ? 'bg-red-50 text-red-700 border-red-200'
        : 'bg-blue-50 text-blue-700 border-blue-200';
};

const statusBadge = (status) => {
    return status === 'generated'
        ? 'bg-green-50 text-green-700 border-green-200'
        : 'bg-gray-50 text-gray-600 border-gray-200';
};

const typeLabel = (type) => {
    return reportTypes.find((t) => t.value === type)?.label ?? type;
};

const applyFilters = () => {
    const params = new URLSearchParams();
    if (activeFilters.value.type) params.set('type', activeFilters.value.type);
    if (activeFilters.value.format) params.set('format', activeFilters.value.format);
    if (activeFilters.value.from) params.set('from', activeFilters.value.from);
    if (activeFilters.value.to) params.set('to', activeFilters.value.to);

    const query = params.toString();
    window.location.href = query ? `${window.location.pathname}?${query}` : window.location.pathname;
};

const clearFilters = () => {
    window.location.href = window.location.pathname;
};

const pageUrl = (page) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', String(page));
    return `${window.location.pathname}?${params.toString()}`;
};
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-5">
                <label class="text-sm font-semibold text-gray-700">
                    Report Type
                    <select v-model="activeFilters.type" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option v-for="opt in reportTypes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Format
                    <select v-model="activeFilters.format" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                        <option v-for="opt in formatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    From Date
                    <input v-model="activeFilters.from" type="date" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    To Date
                    <input v-model="activeFilters.to" type="date" class="mt-2 w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm">
                </label>
                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        class="min-h-[44px] flex-1 rounded-xl bg-[#0c6d57] px-4 text-sm font-semibold text-white transition hover:bg-[#0a5a48]"
                        @click="applyFilters"
                    >
                        Filter
                    </button>
                    <button
                        type="button"
                        class="min-h-[44px] rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-600 transition hover:border-[#0c6d57]/40"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <div v-if="reports.length === 0" class="rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-sm">
            <p class="text-sm text-gray-500">No generated reports found. Generate your first report to see it here.</p>
            <a href="/reports" class="mt-4 inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-6 text-sm font-semibold text-white">
                Generate a Report
            </a>
        </div>

        <div v-else class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Report Type</th>
                            <th class="px-4 py-3 text-left">Format</th>
                            <th class="px-4 py-3 text-left">Cycle</th>
                            <th class="px-4 py-3 text-left">Generated By</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Size</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="report in reports" :key="report.id">
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ typeLabel(report.report_type) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold', formatBadge(report.format)]">
                                    {{ report.format.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ report.cycle_code }}</td>
                            <td class="px-4 py-3">{{ report.generated_by }}</td>
                            <td class="px-4 py-3">{{ report.generated_at }}</td>
                            <td class="px-4 py-3">{{ report.file_size }}</td>
                            <td class="px-4 py-3">
                                <span :class="['inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold', statusBadge(report.status)]">
                                    {{ report.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    :href="report.download_url"
                                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg bg-[#0c6d57]/10 px-3 text-xs font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/20"
                                >
                                    Download
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="hasPages" class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-500">Page {{ currentPage }} of {{ lastPage }}</p>
                <div class="flex gap-2">
                    <a
                        v-if="currentPage > 1"
                        :href="pageUrl(currentPage - 1)"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-[#0c6d57]/40"
                    >
                        Previous
                    </a>
                    <a
                        v-if="currentPage < lastPage"
                        :href="pageUrl(currentPage + 1)"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-[#0c6d57]/40"
                    >
                        Next
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
