<script setup>
import { computed, reactive, ref, watch } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';
import ReceiptUpload from '../expenses/ReceiptUpload.vue';

const props = defineProps({
    cycles: {
        type: Array,
        default: () => [],
    },
    buyers: {
        type: Array,
        default: () => [],
    },
    selectedCycleId: {
        type: [Number, String],
        default: '',
    },
    paymentStatusOptions: {
        type: Array,
        default: () => [],
    },
    saleMethodOptions: {
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
const submitIntent = ref('save');
const localErrors = reactive({});
const removeReceipt = ref(false);
const receiptVisible = ref(true);
const receiptKey = ref(0);
const toast = reactive({
    show: Boolean(props.flashStatus),
    type: 'success',
    title: props.flashStatus ? 'Saved' : '',
    message: props.flashStatus || '',
    actionLabel: '',
});

const today = computed(() => new Date().toISOString().split('T')[0]);

const initialBatchId = computed(() => {
    if (props.oldInput.batch_id) {
        return String(props.oldInput.batch_id);
    }

    if (props.selectedCycleId) {
        return String(props.selectedCycleId);
    }

    return '';
});

const form = reactive({
    batch_id: initialBatchId.value,
    buyer_id: String(props.oldInput.buyer_id ?? ''),
    buyer_name: String(props.oldInput.buyer_name ?? ''),
    buyer_contact_number: String(props.oldInput.buyer_contact_number ?? ''),
    buyer_address: String(props.oldInput.buyer_address ?? ''),
    buyer_notes: String(props.oldInput.buyer_notes ?? ''),
    pigs_sold: String(props.oldInput.pigs_sold ?? ''),
    sale_date: String(props.oldInput.sale_date ?? today.value),
    sale_method: String(props.oldInput.sale_method ?? 'live_weight'),
    live_weight_kg: String(props.oldInput.live_weight_kg ?? ''),
    price_per_kg: String(props.oldInput.price_per_kg ?? ''),
    price_per_head: String(props.oldInput.price_per_head ?? ''),
    payment_status: String(props.oldInput.payment_status ?? 'paid'),
    amount_paid: String(props.oldInput.amount_paid ?? ''),
    receipt_reference: String(props.oldInput.receipt_reference ?? ''),
    notes: String(props.oldInput.notes ?? ''),
});

const manualBuyer = reactive({
    name: form.buyer_name,
    contact_number: form.buyer_contact_number,
    address: form.buyer_address,
    notes: form.buyer_notes,
});

const selectedCycle = computed(() => {
    const cycleId = Number(form.batch_id || 0);
    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return null;
    }
    return props.cycles.find((cycle) => Number(cycle.id) === cycleId) ?? null;
});

const selectedBuyer = computed(() => {
    const buyerId = Number(form.buyer_id || 0);
    if (!Number.isInteger(buyerId) || buyerId < 1) {
        return null;
    }
    return props.buyers.find((buyer) => Number(buyer.id) === buyerId) ?? null;
});

const usingExistingBuyer = computed(() => Boolean(form.buyer_id));

watch(() => form.buyer_id, (value) => {
    if (!value) {
        form.buyer_name = manualBuyer.name;
        form.buyer_contact_number = manualBuyer.contact_number;
        form.buyer_address = manualBuyer.address;
        form.buyer_notes = manualBuyer.notes;
        return;
    }

    const buyer = selectedBuyer.value;
    if (!buyer) {
        return;
    }

    form.buyer_name = buyer.name || '';
    form.buyer_contact_number = buyer.contact_number || '';
    form.buyer_address = buyer.address || '';
    form.buyer_notes = buyer.notes || '';
});

watch(
    () => [form.buyer_name, form.buyer_contact_number, form.buyer_address, form.buyer_notes],
    () => {
        if (!usingExistingBuyer.value) {
            manualBuyer.name = form.buyer_name;
            manualBuyer.contact_number = form.buyer_contact_number;
            manualBuyer.address = form.buyer_address;
            manualBuyer.notes = form.buyer_notes;
        }
    }
);

const totalAmount = computed(() => {
    const pigsSold = Number(form.pigs_sold || 0);

    if (form.sale_method === 'live_weight') {
        const weight = Number(form.live_weight_kg || 0);
        const price = Number(form.price_per_kg || 0);
        return Math.round(weight * price * 100) / 100;
    }

    const pricePerHead = Number(form.price_per_head || 0);
    return Math.round(pigsSold * pricePerHead * 100) / 100;
});

const balanceAmount = computed(() => {
    const amountPaid = Number(form.amount_paid || 0);
    return Math.max(totalAmount.value - amountPaid, 0);
});

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number(amount || 0));
};

