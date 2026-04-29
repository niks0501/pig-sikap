<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    sales: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_amount: 0,
            total_paid: 0,
            total_balance: 0,
            total_pigs_sold: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            payment_status: '',
            sale_method: '',
            cycle_id: '',
            date_from: '',
            date_to: '',
        }),
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    paymentStatusOptions: {
        type: Array,
        default: () => [],
    },
    saleMethodOptions: {
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
});

const filters = reactive({
    search: props.filters.search || '',
    payment_status: props.filters.payment_status || '',
    sale_method: props.filters.sale_method || '',
    cycle_id: props.filters.cycle_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const hasSales = computed(() => Array.isArray(props.sales) && props.sales.length > 0);

const totalAmount = computed(() => Number(props.summary.total_amount || 0));
const totalBalance = computed(() => Number(props.summary.total_balance || 0));
const totalPigsSold = computed(() => Number(props.summary.total_pigs_sold || 0));

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

const saleMethodLabel = (method) => {
    if (method === 'live_weight') return 'Live Weight';
    if (method === 'per_head') return 'Per Head';
    return 'Unknown';
};

const buildQueryString = (page = null) => {
    const params = new URLSearchParams();

    if (filters.search) params.set('search', filters.search);
    if (filters.payment_status) params.set('payment_status', filters.payment_status);
    if (filters.sale_method) params.set('sale_method', filters.sale_method);
    if (filters.cycle_id) params.set('cycle_id', filters.cycle_id);
    if (filters.date_from) params.set('date_from', filters.date_from);
    if (filters.date_to) params.set('date_to', filters.date_to);
    if (page) params.set('page', String(page));

    return params.toString();
};

const applyFilters = () => {
    const queryString = buildQueryString();
    const url = queryString ? `${props.routes.index}?${queryString}` : props.routes.index;

    window.location.href = url;
};

const clearFilters = () => {
    filters.search = '';
    filters.payment_status = '';
    filters.sale_method = '';
    filters.cycle_id = '';
    filters.date_from = '';
    filters.date_to = '';
};

const clearAndApply = () => {
    clearFilters();
    applyFilters();
};

const pageUrl = (page) => {
    const queryString = buildQueryString(page);
    return queryString ? `${props.routes.index}?${queryString}` : props.routes.index;
};

const formatBalance = (sale) => {
    const amount = Number(sale?.amount || 0);
    const paid = Number(sale?.amount_paid || 0);
    return Math.max(amount - paid, 0);
};
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sales Records</h1>
                <p class="mt-1 text-sm text-gray-500">Track pig sales, buyers, and payment statuses.</p>
            </div>
            <a
                :href="props.routes.create"
                class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0a5a48]"
            >
                Record Sale
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Sales</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ formatAmount(totalAmount) }}</p>
                <p class="mt-1 text-xs text-gray-500">Filtered entries</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pigs Sold</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ totalPigsSold }}</p>
                <p class="mt-1 text-xs text-gray-500">Heads recorded</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending Balance</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ formatAmount(totalBalance) }}</p>
                <p class="mt-1 text-xs text-gray-500">Unpaid amount</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-6">
                <label class="md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Buyer or batch"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                    <select
                        v-model="filters.payment_status"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                        <option value="">All</option>
                        <option v-for="status in props.paymentStatusOptions" :key="status" :value="status">
                            {{ statusLabel(status) }}
                        </option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Method</span>
                    <select
                        v-model="filters.sale_method"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                        <option value="">All</option>
                        <option v-for="method in props.saleMethodOptions" :key="method" :value="method">
                            {{ saleMethodLabel(method) }}
                        </option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Batch</span>
                    <select
                        v-model="filters.cycle_id"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                        <option value="">All</option>
                        <option v-for="cycle in props.cycles" :key="cycle.id" :value="String(cycle.id)">
                            {{ cycle.batch_code }}
                        </option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">From</span>
                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">To</span>
                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                </label>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    @click="clearAndApply"
                >
                    Clear
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0a5a48]"
                    @click="applyFilters"
                >
                    Apply Filters
                </button>
            </div>
        </div>

        <div v-if="!hasSales" class="rounded-2xl border border-dashed border-gray-200 bg-white p-6 text-center">
            <p class="text-sm font-semibold text-gray-800">No sales recorded yet.</p>
            <p class="mt-1 text-sm text-gray-500">Tap the button below to log the first transaction.</p>
            <a
                :href="props.routes.create"
                class="mt-4 inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0a5a48]"
            >
                Record Sale
            </a>
        </div>

        <div v-else>
            <div class="grid gap-4 sm:hidden">
                <div
                    v-for="sale in props.sales"
                    :key="sale.id"
                    class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ sale.buyer?.name || 'Unknown buyer' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ sale.cycle?.batch_code || 'Unknown batch' }} • {{ saleMethodLabel(sale.sale_method) }}</p>
                        </div>
                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold', statusBadgeClass(sale.payment_status)]">
                            {{ statusLabel(sale.payment_status) }}
                        </span>
                    </div>
                    <div class="mt-3 flex items-end justify-between border-t border-gray-100 pt-3">
                        <div>
                            <p class="text-xs text-gray-500">{{ formatDate(sale.sale_date) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ sale.pigs_sold }} heads</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">{{ formatAmount(sale.amount) }}</p>
                            <p class="text-xs text-amber-700">Balance {{ formatAmount(formatBalance(sale)) }}</p>
                        </div>
                    </div>
                    <a
                        :href="props.routes.show?.replace('_ID_', sale.id)"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57]/10 px-4 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/20"
                    >
                        View Transaction
                    </a>
                </div>
            </div>

            <div class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm sm:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Buyer</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Batch</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-6 py-4" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="sale in props.sales" :key="sale.id" class="transition hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(sale.sale_date) }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                {{ sale.buyer?.name || 'Unknown buyer' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ sale.cycle?.batch_code || 'Unknown batch' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ saleMethodLabel(sale.sale_method) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ formatAmount(sale.amount) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-amber-700">{{ formatAmount(formatBalance(sale)) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold', statusBadgeClass(sale.payment_status)]">
                                    {{ statusLabel(sale.payment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a
                                    :href="props.routes.show?.replace('_ID_', sale.id)"
                                    class="font-semibold text-[#0c6d57] hover:text-[#0a5a48]"
                                >
                                    Details
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col items-center justify-between gap-3 text-sm text-gray-500 sm:flex-row">
                <span>
                    Showing {{ props.sales.length }} of {{ props.pagination.total }} sales
                </span>
                <div class="flex gap-2">
                    <a
                        v-if="props.pagination.current_page > 1"
                        :href="pageUrl(props.pagination.current_page - 1)"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Prev
                    </a>
                    <a
                        v-if="props.pagination.current_page < props.pagination.last_page"
                        :href="pageUrl(props.pagination.current_page + 1)"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Next
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
