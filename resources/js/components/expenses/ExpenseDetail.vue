<script setup>
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';

const props = defineProps({
    expense: {
        type: Object,
        required: true,
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
});

const isLoading = ref(false);
const showDuplicateModal = ref(false);
const duplicateDate = ref('');

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleString(undefined, { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(parseFloat(amount || 0));
};

const formatCategoryLabel = (category) => {
    return category?.charAt(0).toUpperCase() + category?.slice(1) || '';
};

const hasReceipt = computed(() => {
    return props.expense?.receipt_url && props.expense.receipt_url.trim() !== '';
});

const hasStructuredAmount = computed(() => {
    return props.expense.quantity !== null || props.expense.unit || props.expense.unit_cost !== null;
});

const isCycleArchived = computed(() => {
    return props.expense.cycle?.isArchived ?? props.expense.cycle?.status === 'archived';
});

const canEdit = computed(() => {
    return !isCycleArchived.value;
});

const duplicateExpense = async () => {
    if (!duplicateDate.value) return;

    isLoading.value = true;

    try {
        const formData = new FormData();
        formData.append('_token', props.csrfToken);
        formData.append('expense_date', duplicateDate.value);

        const response = await fetch(props.routes.duplicate?.replace('_ID_', props.expense.id), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
            },
            body: formData,
        });

        if (response.ok) {
            const data = await response.json();
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                window.location.reload();
            }
        } else {
            const data = await response.json().catch(() => ({}));
            alert(data.message || 'Failed to duplicate expense. Please try again.');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    } finally {
        isLoading.value = false;
        showDuplicateModal.value = false;
    }
};

const openDuplicateModal = () => {
    const today = new Date().toISOString().split('T')[0];
    duplicateDate.value = today;
    showDuplicateModal.value = true;
};

const closeDuplicateModal = () => {
    showDuplicateModal.value = false;
    duplicateDate.value = '';
};
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-[#0c6d57]/10 px-3 py-1 text-xs font-semibold text-[#0c6d57]">
                        {{ formatCategoryLabel(expense.category) }}
                    </span>
                    <span v-if="hasReceipt" class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Receipt
                    </span>
                </div>
                <h2 class="mt-2 text-xl font-bold text-gray-900">{{ expense.notes }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ expense.cycle?.batch_code || 'Unknown cycle' }}
                </p>
            </div>

            <div class="text-left sm:text-right">
                <p class="text-2xl font-bold text-gray-900">{{ formatAmount(expense.amount) }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ formatDate(expense.expense_date) }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 mb-6">
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Recorded By</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ expense.created_by_name || 'System' }}</p>
                <p class="mt-0.5 text-xs text-gray-500">on {{ formatDateTime(expense.created_at) }}</p>
            </div>

            <div v-if="expense.updated_by_name" class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Last Updated By</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ expense.updated_by_name }}</p>
                <p class="mt-0.5 text-xs text-gray-500">on {{ formatDateTime(expense.updated_at) }}</p>
            </div>

            <div class="rounded-xl bg-[#0c6d57]/5 p-4">
                <p class="text-xs font-semibold tracking-wide text-[#0c6d57] uppercase">Quantity / Unit</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">
                    <template v-if="hasStructuredAmount">
                        {{ expense.quantity ?? '-' }} {{ expense.unit || '' }}
                    </template>
                    <template v-else>Lump-sum expense</template>
                </p>
            </div>

            <div class="rounded-xl bg-[#0c6d57]/5 p-4">
                <p class="text-xs font-semibold tracking-wide text-[#0c6d57] uppercase">Unit Cost / Halaga kada Yunit</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">
                    {{ expense.unit_cost !== null ? formatAmount(expense.unit_cost) : '-' }}
                </p>
            </div>

            <div v-if="isCycleArchived" class="sm:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Archived Cycle</p>
                        <p class="mt-0.5 text-xs text-amber-700">This expense is linked to an archived cycle and cannot be edited or deleted.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="hasReceipt" class="mb-6">
            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-2">Receipt</p>
            <a
                :href="expense.receipt_url"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
            >
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View Receipt
            </a>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row">
            <a
                v-if="canEdit"
                :href="props.routes.edit?.replace('_ID_', expense.id)"
                class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] sm:w-auto"
            >
                Edit Expense
            </a>

            <button
                v-if="canEdit"
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                @click="openDuplicateModal"
            >
                Duplicate
            </button>

            <a
                :href="props.routes.index"
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
            >
                Back to List
            </a>
        </div>

        <TransitionRoot v-if="showDuplicateModal" appear show as="template">
            <Dialog as="div" class="relative z-50" @close="closeDuplicateModal">
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
                                <DialogTitle class="text-lg font-bold text-gray-900">Duplicate Expense</DialogTitle>
                                <p class="mt-2 text-sm text-gray-500">
                                    Create a copy of this expense with a new date. The receipt will not be copied.
                                </p>

                                <div class="mt-4">
                                    <label for="duplicate-date" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                        New Expense Date *
                                    </label>
                                    <input
                                        id="duplicate-date"
                                        v-model="duplicateDate"
                                        type="date"
                                        :max="new Date().toISOString().split('T')[0]"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                    >
                                </div>

                                <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Original Expense</p>
                                    <p class="text-sm text-gray-900">{{ expense.notes }}</p>
                                    <p class="mt-1 text-sm text-gray-700">{{ formatAmount(expense.amount) }} • {{ formatDate(expense.expense_date) }}</p>
                                </div>

                                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="closeDuplicateModal"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="!duplicateDate || isLoading"
                                        class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="duplicateExpense"
                                    >
                                        {{ isLoading ? 'Duplicating...' : 'Duplicate Expense' }}
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
