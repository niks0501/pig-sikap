<script setup>
import { computed, reactive, ref } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';

const props = defineProps({
    sale: { type: Object, required: true },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
    canEditPayment: { type: Boolean, default: false },
    paymentStatusOptions: { type: Array, default: () => [] },
});

const saleState = reactive({ ...props.sale });
const toast = reactive({ show: false, type: 'success', title: '', message: '' });
const showPaymentModal = ref(false);
const showSendModal = ref(false);
const isUpdatingPayment = ref(false);
const isSendingReceipt = ref(false);
const localErrors = reactive({});

const paymentForm = reactive({
    payment_status: String(saleState.payment_status || 'paid'),
    amount_paid: String(saleState.amount_paid || ''),
    receipt_reference: String(saleState.receipt_reference || ''),
    notes: String(saleState.notes || ''),
});

const sendForm = reactive({
    email: String(saleState.buyer?.email || saleState.digital_receipt_email || ''),
});

const totalAmount = computed(() => Number(saleState.amount || 0));
const balanceAmount = computed(() => Math.max(Number(saleState.amount || 0) - Number(saleState.amount_paid || 0), 0));

const formatAmount = (amount) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount || 0));
const formatDate = (dateStr) => dateStr ? new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' }) : 'N/A';

const statusClass = (status) => ({
    paid: 'bg-emerald-100 text-emerald-800',
    partial: 'bg-amber-100 text-amber-800',
    pending: 'bg-rose-100 text-rose-800',
    sent: 'bg-emerald-100 text-emerald-800',
    failed: 'bg-rose-100 text-rose-800',
    not_sent: 'bg-gray-100 text-gray-700',
}[status] || 'bg-gray-100 text-gray-700');

const showToast = (type, title, message) => {
    toast.type = type;
    toast.title = title;
    toast.message = message;
    toast.show = true;
};

const openPreview = () => {
    window.open(props.routes.receiptPreview.replace('_ID_', saleState.id), '_blank');
};

const downloadReceipt = () => {
    window.location.href = props.routes.receiptDownload.replace('_ID_', saleState.id);
};

const updatePayment = async () => {
    isUpdatingPayment.value = true;
    Object.keys(localErrors).forEach((key) => delete localErrors[key]);
    try {
        const payload = new FormData();
        payload.append('_method', 'PUT');
        payload.append('_token', props.csrfToken);
        payload.append('payment_status', paymentForm.payment_status);
        payload.append('amount_paid', paymentForm.amount_paid);
        payload.append('receipt_reference', paymentForm.receipt_reference);
        payload.append('notes', paymentForm.notes);

        const response = await fetch(props.routes.update.replace('_ID_', saleState.id), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: payload,
        });
        const data = await response.json().catch(() => ({}));
        if (response.status === 422) {
            Object.assign(localErrors, data.errors || {});
            return;
        }
        if (!response.ok) {
            showToast('error', 'Update failed', data.message || 'Please try again.');
            return;
        }
        Object.assign(saleState, data.sale || {});
        showPaymentModal.value = false;
        showToast('success', 'Updated', 'Payment details updated.');
    } finally {
        isUpdatingPayment.value = false;
    }
};

