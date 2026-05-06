<script setup>
defineProps({
    kpis: { type: Object, required: true },
});

defineEmits(['filter']);

const formatCurrency = (val) => {
    const num = Number(val) || 0;
    if (Math.abs(num) >= 1000) {
        return '\u20B1' + (num / 1000).toFixed(1) + 'k';
    }
    return '\u20B1' + num.toFixed(2);
};
</script>

<template>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Cycles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Active Cycles</span>
                <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ kpis.active_cycles ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">of {{ kpis.total_cycles ?? 0 }} total cycles</div>
        </div>

        <!-- Total Pigs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Pigs</span>
                <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ kpis.total_pigs ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ kpis.sold_pigs ?? 0 }} sold</div>
        </div>

        <!-- Sick Pigs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer" @click="$emit('filter', { type: 'pig_status', value: 'Sick' })">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Sick / Isolated</span>
                <div class="bg-orange-50 p-2 rounded-lg text-orange-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold" :class="(kpis.sick_pigs ?? 0) > 0 ? 'text-orange-600' : 'text-gray-800'">{{ kpis.sick_pigs ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ kpis.deceased_pigs ?? 0 }} deceased</div>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Expenses</span>
                <div class="bg-rose-50 p-2 rounded-lg text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ formatCurrency(kpis.total_expenses) }}</div>
            <div class="text-xs text-gray-500 mt-1">Per selected period</div>
        </div>

        <!-- Total Sales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Revenue</span>
                <div class="bg-emerald-50 p-2 rounded-lg text-[#0c6d57]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-[#0c6d57]">{{ formatCurrency(kpis.total_sales) }}</div>
            <div class="text-xs text-gray-500 mt-1">Collected: {{ formatCurrency(kpis.collected_revenue) }}</div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Pending</span>
                <div class="bg-amber-50 p-2 rounded-lg text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold" :class="(kpis.pending_resolutions ?? 0) > 0 ? 'text-amber-600' : 'text-gray-800'">{{ kpis.pending_resolutions ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Resolutions &amp; withdrawals</div>
        </div>
    </div>

    <!-- Second Row: Additional KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
        <!-- Net Profit -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Net Profit</span>
                <div class="bg-blue-50 p-2 rounded-lg text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold" :class="(kpis.net_profit ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600'">{{ formatCurrency(kpis.net_profit) }}</div>
            <div class="text-xs text-gray-500 mt-1">All cycles</div>
        </div>

        <!-- Sold Pigs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Sold Pigs</span>
                <div class="bg-blue-50 p-2 rounded-lg text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ kpis.sold_pigs ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Total sold</div>
        </div>

        <!-- Upcoming Treatments -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Upcoming Rx</span>
                <div class="bg-green-50 p-2 rounded-lg text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ kpis.upcoming_treatments ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ kpis.overdue_treatments ?? 0 }} overdue</div>
        </div>

        <!-- Pending Withdrawals -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Pending W/D</span>
                <div class="bg-violet-50 p-2 rounded-lg text-violet-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold" :class="(kpis.pending_withdrawals ?? 0) > 0 ? 'text-violet-600' : 'text-gray-800'">{{ kpis.pending_withdrawals ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Withdrawals pending</div>
        </div>
    </div>
</template>
