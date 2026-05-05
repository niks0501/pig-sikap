<script setup>
const props = defineProps({
    member: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    penalties: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
})

const statusBadge = { pending: 'bg-amber-100 text-amber-700', paid: 'bg-emerald-100 text-emerald-700', waived: 'bg-gray-100 text-gray-600' }
</script>

<template>
<div class="space-y-6">
    <a :href="routes.index" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Penalties
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h1 class="text-xl font-bold text-gray-900">{{ member.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Penalty History</p>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-amber-400">
            <p class="text-xs text-gray-500">Pending</p>
            <p class="text-xl font-bold text-amber-600">{{ summary.total_pending }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-emerald-400">
            <p class="text-xs text-gray-500">Paid</p>
            <p class="text-xl font-bold text-emerald-600">{{ summary.total_paid }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-gray-400">
            <p class="text-xs text-gray-500">Waived</p>
            <p class="text-xl font-bold text-gray-600">{{ summary.total_waived }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500">Total</p>
            <p class="text-xl font-bold text-gray-900">₱{{ (summary.total_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
        </div>
    </div>

    <div v-if="penalties.length === 0" class="text-center py-12 text-gray-400 text-sm">No penalties for this member.</div>

    <div class="space-y-3">
        <div v-for="p in penalties" :key="p.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
            :class="p.status === 'pending' ? 'border-l-4 border-l-amber-400' : p.status === 'paid' ? 'border-l-4 border-l-emerald-400' : 'border-l-4 border-l-gray-400'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ p.meeting_title }}</p>
                    <p class="text-xs text-gray-500">{{ p.created_at }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="statusBadge[p.status]" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">{{ p.status }}</span>
                    <p class="text-sm font-semibold text-gray-900">₱{{ (p.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>
            <p v-if="p.reason" class="text-xs text-gray-400 mt-1">Reason: {{ p.reason }}</p>
        </div>
    </div>
</div>
</template>