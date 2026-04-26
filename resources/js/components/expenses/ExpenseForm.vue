<script setup>
import { computed, reactive, ref } from 'vue';
import ReceiptUpload from './ReceiptUpload.vue';

const props = defineProps({
    cycles: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    selectedCycleId: {
        type: [Number, String],
        default: 0,
    },
    formMode: {
        type: String,
        default: 'create',
    },
    expense: {
        type: Object,
        default: () => ({}),
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    oldInput: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['form-submitted']);

const isSubmitting = ref(false);
const selectedReceipt = ref(null);
const removeReceipt = ref(false);

const initialSelectedCycleId = computed(() => {
    const selectedId = Number(props.selectedCycleId || 0);

    return Number.isInteger(selectedId) && selectedId > 0 ? String(selectedId) : '';
});

const initialBatchId = computed(() => {
    if (props.oldInput.batch_id) {
        return String(props.oldInput.batch_id);
    }

    if (initialSelectedCycleId.value) {
        return initialSelectedCycleId.value;
    }

    return props.expense?.batch_id ? String(props.expense.batch_id) : '';
});

const form = reactive({
    batch_id: initialBatchId.value,
    category: String(props.oldInput.category ?? props.expense?.category ?? ''),
    amount: String(props.oldInput.amount ?? props.expense?.amount ?? ''),
    expense_date: String(props.oldInput.expense_date ?? props.expense?.expense_date ?? ''),
    notes: String(props.oldInput.notes ?? props.expense?.notes ?? ''),
});

const isEditMode = computed(() => props.formMode === 'edit');
const isCreateMode = computed(() => props.formMode === 'create');

const selectedCycle = computed(() => {
    const cycleId = Number(form.batch_id || 0);

    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return null;
    }

    return props.cycles.find((cycle) => Number(cycle.id) === cycleId) ?? null;
});

const selectedCycleArchived = computed(() => {
    return selectedCycle.value?.isArchived ?? false;
});

const clientSideBlocked = computed(() => {
    const cycleId = Number(form.batch_id || 0);

    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return true;
    }

    if (form.category === '') {
        return true;
    }

    const amount = parseFloat(form.amount);

    if (isNaN(amount) || amount <= 0) {
        return true;
    }

    if (form.expense_date === '') {
        return true;
    }

    if (form.expense_date > today.value) {
        return true;
    }

    if (form.notes.trim() === '') {
        return true;
    }

    if (selectedCycleArchived.value) {
        return true;
    }

    return false;
});

const clientSideBlockMessage = computed(() => {
    if (selectedCycleArchived.value) {
        return 'Cannot save expense: selected cycle is archived.';
    }

    const cycleId = Number(form.batch_id || 0);

    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return 'Select a cycle before submitting.';
    }

    if (form.category === '') {
        return 'Select a category before submitting.';
    }

    const amount = parseFloat(form.amount);

    if (isNaN(amount) || amount <= 0) {
        return 'Enter a valid amount greater than zero.';
    }

    if (form.expense_date === '') {
        return 'Select an expense date before submitting.';
    }

    if (form.expense_date > today.value) {
        return 'Expense date cannot be in the future.';
    }

    if (form.notes.trim() === '') {
        return 'Enter notes before submitting.';
    }

    return '';
});

const fieldError = (field) => {
    const value = props.errors?.[field];

    if (Array.isArray(value)) {
        return value[0] || '';
    }

    return typeof value === 'string' ? value : '';
};

const handleReceiptSelected = (file) => {
    selectedReceipt.value = file;
    removeReceipt.value = false;
};

const handleReceiptRemoved = (removeExisting = false) => {
    selectedReceipt.value = null;
    removeReceipt.value = Boolean(removeExisting);
};

const submitLabel = computed(() => {
    if (isSubmitting.value) {
        return isEditMode.value ? 'Updating Expense...' : 'Saving Expense...';
    }

    return isEditMode.value ? 'Update Expense' : 'Save Expense';
});

const submitForm = (event) => {
    if (clientSideBlocked.value || isSubmitting.value) {
        event.preventDefault();
        return;
    }

    isSubmitting.value = true;
};

const cancelRoute = computed(() => {
    if (isEditMode.value && props.expense?.id) {
        return props.routes.show?.replace('_ID_', props.expense.id) || props.routes.index;
    }

    return props.routes.index;
});

