<script setup>
import { computed, reactive, ref } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import ToastNotification from '../common/ToastNotification.vue';
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
const toast = reactive({
    show: false,
    type: 'success',
    title: '',
    message: '',
});

const hasExpenses = computed(() => props.expenses && props.expenses.length > 0);

const isAllSelected = computed(() => {
    if (!hasExpenses.value || props.expenses.length === 0) return false;
    return props.expenses.every((expense) => selectedIds.value.has(expense.id));
});

const selectedCount = computed(() => selectedIds.value.size);

const canBulkDeleteSelected = computed(() => props.canBulkDelete && selectedCount.value > 0);

const visibleTotal = computed(() => {
    return sortedExpenses.value.reduce((sum, expense) => sum + parseFloat(expense.amount || 0), 0);
});

const selectedTotal = computed(() => {
    return props.expenses
        .filter((expense) => selectedIds.value.has(expense.id))
        .reduce((sum, expense) => sum + parseFloat(expense.amount || 0), 0);
});

const createRoute = computed(() => {
    if (props.filters?.cycle_id) {
        const url = new URL(props.routes.create, window.location.origin);
        url.searchParams.set('cycle_id', props.filters.cycle_id);

        return url.pathname + url.search;
    }

    return props.routes.create;
});

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

const categoryMeta = (category) => {
    const meta = {
        acquisition: {
            classes: 'border-sky-200 bg-sky-50 text-sky-700',
            icon: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        },
        feed: {
            classes: 'border-emerald-200 bg-emerald-50 text-[#0c6d57]',
            icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        },
        medicine: {
            classes: 'border-violet-200 bg-violet-50 text-violet-700',
            icon: 'M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5C21.846 17.846 20.954 20 19.172 20H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        },
        transport: {
            classes: 'border-amber-200 bg-amber-50 text-amber-700',
            icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        },
        emergency: {
            classes: 'border-rose-200 bg-rose-50 text-rose-700',
            icon: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
        },
    };

    return meta[category] || meta.feed;
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

const formatQuantity = (expense) => {
    if (expense.quantity === null && !expense.unit) {
        return 'Lump-sum';
    }

    const quantity = Number(expense.quantity || 0);
    const quantityText = quantity % 1 === 0 ? String(quantity) : quantity.toFixed(2);

    return `${quantityText} ${expense.unit || ''}`.trim();
};

const showToast = (type, title, message) => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.show = true;
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
            showToast('error', 'Bulk delete failed', data.message || 'Please try again.');
        }
    } catch (error) {
        showToast('error', 'Connection problem', 'Please try again.');
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
        <ToastNotification
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            @close="toast.show = false"
        />

        <ExpenseFilters
            :initial-filters="props.filters"
            :categories="props.categories"
            :cycles="props.cycles"
            :base-url="props.routes.index"
        />

        <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-gray-900">
                    Showing {{ sortedExpenses.length }} expense(s) totaling {{ formatAmount(visibleTotal) }}
                </p>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ props.pagination.total }} record(s) match the current filters
                </p>
            </div>
            <a
                :href="createRoute"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0a5a48]"
            >
                Add Expense
            </a>
        </div>

        <div v-if="canBulkDeleteSelected" class="flex items-center justify-between rounded-xl border border-[#0c6d57] bg-[#0c6d57]/5 px-4 py-3">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
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
                :href="createRoute"
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
<th class="px-6 py-4 text-left">
<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Qty / Unit</span>
</th>
<th class="px-6 py-4 text-right">
<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Unit Cost</span>
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
<td class="px-6 py-4 text-sm text-gray-700">
    <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold', categoryMeta(expense.category).classes]">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryMeta(expense.category).icon" />
        </svg>
        {{ formatCategoryLabel(expense.category) }}
    </span>
</td>
<td class="px-6 py-4 text-sm text-gray-700 max-w-xs">
    <p class="truncate">{{ expense.notes }}</p>
    <span v-if="expense.receipt_url" class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-[#0c6d57]">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        Receipt attached
    </span>
</td>
<td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ formatQuantity(expense) }}</td>
<td class="px-6 py-4 text-sm font-semibold text-gray-700 text-right">{{ expense.unit_cost ? formatAmount(expense.unit_cost) : '-' }}</td>
<td class="px-6 py-4 text-sm text-gray-700">{{ expense.cycle?.batch_code || 'Unknown' }}</td>
<td class="px-6 py-4 text-sm text-gray-700">{{ expense.created_by_name || 'System' }}</td>
<td class="px-6 py-4 text-lg font-black text-gray-900 text-right">{{ formatAmount(expense.amount) }}</td>
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
                                    You selected {{ selectedCount }} expense record(s) totaling {{ formatAmount(selectedTotal) }}.
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

        <a
            :href="createRoute"
            class="fixed bottom-5 right-5 z-40 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#0c6d57] text-white shadow-lg transition hover:bg-[#0a5a48] sm:hidden"
            aria-label="Add expense"
        >
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </a>
    </div>
</template>
