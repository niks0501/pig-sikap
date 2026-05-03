<script setup>
/**
 * DocumentChecklist.vue – Interactive checklist showing the resolution document
 * workflow progress. Each step shows status, actions, and uploaded files.
 * Follows the design system: mobile-first, 44px touch targets, #0c6d57 accent.
 */
import { ref, computed } from 'vue'

const props = defineProps({
    resolution: { type: Object, required: true },
    documentVersions: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

// ── State ──
const generating = ref(false)
const generatingDocx = ref(false)
const generationError = ref(null)
const expandedStep = ref(null)

// ── Computed helpers ──
const workflowStatus = computed(() => props.resolution.workflow_status || 'draft')

const statusOrder = [
    'draft',
    'generated',
    'printed',
    'signature_sheet_uploaded',
    'pending_member_approval',
    'member_approved',
    'dswd_pending',
    'dswd_approved',
    'withdrawal_ready',
    'withdrawn',
    'archived',
]

const currentIndex = computed(() => statusOrder.indexOf(workflowStatus.value))

const steps = computed(() => [
    {
        id: 'generate',
        label: 'Generate Document',
        description: 'Create official resolution PDF and editable DOCX',
        icon: '📄',
        status: currentIndex.value >= 1 ? 'completed' : currentIndex.value === 0 ? 'current' : 'pending',
        hasAction: props.permissions.canGenerate && currentIndex.value <= 1,
    },
    {
        id: 'upload-signed',
        label: 'Upload Signed Resolution',
        description: 'Upload the signed resolution with member signatures',
        icon: '✍️',
        status: currentIndex.value >= 3 ? 'completed' : currentIndex.value >= 1 && currentIndex.value <= 2 ? 'current' : 'pending',
        hasAction: props.permissions.canUploadSigned && currentIndex.value >= 1 && currentIndex.value <= 2,
    },
    {
        id: 'verify-approval',
        label: 'Verify 75% Approval',
        description: 'Confirm member approval threshold has been met',
        icon: '✅',
        status: currentIndex.value >= 5 ? 'completed' : currentIndex.value >= 3 && currentIndex.value <= 4 ? 'current' : 'pending',
        hasAction: props.permissions.canVerifyApproval && currentIndex.value >= 3 && currentIndex.value <= 4,
    },
    {
        id: 'dswd-approval',
        label: 'DSWD Approval',
        description: 'Upload the DSWD approval document',
        icon: '🏛️',
        status: currentIndex.value >= 7 ? 'completed' : currentIndex.value >= 5 && currentIndex.value <= 6 ? 'current' : 'pending',
        hasAction: props.permissions.canUploadDswd && currentIndex.value >= 6 && currentIndex.value <= 6,
    },
    {
        id: 'withdrawal',
        label: 'Ready for Withdrawal',
        description: 'Resolution is approved and ready for fund withdrawal',
        icon: '💰',
        status: currentIndex.value >= 8 ? 'completed' : currentIndex.value === 7 ? 'current' : 'pending',
        hasAction: false,
    },
])

// ── Document version helpers ──
const getVersionsByType = (type) => {
    return props.documentVersions.filter(v => v.document_type === type).sort((a, b) => b.version_number - a.version_number)
}

const latestPdf = computed(() => getVersionsByType('generated_pdf')[0])
const latestDocx = computed(() => getVersionsByType('generated_docx')[0])
const signedVersions = computed(() => getVersionsByType('signed_resolution'))
const dswdVersions = computed(() => getVersionsByType('dswd_approval'))
const signatureSheets = computed(() => getVersionsByType('signature_sheet'))

// ── Actions ──
function toggleStep(stepId) {
    expandedStep.value = expandedStep.value === stepId ? null : stepId
}

async function generatePdf() {
    generating.value = true
    generationError.value = null

    try {
        const r = await fetch(props.routes.generatePdf, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken },
        })
        const d = await r.json().catch(() => ({}))

        if (!r.ok) {
            generationError.value = d.message || 'Failed to generate PDF.'
            return
        }

        window.location.reload()
    } catch {
        generationError.value = 'Network error while generating PDF.'
    } finally {
        generating.value = false
    }
}

async function generateDocx() {
    generatingDocx.value = true
    generationError.value = null

    try {
        const r = await fetch(props.routes.generateDocx, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken },
        })
        const d = await r.json().catch(() => ({}))

        if (!r.ok) {
            generationError.value = d.message || 'Failed to generate DOCX.'
            return
        }

        window.location.reload()
    } catch {
        generationError.value = 'Network error while generating DOCX.'
    } finally {
        generatingDocx.value = false
    }
}

