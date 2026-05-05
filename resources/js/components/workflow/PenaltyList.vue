<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    summary: { type: Object, default: () => ({ total_pending: 0, total_paid: 0, total_waived: 0, total_amount_pending: 0 }) },
    penalties: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
    permissions: { type: Object, default: () => ({ canWaive: false, canMarkPaid: false }) },
})

const search = ref('')
const filterStatus = ref('')
const waiveReason = ref({})
const showReasonInput = ref({})

const filtered = computed(() => props.penalties.filter(p => {
    if (search.value && !p.user_name?.toLowerCase().includes(search.value.toLowerCase())) return false
    if (filterStatus.value && p.status !== filterStatus.value) return false
    return true
}))

const statusBadge = { pending: 'bg-amber-100 text-amber-700', paid: 'bg-emerald-100 text-emerald-700', waived: 'bg-gray-100 text-gray-600', cancelled: 'bg-rose-100 text-rose-700' }

async function waive(penalty) {
    if (!showReasonInput.value[penalty.id]) { showReasonInput.value = { ...showReasonInput.value, [penalty.id]: true }; return }
    if (!waiveReason.value[penalty.id]) return
    const fd = new FormData(); fd.append('_token', props.csrfToken); fd.append('reason', waiveReason.value[penalty.id]); fd.append('_method', 'PATCH')
    try {
        const r = await fetch(props.routes.waive.replace('__ID__', penalty.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) window.location.reload()
    } catch { /* silent */ }
}

async function markPaid(penalty) {
    if (!confirm('Mark this penalty as paid?')) return
    const fd = new FormData(); fd.append('_token', props.csrfToken); fd.append('_method', 'PATCH')
    try {
        const r = await fetch(props.routes.pay.replace('__ID__', penalty.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) window.location.reload()
    } catch { /* silent */ }
}
</script>

<template>
<div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Attendance Penalties</h1>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-amber-400">
            <p class="text-xs text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-amber-600">{{ summary.total_pending }}</p>
            <p class="text-xs text-gray-400">₱{{ (summary.total_amount_pending || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-emerald-400">
            <p class="text-xs text-gray-500">Paid</p>
            <p class="text-2xl font-bold text-emerald-600">{{ summary.total_paid }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 border-l-4 border-l-gray-400">
            <p class="text-xs text-gray-500">Waived</p>
            <p class="text-2xl font-bold text-gray-600">{{ summary.total_waived }}</p>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <input v-model="search" placeholder="Search by member name..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
        <select v-model="filterStatus" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="waived">Waived</option>
        </select>
    </div>

    <div v-if="filtered.length === 0" class="text-center py-12 text-gray-400 text-sm">No penalties found.</div>

    <div class="space-y-3">
        <div v-for="p in filtered" :key="p.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">{{ p.user_name }}</span>
                        <span :class="statusBadge[p.status]" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">{{ p.status }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">{{ p.meeting_title }} · {{ p.created_at }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-sm font-semibold text-gray-900">₱{{ (p.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    <div v-if="p.status === 'pending'" class="flex gap-2">
                        <button v-if="permissions.canMarkPaid" @click="markPaid(p)" class="px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">Mark Paid</button>
                        <button v-if="permissions.canWaive" @click="waive(p)" class="px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 min-h-[36px]">
                            {{ showReasonInput[p.id] ? 'Confirm Waive' : 'Waive' }}
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="showReasonInput[p.id]" class="mt-2">
                <input v-model="waiveReason[p.id]" placeholder="Reason for waiving..." @keyup.enter="waive(p)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <p v-if="p.reason" class="text-xs text-gray-400 mt-1">Reason: {{ p.reason }}</p>
        </div>
    </div>
</div>
</template>