const fieldError = (field) => {
    const localValue = localErrors?.[field];
    const value = localValue ?? props.errors?.[field];

    if (Array.isArray(value)) {
        return value[0] || '';
    }

    return typeof value === 'string' ? value : '';
};

const showToast = (type, title, message, actionLabel = '') => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.actionLabel = actionLabel;
    toast.show = true;
};

const clearLocalErrors = () => {
    Object.keys(localErrors).forEach((key) => {
        delete localErrors[key];
    });
};

const validations = computed(() => ({
    batch_id: Number(form.batch_id || 0) > 0,
    buyer_name: form.buyer_name.trim().length > 0,
    pigs_sold: Number(form.pigs_sold || 0) > 0,
    sale_date: form.sale_date !== '' && form.sale_date <= today.value,
    live_weight_kg: form.sale_method !== 'live_weight' || Number(form.live_weight_kg || 0) > 0,
    price_per_kg: form.sale_method !== 'live_weight' || Number(form.price_per_kg || 0) > 0,
    price_per_head: form.sale_method !== 'per_head' || Number(form.price_per_head || 0) > 0,
    total_amount: totalAmount.value > 0,
    payment_status: form.payment_status !== '',
    amount_paid: Number(form.amount_paid || 0) >= 0 && Number(form.amount_paid || 0) <= totalAmount.value,
}));

const clientSideBlocked = computed(() => {
    return !Object.values(validations.value).every(Boolean);
});

const clientSideBlockMessage = computed(() => {
    if (props.cycles.length === 0) {
        return 'No active cycles are available for recording sales.';
    }

    if (!validations.value.batch_id) {
        return 'Select a batch before submitting.';
    }

    if (!validations.value.buyer_name) {
        return 'Buyer name is required.';
    }

    if (!validations.value.pigs_sold) {
        return 'Enter the number of pigs sold.';
    }

    if (!validations.value.sale_date) {
        return 'Sale date cannot be in the future.';
    }

    if (!validations.value.total_amount) {
        return 'Check the pricing fields before submitting.';
    }

    if (!validations.value.amount_paid) {
        return 'Amount paid must not exceed the total.';
    }

    if (form.payment_status === 'paid' && Number(form.amount_paid || 0) !== totalAmount.value) {
        return 'Paid status requires full payment.';
    }

    if (form.payment_status === 'pending' && Number(form.amount_paid || 0) > 0) {
        return 'Pending status requires zero payment.';
    }

    if (form.payment_status === 'partial') {
        const paid = Number(form.amount_paid || 0);
        if (paid <= 0 || paid >= totalAmount.value) {
            return 'Partial status requires a payment between zero and the total.';
        }
    }

    return '';
});

watch(() => form.payment_status, (status) => {
    if (status === 'paid') {
        form.amount_paid = totalAmount.value ? totalAmount.value.toFixed(2) : '';
    } else if (status === 'pending') {
        form.amount_paid = '0';
    }
});

watch(totalAmount, (value) => {
    if (form.payment_status === 'paid') {
        form.amount_paid = value ? value.toFixed(2) : '';
    } else if (form.payment_status === 'pending') {
        form.amount_paid = '0';
    } else if (form.payment_status === 'partial') {
        const paid = Number(form.amount_paid || 0);
        if (paid > value) {
            form.amount_paid = value.toFixed(2);
        }
    }
});

const resetForm = () => {
    form.buyer_id = '';
    form.buyer_name = '';
    form.buyer_contact_number = '';
    form.buyer_address = '';
    form.buyer_notes = '';
    form.pigs_sold = '';
    form.sale_date = today.value;
    form.sale_method = 'live_weight';
    form.live_weight_kg = '';
    form.price_per_kg = '';
    form.price_per_head = '';
    form.payment_status = 'paid';
    form.amount_paid = '';
    form.receipt_reference = '';
    form.notes = '';
    removeReceipt.value = false;
    receiptVisible.value = true;
    receiptKey.value += 1;
};

const handleToastAction = () => {
    if (props.routes.index) {
        window.location.href = props.routes.index;
    }
};

