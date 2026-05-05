<script setup>
/**
 * ResolutionDetail.vue – Tabbed SPA for resolution management.
 * All mutations update local reactive state (no page reloads).
 */
import { ref, reactive, computed, onMounted } from 'vue'
import AuthorizedWithdrawersPanel from './AuthorizedWithdrawersPanel.vue'

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
    documentVersions: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
    memberSnapshot: { type: Object, default: null },
    authorizedWithdrawers: { type: Array, default: () => [] },
    availableMembers: { type: Array, default: () => [] },
})

// ── Reactive state (initialized from props, updated after mutations) ──
const res = reactive({ ...props.resolution })
const approvalsList = ref([...props.approvals])
const dswdData = ref(props.dswdSubmission ? { ...props.dswdSubmission } : null)
const withdrawalsList = ref([...props.withdrawals])
const docsList = ref([...props.documentVersions])
const eligState = reactive({ ...props.eligibility })

// ── Tab state ──
const activeTab = ref('overview')
const tabs = [
    { id: 'overview', label: 'Overview', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
    { id: 'documents', label: 'Documents', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 'approvals', label: 'Approvals', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'dswd', label: 'DSWD', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { id: 'withdrawals', label: 'Withdrawals', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 'authorized', label: 'Authorized', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
]

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
        const d = await r.json()
        if (r.ok) {
            res.status = d.resolution.status
            res.approval_percentage = d.resolution.approval_percentage
            res.approved_count = d.resolution.approved_count
            res.has_met_threshold = d.resolution.has_met_threshold
            approvalsList.value = d.approvals
            showApprovalPanel.value = false
        }
    } catch { /* silent */ }
    finally { savingApprovals.value = false }
}

// ── DSWD state ──
const showDswdPanel = ref(false)
const dswdForm = ref({ status: dswdData.value?.status || 'not_submitted', notes: dswdData.value?.notes || '' })
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
        const d = await r.json()
        if (r.ok) {
            res.status = d.resolution.status
            res.approval_percentage = d.resolution.approval_percentage
            res.approved_count = d.resolution.approved_count
            res.has_met_threshold = d.resolution.has_met_threshold
            dswdData.value = d.resolution.dswdSubmission
            showDswdPanel.value = false
        }
    } catch { /* silent */ }
    finally { savingDswd.value = false }
}

// ── Document workflow state ──
const generatingPdf = ref(false)
const generatingDocx = ref(false)
const docError = ref(null)
const uploadError = ref(null)
const uploadSuccess = ref(false)
const signingFile = ref(null)
const sigSheetFile = ref(null)
const uploadingSigned = ref(false)

async function genPdf() {
    generatingPdf.value = true; docError.value = null
    try {
        const r = await fetch(props.routes.generatePdf, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken } })
        const d = await r.json()
        if (r.ok) { docsList.value = d.documentVersions }
        else { docError.value = d.message || 'Failed to generate PDF.' }
    } catch { docError.value = 'Network error while generating PDF.' }
    finally { generatingPdf.value = false }
}

async function genDocx() {
    generatingDocx.value = true; docError.value = null
    try {
        const r = await fetch(props.routes.generateDocx, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken } })
        const d = await r.json()
        if (r.ok) { docsList.value = d.documentVersions }
        else { docError.value = d.message || 'Failed to generate DOCX.' }
    } catch { docError.value = 'Network error while generating DOCX.' }
    finally { generatingDocx.value = false }
}

async function uploadSigned() {
    if (!signingFile.value) return
    uploadingSigned.value = true; uploadError.value = null; uploadSuccess.value = false
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('signed_document', signingFile.value)
    if (sigSheetFile.value) fd.append('signature_sheet', sigSheetFile.value)
    try {
        const r = await fetch(props.routes.uploadSigned, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        const d = await r.json()
        if (r.ok) {
            docsList.value = d.documentVersions
            uploadSuccess.value = true
            signingFile.value = null
            sigSheetFile.value = null
        } else { uploadError.value = d.message || 'Upload failed.' }
    } catch { uploadError.value = 'Network error during upload.' }
    finally { uploadingSigned.value = false }
}

async function verifyThreshold() {
    docError.value = null
    try {
        const r = await fetch(props.routes.verifyApprovals, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken } })
        const d = await r.json()
        if (r.ok) {
            res.status = d.resolution.status
            res.workflow_status = d.resolution.workflow_status
        } else { docError.value = d.message || 'Cannot verify approval threshold.' }
    } catch { docError.value = 'Network error verifying approvals.' }
}

