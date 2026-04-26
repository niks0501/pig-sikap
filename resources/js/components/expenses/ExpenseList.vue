<script setup>
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import ExpenseFilters from './ExpenseFilters.vue';
import ExpenseTableRow from './ExpenseTableRow.vue';

const props = defineProps({
    expenses: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_amount: 0,
            entry_count: 0,
            feed_share_percent: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: '',
            cycle_id: '',
            month: '',
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
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 0,
        }),
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    canBulkDelete: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['bulk-action']);

const isLoading = ref(false);
const selectedIds = ref(new Set());
const sortColumn = ref('expense_date');
const sortDirection = ref('desc');
const bulkActionLoading = ref(false);
const showBulkConfirm = ref(false);
const bulkActionType = ref('');

const hasExpenses = computed(() => props.expenses && props.expenses.length > 0);

const isAllSelected = computed(() => {
    if (!hasExpenses.value || props.expenses.length === 0) return false;
    return props.expenses.every((expense) => selectedIds.value.has(expense.id));
});

const selectedCount = computed(() => selectedIds.value.size);

const canBulkDeleteSelected = computed(() => props.canBulkDelete && selectedCount.value > 0);

const sortedExpenses = computed(() => {
    if (!hasExpenses.value) return [];

    const sorted = [...props.expenses];

    sorted.sort((a, b) => {
        let aVal, bVal;

        switch (sortColumn.value) {
            case 'expense_date':
                aVal = new Date(a.expense_date || 0);
                bVal = new Date(b.expense_date || 0);
                break;
            case 'category':
                aVal = (a.category || '').toLowerCase();
                bVal = (b.category || '').toLowerCase();
                break;
            case 'amount':
                aVal = parseFloat(a.amount || 0);
                bVal = parseFloat(b.amount || 0);
                break;
            case 'cycle':
                aVal = (a.cycle?.batch_code || '').toLowerCase();
                bVal = (b.cycle?.batch_code || '').toLowerCase();
                break;
            default:
                return 0;
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return sorted;
});

const toggleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'desc';
    }
};

const getSortIcon = (column) => {
    if (sortColumn.value !== column) {
        return 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4';
    }

    return sortDirection.value === 'asc'
        ? 'M5 15l7-7 7 7'
        : 'M19 9l-7 7-7-7';
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value.clear();
    } else {
        props.expenses.forEach((expense) => {
            selectedIds.value.add(expense.id);
        });
    }
};

const toggleSelectOne = (expenseId) => {
    if (selectedIds.value.has(expenseId)) {
        selectedIds.value.delete(expenseId);
    } else {
        selectedIds.value.add(expenseId);
    }
};

const isSelected = (expenseId) => selectedIds.value.has(expenseId);

const formatCategoryLabel = (category) => {
    return category?.charAt(0).toUpperCase() + category?.slice(1) || '';
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(parseFloat(amount || 0));
};

const clearSelection = () => {
    selectedIds.value.clear();
};

const performBulkDelete = () => {
    bulkActionType.value = 'delete';
    showBulkConfirm.value = true;
};

const confirmBulkAction = async () => {
    if (bulkActionType.value !== 'delete') return;

    bulkActionLoading.value = true;
    showBulkConfirm.value = false;

    try {
        const formData = new FormData();
        formData.append('_token', props.csrfToken);
        formData.append('_method', 'POST');

        selectedIds.value.forEach((id) => {
            formData.append('ids[]', id);
        });

        const response = await fetch(props.routes.bulkDelete, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
            },
            body: formData,
        });

        if (response.ok) {
            const data = await response.json();
            window.location.href = data.redirect_url || props.routes.index;
        } else {
            const data = await response.json().catch(() => ({}));
            alert(data.message || 'Bulk delete failed. Please try again.');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    } finally {
        bulkActionLoading.value = false;
        clearSelection();
    }
};

const cancelBulkAction = () => {
    showBulkConfirm.value = false;
    bulkActionType.value = '';
};
</script>