const submitForm = async (event) => {
    submitIntent.value = event.submitter?.dataset?.intent || 'save';

    if (clientSideBlocked.value || isSubmitting.value) {
        event.preventDefault();
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

        emit('form-submitted', data.sale);

        if (submitIntent.value === 'another') {
            resetForm();
            showToast('success', 'Saved', 'Sale saved. Ready for the next entry.');
            return;
        }

        showToast('success', 'Saved', 'Sale saved successfully.', 'Back to list');
        window.setTimeout(() => {
            window.location.href = data.redirect_url || props.routes.index;
        }, 900);
    } catch (error) {
        showToast('error', 'Connection problem', 'The sale was not saved. Please try again.');
    } finally {
        isSubmitting.value = false;
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
            <p class="text-sm font-semibold text-gray-800">No active cycles are available for sales recording.</p>
            <p class="text-sm text-gray-600">Create or reactivate a cycle first, then return to this form.</p>
            <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                Back
            </a>
        </div>

        <form
            v-else
            :action="props.routes.store"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            @submit="submitForm"
        >
            <input type="hidden" name="_token" :value="props.csrfToken">
            <input v-if="removeReceipt" type="hidden" name="remove_receipt" value="1">

            <p v-if="clientSideBlockMessage" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">
                {{ clientSideBlockMessage }}
            </p>

            <div class="space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">1. Buyer Information</h3>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Select Existing Buyer</span>
                        <select
                            v-model="form.buyer_id"
                            name="buyer_id"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                            <option value="">Create new buyer</option>
                            <option v-for="buyer in props.buyers" :key="buyer.id" :value="String(buyer.id)">
                                {{ buyer.name }}
                            </option>
                        </select>
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Buyer Name *</span>
                        <input
                            v-model="form.buyer_name"
                            type="text"
                            name="buyer_name"
                            :readonly="usingExistingBuyer"
                            :class="[
                                'w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('buyer_name')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                usingExistingBuyer ? 'bg-gray-50 text-gray-500' : 'bg-white',
                            ]"
                            placeholder="Enter buyer name"
                            required
                        >
                        <p v-if="fieldError('buyer_name')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('buyer_name') }}
                        </p>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Contact Number</span>
                        <input
                            v-model="form.buyer_contact_number"
                            type="text"
                            name="buyer_contact_number"
                            :readonly="usingExistingBuyer"
                            :class="[
                                'w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('buyer_contact_number')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                usingExistingBuyer ? 'bg-gray-50 text-gray-500' : 'bg-white',
                            ]"
                            placeholder="Optional contact"
                        >
                        <p v-if="fieldError('buyer_contact_number')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('buyer_contact_number') }}
                        </p>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Address</span>
                        <input
                            v-model="form.buyer_address"
                            type="text"
                            name="buyer_address"
                            :readonly="usingExistingBuyer"
                            :class="[
                                'w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('buyer_address')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                                usingExistingBuyer ? 'bg-gray-50 text-gray-500' : 'bg-white',
                            ]"
                            placeholder="Optional address"
                        >
                        <p v-if="fieldError('buyer_address')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('buyer_address') }}
                        </p>
                    </label>
                </div>
            </div>

            <div class="space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">2. Sale Details</h3>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                            Batch *
                            <span v-if="selectedCycle" class="text-xs font-semibold text-gray-500">({{ selectedCycle.current_count }} available)</span>
                        </span>
                        <select
                            v-model="form.batch_id"
                            name="batch_id"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('batch_id')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                        >
                            <option value="" disabled>Select batch...</option>
                            <option v-for="cycle in props.cycles" :key="cycle.id" :value="String(cycle.id)">
                                {{ cycle.batch_code }} ({{ cycle.current_count }} heads)
                            </option>
                        </select>
                        <p v-if="fieldError('batch_id')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('batch_id') }}
                        </p>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Sale Date *</span>
                        <input
                            v-model="form.sale_date"
                            type="date"
                            name="sale_date"
                            :max="today"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('sale_date')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                        >
                        <p v-if="fieldError('sale_date')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('sale_date') }}
                        </p>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Sale Method *</span>
                        <select
                            v-model="form.sale_method"
                            name="sale_method"
                            required
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                            <option v-for="method in props.saleMethodOptions" :key="method" :value="method">
                                {{ method === 'live_weight' ? 'Live Weight' : 'Per Head' }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Pigs Sold *</span>
                        <input
                            v-model="form.pigs_sold"
                            type="number"
                            name="pigs_sold"
                            min="1"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('pigs_sold')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0"
                        >
                        <p v-if="fieldError('pigs_sold')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('pigs_sold') }}
                        </p>
                    </label>

                    <label v-if="form.sale_method === 'live_weight'">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Live Weight (kg) *</span>
                        <input
                            v-model="form.live_weight_kg"
                            type="number"
                            step="0.01"
                            name="live_weight_kg"
                            min="0.01"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('live_weight_kg')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                        <p v-if="fieldError('live_weight_kg')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('live_weight_kg') }}
                        </p>
                    </label>

                    <label v-if="form.sale_method === 'live_weight'">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Price per kg *</span>
                        <input
                            v-model="form.price_per_kg"
                            type="number"
                            step="0.01"
                            name="price_per_kg"
                            min="0.01"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('price_per_kg')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                        <p v-if="fieldError('price_per_kg')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('price_per_kg') }}
                        </p>
                    </label>

                    <label v-if="form.sale_method === 'per_head'">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Price per Head *</span>
                        <input
                            v-model="form.price_per_head"
                            type="number"
                            step="0.01"
                            name="price_per_head"
                            min="0.01"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('price_per_head')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                        <p v-if="fieldError('price_per_head')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('price_per_head') }}
                        </p>
                    </label>
                </div>

                <div class="rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#0c6d57]">Computed Total Amount</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ formatAmount(totalAmount) }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">3. Payment and Receipt</h3>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="mb-2 block text-sm font-bold text-gray-700">Payment Status *</span>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border bg-white p-3 text-center text-sm font-semibold transition-colors">
                                <input v-model="form.payment_status" type="radio" name="payment_status" value="paid" class="sr-only">
                                <span class="text-emerald-700">Paid</span>
                                <span class="mt-1 text-xs text-gray-500">Fully settled</span>
                            </label>
                            <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border bg-white p-3 text-center text-sm font-semibold transition-colors">
                                <input v-model="form.payment_status" type="radio" name="payment_status" value="partial" class="sr-only">
                                <span class="text-amber-700">Partial</span>
                                <span class="mt-1 text-xs text-gray-500">Downpayment</span>
                            </label>
                            <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border bg-white p-3 text-center text-sm font-semibold transition-colors">
                                <input v-model="form.payment_status" type="radio" name="payment_status" value="pending" class="sr-only">
                                <span class="text-rose-700">Pending</span>
                                <span class="mt-1 text-xs text-gray-500">No payment yet</span>
                            </label>
                        </div>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Amount Paid *</span>
                        <input
                            v-model="form.amount_paid"
                            type="number"
                            name="amount_paid"
                            step="0.01"
                            min="0"
                            :max="totalAmount"
                            required
                            :class="[
                                'w-full rounded-xl border bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2',
                                fieldError('amount_paid')
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                                    : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20',
                            ]"
                            placeholder="0.00"
                        >
                        <p v-if="fieldError('amount_paid')" class="mt-1 text-xs font-semibold text-rose-700">
                            {{ fieldError('amount_paid') }}
                        </p>
                    </label>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Balance</p>
                        <p class="mt-2 text-xl font-bold text-amber-700">{{ formatAmount(balanceAmount) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Remaining amount to collect</p>
                    </div>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Receipt Reference</span>
                        <input
                            v-model="form.receipt_reference"
                            type="text"
                            name="receipt_reference"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                            placeholder="Optional receipt note"
                        >
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Notes</span>
                        <textarea
                            v-model="form.notes"
                            name="notes"
                            rows="3"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                            placeholder="Optional notes"
                        ></textarea>
                    </label>

                    <div class="sm:col-span-2">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:w-auto"
                            @click="receiptVisible = !receiptVisible"
                        >
                            {{ receiptVisible ? 'Hide Receipt' : 'Attach Receipt' }}
                        </button>

                        <div v-if="receiptVisible" class="mt-3">
                            <ReceiptUpload
                                :key="receiptKey"
                                :error-message="fieldError('receipt')"
                                @receipt-selected="removeReceipt = false"
                                @receipt-removed="removeReceipt = false"
                            />
                        </div>
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
                    {{ isSubmitting ? 'Saving Sale...' : 'Save Sale' }}
                </button>
                <button
                    type="submit"
                    data-intent="another"
                    :disabled="isSubmitting || clientSideBlocked"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                >
                    Save and Add Another
                </button>
                <a
                    :href="props.routes.index"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                >
                    Cancel
                </a>
            </div>
        </form>
    </section>
</template>
