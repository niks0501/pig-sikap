<script setup>
import { computed, ref } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';

const props = defineProps({
    expenses: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ total_amount: 0, entry_count: 0 }) },
    filters: { type: Object, default: () => ({ search: '', category: '', feed_subcategory: '', fund_source: '', date_from: '', date_to: '' }) },
    categories: { type: Array, default: () => [] },
    feedSubcategories: { type: Array, default: () => [] },
    fundSources: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    resolutions: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 12, total: 0 }) },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const expensesList = ref([...props.expenses]);
const paginationData = ref({ ...props.pagination });
const summaryData = ref({ ...props.summary });
const currentFilters = ref({ ...props.filters });
const isLoading = ref(false);
const localFilters = ref({ ...props.filters });
const toast = ref({ show: false, type: 'success', title: '', message: '' });

const hasExpenses = computed(() => expensesList.value && expensesList.value.length > 0);

const sortedExpenses = computed(() => {
    if (!hasExpenses.value) return [];
    return [...expensesList.value].sort((a, b) => new Date(b.expense_date || 0) - new Date(a.expense_date || 0));
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatAmount = (amount) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(parseFloat(amount || 0));

const formatCategoryLabel = (c) => c?.charAt(0).toUpperCase() + c?.slice(1) || '';
const formatFeedLabel = (c) => c ? c.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('-') : '';
const formatFundLabel = (c) => c ? c.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : '';

const visibleTotal = computed(() => sortedExpenses.value.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0));

const buildQueryParams = (filters, page = 1) => {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.category) params.set('category', filters.category);
    if (filters.feed_subcategory) params.set('feed_subcategory', filters.feed_subcategory);
    if (filters.fund_source) params.set('fund_source', filters.fund_source);
    if (filters.date_from) params.set('date_from', filters.date_from);
    if (filters.date_to) params.set('date_to', filters.date_to);
    params.set('page', String(page));
    return params.toString();
};

const fetchData = async (filters, page = 1) => {
    isLoading.value = true;
    try {
        const queryString = buildQueryParams(filters, page);
        const url = `${props.routes.index}?${queryString}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) { const err = await response.json().catch(() => ({})); toast.value = { show: true, type: 'error', title: 'Failed', message: err.message || 'Try again.' }; return; }
        const data = await response.json();
        expensesList.value = data.expenses;
        paginationData.value = data.pagination;
        summaryData.value = data.summary;
        currentFilters.value = { ...filters, page };
    } catch {
        toast.value = { show: true, type: 'error', title: 'Connection problem', message: 'Try again.' };
    } finally {
        isLoading.value = false;
    }
};

const handleFiltersApply = (filters) => fetchData(filters, 1);
const goToPage = (page) => fetchData(currentFilters.value, page);
const applyLocalFilters = () => fetchData(localFilters.value, 1);
const clearFilters = () => {
    localFilters.value = { search: '', category: '', feed_subcategory: '', fund_source: '', date_from: '', date_to: '' };
    fetchData(localFilters.value, 1);
};

const categoryMeta = (category) => {
    const meta = {
        feed: { classes: 'border-emerald-200 bg-emerald-50 text-[#0c6d57]' },
        medicine: { classes: 'border-violet-200 bg-violet-50 text-violet-700' },
        transport: { classes: 'border-amber-200 bg-amber-50 text-amber-700' },
        emergency: { classes: 'border-rose-200 bg-rose-50 text-rose-700' },
        supplies: { classes: 'border-blue-200 bg-blue-50 text-blue-700' },
        utilities: { classes: 'border-indigo-200 bg-indigo-50 text-indigo-700' },
        labor: { classes: 'border-orange-200 bg-orange-50 text-orange-700' },
        other: { classes: 'border-gray-200 bg-gray-50 text-gray-700' },
    };
    return meta[category] || meta.other;
};
</script>

<template>
    <div class="space-y-4">
        <ToastNotification :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" @close="toast.show = false" />

        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-3">
            <div class="flex flex-wrap gap-3">
                <input v-model="localFilters.search" type="search" placeholder="Search item or notes..." class="flex-1 min-w-[200px] rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                <select v-model="localFilters.category" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                    <option value="">All Categories</option>
                    <option v-for="c in props.categories" :key="c" :value="c">{{ formatCategoryLabel(c) }}</option>
                </select>
                <select v-model="localFilters.fund_source" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                    <option value="">All Fund Sources</option>
                    <option v-for="fs in props.fundSources" :key="fs" :value="fs">{{ formatFundLabel(fs) }}</option>
                </select>
                <input v-model="localFilters.date_from" type="date" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                <input v-model="localFilters.date_to" type="date" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                <button @click="applyLocalFilters" class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0a5a48]">Apply</button>
                <button @click="clearFilters" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Clear</button>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-gray-900">Showing {{ sortedExpenses.length }} expense(s) totaling {{ formatAmount(visibleTotal) }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ paginationData.total }} record(s) match current filters</p>
            </div>
            <a :href="props.routes.create" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0a5a48]">Add Expense</a>
        </div>

        <div v-if="!hasExpenses" class="bg-white rounded-xl border border-gray-100 p-8 text-center">
            <h3 class="text-sm font-semibold text-gray-900">No association expense records found</h3>
            <p class="mt-1 text-sm text-gray-500">Try changing filters or add a new association expense.</p>
            <a :href="props.routes.create" class="mt-4 inline-flex items-center rounded-lg bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a5a48]">Add First Expense</a>
        </div>

        <div v-else class="relative">
            <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/60">
                <svg class="h-8 w-8 animate-spin text-[#0c6d57]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>

            <div class="grid gap-3 sm:hidden">
                <div v-for="expense in sortedExpenses" :key="expense.id" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <span :class="['inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-bold', categoryMeta(expense.category).classes]">{{ formatCategoryLabel(expense.category) }}</span>
                            <p class="mt-1.5 text-sm font-bold text-gray-900 truncate">{{ expense.item_name }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(expense.expense_date) }}</p>
                            <p v-if="expense.fund_source" class="text-xs text-gray-500">{{ formatFundLabel(expense.fund_source) }}</p>
                        </div>
                        <p class="text-lg font-black text-gray-900">{{ formatAmount(expense.amount) }}</p>
                    </div>
                    <a :href="props.routes.show?.replace('_ID_', expense.id)" class="mt-2 inline-flex items-center text-xs font-semibold text-[#0c6d57] hover:text-[#0a5a48]">Details →</a>
                </div>
            </div>

            <div class="hidden sm:block bg-white rounded-xl border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Fund Source</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="expense in sortedExpenses" :key="expense.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(expense.expense_date) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-[200px] truncate">{{ expense.item_name }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold', categoryMeta(expense.category).classes]">{{ formatCategoryLabel(expense.category) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ expense.fund_source ? formatFundLabel(expense.fund_source) : '-' }}</td>
                            <td class="px-6 py-4 text-lg font-black text-gray-900 text-right">{{ formatAmount(expense.amount) }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a :href="props.routes.show?.replace('_ID_', expense.id)" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48]">Details</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="paginationData && paginationData.last_page > 1" class="flex justify-center gap-2">
            <button v-if="paginationData.current_page > 1" @click="goToPage(paginationData.current_page - 1)" :disabled="isLoading" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
            <span class="rounded-lg bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white">{{ paginationData.current_page }} / {{ paginationData.last_page }}</span>
            <button v-if="paginationData.current_page < paginationData.last_page" @click="goToPage(paginationData.current_page + 1)" :disabled="isLoading" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
        </div>
    </div>
</template>