const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const formatCategoryLabel = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1);
};
</script>

<template>
    <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
        <div v-if="props.cycles.length === 0" class="space-y-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-5">
            <p class="text-sm font-semibold text-gray-800">No active cycles are available for expense recording.</p>
            <p class="text-sm text-gray-600">Create or reactivate a cycle first, then return to this form.</p>
            <a :href="cancelRoute" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                Back
            </a>
        </div>

        <form
            v-else
            :action="isEditMode ? props.routes.update?.replace('_ID_', props.expense.id) : props.routes.store"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            @submit="submitForm"
        >
            <input type="hidden" name="_token" :value="props.csrfToken">
            <input v-if="isEditMode" type="hidden" name="_method" value="PUT">
            <input v-if="removeReceipt" type="hidden" name="remove_receipt" value="1">

            <p v-if="clientSideBlockMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">
                {{ clientSideBlockMessage }}
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Cycle *</span>
                    <select
                        v-model="form.batch_id"
                        name="batch_id"
                        required
                        :class="[
                            'w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('batch_id')
                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                        ]"
                    >
                        <option value="" disabled>Select cycle...</option>
                        <option
                            v-for="cycle in props.cycles"
                            :key="cycle.id"
                            :value="String(cycle.id)"
                        >
                            {{ cycle.batch_code }}{{ cycle.isArchived ? ' (Archived)' : '' }}
                        </option>
                    </select>
                    <p v-if="fieldError('batch_id')" class="mt-1.5 text-xs font-semibold text-rose-700">
                        {{ fieldError('batch_id') }}
                    </p>
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Category *</span>
                    <select
                        v-model="form.category"
                        name="category"
                        required
                        :class="[
                            'w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('category')
                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                        ]"
                    >
                        <option value="" disabled>Select category...</option>
                        <option
                            v-for="category in props.categories"
                            :key="category"
                            :value="category"
                        >
                            {{ formatCategoryLabel(category) }}
                        </option>
                    </select>
                    <p v-if="fieldError('category')" class="mt-1.5 text-xs font-semibold text-rose-700">
                        {{ fieldError('category') }}
                    </p>
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Amount *</span>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Php</span>
                        <input
                            v-model="form.amount"
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            max="999999.99"
                            required
                            :class="[
                                'w-full rounded-xl border py-3 pl-10 pr-4 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                fieldError('amount')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                    </div>
                    <p v-if="fieldError('amount')" class="mt-1.5 text-xs font-semibold text-rose-700">
                        {{ fieldError('amount') }}
                    </p>
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Expense Date *</span>
                    <input
                        v-model="form.expense_date"
                        type="date"
                        name="expense_date"
                        :max="today"
                        required
                        :class="[
                            'w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('expense_date')
                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                        ]"
                    >
                    <p v-if="fieldError('expense_date')" class="mt-1.5 text-xs font-semibold text-rose-700">
                        {{ fieldError('expense_date') }}
                    </p>
                </label>

                <div class="sm:col-span-2">
                    <ReceiptUpload
                        :current-receipt-url="props.expense?.receipt_url || ''"
                        :error-message="fieldError('receipt')"
                        @receipt-selected="handleReceiptSelected"
                        @receipt-removed="handleReceiptRemoved"
                    />
                </div>

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Notes *</span>
                    <textarea
                        v-model="form.notes"
                        name="notes"
                        rows="3"
                        required
                        maxlength="1000"
                        :class="[
                            'w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('notes')
                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                        ]"
                        placeholder="Enter expense description or notes..."
                    ></textarea>
                    <div class="mt-1.5 flex items-center justify-between">
                        <p v-if="fieldError('notes')" class="text-xs font-semibold text-rose-700">
                            {{ fieldError('notes') }}
                        </p>
                        <p class="ml-auto text-xs text-gray-500">{{ form.notes.length }}/1000</p>
                    </div>
                </label>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                <button
                    type="submit"
                    :disabled="isSubmitting || clientSideBlocked"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                >
                    {{ submitLabel }}
                </button>
                <a
                    :href="cancelRoute"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                >
                    Cancel
                </a>
            </div>
        </form>
    </section>
</template>
