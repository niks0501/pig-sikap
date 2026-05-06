<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import DashboardFilterBar from './DashboardFilterBar.vue';
import DashboardKpiCards from './DashboardKpiCards.vue';
import DashboardAlerts from './DashboardAlerts.vue';
import DashboardActivityFeed from './DashboardActivityFeed.vue';
import PigStatusDoughnut from './charts/PigStatusDoughnut.vue';
import PigCountBar from './charts/PigCountBar.vue';
import ExpenseCategoryDoughnut from './charts/ExpenseCategoryDoughnut.vue';
import SalesVsExpensesLine from './charts/SalesVsExpensesLine.vue';
import NetProfitBar from './charts/NetProfitBar.vue';
import HealthIncidentsBar from './charts/HealthIncidentsBar.vue';
import MortalityLine from './charts/MortalityLine.vue';
import TreatmentCompletionDoughnut from './charts/TreatmentCompletionDoughnut.vue';
import ApprovalStatusBar from './charts/ApprovalStatusBar.vue';

const props = defineProps({
    routes: { type: Object, default: () => ({}) },
    userName: { type: String, default: '' },
    overviewUrl: { type: String, default: '/dashboard/overview' },
});

const filters = reactive({
    cycle_id: null,
    date_from: '',
    date_to: '',
    pig_status: '',
    pig_sex: '',
});

const data = ref(null);
const loading = ref(true);
const error = ref(null);
const lastUpdated = ref('');

let fetchTimer = null;

const activeFilterCount = computed(() => {
    let count = 0;
    if (filters.cycle_id) count++;
    if (filters.date_from || filters.date_to) count++;
    if (filters.pig_status) count++;
    if (filters.pig_sex) count++;
    return count;
});

function debouncedFetch() {
    clearTimeout(fetchTimer);
    fetchTimer = setTimeout(() => fetchData(), 300);
}

async function fetchData() {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        if (filters.cycle_id) params.set('cycle_id', filters.cycle_id);
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.pig_status) params.set('pig_status', filters.pig_status);
        if (filters.pig_sex) params.set('pig_sex', filters.pig_sex);

        const url = props.overviewUrl + (params.toString() ? '?' + params.toString() : '');
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) throw new Error('Failed to fetch dashboard data');

        data.value = await res.json();
        lastUpdated.value = data.value.last_updated || '';
    } catch (e) {
        error.value = e.message || 'Failed to load dashboard data';
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    filters.cycle_id = null;
    filters.date_from = '';
    filters.date_to = '';
    filters.pig_status = '';
    filters.pig_sex = '';
    debouncedFetch();
}

function handleFilterChange() {
    debouncedFetch();
}

function handleChartFilter(payload) {
    if (!payload || !payload.type) return;

    switch (payload.type) {
        case 'pig_status':
            filters.pig_status = payload.value === filters.pig_status ? '' : payload.value;
            break;
        case 'cycle':
            filters.cycle_id = payload.value === filters.cycle_id ? null : payload.value;
            break;
        case 'expense_category':
            break;
    }
    debouncedFetch();
}

onMounted(() => {
    fetchData();
});