const sendReceipt = async () => {
    if (!sendForm.email.trim()) {
        localErrors.email = ['Email is required.'];
        return;
    }

    isSendingReceipt.value = true;
    Object.keys(localErrors).forEach((key) => delete localErrors[key]);
    try {
        const response = await fetch(props.routes.receiptSend.replace('_ID_', saleState.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ email: sendForm.email.trim() }),
        });

        const data = await response.json().catch(() => ({}));
        if (response.status === 422) {
            Object.assign(localErrors, data.errors || {});
            if (!data.errors) localErrors.email = [data.message || 'Could not send receipt.'];
            return;
        }
        if (!response.ok) {
            showToast('error', 'Send failed', data.message || 'Please try again.');
            return;
        }

        Object.assign(saleState, data.sale || {});
        showSendModal.value = false;
        showToast('success', 'Sent', 'Digital receipt sent successfully.');
    } finally {
        isSendingReceipt.value = false;
    }
};
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sale #{{ saleState.id }}</h1>
                <p class="mt-1 text-sm text-gray-500">Recorded on {{ formatDate(saleState.sale_date) }}</p>
            </div>
            <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Back to Sales</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Buyer Information</h3>
                    <p class="mt-3 text-lg font-semibold text-gray-900">{{ saleState.buyer?.name || 'Unknown buyer' }}</p>
                    <p class="text-sm text-gray-600">{{ saleState.buyer?.email || 'No email provided' }}</p>
                    <p class="text-sm text-gray-600">{{ saleState.buyer?.contact_number || 'No contact number provided' }}</p>
                    <p class="text-sm text-gray-600">{{ saleState.buyer?.address || 'No address provided' }}</p>
                </div>

                <div class="rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-[#0c6d57]">Sale Summary</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatAmount(saleState.amount) }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm text-gray-700">
                        <p>Batch: <span class="font-semibold">{{ saleState.cycle?.batch_code || 'N/A' }}</span></p>
                        <p>Method: <span class="font-semibold">{{ saleState.sale_method === 'live_weight' ? 'Live Weight' : 'Per Head' }}</span></p>
                        <p>Pigs Sold: <span class="font-semibold">{{ saleState.pigs_sold }}</span></p>
                        <p>Sale Date: <span class="font-semibold">{{ formatDate(saleState.sale_date) }}</span></p>
                    </div>
                    <div v-if="props.routes.profitabilityShow" class="mt-4 rounded-xl border border-[#0c6d57]/20 bg-white p-4">
                        <p class="text-sm font-bold text-gray-900">Next step: review profitability</p>
                        <p class="mt-1 text-sm text-gray-600">This sale now feeds the cycle profitability and profit-sharing computation.</p>
                        <a :href="props.routes.profitabilityShow" class="mt-3 inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] sm:w-auto">
                            Review Profitability
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Payment Summary</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-gray-50 p-3"><p class="text-xs text-gray-500">Amount Paid</p><p class="text-lg font-semibold">{{ formatAmount(saleState.amount_paid) }}</p></div>
                        <div class="rounded-xl bg-gray-50 p-3"><p class="text-xs text-gray-500">Balance</p><p class="text-lg font-semibold text-amber-700">{{ formatAmount(balanceAmount) }}</p></div>
                        <div class="rounded-xl bg-gray-50 p-3"><p class="text-xs text-gray-500">Payment Status</p><span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold mt-1', statusClass(saleState.payment_status)]">{{ saleState.payment_status }}</span></div>
                    </div>
                    <button v-if="props.canEditPayment" type="button" class="mt-4 inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white" @click="showPaymentModal = true">Edit Payment</button>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Notes</h3>
                    <p class="mt-3 text-sm text-gray-700">{{ saleState.notes || 'No notes added.' }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Digital Receipt</h3>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <p>Receipt Number: <span class="font-semibold">{{ saleState.digital_receipt_number || 'Will be generated on preview' }}</span></p>
                        <p>Buyer: <span class="font-semibold">{{ saleState.buyer?.name || 'N/A' }}</span></p>
                        <p>Sale Date: <span class="font-semibold">{{ formatDate(saleState.sale_date) }}</span></p>
                        <p>Total Amount: <span class="font-semibold">{{ formatAmount(totalAmount) }}</span></p>
                        <p>Amount Paid: <span class="font-semibold">{{ formatAmount(saleState.amount_paid) }}</span></p>
                        <p>Balance: <span class="font-semibold">{{ formatAmount(balanceAmount) }}</span></p>
                        <p>Last Email Used: <span class="font-semibold">{{ saleState.digital_receipt_email || saleState.buyer?.email || 'N/A' }}</span></p>
                        <p>Receipt Status: <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', statusClass(saleState.digital_receipt_status || 'not_sent')]">{{ saleState.digital_receipt_status || 'not_sent' }}</span></p>
                    </div>
                    <div class="mt-4 grid gap-2">
                        <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700" @click="openPreview">Preview Receipt</button>
                        <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700" @click="downloadReceipt">Download PDF</button>
                        <button type="button" class="rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white" @click="showSendModal = true">Send to Email</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4" @keydown.esc="showPaymentModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">Edit Payment</h3>
                <div class="mt-4 space-y-3">
                    <label class="block"><span class="mb-1.5 block text-sm font-bold text-gray-700">Payment Status</span><select v-model="paymentForm.payment_status" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm"><option v-for="status in props.paymentStatusOptions" :key="status" :value="status">{{ status }}</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-bold text-gray-700">Amount Paid</span><input v-model="paymentForm.amount_paid" type="number" step="0.01" min="0" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-bold text-gray-700">Receipt Reference</span><input v-model="paymentForm.receipt_reference" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-bold text-gray-700">Notes</span><textarea v-model="paymentForm.notes" rows="3" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm"></textarea></label>
                </div>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <button type="button" :disabled="isUpdatingPayment" class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-70" @click="updatePayment">{{ isUpdatingPayment ? 'Saving...' : 'Save Payment' }}</button>
                    <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700" @click="showPaymentModal = false">Cancel</button>
                </div>
            </div>
        </div>

        <div v-if="showSendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4" @keydown.esc="showSendModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">Send Digital Receipt</h3>
                <p class="mt-2 text-sm text-gray-600">Enter buyer email to send the receipt.</p>
                <label class="mt-4 block">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Buyer Email</span>
                    <input v-model="sendForm.email" type="email" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm" placeholder="name@example.com">
                    <p v-if="localErrors.email" class="mt-1 text-xs font-semibold text-rose-700">{{ Array.isArray(localErrors.email) ? localErrors.email[0] : localErrors.email }}</p>
                </label>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <button type="button" :disabled="isSendingReceipt" class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-70" @click="sendReceipt">{{ isSendingReceipt ? 'Sending...' : 'Send Receipt' }}</button>
                    <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700" @click="showSendModal = false">Cancel</button>
                </div>
            </div>
        </div>

        <ToastNotification :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" @close="toast.show = false" />
    </section>
</template>
