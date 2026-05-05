<script setup>
import { computed, ref } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';

const props = defineProps({
    expenses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '', category: '', date_from: '', date_to: '' }) },
    categories: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 12, total: 0 }) },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const expensesList = ref([...props.expenses]);
const paginationData = ref({ ...props.pagination });
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

const scopeBadge = (scope) => {
    if (scope === 'cycle') return { label: 'Cycle', classes: 'border-sky-200 bg-sky-50 text-sky-700' };
    return { label: 'Association', classes: 'border-amber-200 bg-amber-50 text-amber-700' };
};

const visibleTotal = computed(() => sortedExpenses.value.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0));

const cycleTotal = computed(() => sortedExpenses.value.filter(e => e.expense_scope === 'cycle').reduce((sum, e) => sum + parseFloat(e.amount || 0), 0));
const associationTotal = computed(() => sortedExpenses.value.filter(e => e.expense_scope === 'association').reduce((sum, e) => sum + parseFloat(e.amount || 0), 0));

const buildQueryParams = (filters, page = 1) => {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.category) params.set('category', filters.category);
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
        currentFilters.value = { ...filters, page };
    } catch {
        toast.value = { show: true, type: 'error', title: 'Connection problem', message: 'Try again.' };
    } finally {
        isLoading.value = false;
    }
};

const applyLocalFilters = () => fetchData(localFilters.value, 1);
const clearFilters = () => {
    localFilters.value = { search: '', category: '', date_from: '', date_to: '' };
    fetchData(localFilters.value, 1);
};
const goToPage = (page) => fetchData(currentFilters.value, page);
</script>

<template>
    <div class="space-y-4">
        <ToastNotification :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" @close="toast.show = false" />

        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-3">
            <div class="flex flex-wrap gap-3">
                <input v-model="localFilters.search" type="search" placeholder="Search..." class="flex-1 min-w-[200px] rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                <select v-model="localFilters.category" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                    <option value="">All Categories</option>
                    <option v-for="c in props.categories" :key="c" :value="c">{{ formatCategoryLabel(c) }}</option>
                </select>
                <input v-model="localFilters.date_from" type="date" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                <input v-model="localFilters.date_to" type="date" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none">
                <button @click="applyLocalFilters" class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0a5a48]">Apply</button>
                <button @click="clearFilters" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Clear</button>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-gray-900">{{ sortedExpenses.length }} expense(s) · Total: {{ formatAmount(visibleTotal) }}</p>
                <p class="mt-0.5 text-xs text-gray-500">Cycle: {{ formatAmount(cycleTotal) }} · Association: {{ formatAmount(associationTotal) }}</p>
            </div>
        </div>

        <div v-if="!hasExpenses" class="bg-white rounded-xl border border-gray-100 p-8 text-center">
            <h3 class="text-sm font-semibold text-gray-900">No expenses found</h3>
            <p class="mt-1 text-sm text-gray-500">Try changing filters.</p>
        </div>

        <div v-else class="relative">
            <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/60">
                <svg class="h-8 w-8 animate-spin text-[#0c6d57]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>

            <div class="grid gap-3 sm:hidden">
                <div v-for="expense in sortedExpenses" :key="'all-' + expense.expense_scope + '-' + expense.id" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="rounded-full border px-2 py-0.5 text-xs font-bold" :class="scopeBadge(expense.expense_scope).classes">{{ scopeBadge(expense.expense_scope).label }}</span>
                                <span class="text-xs text-gray-500">{{ formatCategoryLabel(expense.category) }}</span>
                            </div>
                            <p class="text-sm font-bold text-gray-900 truncate">{{ expense.item_name || expense.notes }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(expense.expense_date) }}</p>
                        </div>
                        <p class="text-lg font-black text-gray-900">{{ formatAmount(expense.amount) }}</p>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-white rounded-xl border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scope</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="expense in sortedExpenses" :key="'all-' + expense.expense_scope + '-' + expense.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span :class="['rounded-full border px-2.5 py-1 text-xs font-bold', scopeBadge(expense.expense_scope).classes]">{{ scopeBadge(expense.expense_scope).label }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(expense.expense_date) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-[250px] truncate">{{ expense.item_name || expense.notes }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ formatCategoryLabel(expense.category) }}</td>
                            <td class="px-6 py-4 text-lg font-black text-gray-900 text-right">{{ formatAmount(expense.amount) }}</td>
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
