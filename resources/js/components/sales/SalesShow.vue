<script setup>
import { computed, reactive, ref, watch } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';
import ReceiptUpload from '../expenses/ReceiptUpload.vue';

const props = defineProps({
    sale: {
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
    canEditPayment: {
        type: Boolean,
        default: false,
    },
    canEditReceipt: {
        type: Boolean,
        default: false,
    },
    paymentStatusOptions: {
        type: Array,
        default: () => [],
    },
});

const saleState = reactive({ ...props.sale });
const isSubmitting = ref(false);
const removeReceipt = ref(false);
const receiptVisible = ref(Boolean(saleState.receipt_url));
const receiptKey = ref(0);
const localErrors = reactive({});
const toast = reactive({
    show: false,
    type: 'success',
    title: '',
    message: '',
    actionLabel: '',
});

const form = reactive({
    payment_status: String(saleState.payment_status || 'paid'),
    amount_paid: String(saleState.amount_paid ?? ''),
    receipt_reference: String(saleState.receipt_reference || ''),
    notes: String(saleState.notes || ''),
});

const totalAmount = computed(() => Number(saleState.amount || 0));
const balanceAmount = computed(() => {
    const paid = Number(form.amount_paid || 0);
    return Math.max(totalAmount.value - paid, 0);
});

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number(amount || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const saleMethodLabel = (method) => {
    if (method === 'live_weight') return 'Live Weight';
    if (method === 'per_head') return 'Per Head';
    return 'Unknown';
};

const statusBadgeClass = (status) => {
    const meta = {
        paid: 'bg-emerald-100 text-emerald-800',
        partial: 'bg-amber-100 text-amber-800',
        pending: 'bg-rose-100 text-rose-800',
    };

    return meta[status] || 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
    if (!status) return 'Unknown';
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const fieldError = (field) => {
    const localValue = localErrors?.[field];
    const value = localValue ?? null;

    if (Array.isArray(value)) {
        return value[0] || '';
    }

    return typeof value === 'string' ? value : '';
};

const clearLocalErrors = () => {
    Object.keys(localErrors).forEach((key) => {
        delete localErrors[key];
    });
};

const showToast = (type, title, message, actionLabel = '') => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.actionLabel = actionLabel;
    toast.show = true;
};

const updateFormFromSale = () => {
    form.payment_status = String(saleState.payment_status || 'paid');
    form.amount_paid = String(saleState.amount_paid ?? '');
    form.receipt_reference = String(saleState.receipt_reference || '');
    form.notes = String(saleState.notes || '');
};

watch(() => saleState.payment_status, updateFormFromSale);

const submitForm = async (event) => {
    if (isSubmitting.value) {
        event.preventDefault();
        return;
    }

    event.preventDefault();
    isSubmitting.value = true;
    clearLocalErrors();

    try {
        const formData = new FormData(event.currentTarget);
        formData.append('_method', 'PUT');

        const response = await fetch(props.routes.update?.replace('_ID_', saleState.id), {
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
            showToast('error', 'Update failed', data.message || 'Please try again.');
            return;
        }

        Object.assign(saleState, data.sale || {});
        updateFormFromSale();
        removeReceipt.value = false;
        receiptKey.value += 1;

        showToast('success', 'Updated', 'Sale updated successfully.');
    } catch (error) {
        showToast('error', 'Connection problem', 'The sale was not updated. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">Sale #{{ saleState.id }}</h1>
                    <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold', statusBadgeClass(saleState.payment_status)]">
                        {{ statusLabel(saleState.payment_status) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Recorded on {{ formatDate(saleState.sale_date) }}</p>
            </div>
            <a
                :href="props.routes.index"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Sales
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Buyer Information</h3>
                    <div class="mt-4 space-y-2">
                        <p class="text-lg font-semibold text-gray-900">{{ saleState.buyer?.name || 'Unknown buyer' }}</p>
                        <p class="text-sm text-gray-500">{{ saleState.buyer?.contact_number || 'No contact provided' }}</p>
                        <p class="text-sm text-gray-500">{{ saleState.buyer?.address || 'No address provided' }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#0c6d57]">Total Sale Amount</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatAmount(totalAmount) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ saleMethodLabel(saleState.sale_method) }}</p>
                        </div>
                        <div class="text-sm text-gray-700">
                            <p>Batch: <span class="font-semibold">{{ saleState.cycle?.batch_code || 'Unknown' }}</span></p>
                            <p>Pigs sold: <span class="font-semibold">{{ saleState.pigs_sold }}</span></p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs uppercase text-gray-500">Paid</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatAmount(saleState.amount_paid) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs uppercase text-gray-500">Balance</p>
                            <p class="mt-1 text-lg font-semibold text-amber-700">{{ formatAmount(balanceAmount) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs uppercase text-gray-500">Sale Date</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatDate(saleState.sale_date) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="saleState.notes" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Notes</h3>
                    <p class="mt-3 text-sm text-gray-700">{{ saleState.notes }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Receipt</h3>
                    <div class="mt-4">
                        <div v-if="saleState.receipt_url" class="space-y-3">
                            <div v-if="saleState.receipt_url.toLowerCase().endsWith('.pdf')" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-sm font-semibold text-gray-700">PDF receipt attached</p>
                                <a
                                    :href="saleState.receipt_url"
                                    target="_blank"
                                    class="mt-3 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700"
                                >
                                    View PDF
                                </a>
                            </div>
                            <img
                                v-else
                                :src="saleState.receipt_url"
                                alt="Receipt preview"
                                class="rounded-xl border border-gray-200"
                            >
                        </div>
                        <p v-else class="text-sm text-gray-500">No receipt uploaded.</p>
                    </div>
                </div>

                <div v-if="props.canEditPayment || props.canEditReceipt" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Update Payment</h3>

                    <form class="mt-4 space-y-4" method="POST" @submit="submitForm">
                        <input type="hidden" name="_token" :value="props.csrfToken">
                        <input v-if="removeReceipt" type="hidden" name="remove_receipt" value="1">

                        <div v-if="props.canEditPayment" class="space-y-3">
                            <label>
                                <span class="mb-1.5 block text-sm font-bold text-gray-700">Payment Status</span>
                                <select
                                    v-model="form.payment_status"
                                    name="payment_status"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                                    <option v-for="status in props.paymentStatusOptions" :key="status" :value="status">
                                        {{ statusLabel(status) }}
                                    </option>
                                </select>
                            </label>

                            <label>
                                <span class="mb-1.5 block text-sm font-bold text-gray-700">Amount Paid</span>
                                <input
                                    v-model="form.amount_paid"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="totalAmount"
                                    name="amount_paid"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                                <p v-if="fieldError('amount_paid')" class="mt-1 text-xs font-semibold text-rose-700">
                                    {{ fieldError('amount_paid') }}
                                </p>
                            </label>
                        </div>

                        <div v-if="props.canEditReceipt" class="space-y-3">
                            <label>
                                <span class="mb-1.5 block text-sm font-bold text-gray-700">Receipt Reference</span>
                                <input
                                    v-model="form.receipt_reference"
                                    type="text"
                                    name="receipt_reference"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                            </label>

                            <label>
                                <span class="mb-1.5 block text-sm font-bold text-gray-700">Notes</span>
                                <textarea
                                    v-model="form.notes"
                                    name="notes"
                                    rows="3"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                ></textarea>
                            </label>

                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                @click="receiptVisible = !receiptVisible"
                            >
                                {{ receiptVisible ? 'Hide Receipt' : 'Manage Receipt' }}
                            </button>

                            <div v-if="receiptVisible">
                                <ReceiptUpload
                                    :key="receiptKey"
                                    :current-receipt-url="saleState.receipt_url || ''"
                                    :error-message="fieldError('receipt')"
                                    @receipt-selected="removeReceipt = false"
                                    @receipt-removed="(removeExisting) => { removeReceipt = Boolean(removeExisting); }"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ isSubmitting ? 'Updating...' : 'Save Updates' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <ToastNotification
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            :action-label="toast.actionLabel"
            @close="toast.show = false"
        />
    </section>
</template>