<template>
    <div class="space-y-4">
        <ExpenseFilters
            :initial-filters="props.filters"
            :categories="props.categories"
            :cycles="props.cycles"
            :base-url="props.routes.index"
        />

        <div v-if="canBulkDeleteSelected" class="flex items-center justify-between rounded-xl border border-[#0c6d57] bg-[#0c6d57]/5 px-4 py-3">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-semibold text-[#0c6d57]">{{ selectedCount }} expense(s) selected</span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="bulkActionLoading"
                    class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-50"
                    @click="performBulkDelete"
                >
                    {{ bulkActionLoading ? 'Deleting...' : 'Delete Selected' }}
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                    @click="clearSelection"
                >
                    Clear Selection
                </button>
            </div>
        </div>

        <div v-if="!hasExpenses" class="bg-white rounded-xl border border-gray-100 p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No expense records found</h3>
            <p class="mt-1 text-sm text-gray-500">Try changing filters or add a new expense entry.</p>
            <a
                :href="props.routes.create"
                class="mt-4 inline-flex items-center justify-center rounded-lg bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#0a5a48]"
            >
                Add First Expense
            </a>
        </div>

        <div v-else>
            <div class="grid gap-3 sm:hidden">
                <ExpenseTableRow
                    v-for="expense in sortedExpenses"
                    :key="expense.id"
                    :expense="expense"
                    :is-selected="isSelected(expense.id)"
                    :show-checkbox="props.canBulkDelete"
                    :routes="props.routes"
                    @toggle-select="toggleSelectOne(expense.id)"
                />
            </div>

            <div class="hidden sm:block bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th v-if="props.canBulkDelete" class="px-6 py-4 text-left">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                class="rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]"
                                @change="toggleSelectAll"
                            >
                        </th>
<th
class="px-6 py-4 text-left cursor-pointer select-none"
                            @click="toggleSort('expense_date')"
                        >
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('expense_date')" />
                                </svg>
                            </div>
                        </th>
<th
class="px-6 py-4 text-left cursor-pointer select-none"
                            @click="toggleSort('category')"
                        >
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('category')" />
                                </svg>
                            </div>
                        </th>
<th class="px-6 py-4 text-left">
<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</span>
</th>
<th
class="px-6 py-4 text-left cursor-pointer select-none"
                            @click="toggleSort('cycle')"
                        >
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cycle</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('cycle')" />
                                </svg>
                            </div>
                        </th>
<th class="px-6 py-4 text-left">
<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recorded By</span>
</th>
<th
class="px-6 py-4 text-right cursor-pointer select-none"
                            @click="toggleSort('amount')"
                        >
                            <div class="flex items-center justify-end gap-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('amount')" />
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="expense in sortedExpenses"
                        :key="expense.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            isSelected(expense.id) ? 'bg-[#0c6d57]/5' : '',
                        ]"
                    >
                        <td v-if="props.canBulkDelete" class="px-6 py-4">
                            <input
                                type="checkbox"
                                :checked="isSelected(expense.id)"
                                class="rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]"
                                @change="toggleSelectOne(expense.id)"
                            >
                        </td>
<td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(expense.expense_date) }}</td>
<td class="px-6 py-4 text-sm text-gray-700">{{ formatCategoryLabel(expense.category) }}</td>
<td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ expense.notes }}</td>
<td class="px-6 py-4 text-sm text-gray-700">{{ expense.cycle?.batch_code || 'Unknown' }}</td>
<td class="px-6 py-4 text-sm text-gray-700">{{ expense.created_by_name || 'System' }}</td>
<td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ formatAmount(expense.amount) }}</td>
<td class="px-6 py-4 text-right text-sm">
                            <a :href="props.routes.show?.replace('_ID_', expense.id)" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48]">
                                Details
                            </a>
                        </td>
                    </tr>
</tbody>
        </table>
        </div>
        </div>

        <div v-if="props.pagination && props.pagination.last_page > 1" class="flex justify-center gap-2">
            <a
                v-if="props.pagination.current_page > 1"
                :href="`${props.routes.index}?page=${props.pagination.current_page - 1}`"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Previous
            </a>
            <span class="rounded-lg bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white">
                {{ props.pagination.current_page }} / {{ props.pagination.last_page }}
            </span>
            <a
                v-if="props.pagination.current_page < props.pagination.last_page"
                :href="`${props.routes.index}?page=${props.pagination.current_page + 1}`"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Next
            </a>
        </div>

        <TransitionRoot v-if="showBulkConfirm" appear show as="template">
            <Dialog as="div" class="relative z-50" @close="cancelBulkAction">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">Confirm Bulk Delete</DialogTitle>
                                <p class="mt-2 text-sm text-gray-500">
                                    Are you sure you want to delete {{ selectedCount }} expense record(s)? This action cannot be undone.
                                </p>

                                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-5 w-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <p class="text-sm text-rose-800">This will permanently delete the selected expenses and their associated receipt files.</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="cancelBulkAction"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                                        @click="confirmBulkAction"
                                    >
                                        Delete {{ selectedCount }} Expense(s)
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
