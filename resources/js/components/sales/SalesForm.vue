<script setup>
import { computed, reactive, ref, watch } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';

const props = defineProps({
    cycles: { type: Array, default: () => [] },
    buyers: { type: Array, default: () => [] },
    selectedCycleId: { type: [Number, String], default: '' },
    paymentStatusOptions: { type: Array, default: () => [] },
    saleMethodOptions: { type: Array, default: () => [] },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
    oldInput: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    flashStatus: { type: String, default: '' },
});

const isSubmitting = ref(false);
const localErrors = reactive({});
const submitIntent = ref('save');
const savedSale = ref(null);
const showSuccessModal = ref(false);
const sendEmail = ref('');
const isSendingReceipt = ref(false);
const sendError = ref('');

const toast = reactive({
    show: Boolean(props.flashStatus),
    type: 'success',
    title: props.flashStatus ? 'Saved' : '',
    message: props.flashStatus || '',
    actionLabel: '',
});

const today = computed(() => new Date().toISOString().split('T')[0]);

const form = reactive({
    batch_id: String(props.oldInput.batch_id ?? props.selectedCycleId ?? ''),
    buyer_id: String(props.oldInput.buyer_id ?? ''),
    buyer_name: String(props.oldInput.buyer_name ?? ''),
    buyer_email: String(props.oldInput.buyer_email ?? ''),
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

const selectedBuyer = computed(() => {
    const id = Number(form.buyer_id || 0);
    if (!id) return null;
    return props.buyers.find((buyer) => Number(buyer.id) === id) ?? null;
});

const usingExistingBuyer = computed(() => Boolean(form.buyer_id));

watch(() => form.buyer_id, () => {
    if (!usingExistingBuyer.value) return;
    const buyer = selectedBuyer.value;
    if (!buyer) return;
    form.buyer_name = String(buyer.name || '');
    form.buyer_email = String(buyer.email || '');
    form.buyer_contact_number = String(buyer.contact_number || '');
    form.buyer_address = String(buyer.address || '');
});

const totalAmount = computed(() => {
    if (form.sale_method === 'live_weight') {
        return Math.round(Number(form.live_weight_kg || 0) * Number(form.price_per_kg || 0) * 100) / 100;
    }
    return Math.round(Number(form.pigs_sold || 0) * Number(form.price_per_head || 0) * 100) / 100;
});

const balanceAmount = computed(() => Math.max(totalAmount.value - Number(form.amount_paid || 0), 0));

watch(() => form.payment_status, (status) => {
    if (status === 'paid') {
        form.amount_paid = totalAmount.value > 0 ? totalAmount.value.toFixed(2) : '';
    } else if (status === 'pending') {
        form.amount_paid = '0';
    }
});

watch(totalAmount, (value) => {
    if (form.payment_status === 'paid') {
        form.amount_paid = value > 0 ? value.toFixed(2) : '';
    }
});

const formatAmount = (amount) => new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
}).format(Number(amount || 0));

const statusLabel = (status) => {
    if (!status) return 'Unknown';
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const fieldError = (field) => {
    const value = localErrors[field] ?? props.errors[field];
    if (Array.isArray(value)) return value[0] || '';
    return typeof value === 'string' ? value : '';
};

const clearLocalErrors = () => {
    Object.keys(localErrors).forEach((key) => delete localErrors[key]);
};

const showToast = (type, title, message, actionLabel = '') => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.actionLabel = actionLabel;
    toast.show = true;
};

const clientSideBlocked = computed(() => {
    if (!Number(form.batch_id || 0)) return true;
    if (!form.buyer_name.trim()) return true;
    if (Number(form.pigs_sold || 0) < 1) return true;
    if (!form.sale_date || form.sale_date > today.value) return true;
    if (totalAmount.value <= 0) return true;
    const paid = Number(form.amount_paid || 0);
    if (paid < 0 || paid > totalAmount.value) return true;
    if (form.payment_status === 'paid' && Math.abs(paid - totalAmount.value) > 0.01) return true;
    if (form.payment_status === 'pending' && paid > 0) return true;
    if (form.payment_status === 'partial' && (paid <= 0 || paid >= totalAmount.value)) return true;
    return false;
});

