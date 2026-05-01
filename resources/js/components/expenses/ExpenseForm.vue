<script setup>
import { computed, reactive, ref, watch } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';
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
    preferences: {
        type: Object,
        default: () => ({
            last_category: '',
            last_cycle_id: 0,
            preset_amounts: [100, 500, 1000, 2000],
        }),
    },
    recentTemplates: {
        type: Array,
        default: () => [],
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
    flashStatus: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['form-submitted']);

const isSubmitting = ref(false);
const removeReceipt = ref(false);
const submitIntent = ref('save');
const cycleSearch = ref('');
const receiptVisible = ref(props.formMode === 'edit' && Boolean(props.expense?.receipt_url));
const receiptKey = ref(0);
const templates = ref([...props.recentTemplates]);
const localErrors = reactive({});
const lastSavedUrl = ref('');
const toast = reactive({
    show: Boolean(props.flashStatus),
    type: 'success',
    title: props.flashStatus ? 'Saved' : '',
    message: props.flashStatus || '',
    actionLabel: '',
});

const today = computed(() => new Date().toISOString().split('T')[0]);
const isEditMode = computed(() => props.formMode === 'edit');
const isCreateMode = computed(() => props.formMode === 'create');

const initialSelectedCycleId = computed(() => {
    const selectedId = Number(props.selectedCycleId || props.preferences.last_cycle_id || 0);

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
    category: String(props.oldInput.category ?? props.expense?.category ?? props.preferences.last_category ?? ''),
    quantity: String(props.oldInput.quantity ?? props.expense?.quantity ?? ''),
    unit: String(props.oldInput.unit ?? props.expense?.unit ?? ''),
    unit_cost: String(props.oldInput.unit_cost ?? props.expense?.unit_cost ?? ''),
    amount: String(props.oldInput.amount ?? props.expense?.amount ?? ''),
    expense_date: String(props.oldInput.expense_date ?? props.expense?.expense_date ?? today.value),
    notes: String(props.oldInput.notes ?? props.expense?.notes ?? ''),
});

const hasStructuredInput = computed(() => {
    return form.quantity.trim() !== '' || form.unit.trim() !== '' || form.unit_cost.trim() !== '';
});

const structuredTotal = computed(() => {
    const quantity = Number(form.quantity);
    const unitCost = Number(form.unit_cost);

    if (!Number.isFinite(quantity) || !Number.isFinite(unitCost) || quantity <= 0 || unitCost <= 0) {
        return 0;
    }

    return Math.round(quantity * unitCost * 100) / 100;
});

const usesStructuredAmount = computed(() => structuredTotal.value > 0 && form.unit.trim() !== '');

watch(structuredTotal, (total) => {
    if (total > 0) {
        form.amount = total.toFixed(2);
    }
});

const filteredCycles = computed(() => {
    const search = cycleSearch.value.trim().toLowerCase();

    if (search === '') {
        return props.cycles;
    }

    return props.cycles.filter((cycle) => {
        return String(cycle.batch_code || '').toLowerCase().includes(search);
    });
});

const presetAmounts = computed(() => {
    const amounts = Array.isArray(props.preferences.preset_amounts)
        ? props.preferences.preset_amounts
        : [100, 500, 1000, 2000];

    return amounts
        .map((amount) => Number(amount))
        .filter((amount) => Number.isFinite(amount) && amount > 0)
        .slice(0, 6);
});

const selectedCycle = computed(() => {
    const cycleId = Number(form.batch_id || 0);

    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return null;
    }

    return props.cycles.find((cycle) => Number(cycle.id) === cycleId) ?? null;
});

const selectedCycleArchived = computed(() => selectedCycle.value?.isArchived ?? false);

const validations = computed(() => ({
    batch_id: Number(form.batch_id || 0) > 0 && !selectedCycleArchived.value,
    category: form.category !== '',
    quantity: !hasStructuredInput.value || Number(form.quantity) > 0,
    unit: !hasStructuredInput.value || form.unit.trim() !== '',
    unit_cost: !hasStructuredInput.value || Number(form.unit_cost) > 0,
    amount: usesStructuredAmount.value || Number(form.amount) > 0,
    expense_date: form.expense_date !== '' && form.expense_date <= today.value,
    notes: form.notes.trim().length > 0,
}));

const clientSideBlocked = computed(() => {
    return !Object.values(validations.value).every(Boolean);
});

