<script setup>
/**
 * ResolutionDetail.vue – The central workflow hub showing resolution status,
 * budget, approvals, DSWD tracking, and withdrawal actions.
 */
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
    resolution: { type: Object, required: true },
    meeting: { type: Object, default: null },
    lineItems: { type: Array, default: () => [] },
    approvals: { type: Array, default: () => [] },
    dswdSubmission: { type: Object, default: null },
    withdrawals: { type: Array, default: () => [] },
    totalMembers: { type: Number, default: 0 },
    eligibility: { type: Object, default: () => ({ eligible: false, reasons: [] }) },
    threshold: { type: Number, default: 75 },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

// ── Approval state ──
const showApprovalPanel = ref(false)
const members = ref([])
const loadingMembers = ref(false)
const savingApprovals = ref(false)

async function loadApprovalData() {
    loadingMembers.value = true
    try {
        const r = await fetch(props.routes.approvalsData, { headers: { 'Accept': 'application/json' } })
        const d = await r.json()
        members.value = d.members.map(m => {
            const existing = d.approvals.find(a => a.user_id === m.id)
            return { ...m, is_approved: existing?.is_approved || false, rejection_reason: existing?.rejection_reason || '' }
        })
    } catch { /* silent */ }
    finally { loadingMembers.value = false }
}

function toggleApproval(m) { m.is_approved = !m.is_approved; if (m.is_approved) m.rejection_reason = '' }

async function saveApprovals() {
    savingApprovals.value = true
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    members.value.forEach((m, i) => {
        fd.append(`approvals[${i}][user_id]`, m.id)
        fd.append(`approvals[${i}][is_approved]`, m.is_approved ? '1' : '0')
        if (m.rejection_reason) fd.append(`approvals[${i}][rejection_reason]`, m.rejection_reason)
    })
    try {
        const r = await fetch(props.routes.approvalsStore, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) window.location.reload()
    } catch { /* silent */ }
    finally { savingApprovals.value = false }
}

// ── DSWD state ──
const showDswdPanel = ref(false)
const dswdForm = ref({ status: props.dswdSubmission?.status || 'not_submitted', notes: props.dswdSubmission?.notes || '' })
const dswdFile = ref(null)
const savingDswd = ref(false)

async function saveDswd() {
    savingDswd.value = true
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('status', dswdForm.value.status)
    fd.append('notes', dswdForm.value.notes)
    if (dswdFile.value) fd.append('submission_file', dswdFile.value)
    try {
        const r = await fetch(props.routes.dswdStore, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) window.location.reload()
    } catch { /* silent */ }
    finally { savingDswd.value = false }
}

// ── Liquidation report state ──
const reportLoading = ref({})
const reportErrors = ref({})

async function generateLiquidationReport(withdrawal) {
    if (!withdrawal.generate_report_url) return

    reportLoading.value = { ...reportLoading.value, [withdrawal.id]: true }
    reportErrors.value = { ...reportErrors.value, [withdrawal.id]: null }

    const fd = new FormData()
    fd.append('_token', props.csrfToken)

    try {
        const r = await fetch(withdrawal.generate_report_url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken },
            body: fd,
        })
        const d = await r.json().catch(() => ({}))

        if (!r.ok) {
            const message = d.errors?.withdrawal?.join(' ') || d.message || 'Liquidation report could not be generated. Please try again.'
            reportErrors.value = { ...reportErrors.value, [withdrawal.id]: message }
            return
        }

        window.location.reload()
    } catch {
        reportErrors.value = { ...reportErrors.value, [withdrawal.id]: 'The report could not be generated because the connection was interrupted.' }
    } finally {
        reportLoading.value = { ...reportLoading.value, [withdrawal.id]: false }
    }
}