const submitForm = async (event) => {
    submitIntent.value = event.submitter?.dataset?.intent || 'save';
    if (isSubmitting.value || clientSideBlocked.value) {
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

        savedSale.value = data.sale || null;
        sendEmail.value = data.sale?.buyer?.email || form.buyer_email || '';

        if (submitIntent.value === 'another') {
            window.location.href = props.routes.create || props.routes.index;
            return;
        }

        showSuccessModal.value = true;
    } catch {
        showToast('error', 'Connection problem', 'The sale was not saved. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const viewSale = () => {
    if (!savedSale.value) {
        window.location.href = props.routes.index;
        return;
    }
    window.location.href = `/sales/${savedSale.value.id}`;
};

const sendLater = () => {
    showSuccessModal.value = false;
    viewSale();
};

const sendNow = async () => {
    if (!savedSale.value || !sendEmail.value.trim()) {
        sendError.value = 'Email is required to send the digital receipt.';
        return;
    }

    isSendingReceipt.value = true;
    sendError.value = '';

    try {
        const response = await fetch(props.routes.receiptSend.replace('_ID_', savedSale.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ email: sendEmail.value.trim() }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            sendError.value = data.message || 'Digital receipt could not be sent.';
            return;
        }

        showSuccessModal.value = false;
        showToast('success', 'Receipt sent', 'Digital receipt sent successfully.');
        viewSale();
    } catch {
        sendError.value = 'Digital receipt could not be sent. Please check your internet and try again.';
    } finally {
        isSendingReceipt.value = false;
    }
};
</script>

<template>
    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8">
        <ToastNotification :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" :action-label="toast.actionLabel" @close="toast.show = false" />

        <form class="space-y-6" method="POST" @submit="submitForm">
            <input type="hidden" name="_token" :value="props.csrfToken">

            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">1. Buyer Information</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Select Existing Buyer</span>
                        <select v-model="form.buyer_id" name="buyer_id" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <option value="">Create new buyer</option>
                            <option v-for="buyer in props.buyers" :key="buyer.id" :value="String(buyer.id)">{{ buyer.name }}</option>
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Buyer Name *</span>
                        <input v-model="form.buyer_name" name="buyer_name" :readonly="usingExistingBuyer" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                        <p v-if="fieldError('buyer_name')" class="mt-1 text-xs font-semibold text-rose-700">{{ fieldError('buyer_name') }}</p>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Buyer Email</span>
                        <input v-model="form.buyer_email" name="buyer_email" type="email" :readonly="usingExistingBuyer" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" placeholder="name@example.com">
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Contact Number</span>
                        <input v-model="form.buyer_contact_number" name="buyer_contact_number" :readonly="usingExistingBuyer" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Address</span>
                        <input v-model="form.buyer_address" name="buyer_address" :readonly="usingExistingBuyer" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
                    </label>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">2. Sale Details</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Batch *</span>
                        <select v-model="form.batch_id" name="batch_id" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                            <option value="" disabled>Select batch...</option>
                            <option v-for="cycle in props.cycles" :key="cycle.id" :value="String(cycle.id)">{{ cycle.batch_code }} ({{ cycle.current_count }} heads)</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Sale Date *</span>
                        <input v-model="form.sale_date" name="sale_date" type="date" :max="today" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Sale Method *</span>
                        <select v-model="form.sale_method" name="sale_method" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
                            <option v-for="method in props.saleMethodOptions" :key="method" :value="method">{{ method === 'live_weight' ? 'Live Weight' : 'Per Head' }}</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Pigs Sold *</span>
                        <input v-model="form.pigs_sold" name="pigs_sold" type="number" min="1" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                    <label v-if="form.sale_method === 'live_weight'">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Live Weight (kg) *</span>
                        <input v-model="form.live_weight_kg" name="live_weight_kg" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                    <label v-if="form.sale_method === 'live_weight'">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Price per kg *</span>
                        <input v-model="form.price_per_kg" name="price_per_kg" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                    <label v-if="form.sale_method === 'per_head'" class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Price per Head *</span>
                        <input v-model="form.price_per_head" name="price_per_head" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                </div>
                <div class="rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#0c6d57]">Computed Total Amount</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ formatAmount(totalAmount) }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">3. Payment and Receipt Delivery</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Payment Status *</span>
                        <select v-model="form.payment_status" name="payment_status" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
                            <option v-for="status in props.paymentStatusOptions" :key="status" :value="status">{{ statusLabel(status) }}</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Amount Paid *</span>
                        <input v-model="form.amount_paid" name="amount_paid" type="number" step="0.01" min="0" :max="totalAmount" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" required>
                    </label>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Balance</p>
                        <p class="mt-2 text-xl font-bold text-amber-700">{{ formatAmount(balanceAmount) }}</p>
                    </div>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Receipt Reference</span>
                        <input v-model="form.receipt_reference" name="receipt_reference" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
                    </label>
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Notes</span>
                        <textarea v-model="form.notes" name="notes" rows="3" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm"></textarea>
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                <button type="submit" data-intent="save" :disabled="isSubmitting || clientSideBlocked" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white disabled:opacity-70 sm:w-auto">
                    {{ isSubmitting ? 'Saving Sale...' : 'Save Sale' }}
                </button>
                <button type="submit" data-intent="another" :disabled="isSubmitting || clientSideBlocked" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 disabled:opacity-70 sm:w-auto">
                    Save and Add Another
                </button>
                <a :href="props.routes.index" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 sm:w-auto">Cancel</a>
            </div>
        </form>

        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4" @keydown.esc="showSuccessModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">Sale recorded successfully. Send digital receipt to buyer?</h3>
                <p class="mt-2 text-sm text-gray-600">You can send it now or do it later from the sale details page.</p>
                <label class="mt-4 block">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Buyer Email</span>
                    <input v-model="sendEmail" type="email" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm" placeholder="name@example.com">
                </label>
                <p v-if="sendError" class="mt-2 text-sm font-semibold text-rose-700">{{ sendError }}</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-3">
                    <button type="button" :disabled="isSendingReceipt" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-70" @click="sendNow">
                        {{ isSendingReceipt ? 'Sending...' : 'Send Now' }}
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700" @click="sendLater">Send Later</button>
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700" @click="viewSale">View Sale</button>
                </div>
            </div>
        </div>
    </section>
</template>