const clientSideBlockMessage = computed(() => {
    if (selectedCycleArchived.value) {
        return 'Cannot save expense: selected cycle is archived.';
    }

    if (!validations.value.batch_id) {
        return 'Select a cycle before submitting.';
    }

    if (!validations.value.category) {
        return 'Select a category before submitting.';
    }

    if (!validations.value.amount) {
        return hasStructuredInput.value
            ? 'Complete Quantity, Unit, and Unit Cost so Total Amount can be computed.'
            : 'Enter a valid amount greater than zero.';
    }

    if (!validations.value.quantity) {
        return 'Enter a valid Quantity / Bilang greater than zero.';
    }

    if (!validations.value.unit) {
        return 'Enter Unit / Yunit when using quantity and unit cost.';
    }

    if (!validations.value.unit_cost) {
        return 'Enter a valid Unit Cost / Halaga kada Yunit greater than zero.';
    }

    if (!validations.value.expense_date) {
        return form.expense_date > today.value
            ? 'Expense date cannot be in the future.'
            : 'Select an expense date before submitting.';
    }

    if (!validations.value.notes) {
        return 'Enter notes before submitting.';
    }

    return '';
});

const fieldError = (field) => {
    const localValue = localErrors?.[field];
    const value = localValue ?? props.errors?.[field];

    if (Array.isArray(value)) {
        return value[0] || '';
    }

    return typeof value === 'string' ? value : '';
};

const fieldReady = (field) => {
    return validations.value[field] && !fieldError(field);
};

const clearLocalErrors = () => {
    Object.keys(localErrors).forEach((key) => {
        delete localErrors[key];
    });
};

const handleReceiptSelected = () => {
    removeReceipt.value = false;
};

const handleReceiptRemoved = (removeExisting = false) => {
    removeReceipt.value = Boolean(removeExisting);
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        maximumFractionDigits: Number(amount) % 1 === 0 ? 0 : 2,
    }).format(Number(amount || 0));
};

const formatCategoryLabel = (category) => {
    return category?.charAt(0).toUpperCase() + category?.slice(1) || '';
};

const submitLabel = computed(() => {
    if (isSubmitting.value) {
        return isEditMode.value ? 'Updating Expense...' : 'Saving Expense...';
    }

    return isEditMode.value ? 'Update Expense' : 'Save Expense';
});

const cancelRoute = computed(() => {
    if (isEditMode.value && props.expense?.id) {
        return props.routes.show?.replace('_ID_', props.expense.id) || props.routes.index;
    }

    return props.routes.index;
});

const applyPresetAmount = (amount) => {
    form.quantity = '';
    form.unit = '';
    form.unit_cost = '';
    form.amount = String(amount);
};

const applyTemplate = (template) => {
    form.batch_id = String(template.batch_id || form.batch_id || '');
    form.category = String(template.category || '');
    form.quantity = String(template.quantity ?? '');
    form.unit = String(template.unit ?? '');
    form.unit_cost = String(template.unit_cost ?? '');
    form.amount = String(template.amount || '');
    form.notes = String(template.notes || '');
    form.expense_date = today.value;
};

const resetForAnother = (preferences = {}) => {
    form.batch_id = String(preferences.last_cycle_id || form.batch_id || '');
    form.category = String(preferences.last_category || form.category || '');
    form.quantity = '';
    form.unit = '';
    form.unit_cost = '';
    form.amount = '';
    form.expense_date = today.value;
    form.notes = '';
    removeReceipt.value = false;
    receiptVisible.value = false;
    receiptKey.value += 1;
};

const showToast = (type, title, message, actionLabel = '') => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.actionLabel = actionLabel;
    toast.show = true;
};

const refreshTemplates = async () => {
    if (!props.routes.recentTemplates) {
        return;
    }

    try {
        const response = await fetch(props.routes.recentTemplates, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            templates.value = Array.isArray(data.templates) ? data.templates : templates.value;
        }
    } catch (error) {
        // The form still works without templates when the connection is weak.
    }
};