// ── Helpers ──
const fmt = v => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const pct = computed(() => props.resolution.approval_percentage)
const pctBarWidth = computed(() => Math.min(pct.value, 100) + '%')
const pctColor = computed(() => pct.value >= props.threshold ? 'bg-emerald-500' : 'bg-amber-500')
const statusColors = {
    draft: 'bg-gray-100 text-gray-700', pending_approval: 'bg-amber-100 text-amber-700',
    approved: 'bg-blue-100 text-blue-700', dswd_submitted: 'bg-indigo-100 text-indigo-700',
    withdrawn: 'bg-emerald-100 text-emerald-700', finalized: 'bg-emerald-200 text-emerald-800',
}
const statusLabels = {
    draft: 'Draft', pending_approval: 'Pending Approval', approved: 'Approved',
    dswd_submitted: 'DSWD Submitted', withdrawn: 'Withdrawn', finalized: 'Finalized',
}
const dswdBadge = { not_submitted: 'bg-gray-100 text-gray-700', submitted: 'bg-blue-100 text-blue-700', approved: 'bg-emerald-100 text-emerald-700', returned: 'bg-rose-100 text-rose-700' }
const wBadge = { draft: 'bg-gray-100 text-gray-700', pending: 'bg-amber-100 text-amber-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' }

// Workflow steps
const steps = computed(() => [
    { label: 'Created', done: true },
    { label: 'Approved (≥75%)', done: props.resolution.has_met_threshold },
    { label: 'DSWD', done: props.dswdSubmission?.status === 'approved' },
    { label: 'Withdrawn', done: ['withdrawn', 'finalized'].includes(props.resolution.status) },
    { label: 'Finalized', done: props.resolution.status === 'finalized' },
])
</script>