// ── Document helpers ──
const getVersionsByType = (type) => docsList.value.filter(v => v.document_type === type).sort((a, b) => b.version_number - a.version_number)
const latestPdf = computed(() => getVersionsByType('generated_pdf')[0])
const latestDocx = computed(() => getVersionsByType('generated_docx')[0])
const signedVersions = computed(() => getVersionsByType('signed_resolution'))
const dswdDocVersions = computed(() => getVersionsByType('dswd_approval'))
const sigSheets = computed(() => getVersionsByType('signature_sheet'))

// ── Withdrawal state ──
const showWithdrawalForm = ref(false)
const wfAmount = ref('')
const wfBank = ref('')
const wfNotes = ref('')
const wfProof = ref(null)
const submittingWithdrawal = ref(false)
const wfErrors = ref({})
const wfAlert = ref([])

const reportLoading = ref({})
const reportErrors = ref({})

async function submitWithdrawal() {
    wfErrors.value = {}; wfAlert.value = []
    if (!wfAmount.value || wfAmount.value <= 0) { wfErrors.value.amount = ['Enter a valid amount.']; return }
    if (wfAmount.value > res.remaining_balance) { wfErrors.value.amount = [`Amount exceeds remaining balance.`]; return }
    submittingWithdrawal.value = true
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('amount', wfAmount.value)
    fd.append('bank_account', wfBank.value)
    fd.append('notes', wfNotes.value)
    if (wfProof.value) fd.append('proof_file', wfProof.value)
    try {
        const r = await fetch(props.routes.withdrawStore, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        const d = await r.json()
        if (r.ok) {
            res.grand_total = d.resolution.grand_total
            res.total_withdrawn = d.resolution.total_withdrawn
            res.remaining_balance = d.resolution.remaining_balance
            res.status = d.resolution.status
            withdrawalsList.value.push({
                id: d.withdrawal.id,
                amount: parseFloat(d.withdrawal.amount),
                status: d.withdrawal.status,
                requested_at: d.withdrawal.requested_at,
                requester_name: d.withdrawal.requester?.name,
                notes: d.withdrawal.notes,
                has_report: false,
                generate_report_url: props.routes.reportGenerate?.replace('__WITHDRAWAL__', d.withdrawal.id),
                proof_file_url: null,
                preview_url: null,
                download_url: null,
            })
            showWithdrawalForm.value = false
            resetWithdrawalForm()
        } else if (r.status === 422) {
            const fieldLabels = { amount: 'Amount', bank_account: 'Bank Account', proof_file: 'Proof File', notes: 'Notes' }
            for (const [key, msgs] of Object.entries(d.errors || {})) {
                if (key === 'resolution') wfAlert.value.push(...msgs)
                else if (fieldLabels[key]) wfErrors.value[key] = msgs
                else wfAlert.value.push(...msgs)
            }
        } else { wfAlert.value = ['Something went wrong.'] }
    } catch { wfAlert.value = ['Connection error.'] }
    finally { submittingWithdrawal.value = false }
}

function resetWithdrawalForm() { wfAmount.value = ''; wfBank.value = ''; wfNotes.value = ''; wfProof.value = null; wfErrors.value = {}; wfAlert.value = [] }

async function generateLiquidationReport(withdrawal) {
    if (!withdrawal.generate_report_url) return
    reportLoading.value = { ...reportLoading.value, [withdrawal.id]: true }
    reportErrors.value = { ...reportErrors.value, [withdrawal.id]: null }
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    try {
        const r = await fetch(withdrawal.generate_report_url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        const d = await r.json().catch(() => ({}))
        if (!r.ok) {
            const msg = d.errors?.withdrawal?.join(' ') || d.message || 'Could not generate report.'
            reportErrors.value = { ...reportErrors.value, [withdrawal.id]: msg }
            return
        }
        const wi = withdrawalsList.value.find(w => w.id === withdrawal.id)
        if (wi) {
            wi.status = d.withdrawal.status
            wi.completed_at = d.withdrawal.completed_at
            wi.has_report = true
            wi.preview_url = d.withdrawal.preview_url
            wi.download_url = d.withdrawal.download_url
        }
        if (d.resolution) {
            res.status = d.resolution.status
            res.remaining_balance = d.resolution.remaining_balance
        }
    } catch {
        reportErrors.value = { ...reportErrors.value, [withdrawal.id]: 'Connection interrupted.' }
    } finally {
        reportLoading.value = { ...reportLoading.value, [withdrawal.id]: false }
    }
}

// ── Helpers ──
const fmt = v => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const pct = computed(() => res.approval_percentage)
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

const steps = computed(() => [
    { label: 'Created', done: true },
    { label: 'Approved (≥75%)', done: res.has_met_threshold },
    { label: 'DSWD', done: dswdData.value?.status === 'approved' },
    { label: 'Withdrawn', done: ['withdrawn', 'finalized'].includes(res.status) },
    { label: 'Finalized', done: res.status === 'finalized' },
])

onMounted(() => {
    res.grand_total = props.resolution.grand_total
    res.total_withdrawn = props.resolution.total_withdrawn
    res.remaining_balance = props.resolution.remaining_balance
})
</script>

<template>
<div class="space-y-6">
    <!-- Back link -->
    <a :href="routes.resolutionsIndex" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Resolutions
    </a>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ res.title }}</h1>
                <p v-if="meeting" class="text-sm text-gray-500 mt-1">
                    From meeting: <a :href="meeting.show_url" class="text-[#0c6d57] hover:underline">{{ meeting.title }}</a> ({{ meeting.date }})
                </p>
            </div>
            <span :class="statusColors[res.status]" class="px-3 py-1 rounded-full text-xs font-semibold">{{ statusLabels[res.status] }}</span>
        </div>
        <p v-if="res.description" class="text-sm text-gray-600 mb-4 whitespace-pre-line">{{ res.description }}</p>

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
            <span>Created by {{ res.creator_name }}</span>
            <span>{{ res.created_at }}</span>
            <span v-if="res.resolution_file_url"><a :href="res.resolution_file_url" target="_blank" class="text-[#0c6d57] hover:underline">📎 Resolution File</a></span>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-1 overflow-x-auto -mb-px" role="tablist">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" role="tab" :aria-selected="activeTab === tab.id"
                class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors min-h-[44px]"
                :class="activeTab === tab.id ? 'border-[#0c6d57] text-[#0c6d57]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/></svg>
                {{ tab.label }}
            </button>
        </nav>
    </div>

    <!-- Tab: Overview -->
    <div v-show="activeTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Budget Total</p>
                <p class="text-lg font-bold text-gray-900">{{ fmt(res.grand_total) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Withdrawn</p>
                <p class="text-lg font-bold text-gray-900">{{ fmt(res.total_withdrawn) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Remaining</p>
                <p class="text-lg font-bold" :class="res.remaining_balance > 0 ? 'text-emerald-600' : 'text-gray-400'">{{ fmt(res.remaining_balance) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Approval</p>
                <p class="text-lg font-bold" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</p>
            </div>
        </div>

        <!-- Quick-access approval progress in overview -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Approval Progress</h2>
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600">{{ res.approved_count }} / {{ props.totalMembers }} members signed</span>
                <span class="font-semibold" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</span>
            </div>
            <div class="relative w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div :class="pctColor" class="h-full rounded-full transition-all duration-500" :style="{ width: pctBarWidth }"></div>
            </div>
            <p class="text-xs mt-1" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">
                {{ res.has_met_threshold ? '✅ Threshold met!' : `⚠️ Need ${props.threshold}% to proceed` }}
            </p>
        </div>
    </div>

    <!-- Tab: Documents -->
    <div v-show="activeTab === 'documents'" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Document Workflow</h2>
            <p class="text-xs text-gray-500 mb-5">Generate, upload, and verify resolution documents.</p>

            <div v-if="docError" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-start gap-2">
                <span class="shrink-0 mt-0.5">⚠️</span>
                <div class="flex-1"><p class="font-medium">Error</p><p class="text-xs mt-0.5">{{ docError }}</p></div>
                <button @click="docError = null" class="text-rose-400 hover:text-rose-600 shrink-0">✕</button>
            </div>

            <!-- Generate Documents -->
            <div class="border rounded-xl p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">📄 Generate Documents</h3>
                <div v-if="latestPdf || latestDocx" class="space-y-2 mb-3">
                    <div v-if="latestPdf" class="flex items-center justify-between px-3 py-2 bg-white rounded-lg border border-gray-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-red-500 shrink-0">📕</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">Resolution PDF (v{{ latestPdf.version_number }})</p>
                                <p class="text-xs text-gray-400">{{ latestPdf.formatted_file_size }} · {{ latestPdf.generated_at }}</p>
                            </div>
                            <span v-if="latestPdf.document_type === 'dswd_approval' || latestPdf.document_type === 'signed_resolution'" class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 border border-gray-200 shrink-0">🔒 Private</span>
                        </div>
                        <a v-if="latestPdf.file_url" :href="latestPdf.file_url" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">View</a>
                    </div>
                    <div v-if="latestDocx" class="flex items-center justify-between px-3 py-2 bg-white rounded-lg border border-gray-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-blue-500 shrink-0">📘</span>
                            <div class="min-w-0"><p class="text-sm font-medium text-gray-900 truncate">Editable DOCX (v{{ latestDocx.version_number }})</p><p class="text-xs text-gray-400">{{ latestDocx.formatted_file_size }} · {{ latestDocx.generated_at }}</p></div>
                        </div>
                        <a v-if="latestDocx.file_url" :href="latestDocx.file_url" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 min-h-[36px]">Download</a>
                    </div>
                </div>
                <div v-if="permissions.canGenerate" class="flex flex-wrap gap-2">
                    <button @click="genPdf" :disabled="generatingPdf" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-[#0c6d57] rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">
                        <svg v-if="generatingPdf" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ generatingPdf ? 'Generating...' : (latestPdf ? 'Regenerate PDF' : 'Generate PDF') }}
                    </button>
                    <button @click="genDocx" :disabled="generatingDocx" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 min-h-[44px] disabled:opacity-50">
                        {{ generatingDocx ? 'Creating...' : (latestDocx ? 'Regenerate DOCX' : 'Create Editable DOCX') }}
                    </button>
                </div>
            </div>

            <!-- Upload Signed -->
            <div class="border rounded-xl p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">✍️ Upload Signed Resolution</h3>
                <div v-if="signedVersions.length > 0" class="space-y-2 mb-3">
                    <div v-for="doc in signedVersions" :key="doc.id" class="flex items-center justify-between px-3 py-2 bg-white rounded-lg border border-gray-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-emerald-500 shrink-0">✅</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">Signed Resolution (v{{ doc.version_number }})</p>
                                <p class="text-xs text-gray-400">{{ doc.formatted_file_size }} · {{ doc.generated_at }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 border border-gray-200 shrink-0">🔒 Private</span>
                        </div>
                        <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">View</a>
                    </div>
                </div>
                <div v-if="sigSheets.length > 0" class="space-y-2 mb-3">
                    <p class="text-xs text-gray-500 font-medium">Signature Sheets</p>
                    <div v-for="doc in sigSheets" :key="doc.id" class="flex items-center justify-between px-3 py-2 bg-white rounded-lg border border-gray-100">
                        <span class="text-sm text-gray-900">📋 Signature Sheet (v{{ doc.version_number }})</span>
                        <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">View</a>
                    </div>
                </div>
                <div v-if="permissions.canUploadSigned">
                    <div v-if="uploadSuccess" class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">✅ Document uploaded successfully!</div>
                    <div v-if="uploadError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">⚠️ {{ uploadError }}</div>
                    <div class="space-y-3">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Signed Resolution (PDF) *</label><input type="file" accept=".pdf" @change="signingFile = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0c6d57]/10 file:text-[#0c6d57]" /></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Signature Sheet (Optional)</label><input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="sigSheetFile = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700" /></div>
                        <button @click="uploadSigned" :disabled="uploadingSigned || !signingFile" class="px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">{{ uploadingSigned ? 'Uploading...' : 'Upload Signed Document' }}</button>
                    </div>
                </div>
            </div>

            <!-- Verify Threshold -->
            <div class="border rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">✅ Verify 75% Approval</h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ res.approved_count || 0 }} / {{ props.totalMembers }} members signed</span>
                            <span class="font-semibold" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" :class="pctColor" :style="{ width: pctBarWidth }"></div>
                        </div>
                    </div>
                </div>
                <button v-if="permissions.canVerifyApproval && res.has_met_threshold" @click="verifyThreshold" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-[#0c6d57] rounded-xl hover:bg-[#0a5a48] min-h-[44px]">✅ Verify & Proceed to DSWD</button>
                <p v-else-if="!res.has_met_threshold" class="text-xs text-amber-600">⚠️ The 75% approval threshold has not been met yet.</p>
            </div>
        </div>
    </div>

    <!-- Tab: Approvals -->
    <div v-show="activeTab === 'approvals'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <!-- Snapshot info card -->
        <div v-if="memberSnapshot" class="mb-6 rounded-xl border px-4 py-3"
            :class="res.is_approval_locked ? 'border-gray-200 bg-gray-50' : 'border-blue-200 bg-blue-50'">
            <div class="flex items-center gap-2">
                <span>📸</span>
                <div>
                    <p class="text-sm font-semibold" :class="res.is_approval_locked ? 'text-gray-700' : 'text-blue-800'">
                        Member Snapshot
                        <span v-if="res.is_approval_locked" class="text-xs text-gray-500">(Frozen)</span>
                    </p>
                    <p class="text-xs" :class="res.is_approval_locked ? 'text-gray-500' : 'text-blue-600'">
                        {{ memberSnapshot.eligible_count }} eligible members · {{ memberSnapshot.required_approvals }} required approvals (75%)
                    </p>
                    <p class="text-xs" :class="res.is_approval_locked ? 'text-gray-400' : 'text-blue-400'">
                        Taken {{ memberSnapshot.snapshot_taken_at }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="res.is_approval_locked" class="mb-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 flex items-center gap-2">
            <span>🔒</span> Approval changes are locked. This resolution has DSWD approval or has withdrawals.
        </div>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Member Approval</h2>
            <button v-if="!['finalized','withdrawn'].includes(res.status) && !res.is_approval_locked" @click="showApprovalPanel = !showApprovalPanel; if(showApprovalPanel && members.length === 0) loadApprovalData()"
                class="px-4 py-2 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px]">
                {{ showApprovalPanel ? 'Close' : 'Record Signatures' }}
            </button>
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600">{{ res.approved_count }} / {{ props.totalMembers }} members signed</span>
                <span class="font-semibold" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">{{ pct }}%</span>
            </div>
            <div class="relative w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div :class="pctColor" class="h-full rounded-full transition-all duration-500" :style="{ width: pctBarWidth }"></div>
            </div>
            <p class="text-xs mt-1" :class="res.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">
                {{ res.has_met_threshold ? '✅ Threshold met!' : `⚠️ Need ${props.threshold}% to proceed` }}
            </p>
        </div>

        <div v-if="approvalsList.length > 0" class="space-y-1.5 mb-4">
            <div v-for="a in approvalsList" :key="a.id" class="flex items-center justify-between px-3 py-2 rounded-lg" :class="a.is_approved ? 'bg-emerald-50' : 'bg-gray-50'">
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ a.is_approved ? '✅' : '❌' }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ a.user_name }}</span>
                    <span class="text-xs text-gray-500">({{ a.user_role }})</span>
                </div>
                <span class="text-xs text-gray-400">{{ a.approved_at || a.rejection_reason || '' }}</span>
            </div>
        </div>

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

    <!-- Tab: DSWD -->
    <div v-show="activeTab === 'dswd'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">DSWD Status</h2>
            <button v-if="res.has_met_threshold && !['finalized'].includes(res.status)" @click="showDswdPanel = !showDswdPanel"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors min-h-[44px]">
                {{ showDswdPanel ? 'Close' : 'Update DSWD Status' }}
            </button>
        </div>

        <div v-if="dswdData" class="flex items-center gap-3 mb-3">
            <span :class="dswdBadge[dswdData.status]" class="px-3 py-1 rounded-full text-xs font-semibold capitalize">{{ dswdData.status.replace('_', ' ') }}</span>
            <span v-if="dswdData.submitted_at" class="text-xs text-gray-500">{{ dswdData.submitted_at }}</span>
            <a v-if="dswdData.submission_file_url" :href="dswdData.submission_file_url" target="_blank" class="text-xs text-[#0c6d57] hover:underline">📎 View File</a>
        </div>
        <p v-else class="text-sm text-gray-400">Not yet submitted to DSWD.</p>
        <p v-if="dswdData?.notes" class="text-sm text-gray-600 mt-2">{{ dswdData.notes }}</p>
        <p v-if="!res.has_met_threshold" class="text-xs text-amber-600 mt-2">⚠️ Approval must reach {{ props.threshold }}% before DSWD submission.</p>

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

        <!-- DSWD document versions -->
        <div v-if="res.is_approval_locked" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 flex items-center gap-2">
            <span>🔒</span> Document workflow is frozen. Approvals and document changes are locked.
        </div>

        <div v-if="dswdDocVersions.length > 0" class="mt-4 border-t border-gray-200 pt-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">🏛️ DSWD Approval Documents</h3>
            <div v-for="doc in dswdDocVersions" :key="doc.id" class="flex items-center justify-between px-3 py-2 bg-white rounded-lg border border-gray-100 mb-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="shrink-0">🏛️</span>
                    <div class="min-w-0"><p class="text-sm font-medium text-gray-900 truncate">DSWD Approval (v{{ doc.version_number }})</p><p class="text-xs text-gray-400">{{ doc.formatted_file_size }} · {{ doc.generated_at }}</p></div>
                </div>
                <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">View</a>
            </div>
        </div>
    </div>

    <!-- Tab: Authorized -->
    <div v-show="activeTab === 'authorized'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Authorized Withdrawers</h2>
        </div>
        <p class="text-sm text-gray-500 mb-4">Designate members who can execute withdrawals for this resolution. If no one is designated, existing behavior applies (treasurer/president).</p>
        <div v-if="permissions.canDesignateWithdrawers">
            <AuthorizedWithdrawersPanel
                :authorizedWithdrawers="authorizedWithdrawers"
                :availableMembers="availableMembers"
                :routes="{ authorizedWithdrawersStore: routes.authorizedWithdrawersStore, authorizedWithdrawersRevoke: routes.authorizedWithdrawersRevoke }"
                :csrfToken="csrfToken"
                @updated="() => {}"
            />
        </div>
        <div v-else class="text-sm text-gray-500">Only the president can manage authorized withdrawers.</div>
    </div>

    <!-- Tab: Withdrawals -->
    <div v-show="activeTab === 'withdrawals'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Withdrawals</h2>
            <div class="flex gap-2">
                <button v-if="eligState.eligible && res.remaining_balance > 0 && !showWithdrawalForm" @click="showWithdrawalForm = true"
                    class="px-4 py-2 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px] inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Withdrawal
                </button>
                <button v-if="showWithdrawalForm" @click="showWithdrawalForm = false; resetWithdrawalForm()" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-xl hover:bg-gray-300 min-h-[44px]">Cancel</button>
            </div>
        </div>

        <div v-if="!eligState.eligible && res.status !== 'finalized'" class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
            <p class="text-sm font-medium text-amber-800 mb-1">Cannot create withdrawal yet:</p>
            <ul class="text-xs text-amber-700 list-disc list-inside"><li v-for="r in eligState.reasons" :key="r">{{ r }}</li></ul>
        </div>

        <!-- Inline withdrawal form -->
        <div v-if="showWithdrawalForm" class="mb-6 border rounded-xl p-4 space-y-4">
            <div v-if="wfAlert.length > 0" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold mb-1">Unable to create withdrawal</p>
                <ul class="list-disc list-inside text-xs"><li v-for="msg in wfAlert" :key="msg">{{ msg }}</li></ul>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center mb-4">
                <div><p class="text-xs text-gray-500">Budget</p><p class="text-lg font-bold text-gray-900">{{ fmt(res.grand_total) }}</p></div>
                <div><p class="text-xs text-gray-500">Withdrawn</p><p class="text-lg font-bold text-gray-900">{{ fmt(res.total_withdrawn) }}</p></div>
                <div><p class="text-xs text-gray-500">Available</p><p class="text-lg font-bold text-emerald-600">{{ fmt(res.remaining_balance) }}</p></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">₱</span>
                    <input v-model.number="wfAmount" type="number" step="0.01" min="0.01" :max="res.remaining_balance" class="w-full pl-8 pr-4 py-2.5 rounded-lg border text-sm" :class="wfErrors.amount ? 'border-rose-400' : 'border-gray-300'" /></div>
                <p v-if="wfErrors.amount" class="text-xs text-rose-600 mt-1">{{ wfErrors.amount[0] }}</p>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Bank Account</label>
                <input v-model="wfBank" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" placeholder="e.g. Land Bank Account #12345" />
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Proof of Withdrawal</label>
                <input type="file" @change="wfProof = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0c6d57]/10 file:text-[#0c6d57]" />
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="wfNotes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" placeholder="Optional remarks..."></textarea>
            </div>
            <div class="flex justify-end">
                <button @click="submitWithdrawal" :disabled="submittingWithdrawal" class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50 inline-flex items-center gap-2">
                    <svg v-if="submittingWithdrawal" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ submittingWithdrawal ? 'Processing...' : 'Create Withdrawal Record' }}
                </button>
            </div>
        </div>

        <div v-if="withdrawalsList.length === 0" class="text-center py-6 text-gray-400 text-sm">No withdrawals yet.</div>
        <div v-else class="space-y-3">
            <div v-for="w in withdrawalsList" :key="w.id" class="px-4 py-3 rounded-lg border border-gray-100">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ fmt(w.amount) }}</p>
                        <p class="text-xs text-gray-500">{{ w.requester_name }} · {{ w.requested_at }}</p>
                        <p v-if="w.notes" class="text-xs text-gray-500 mt-0.5">{{ w.notes }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <span :class="wBadge[w.status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">{{ w.status }}</span>
                        <a v-if="w.has_report && w.preview_url" :href="w.preview_url" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center rounded-xl border border-[#0c6d57]/20 bg-[#e7f5f0] px-3 py-2 text-xs font-semibold text-[#0c6d57] hover:bg-[#d5eee6]">Preview PDF</a>
                        <a v-if="w.has_report && w.download_url" :href="w.download_url" class="inline-flex min-h-[44px] items-center rounded-xl bg-[#0c6d57] px-3 py-2 text-xs font-semibold text-white hover:bg-[#0a5a48]">Download PDF</a>
                        <button v-if="!w.has_report && w.generate_report_url" type="button" @click="generateLiquidationReport(w)" :disabled="reportLoading[w.id]" class="inline-flex min-h-[44px] items-center rounded-xl bg-[#0c6d57] px-3 py-2 text-xs font-semibold text-white hover:bg-[#0a5a48] disabled:opacity-50">
                            {{ reportLoading[w.id] ? 'Generating...' : 'Generate Report' }}
                        </button>
                        <a v-if="w.proof_file_url" :href="w.proof_file_url" target="_blank" class="inline-flex min-h-[44px] items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-[#0c6d57] hover:bg-gray-50">View Proof</a>
                    </div>
                </div>
                <div v-if="reportErrors[w.id]" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ reportErrors[w.id] }}</div>
            </div>
        </div>
    </div>
</div>
</template>