const submitForm = async (event) => {
    submitIntent.value = event.submitter?.dataset?.intent || 'save';

    if (clientSideBlocked.value || isSubmitting.value) {
        event.preventDefault();
        return;
    }

    if (isEditMode.value) {
        isSubmitting.value = true;
        return;
    }

    event.preventDefault();
    isSubmitting.value = true;
    clearLocalErrors();

    try {
        const formData = new FormData(event.currentTarget);
        formData.append('add_another', submitIntent.value === 'another' ? '1' : '0');

        const response = await fetch(props.routes.store, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (response.status === 422) {
            Object.assign(localErrors, data.errors || {});
            showToast('error', 'Please check the form', data.message || 'Some fields need correction.');
            return;
        }

        if (!response.ok) {
            showToast('error', 'Save failed', data.message || 'Please try again.');
            return;
        }

        emit('form-submitted', data.expense);
        lastSavedUrl.value = data.redirect_url || props.routes.index;

        if (submitIntent.value === 'another') {
            resetForAnother(data.preferences || {});
            showToast('success', 'Saved', 'Expense saved. Ready for the next entry.');
            await refreshTemplates();
            return;
        }

        showToast('success', 'Saved', 'Expense saved successfully.', 'View record');
        window.setTimeout(() => {
            window.location.href = lastSavedUrl.value;
        }, 900);
    } catch (error) {
        showToast('error', 'Connection problem', 'The expense was not saved. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const handleToastAction = () => {
    if (lastSavedUrl.value) {
        window.location.href = lastSavedUrl.value;
    }
};
</script>

<template>
    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8">
        <ToastNotification
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            :action-label="toast.actionLabel"
            @close="toast.show = false"
            @action="handleToastAction"
        />

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

            <p v-if="clientSideBlockMessage" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">
                {{ clientSideBlockMessage }}
            </p>

            <div v-if="isCreateMode && templates.length > 0" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-sm font-bold text-gray-900">Recent templates</p>
                    <button type="button" class="text-xs font-bold text-[#0c6d57] hover:text-[#0a5a48]" @click="refreshTemplates">
                        Refresh
                    </button>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="template in templates"
                        :key="template.id"
                        type="button"
                        class="rounded-xl border border-gray-200 bg-white p-3 text-left transition hover:border-[#0c6d57]/50 hover:bg-[#0c6d57]/5"
                        @click="applyTemplate(template)"
                    >
                        <span class="block text-xs font-bold uppercase text-[#0c6d57]">{{ template.category_label }}</span>
                        <span class="mt-1 block truncate text-sm font-semibold text-gray-900">{{ template.notes }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ formatAmount(template.amount) }} | {{ template.cycle_code || 'Recent cycle' }}</span>
                    </button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Cycle *
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-500" title="Choose the pig cycle where this expense belongs." aria-label="Cycle help">?</button>
                        <span v-if="fieldReady('batch_id')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <input
                        v-if="props.cycles.length > 7"
                        v-model="cycleSearch"
                        type="search"
                        class="mb-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        placeholder="Search cycle code..."
                    >
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
                            v-for="cycle in filteredCycles"
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
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Category *
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-500" title="Use the paper logbook category that best matches the expense." aria-label="Category help">?</button>
                        <span v-if="fieldReady('category')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
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

                <div class="sm:col-span-2 rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-[#0a5a48]">Optional quantity computation</p>
                            <p class="mt-1 text-xs text-[#0a5a48]/80">
                                Use this for logbook entries with Bilang, Yunit, and Halaga kada Yunit. Leave blank for lump-sum expenses.
                            </p>
                        </div>
                        <p class="rounded-xl bg-white px-3 py-2 text-sm font-black text-[#0c6d57]">
                            Total Amount / Kabuuang Halaga: {{ formatAmount(usesStructuredAmount ? structuredTotal : form.amount) }}
                        </p>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <label>
                            <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                                Quantity / Bilang
                                <span v-if="fieldReady('quantity') && hasStructuredInput" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                            </span>
                            <input
                                v-model="form.quantity"
                                type="number"
                                name="quantity"
                                step="0.01"
                                min="0.01"
                                max="999999.99"
                                :class="[
                                    'w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                    fieldError('quantity')
                                        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                        : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                ]"
                                placeholder="Example: 2"
                            >
                            <p v-if="fieldError('quantity')" class="mt-1.5 text-xs font-semibold text-rose-700">
                                {{ fieldError('quantity') }}
                            </p>
                        </label>

                        <label>
                            <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                                Unit / Yunit
                                <span v-if="fieldReady('unit') && hasStructuredInput" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                            </span>
                            <input
                                v-model="form.unit"
                                type="text"
                                name="unit"
                                maxlength="50"
                                :class="[
                                    'w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                    fieldError('unit')
                                        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                        : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                ]"
                                placeholder="sack, kilo, bottle"
                            >
                            <p v-if="fieldError('unit')" class="mt-1.5 text-xs font-semibold text-rose-700">
                                {{ fieldError('unit') }}
                            </p>
                        </label>

                        <label>
                            <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                                Unit Cost / Halaga kada Yunit
                                <span v-if="fieldReady('unit_cost') && hasStructuredInput" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                            </span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Php</span>
                                <input
                                    v-model="form.unit_cost"
                                    type="number"
                                    name="unit_cost"
                                    step="0.01"
                                    min="0.01"
                                    max="999999.99"
                                    :class="[
                                        'w-full rounded-xl border py-3 pl-10 pr-4 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                        fieldError('unit_cost')
                                            ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                            : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                    ]"
                                    placeholder="0.00"
                                >
                            </div>
                            <p v-if="fieldError('unit_cost')" class="mt-1.5 text-xs font-semibold text-rose-700">
                                {{ fieldError('unit_cost') }}
                            </p>
                        </label>
                    </div>
                </div>

                <label>
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Total Amount / Kabuuang Halaga *
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-500" title="Enter a direct amount for lump-sum expenses, or let Quantity and Unit Cost compute this total." aria-label="Amount help">?</button>
                        <span v-if="fieldReady('amount')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Php</span>
                        <input
                            v-model="form.amount"
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            max="999999.99"
                            :readonly="usesStructuredAmount"
                            :required="!hasStructuredInput"
                            :class="[
                                'w-full rounded-xl border py-3 pl-10 pr-4 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                usesStructuredAmount ? 'cursor-not-allowed bg-gray-100 text-gray-600' : 'bg-white',
                                fieldError('amount')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                    </div>
                    <p v-if="usesStructuredAmount" class="mt-1.5 text-xs font-medium text-[#0a5a48]">
                        Auto-computed from Quantity x Unit Cost. The server will verify this total when saving.
                    </p>
                    <p v-else class="mt-1.5 text-xs text-gray-500">
                        For lump-sum expenses like emergency funds or miscellaneous costs, enter the total directly here.
                    </p>
                    <div v-if="presetAmounts.length > 0" class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="amount in presetAmounts"
                            :key="amount"
                            type="button"
                            class="rounded-lg border border-[#0c6d57]/20 bg-[#0c6d57]/5 px-3 py-1.5 text-xs font-bold text-[#0c6d57] transition hover:bg-[#0c6d57]/10"
                            @click="applyPresetAmount(amount)"
                        >
                            {{ formatAmount(amount) }}
                        </button>
                    </div>
                    <p v-if="fieldError('amount')" class="mt-1.5 text-xs font-semibold text-rose-700">
                        {{ fieldError('amount') }}
                    </p>
                </label>

                <label>
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Expense Date *
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-500" title="Today is filled in automatically for new records." aria-label="Date help">?</button>
                        <span v-if="fieldReady('expense_date')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
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

                <label class="sm:col-span-2">
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Notes *
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-500" title="Write the same short description you would put in the paper logbook." aria-label="Notes help">?</button>
                        <span v-if="fieldReady('notes')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
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

                <div class="sm:col-span-2">
                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50 sm:w-auto"
                        @click="receiptVisible = !receiptVisible"
                    >
                        {{ receiptVisible ? 'Hide Receipt' : (props.expense?.receipt_url ? 'Manage Receipt' : 'Attach Receipt') }}
                    </button>

                    <div v-if="receiptVisible" class="mt-3">
                        <ReceiptUpload
                            :key="receiptKey"
                            :current-receipt-url="props.expense?.receipt_url || ''"
                            :error-message="fieldError('receipt')"
                            @receipt-selected="handleReceiptSelected"
                            @receipt-removed="handleReceiptRemoved"
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                <button
                    type="submit"
                    data-intent="save"
                    :disabled="isSubmitting || clientSideBlocked"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                >
                    {{ submitLabel }}
                </button>
                <button
                    v-if="isCreateMode"
                    type="submit"
                    data-intent="another"
                    :disabled="isSubmitting || clientSideBlocked"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-[#0c6d57] bg-white px-6 py-3 text-sm font-bold text-[#0c6d57] transition-colors hover:bg-[#0c6d57]/5 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                >
                    Save & Add Another
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