<template>
<div class="space-y-6">
    <!-- Status Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ resolution.title }}</h1>
                <p v-if="meeting" class="text-sm text-gray-500 mt-1">
                    From meeting: <a :href="meeting.show_url" class="text-[#0c6d57] hover:underline">{{ meeting.title }}</a> ({{ meeting.date }})
                </p>
            </div>
            <span :class="statusColors[resolution.status]" class="px-3 py-1 rounded-full text-xs font-semibold">{{ statusLabels[resolution.status] }}</span>
        </div>
        <p v-if="resolution.description" class="text-sm text-gray-600 mb-4 whitespace-pre-line">{{ resolution.description }}</p>

        <!-- Workflow Progress Steps -->
        <div class="flex items-center gap-1 mt-4 mb-2">
            <template v-for="(step, i) in steps" :key="i">
                <div class="flex items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                        :class="step.done ? 'bg-[#0c6d57] text-white' : 'bg-gray-200 text-gray-500'">
                        <svg v-if="step.done" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="text-xs font-medium" :class="step.done ? 'text-[#0c6d57]' : 'text-gray-400'">{{ step.label }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mx-1" :class="steps[i+1]?.done ? 'bg-[#0c6d57]' : 'bg-gray-200'"></div>
            </template>
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-400 mt-4">
            <span>Created by {{ resolution.creator_name }}</span>
            <span>{{ resolution.created_at }}</span>
            <span v-if="resolution.resolution_file_url"><a :href="resolution.resolution_file_url" target="_blank" class="text-[#0c6d57] hover:underline">📎 Resolution File</a></span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Budget Total</p>
            <p class="text-lg font-bold text-gray-900">{{ fmt(resolution.grand_total) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Withdrawn</p>
            <p class="text-lg font-bold text-gray-900">{{ fmt(resolution.total_withdrawn) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Remaining</p>
            <p class="text-lg font-bold" :class="resolution.remaining_balance > 0 ? 'text-emerald-600' : 'text-gray-400'">{{ fmt(resolution.remaining_balance) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Approval</p>
            <p class="text-lg font-bold" :class="resolution.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</p>
        </div>
    </div>

    <!-- Budget Line Items -->
    <div v-if="lineItems.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Budget Line Items</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead><tr class="text-left text-xs font-medium text-gray-500 uppercase">
                    <th class="px-3 py-2">Category</th><th class="px-3 py-2">Description</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2">Unit</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Total</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="li in lineItems" :key="li.id">
                        <td class="px-3 py-2.5 font-medium">{{ li.category }}</td>
                        <td class="px-3 py-2.5">{{ li.description }}</td>
                        <td class="px-3 py-2.5 text-right">{{ li.quantity }}</td>
                        <td class="px-3 py-2.5">{{ li.unit }}</td>
                        <td class="px-3 py-2.5 text-right">{{ fmt(li.unit_cost) }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold">{{ fmt(li.total) }}</td>
                    </tr>
                </tbody>
                <tfoot><tr class="border-t-2 border-gray-300"><td colspan="5" class="px-3 py-2.5 text-right font-bold">Grand Total</td><td class="px-3 py-2.5 text-right font-bold text-[#0c6d57]">{{ fmt(resolution.grand_total) }}</td></tr></tfoot>
            </table>
        </div>
    </div>

    <!-- Approval Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Member Approval</h2>
            <button v-if="!['finalized','withdrawn'].includes(resolution.status)" @click="showApprovalPanel = !showApprovalPanel; if(showApprovalPanel && members.length === 0) loadApprovalData()"
                class="px-4 py-2 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px]">
                {{ showApprovalPanel ? 'Close' : 'Record Signatures' }}
            </button>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600">{{ resolution.approved_count }} / {{ totalMembers }} members signed</span>
                <span class="font-semibold" :class="resolution.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</span>
            </div>
            <div class="relative w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div :class="pctColor" class="h-full rounded-full transition-all duration-500" :style="{ width: pctBarWidth }"></div>
            </div>
            <p class="text-xs mt-1" :class="resolution.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">
                {{ resolution.has_met_threshold ? '✅ Threshold met!' : `⚠️ Need ${threshold}% to proceed` }}
            </p>
        </div>

        <!-- Existing approvals -->
        <div v-if="approvals.length > 0" class="space-y-1.5 mb-4">
            <div v-for="a in approvals" :key="a.id" class="flex items-center justify-between px-3 py-2 rounded-lg" :class="a.is_approved ? 'bg-emerald-50' : 'bg-gray-50'">
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ a.is_approved ? '✅' : '❌' }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ a.user_name }}</span>
                    <span class="text-xs text-gray-500">({{ a.user_role }})</span>
                </div>
                <span class="text-xs text-gray-400">{{ a.approved_at || a.rejection_reason || '' }}</span>
            </div>
        </div>

        <!-- Approval panel (expanded) -->
        <div v-if="showApprovalPanel" class="border-t border-gray-200 pt-4 mt-4">
            <div v-if="loadingMembers" class="text-center py-8 text-gray-400 text-sm">Loading members...</div>
            <div v-else class="space-y-2">
                <button v-for="m in members" :key="m.id" type="button" @click="toggleApproval(m)"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg border transition-colors hover:bg-gray-50 min-h-[48px]"
                    :class="m.is_approved ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium" :class="m.is_approved ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">{{ m.name?.charAt(0) }}</div>
                        <div class="text-left"><p class="text-sm font-medium text-gray-900">{{ m.name }}</p><p class="text-xs text-gray-500">{{ m.role?.name }}</p></div>
                    </div>
                    <span :class="m.is_approved ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2.5 py-0.5 rounded-full text-xs font-medium">{{ m.is_approved ? 'Signed' : 'Not signed' }}</span>
                </button>
                <div class="flex justify-end mt-4">
                    <button @click="saveApprovals" :disabled="savingApprovals" class="px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">{{ savingApprovals ? 'Saving...' : 'Save Approvals' }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DSWD Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">DSWD Status</h2>
            <button v-if="resolution.has_met_threshold && !['finalized'].includes(resolution.status)" @click="showDswdPanel = !showDswdPanel"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors min-h-[44px]">
                {{ showDswdPanel ? 'Close' : 'Update DSWD Status' }}
            </button>
        </div>
        <div v-if="dswdSubmission" class="flex items-center gap-3 mb-3">
            <span :class="dswdBadge[dswdSubmission.status]" class="px-3 py-1 rounded-full text-xs font-semibold capitalize">{{ dswdSubmission.status.replace('_', ' ') }}</span>
            <span v-if="dswdSubmission.submitted_at" class="text-xs text-gray-500">{{ dswdSubmission.submitted_at }}</span>
            <a v-if="dswdSubmission.submission_file_url" :href="dswdSubmission.submission_file_url" target="_blank" class="text-xs text-[#0c6d57] hover:underline">📎 View File</a>
        </div>
        <p v-else class="text-sm text-gray-400">Not yet submitted to DSWD.</p>
        <p v-if="dswdSubmission?.notes" class="text-sm text-gray-600 mt-2">{{ dswdSubmission.notes }}</p>
        <p v-if="!resolution.has_met_threshold" class="text-xs text-amber-600 mt-2">⚠️ Approval must reach {{ threshold }}% before DSWD submission.</p>

        <div v-if="showDswdPanel" class="border-t border-gray-200 pt-4 mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="dswdForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="not_submitted">Not Submitted</option><option value="submitted">Submitted</option><option value="approved">Approved</option><option value="returned">Returned</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload DSWD Document</label>
                <input type="file" @change="dswdFile = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="dswdForm.notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <div class="flex justify-end"><button @click="saveDswd" :disabled="savingDswd" class="px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 min-h-[44px] disabled:opacity-50">{{ savingDswd ? 'Saving...' : 'Save DSWD Status' }}</button></div>
        </div>
    </div>

    <!-- Withdrawals Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Withdrawals</h2>
            <a v-if="eligibility.eligible && resolution.remaining_balance > 0" :href="routes.withdrawCreate"
                class="px-4 py-2 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px] inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Withdrawal
            </a>
        </div>
        <div v-if="!eligibility.eligible && resolution.status !== 'finalized'" class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
            <p class="text-sm font-medium text-amber-800 mb-1">Cannot create withdrawal yet:</p>
            <ul class="text-xs text-amber-700 list-disc list-inside"><li v-for="r in eligibility.reasons" :key="r">{{ r }}</li></ul>
        </div>
        <div v-if="withdrawals.length === 0" class="text-center py-6 text-gray-400 text-sm">No withdrawals yet.</div>
        <div v-else class="space-y-3">
            <div v-for="w in withdrawals" :key="w.id" class="px-4 py-3 rounded-lg border border-gray-100">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ fmt(w.amount) }}</p>
                    <p class="text-xs text-gray-500">{{ w.requester_name }} · {{ w.requested_at }}</p>
                    <p v-if="w.notes" class="text-xs text-gray-500 mt-0.5">{{ w.notes }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span :class="wBadge[w.status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">{{ w.status }}</span>
                    <a v-if="w.has_report && w.preview_url" :href="w.preview_url" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center rounded-xl border border-[#0c6d57]/20 bg-[#e7f5f0] px-3 py-2 text-xs font-semibold text-[#0c6d57] hover:bg-[#d5eee6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0c6d57] focus-visible:ring-offset-2">Preview PDF</a>
                    <a v-if="w.has_report && w.download_url" :href="w.download_url" class="inline-flex min-h-[44px] items-center rounded-xl bg-[#0c6d57] px-3 py-2 text-xs font-semibold text-white hover:bg-[#0a5a48] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0c6d57] focus-visible:ring-offset-2">Download PDF</a>
                    <button v-if="!w.has_report && w.generate_report_url" type="button" @click="generateLiquidationReport(w)" :disabled="reportLoading[w.id]" class="inline-flex min-h-[44px] items-center rounded-xl bg-[#0c6d57] px-3 py-2 text-xs font-semibold text-white hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0c6d57] focus-visible:ring-offset-2">
                        {{ reportLoading[w.id] ? 'Generating Report...' : 'Generate Liquidation Report' }}
                    </button>
                    <a v-if="w.proof_file_url" :href="w.proof_file_url" target="_blank" class="inline-flex min-h-[44px] items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-[#0c6d57] hover:bg-gray-50">View Proof</a>
                </div>
                </div>
                <div v-if="reportErrors[w.id]" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ reportErrors[w.id] }}
                </div>
            </div>
        </div>
    </div>
</div>
</template>
