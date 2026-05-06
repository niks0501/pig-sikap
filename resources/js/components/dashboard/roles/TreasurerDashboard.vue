<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    userName: { type: String, default: 'User' },
    roleName: { type: String, default: 'User' },
    dataUrl: { type: String, required: true },
    routes: { type: Object, default: () => ({}) },
});

const loading = ref(true);
const error = ref(null);
const data = ref(null);

function formatCurrency(amount) {
    if (amount == null) return 'PHP 0.00';
    return 'PHP ' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function fetchData() {
    try {
        loading.value = true;
        error.value = null;
        const res = await fetch(props.dataUrl);
        if (!res.ok) throw new Error('Failed to load dashboard data');
        data.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 space-y-6">
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-gray-400 text-sm">Loading your workspace...</div>
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center">
            <p class="text-red-600 font-medium">Could not load dashboard</p>
            <p class="text-sm text-red-500 mt-1">{{ error }}</p>
            <button @click="fetchData" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">Retry</button>
        </div>

        <template v-else-if="data">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Good day, {{ data.user_name || props.userName }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-medium text-gray-600">
                            {{ props.roleName }}
                        </span>
                        <span class="text-xs text-gray-400">Treasurer Workspace</span>
                    </div>
                </div>
                <span v-if="data.last_updated" class="text-xs text-gray-400 shrink-0">Updated {{ new Date(data.last_updated).toLocaleTimeString() }}</span>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.kpis?.total_expenses) }}</p>
                            <p class="text-xs text-gray-500">Total Expenses</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.kpis?.total_sales) }}</p>
                            <p class="text-xs text-gray-500">Total Sales</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.kpis?.collected_revenue) }}</p>
                            <p class="text-xs text-gray-500">Collected</p>
                        </div>
                    </div>
                </div>

                <div :class="['rounded-2xl border shadow-sm p-4', data.kpis?.net_profit != null ? (data.kpis.net_profit >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50') : 'border-gray-100 bg-white']">
                    <div class="flex items-center gap-3">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', data.kpis?.net_profit != null ? (data.kpis.net_profit >= 0 ? 'bg-emerald-100' : 'bg-red-100') : 'bg-gray-100']">
                            <svg :class="['w-5 h-5', data.kpis?.net_profit != null ? (data.kpis.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600') : 'text-gray-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.kpis?.net_profit) }}</p>
                            <p class="text-xs text-gray-500">Net Profit/Loss</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.pending_withdrawals ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Pending Withdrawals</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Recent Expenses</h2>
                        <a v-if="routes.expensesIndex" :href="routes.expensesIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View All</a>
                    </div>
                    <div class="p-3">
                        <div v-if="!data.recent_expenses?.length" class="text-center py-6 text-sm text-gray-400">
                            No expenses recorded yet.
                        </div>
                        <div v-for="e in data.recent_expenses" :key="'exp-'+e.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ e.category || 'Expense' }}</p>
                                <p class="text-xs text-gray-400">{{ e.cycle_name || 'General' }}</p>
                            </div>
                            <span class="text-sm font-semibold text-violet-700">{{ formatCurrency(e.amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Recent Sales</h2>
                        <a v-if="routes.salesIndex" :href="routes.salesIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View All</a>
                    </div>
                    <div class="p-3">
                        <div v-if="!data.recent_sales?.length" class="text-center py-6 text-sm text-gray-400">
                            No sales recorded yet.
                        </div>
                        <div v-for="s in data.recent_sales" :key="'sale-'+s.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ s.cycle_name || 'Sale' }}</p>
                                <p class="text-xs text-gray-400">{{ new Date(s.created_at).toLocaleDateString() }}</p>
                            </div>
                            <span class="text-sm font-semibold text-blue-700">{{ formatCurrency(s.amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a v-if="routes.expensesCreate" :href="routes.expensesCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-violet-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">Add Expense</span>
                    </a>
                    <a v-if="routes.salesCreate" :href="routes.salesCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">Record Sale</span>
                    </a>
                    <a v-if="routes.profitabilityIndex" :href="routes.profitabilityIndex" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">View Profitability</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</template>