function formatDate(isoString) {
    if (!isoString) return '';
    return new Date(isoString).toLocaleString();
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#0c6d57] to-emerald-600 rounded-2xl p-6 md:p-8 text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold">Overall Dashboard</h2>
                    <p class="text-emerald-50 text-sm md:text-base mt-1">System-wide overview of pig livelihood operations, health, expenses, sales, and approvals</p>
                </div>
                <div class="flex items-center gap-4">
                    <span v-if="lastUpdated" class="text-xs text-emerald-100">
                        Updated {{ formatDate(lastUpdated) }}
                    </span>
                    <button
                        @click="fetchData"
                        :disabled="loading"
                        class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <DashboardFilterBar
                :filters="filters"
                :filter-options="data?.filters"
                :active-count="activeFilterCount"
                @clear="clearFilters"
                @filter-change="handleFilterChange"
            />

            <!-- Error State -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
                {{ error }}
                <button @click="fetchData" class="ml-2 underline font-medium hover:text-red-800">Retry</button>
            </div>

            <!-- Loading State -->
            <div v-if="loading && !data" class="space-y-6">
                <!-- Skeleton KPI Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div v-for="i in 6" :key="i" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 animate-pulse">
                        <div class="flex justify-between items-center mb-3">
                            <div class="h-3 bg-gray-200 rounded w-20"></div>
                            <div class="h-8 w-8 bg-gray-200 rounded-lg"></div>
                        </div>
                        <div class="h-7 bg-gray-200 rounded w-16 mb-2"></div>
                        <div class="h-3 bg-gray-200 rounded w-24"></div>
                    </div>
                </div>
                <!-- Skeleton Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-pulse">
                        <div class="h-5 bg-gray-200 rounded w-40 mb-6"></div>
                        <div class="h-48 bg-gray-200 rounded"></div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-pulse">
                        <div class="h-5 bg-gray-200 rounded w-40 mb-6"></div>
                        <div class="h-48 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <template v-if="data">
                <!-- KPI Cards -->
                <DashboardKpiCards :kpis="data.kpis" @filter="handleChartFilter" />

                <!-- Alerts -->
                <DashboardAlerts v-if="data.alerts?.length" :alerts="data.alerts" />

                <!-- Main Content: Charts + Sidebar -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- LEFT: Charts (3/4) -->
                    <div class="lg:col-span-3 space-y-6">

                        <!-- SECTION: Livestock Status -->
                        <section>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Livestock Status</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Pig Population by Status</h4>
                                    <PigStatusDoughnut :chart-data="data.charts.pig_status_distribution" @filter="handleChartFilter" />
                                </div>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Pig Count by Batch</h4>
                                    <PigCountBar :chart-data="data.charts.pig_count_by_cycle" @filter="handleChartFilter" />
                                </div>
                            </div>
                        </section>

                        <!-- SECTION: Financial & Profitability -->
                        <section>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Financial &amp; Profitability</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Expenses by Category</h4>
                                    <ExpenseCategoryDoughnut :chart-data="data.charts.expenses_by_category" @filter="handleChartFilter" />
                                </div>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Sales vs Expenses (12-Month Trend)</h4>
                                    <SalesVsExpensesLine :chart-data="data.charts.sales_vs_expenses_trend" />
                                </div>
                                <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Net Profit per Cycle</h4>
                                    <NetProfitBar :chart-data="data.charts.net_profit_per_cycle" />
                                </div>
                            </div>
                        </section>

                        <!-- SECTION: Health Monitoring -->
                        <section>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Health Monitoring</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Health Incidents by Type</h4>
                                    <HealthIncidentsBar :chart-data="data.charts.health_incidents_by_type" />
                                </div>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Mortality Trend (6 Months)</h4>
                                    <MortalityLine :chart-data="data.charts.mortality_trend" />
                                </div>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Treatment Completion</h4>
                                    <TreatmentCompletionDoughnut :chart-data="data.charts.treatment_completion" />
                                </div>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Resolution Approvals Pipeline</h4>
                                    <ApprovalStatusBar :chart-data="data.charts.pending_approvals_summary" />
                                </div>
                            </div>
                        </section>

                        <!-- SECTION: Operations Tables -->
                        <section>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Operational Items</h3>
                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                                <!-- Pending Resolutions -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-semibold text-gray-700">Pending Resolutions</h4>
                                        <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">{{ data.tables?.pending_resolutions?.length || 0 }}</span>
                                    </div>
                                    <template v-if="data.tables?.pending_resolutions?.length">
                                        <div class="space-y-3">
                                            <div v-for="item in data.tables.pending_resolutions.slice(0, 5)" :key="item.id" class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-xs font-medium text-gray-900 line-clamp-2 flex-1">{{ item.title }}</p>
                                                    <span :class="{
                                                        'bg-amber-100 text-amber-700': item.status === 'pending_approval',
                                                        'bg-blue-100 text-blue-700': item.status === 'approved',
                                                        'bg-purple-100 text-purple-700': item.status === 'dswd_submitted',
                                                    }" class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full whitespace-nowrap shrink-0">{{ item.status.replace(/_/g, ' ') }}</span>
                                                </div>
                                                <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-400">
                                                    <span>{{ item.focal_person || '-' }}</span>
                                                    <span v-if="item.age_days > 0" :class="item.age_days > 7 ? 'text-red-500 font-semibold' : ''">{{ item.age_days }}d old</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <p v-else class="text-xs text-gray-400 py-4 text-center">No pending resolutions</p>
                                </div>

                                <!-- Upcoming Treatments -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-semibold text-gray-700">Upcoming Treatments</h4>
                                        <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">{{ data.tables?.upcoming_treatments?.length || 0 }}</span>
                                    </div>
                                    <template v-if="data.tables?.upcoming_treatments?.length">
                                        <div class="space-y-3">
                                            <div v-for="item in data.tables.upcoming_treatments.slice(0, 5)" :key="item.id" class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                                <p class="text-xs font-medium text-gray-900">{{ item.task_name }}</p>
                                                <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-400">
                                                    <span>{{ item.task_type }}</span>
                                                    <span>{{ item.batch_code || '-' }}</span>
                                                    <span>{{ item.planned_start_date }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <p v-else class="text-xs text-gray-400 py-4 text-center">No upcoming treatments</p>
                                </div>

                                <!-- Pending Withdrawals -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-semibold text-gray-700">Pending Withdrawals</h4>
                                        <span class="text-xs font-bold text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">{{ data.tables?.pending_withdrawals?.length || 0 }}</span>
                                    </div>
                                    <template v-if="data.tables?.pending_withdrawals?.length">
                                        <div class="space-y-3">
                                            <div v-for="item in data.tables.pending_withdrawals.slice(0, 5)" :key="item.id" class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                                <p class="text-xs font-medium text-gray-900 line-clamp-2">{{ item.resolution_title }}</p>
                                                <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-400">
                                                    <span class="font-semibold text-gray-700">&#8369;{{ item.amount?.toLocaleString() }}</span>
                                                    <span>{{ item.requested_at || '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <p v-else class="text-xs text-gray-400 py-4 text-center">No pending withdrawals</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- RIGHT: Activity Sidebar (1/4) -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-6">
                            <!-- Quick Approval Stats -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                                <h4 class="text-sm font-semibold text-gray-700 mb-4">Approvals Pipeline</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Pending Approval</span>
                                        <span class="font-bold text-amber-600">{{ data.charts?.pending_approvals_summary?.pending_approval || 0 }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Ready for DSWD</span>
                                        <span class="font-bold text-blue-600">{{ data.charts?.pending_approvals_summary?.ready_for_dswd || 0 }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Awaiting DSWD</span>
                                        <span class="font-bold text-purple-600">{{ data.charts?.pending_approvals_summary?.awaiting_dswd || 0 }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Ready for W/D</span>
                                        <span class="font-bold text-emerald-600">{{ data.charts?.pending_approvals_summary?.ready_for_withdrawal || 0 }}</span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Overdue Rx</span>
                                        <span class="font-bold" :class="data.kpis?.overdue_treatments > 0 ? 'text-red-600' : 'text-gray-600'">{{ data.kpis?.overdue_treatments || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity Feed -->
                            <DashboardActivityFeed :activities="data.recent_activity || []" />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
