<script setup>
import { computed, reactive, ref, watch } from 'vue';
import ToastNotification from '../common/ToastNotification.vue';
import ReceiptUpload from './ReceiptUpload.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    feedSubcategories: { type: Array, default: () => [] },
    fundSources: { type: Array, default: () => [] },
    formMode: { type: String, default: 'create' },
    expense: { type: Object, default: () => ({}) },
    suppliers: { type: Array, default: () => [] },
    canvasses: { type: Array, default: () => [] },
    resolutions: { type: Array, default: () => [] },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
    oldInput: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    flashStatus: { type: String, default: '' },
});

const isSubmitting = ref(false);
const removeReceipt = ref(false);
const receiptVisible = ref(props.formMode === 'edit' && Boolean(props.expense?.receipt_url));
const receiptKey = ref(0);
const localErrors = reactive({});
const toast = reactive({
    show: Boolean(props.flashStatus),
    type: 'success',
    title: props.flashStatus ? 'Saved' : '',
    message: props.flashStatus || '',
    actionLabel: '',
});

const today = computed(() => new Date().toISOString().split('T')[0]);
const isEditMode = computed(() => props.formMode === 'edit');

const form = reactive({
    item_name: String(props.oldInput.item_name ?? props.expense?.item_name ?? ''),
    category: String(props.oldInput.category ?? props.expense?.category ?? ''),
    feed_subcategory: String(props.oldInput.feed_subcategory ?? props.expense?.feed_subcategory ?? ''),
    quantity: String(props.oldInput.quantity ?? props.expense?.quantity ?? ''),
    unit: String(props.oldInput.unit ?? props.expense?.unit ?? ''),
    unit_cost: String(props.oldInput.unit_cost ?? props.expense?.unit_cost ?? ''),
    amount: String(props.oldInput.amount ?? props.expense?.amount ?? ''),
    expense_date: String(props.oldInput.expense_date ?? props.expense?.expense_date ?? today.value),
    receipt_reference: String(props.oldInput.receipt_reference ?? props.expense?.receipt_reference ?? ''),
    supplier_id: String(props.oldInput.supplier_id ?? props.expense?.supplier_id ?? ''),
    canvass_id: String(props.oldInput.canvass_id ?? props.expense?.canvass_id ?? ''),
    fund_source: String(props.oldInput.fund_source ?? props.expense?.fund_source ?? ''),
    approved_resolution_id: String(props.oldInput.approved_resolution_id ?? props.expense?.approved_resolution_id ?? ''),
    withdrawal_id: String(props.oldInput.withdrawal_id ?? props.expense?.withdrawal_id ?? ''),
    notes: String(props.oldInput.notes ?? props.expense?.notes ?? ''),
});

const showFeedSubcategory = computed(() => form.category === 'feed');

watch(() => form.category, () => {
    if (!showFeedSubcategory.value) {
        form.feed_subcategory = '';
    }
});

const hasStructuredInput = computed(() => {
    return form.quantity.trim() !== '' || form.unit.trim() !== '' || form.unit_cost.trim() !== '';
});

const structuredTotal = computed(() => {
    const q = Number(form.quantity);
    const uc = Number(form.unit_cost);
    if (!Number.isFinite(q) || !Number.isFinite(uc) || q <= 0 || uc <= 0) return 0;
    return Math.round(q * uc * 100) / 100;
});

const usesStructuredAmount = computed(() => structuredTotal.value > 0 && form.unit.trim() !== '');

watch(structuredTotal, (total) => {
    if (total > 0) form.amount = total.toFixed(2);
});

const validations = computed(() => ({
    item_name: form.item_name.trim() !== '',
    category: form.category !== '',
    feed_subcategory: !showFeedSubcategory.value || form.feed_subcategory !== '',
    quantity: !hasStructuredInput.value || Number(form.quantity) > 0,
    unit: !hasStructuredInput.value || form.unit.trim() !== '',
    unit_cost: !hasStructuredInput.value || Number(form.unit_cost) > 0,
    amount: usesStructuredAmount.value || Number(form.amount) > 0,
    expense_date: form.expense_date !== '' && form.expense_date <= today.value,
    notes: form.notes.trim().length > 0,
}));

const clientSideBlocked = computed(() => !Object.values(validations.value).every(Boolean));

const fieldError = (field) => {
    const val = localErrors?.[field] ?? props.errors?.[field];
    if (Array.isArray(val)) return val[0] || '';
    return typeof val === 'string' ? val : '';
};

const fieldReady = (field) => validations.value[field] && !fieldError(field);

