<script setup>
/**
 * WorkflowTimeline.vue – Visual stepper showing the resolution workflow
 * progress: Meeting Minutes → Resolution → 75% Approval →
 * DSWD Approval → Withdrawal → Expense Liquidation.
 */
import { computed } from 'vue'

const props = defineProps({
    workflowStatus: { type: String, default: 'draft' },
    resolutionStatus: { type: String, default: 'draft' },
    dswdStatus: { type: String, default: null },
    hasWithdrawals: { type: Boolean, default: false },
    hasLiquidationReport: { type: Boolean, default: false },
    approvalPercentage: { type: Number, default: 0 },
    totalMembers: { type: Number, default: 0 },
    approvedCount: { type: Number, default: 0 },
})

// Workflow step definitions with statuses that mark them complete
const steps = [
    {
        id: 'meeting',
        label: 'Meeting Minutes',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        completeStatuses: ['generated', 'printed', 'signature_sheet_uploaded', 'pending_member_approval', 'member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'],
        isComplete: computed(() => props.workflowStatus !== 'draft'),
    },
    {
        id: 'resolution',
        label: 'Resolution Created',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        completeStatuses: ['generated', 'printed', 'signature_sheet_uploaded', 'pending_member_approval', 'member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'],
        isComplete: computed(() => props.workflowStatus !== 'draft'),
    },
    {
        id: 'approval',
        label: '75% Member Approval',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        completeStatuses: ['member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'],
        isComplete: computed(() => ['member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'].includes(props.workflowStatus)),
        showStats: true,
    },
    {
        id: 'dswd',
        label: 'DSWD Approval',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        completeStatuses: ['dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'],
        isComplete: computed(() => ['dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'].includes(props.workflowStatus) || props.dswdStatus === 'approved'),
    },
    {
        id: 'withdrawal',
        label: 'Withdrawal',
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        completeStatuses: ['withdrawn', 'archived'],
        isComplete: computed(() => ['withdrawn', 'archived'].includes(props.workflowStatus) || props.hasWithdrawals),
    },
    {
        id: 'liquidation',
        label: 'Expense Liquidation',
        icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        completeStatuses: ['archived'],
        isComplete: computed(() => props.workflowStatus === 'archived' || props.hasLiquidationReport),
    },
]

// Determine active (current) step index
const activeStepIndex = computed(() => {
    for (let i = steps.length - 1; i >= 0; i--) {
        if (steps[i].isComplete.value) return i + 1
    }
    return 0
})

function stepStatus(index) {
    const step = steps[index]
    if (step.isComplete.value) return 'complete'
    if (index === activeStepIndex.value) return 'active'
    return 'pending'
}

const statusClasses = {
    complete: { circle: 'bg-[#0c6d57] text-white', line: 'bg-[#0c6d57]', icon: 'text-white' },
    active: { circle: 'border-2 border-[#0c6d57] bg-[#e7f5f0] text-[#0c6d57]', line: 'bg-gray-200', icon: 'text-[#0c6d57]' },
    pending: { circle: 'border-2 border-gray-200 bg-white text-gray-400', line: 'bg-gray-200', icon: 'text-gray-400' },
}
</script>

<template>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Workflow Progress</h3>
    <div class="flex items-start overflow-x-auto pb-2">
        <div v-for="(step, i) in steps" :key="step.id" class="flex items-start flex-1 min-w-0">
            <!-- Circle + label -->
            <div class="flex flex-col items-center shrink-0">
                <div
                    :class="statusClasses[stepStatus(i)].circle"
                    class="w-10 h-10 rounded-full flex items-center justify-center transition-colors duration-300"
                >
                    <svg v-if="stepStatus(i) === 'complete'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg v-else class="w-5 h-5" :class="statusClasses[stepStatus(i)].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon.replace(/\\\\/g, '\\\\')"/>
                    </svg>
                </div>
                <span
                    class="text-xs font-medium mt-1.5 text-center leading-tight max-w-[80px]"
                    :class="stepStatus(i) === 'complete' ? 'text-[#0c6d57]' : stepStatus(i) === 'active' ? 'text-[#0c6d57]' : 'text-gray-400'"
                >{{ step.label }}</span>
                <!-- Show stats for approval step -->
                <span v-if="step.showStats && (stepStatus(i) === 'active' || stepStatus(i) === 'pending')" class="text-[10px] text-gray-400 mt-0.5">
                    {{ approvedCount }}/{{ totalMembers }}
                </span>
                <span v-if="step.showStats && stepStatus(i) === 'complete'" class="text-[10px] text-[#0c6d57] mt-0.5">
                    {{ approvedCount }}/{{ totalMembers }}
                </span>
            </div>
            <!-- Connector line (skip for last) -->
            <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mt-5 mx-1 rounded" :class="statusClasses[stepStatus(i)].line"></div>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="mt-4">
        <div class="bg-gray-100 rounded-full h-1.5 overflow-hidden">
            <div
                class="h-full bg-[#0c6d57] rounded-full transition-all duration-500"
                :style="{ width: (activeStepIndex / steps.length) * 100 + '%' }"
            ></div>
        </div>
    </div>
</div>
</template>