async function verifyApprovalThreshold() {
    try {
        const r = await fetch(props.routes.verifyApprovals, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken },
        })
        const d = await r.json().catch(() => ({}))

        if (!r.ok) {
            generationError.value = d.message || 'Cannot verify approval threshold.'
            return
        }

        window.location.reload()
    } catch {
        generationError.value = 'Network error while verifying approvals.'
    }
}
</script>

<template>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Document Workflow</h2>
    <p class="text-xs text-gray-500 mb-5">
        Follow each step to complete the resolution approval process.
        Current: <span class="font-semibold text-[#0c6d57]">{{ resolution.workflow_status_label }}</span>
    </p>

    <!-- Error alert -->
    <div v-if="generationError" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-start gap-2">
        <span class="shrink-0 mt-0.5">⚠️</span>
        <div>
            <p class="font-medium">Action Failed</p>
            <p class="text-xs mt-0.5">{{ generationError }}</p>
        </div>
        <button @click="generationError = null" class="ml-auto text-rose-400 hover:text-rose-600 shrink-0">✕</button>
    </div>

    <!-- Checklist Steps -->
    <div class="space-y-3">
        <div v-for="step in steps" :key="step.id"
            class="border rounded-xl overflow-hidden transition-all"
            :class="{
                'border-[#0c6d57]/30 bg-emerald-50/30': step.status === 'current',
                'border-emerald-200 bg-emerald-50/20': step.status === 'completed',
                'border-gray-100': step.status === 'pending',
            }">
            <!-- Step Header -->
            <button
                @click="toggleStep(step.id)"
                class="w-full flex items-center gap-3 px-4 py-3.5 text-left min-h-13 transition-colors hover:bg-gray-50/50"
                :class="{ 'cursor-pointer': true }">
                <!-- Status indicator -->
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm"
                    :class="{
                        'bg-emerald-100 text-emerald-700': step.status === 'completed',
                        'bg-[#0c6d57] text-white': step.status === 'current',
                        'bg-gray-100 text-gray-400': step.status === 'pending',
                    }">
                    <svg v-if="step.status === 'completed'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span v-else>{{ step.icon }}</span>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate"
                        :class="{
                            'text-emerald-700': step.status === 'completed',
                            'text-gray-900': step.status === 'current',
                            'text-gray-400': step.status === 'pending',
                        }">
                        {{ step.label }}
                    </p>
                    <p class="text-xs truncate"
                        :class="step.status === 'pending' ? 'text-gray-300' : 'text-gray-500'">
                        {{ step.description }}
                    </p>
                </div>

                <!-- Expand chevron -->
                <svg class="w-5 h-5 text-gray-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': expandedStep === step.id }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Expanded Content -->
            <div v-show="expandedStep === step.id" class="px-4 pb-4 pt-1 border-t border-gray-100">
                <!-- Generate Documents Step -->
                <div v-if="step.id === 'generate'">
                    <!-- Existing documents -->
                    <div v-if="latestPdf || latestDocx" class="space-y-2 mb-3">
                        <div v-if="latestPdf" class="flex items-center justify-between px-3 py-2.5 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-red-500 flex-shrink-0">📕</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">Resolution PDF (v{{ latestPdf.version_number }})</p>
                                    <p class="text-xs text-gray-400">{{ latestPdf.formatted_file_size }} · {{ latestPdf.generated_at }}</p>
                                </div>
                            </div>
                            <a v-if="latestPdf.file_url" :href="latestPdf.file_url" target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">
                                View
                            </a>
                        </div>
                        <div v-if="latestDocx" class="flex items-center justify-between px-3 py-2.5 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-blue-500 flex-shrink-0">📘</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">Editable DOCX (v{{ latestDocx.version_number }})</p>
                                    <p class="text-xs text-gray-400">{{ latestDocx.formatted_file_size }} · {{ latestDocx.generated_at }}</p>
                                </div>
                            </div>
                            <a v-if="latestDocx.file_url" :href="latestDocx.file_url" target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 min-h-[36px]">
                                Download
                            </a>
                        </div>
                    </div>

                    <!-- Generate buttons -->
                    <div v-if="step.hasAction" class="flex flex-wrap gap-2 mt-3">
                        <button @click="generatePdf" :disabled="generating"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-[#0c6d57] rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span>{{ generating ? 'Generating...' : (latestPdf ? 'Regenerate PDF' : 'Generate PDF') }}</span>
                        </button>
                        <button @click="generateDocx" :disabled="generatingDocx"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition-colors min-h-[44px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="generatingDocx" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span>{{ generatingDocx ? 'Creating...' : (latestDocx ? 'Regenerate DOCX' : 'Create Editable DOCX') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Upload Signed Step -->
                <div v-else-if="step.id === 'upload-signed'">
                    <!-- Existing signed documents -->
                    <div v-if="signedVersions.length > 0" class="space-y-2 mb-3">
                        <div v-for="doc in signedVersions" :key="doc.id" class="flex items-center justify-between px-3 py-2.5 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-emerald-500 flex-shrink-0">✅</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">Signed Resolution (v{{ doc.version_number }})</p>
                                    <p class="text-xs text-gray-400">{{ doc.formatted_file_size }} · {{ doc.generated_at }}</p>
                                </div>
                            </div>
                            <a v-if="doc.file_url" :href="doc.file_url" target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">
                                View
                            </a>
                        </div>
                    </div>

                    <!-- Signature sheets -->
                    <div v-if="signatureSheets.length > 0" class="space-y-2 mb-3">
                        <p class="text-xs text-gray-500 font-medium">Signature Sheets</p>
                        <div v-for="doc in signatureSheets" :key="doc.id" class="flex items-center justify-between px-3 py-2.5 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex-shrink-0">📋</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">Signature Sheet (v{{ doc.version_number }})</p>
                                    <p class="text-xs text-gray-400">{{ doc.formatted_file_size }}</p>
                                </div>
                            </div>
                            <a v-if="doc.file_url" :href="doc.file_url" target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">
                                View
                            </a>
                        </div>
                    </div>

                    <!-- Upload form hint -->
                    <div v-if="step.hasAction" class="mt-3">
                        <p class="text-xs text-gray-500 mb-2">
                            Use the upload area below to upload the signed resolution and optional signature sheet.
                        </p>
                    </div>
                    <p v-else-if="step.status === 'pending'" class="text-xs text-gray-400 italic">
                        Generate the document first before uploading the signed copy.
                    </p>
                </div>

                <!-- Verify Approval Step -->
                <div v-else-if="step.id === 'verify-approval'">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ resolution.approved_count || 0 }} / {{ resolution.total_members || 0 }} members signed</span>
                                <span class="font-semibold" :class="resolution.has_met_threshold ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ resolution.approval_percentage || 0 }}%
                                </span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="resolution.has_met_threshold ? 'bg-emerald-500' : 'bg-amber-500'"
                                    :style="{ width: Math.min(resolution.approval_percentage || 0, 100) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button v-if="step.hasAction && resolution.has_met_threshold" @click="verifyApprovalThreshold"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-[#0c6d57] rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px]">
                        ✅ Verify & Proceed to DSWD
                    </button>
                    <p v-else-if="!resolution.has_met_threshold && step.status !== 'completed'" class="text-xs text-amber-600">
                        ⚠️ The 75% approval threshold has not been met yet.
                    </p>
                </div>

                <!-- DSWD Approval Step -->
                <div v-else-if="step.id === 'dswd-approval'">
                    <div v-if="dswdVersions.length > 0" class="space-y-2 mb-3">
                        <div v-for="doc in dswdVersions" :key="doc.id" class="flex items-center justify-between px-3 py-2.5 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex-shrink-0">🏛️</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">DSWD Approval (v{{ doc.version_number }})</p>
                                    <p class="text-xs text-gray-400">{{ doc.formatted_file_size }} · {{ doc.generated_at }}</p>
                                </div>
                            </div>
                            <a v-if="doc.file_url" :href="doc.file_url" target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">
                                View
                            </a>
                        </div>
                    </div>

                    <div v-if="step.hasAction" class="mt-3">
                        <p class="text-xs text-gray-500 mb-2">
                            Use the upload area below to upload the DSWD approval document.
                        </p>
                    </div>
                    <p v-else-if="step.status === 'pending'" class="text-xs text-gray-400 italic">
                        Member approval verification must be completed first.
                    </p>
                </div>

                <!-- Withdrawal Ready Step -->
                <div v-else-if="step.id === 'withdrawal'">
                    <div v-if="step.status === 'completed' || step.status === 'current'" class="flex items-center gap-2 px-3 py-2.5 bg-emerald-50 rounded-lg border border-emerald-200">
                        <span class="text-emerald-600">🎉</span>
                        <p class="text-sm text-emerald-700 font-medium">This resolution has been approved and is ready for fund withdrawal.</p>
                    </div>
                    <p v-else class="text-xs text-gray-400 italic">
                        All previous steps must be completed before withdrawal.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