const clearLocalErrors = () => Object.keys(localErrors).forEach(k => delete localErrors[k]);

const formatAmount = (amount) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount || 0));

const formatCategoryLabel = (c) => c?.charAt(0).toUpperCase() + c?.slice(1) || '';
const formatFeedLabel = (c) => c ? c.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('-') : '';
const formatFundLabel = (c) => c ? c.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : '';

const submitLabel = computed(() => {
    if (isSubmitting.value) return isEditMode.value ? 'Updating...' : 'Saving...';
    return isEditMode.value ? 'Update Expense' : 'Save Expense';
});

const cancelRoute = computed(() => {
    if (isEditMode.value && props.expense?.id) {
        return props.routes.show?.replace('_ID_', props.expense.id) || props.routes.index;
    }
    return props.routes.index;
});

const showToast = (type, title, message, actionLabel = '') => {
    toast.type = type; toast.title = title; toast.message = message; toast.actionLabel = actionLabel; toast.show = true;
};

const submitForm = async (event) => {
    if (clientSideBlocked.value || isSubmitting.value) { event.preventDefault(); return; }
    if (isEditMode.value) { isSubmitting.value = true; return; }
    event.preventDefault();
    isSubmitting.value = true;
    clearLocalErrors();

    try {
        const formData = new FormData(event.currentTarget);
        const url = isEditMode.value
            ? props.routes.update?.replace('_ID_', props.expense.id)
            : props.routes.store;

        const response = await fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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

        showToast('success', 'Saved', isEditMode.value ? 'Expense updated successfully.' : 'Expense saved successfully.', 'View record');
        window.setTimeout(() => {
            window.location.href = data.redirect_url || props.routes.index;
        }, 900);
    } catch {
        showToast('error', 'Connection problem', 'The expense was not saved. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const handleToastAction = () => {
    window.location.href = props.routes.index;
};
</script>

<template>
    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8">
        <ToastNotification :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" :action-label="toast.actionLabel" @close="toast.show = false" @action="handleToastAction" />

        <form
            :action="isEditMode ? props.routes.update?.replace('_ID_', props.expense.id) : props.routes.store"
            method="POST" enctype="multipart/form-data" class="space-y-6" @submit="submitForm"
        >
            <input type="hidden" name="_token" :value="props.csrfToken">
            <input v-if="isEditMode" type="hidden" name="_method" value="PUT">
            <input v-if="removeReceipt" type="hidden" name="remove_receipt" value="1">

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Item Name *
                        <span v-if="fieldReady('item_name')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <input
                        v-model="form.item_name" type="text" name="item_name" required maxlength="255"
                        :class="['w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('item_name') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']"
                        placeholder="e.g. Hog Grower Pellets 50kg">
                    <p v-if="fieldError('item_name')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('item_name') }}</p>
                </label>

                <label>
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Category *
                        <span v-if="fieldReady('category')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <select v-model="form.category" name="category" required
                        :class="['w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('category') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']">
                        <option value="" disabled>Select category...</option>
                        <option v-for="c in props.categories" :key="c" :value="c">{{ formatCategoryLabel(c) }}</option>
                    </select>
                    <p v-if="fieldError('category')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('category') }}</p>
                </label>

                <label v-if="showFeedSubcategory">
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Feed Subcategory *
                        <span v-if="fieldReady('feed_subcategory')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <select v-model="form.feed_subcategory" name="feed_subcategory" required
                        :class="['w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('feed_subcategory') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']">
                        <option value="" disabled>Select stage...</option>
                        <option v-for="fs in props.feedSubcategories" :key="fs" :value="fs">{{ formatFeedLabel(fs) }}</option>
                    </select>
                    <p v-if="fieldError('feed_subcategory')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('feed_subcategory') }}</p>
                </label>

                <div class="sm:col-span-2 rounded-2xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-[#0a5a48]">Optional Quantity Computation</p>
                            <p class="mt-1 text-xs text-[#0a5a48]/80">For entries with Qty, Unit, and Unit Cost. Leave blank for lump-sum.</p>
                        </div>
                        <p class="rounded-xl bg-white px-3 py-2 text-sm font-black text-[#0c6d57]">
                            Total: {{ formatAmount(usesStructuredAmount ? structuredTotal : form.amount) }}
                        </p>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <label>
                            <span class="mb-1.5 text-sm font-bold text-gray-700">Quantity</span>
                            <input v-model="form.quantity" type="number" name="quantity" step="0.01" min="0.01" max="999999.99"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="e.g. 2">
                        </label>
                        <label>
                            <span class="mb-1.5 text-sm font-bold text-gray-700">Unit</span>
                            <input v-model="form.unit" type="text" name="unit" maxlength="50"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="sack, kilo, bottle">
                        </label>
                        <label>
                            <span class="mb-1.5 text-sm font-bold text-gray-700">Unit Cost</span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Php</span>
                                <input v-model="form.unit_cost" type="number" name="unit_cost" step="0.01" min="0.01" max="999999.99"
                                    class="w-full rounded-xl border border-gray-200 py-3 pl-10 pr-4 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="0.00">
                            </div>
                        </label>
                    </div>
                </div>

                <label>
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Total Amount *
                        <span v-if="fieldReady('amount')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Php</span>
                        <input v-model="form.amount" type="number" name="amount" step="0.01" min="0.01" max="999999.99"
                            :readonly="usesStructuredAmount" :required="!hasStructuredInput"
                            :class="['w-full rounded-xl border py-3 pl-10 pr-4 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                                usesStructuredAmount ? 'cursor-not-allowed bg-gray-100 text-gray-600' : 'bg-white',
                                fieldError('amount') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']" placeholder="0.00">
                    </div>
                    <p v-if="usesStructuredAmount" class="mt-1.5 text-xs font-medium text-[#0a5a48]">Auto-computed from Quantity x Unit Cost.</p>
                    <p v-if="fieldError('amount')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('amount') }}</p>
                </label>

                <label>
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                        Expense Date *
                        <span v-if="fieldReady('expense_date')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                    </span>
                    <input v-model="form.expense_date" type="date" name="expense_date" :max="today" required
                        :class="['w-full rounded-xl border bg-white px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-1',
                            fieldError('expense_date') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']">
                    <p v-if="fieldError('expense_date')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('expense_date') }}</p>
                </label>

                <label>
                    <span class="mb-1.5 text-sm font-bold text-gray-700">Receipt Reference</span>
                    <input v-model="form.receipt_reference" type="text" name="receipt_reference" maxlength="255"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Invoice/OR number">
                </label>

                <label>
                    <span class="mb-1.5 text-sm font-bold text-gray-700">Supplier</span>
                    <select v-model="form.supplier_id" name="supplier_id"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">None</option>
                        <option v-for="s in props.suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 text-sm font-bold text-gray-700">Canvass</span>
                    <select v-model="form.canvass_id" name="canvass_id"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">None</option>
                        <option v-for="c in props.canvasses" :key="c.id" :value="String(c.id)">{{ c.title }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 text-sm font-bold text-gray-700">Fund Source</span>
                    <select v-model="form.fund_source" name="fund_source"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">None</option>
                        <option v-for="fs in props.fundSources" :key="fs" :value="fs">{{ formatFundLabel(fs) }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 text-sm font-bold text-gray-700">Approved Resolution</span>
                    <select v-model="form.approved_resolution_id" name="approved_resolution_id"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">None</option>
                        <option v-for="r in props.resolutions" :key="r.id" :value="String(r.id)">{{ r.resolution_number }} - {{ r.title }}</option>
                    </select>
                </label>

                <div class="sm:col-span-2">
                    <label>
                        <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-gray-700">
                            Notes *
                            <span v-if="fieldReady('notes')" class="ml-auto text-xs font-bold text-[#0c6d57]">Ready</span>
                        </span>
                        <textarea v-model="form.notes" name="notes" rows="3" required maxlength="1000"
                            :class="['w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-1',
                                fieldError('notes') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : 'border-gray-200 focus:border-[#0c6d57] focus:ring-[#0c6d57]/20']"
                            placeholder="Enter expense description or notes..."></textarea>
                        <div class="mt-1.5 flex items-center justify-between">
                            <p v-if="fieldError('notes')" class="text-xs font-semibold text-rose-700">{{ fieldError('notes') }}</p>
                            <p class="ml-auto text-xs text-gray-500">{{ form.notes.length }}/1000</p>
                        </div>
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <button type="button"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50 sm:w-auto"
                        @click="receiptVisible = !receiptVisible">
                        {{ receiptVisible ? 'Hide Receipt' : (props.expense?.receipt_url ? 'Manage Receipt' : 'Attach Receipt') }}
                    </button>
                    <div v-if="receiptVisible" class="mt-3">
                        <ReceiptUpload :key="receiptKey" :current-receipt-url="props.expense?.receipt_url || ''" :error-message="fieldError('receipt')" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                <button type="submit" :disabled="isSubmitting || clientSideBlocked"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                    {{ submitLabel }}
                </button>
                <a :href="cancelRoute"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                    Cancel
                </a>
            </div>
        </form>
    </section>
</template>
