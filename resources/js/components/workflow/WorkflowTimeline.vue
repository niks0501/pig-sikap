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
    hasMetThreshold: { type: Boolean, default: false },
})

const steps = computed(() => {
    const ws = props.workflowStatus

    return [
        {
            id: 'meeting',
            label: 'Meeting',
            labelShort: 'Meeting',
            // A resolution always has a meeting, so this is complete if workflow has started
            complete: ws !== 'draft',
        },
        {
            id: 'generated',
            label: 'Document Generated',
            labelShort: 'Generated',
            // Complete when a PDF or DOCX has been generated
            complete: ['generated', 'printed', 'signature_sheet_uploaded', 'pending_member_approval', 'member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'].includes(ws),
        },
        {
            id: 'approval',
            label: '75% Member Approval',
            labelShort: '75%',
            // Complete when threshold is met and verified
            complete: ['member_approved', 'dswd_pending', 'dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'].includes(ws) || props.hasMetThreshold,
            showCount: true,
        },
        {
            id: 'dswd',
            label: 'DSWD Approval',
            labelShort: 'DSWD',
            complete: ['dswd_approved', 'withdrawal_ready', 'withdrawn', 'archived'].includes(ws) || props.dswdStatus === 'approved',
        },
        {
            id: 'withdrawal',
            label: 'Withdrawal',
            labelShort: 'Withdrawal',
            complete: ['withdrawn', 'archived'].includes(ws) || props.hasWithdrawals,
        },
        {
            id: 'liquidation',
            label: 'Liquidation',
            labelShort: 'Liquidated',
            complete: ws === 'archived' || props.hasLiquidationReport,
        },
    ]
})

/**
 * Find the first incomplete step index. This is the "current/active" step.
 * All steps before it are complete.
 */
const activeIndex = computed(() => {
    const s = steps.value
    for (let i = 0; i < s.length; i++) {
        if (!s[i].complete) return i
    }
    return s.length // all complete
})

/**
 * How many steps are complete (for the progress bar %).
 */
const completedCount = computed(() => steps.value.filter(s => s.complete).length)
const progressPercent = computed(() =>
    steps.value.length > 0
        ? Math.round((completedCount.value / steps.value.length) * 100)
        : 0
)
</script>

<template>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Workflow Progress</h3>

    <div class="flex items-start gap-0 overflow-x-auto pb-2">
        <template v-for="(step, i) in steps" :key="step.id">
            <!-- Step circle + label -->
            <div class="flex flex-col items-center shrink-0" style="width:70px">
                <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-colors duration-300"
                    :class="step.complete
                        ? 'bg-[#0c6d57] text-white'
                        : i === activeIndex
                            ? 'border-2 border-[#0c6d57] bg-[#e7f5f0] text-[#0c6d57]'
                            : 'border-2 border-gray-200 bg-white text-gray-400'"
                >
                    <svg v-if="step.complete" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg v-else-if="i === activeIndex" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span v-else class="text-[11px]">{{ i + 1 }}</span>
                </div>
                <span
                    class="text-[11px] font-medium mt-1.5 text-center leading-tight"
                    :class="step.complete ? 'text-[#0c6d57]' : i === activeIndex ? 'text-[#0c6d57] font-semibold' : 'text-gray-400'"
                >{{ step.labelShort }}</span>
                <!-- Show count for approval step -->
                <span v-if="step.showCount" class="text-[10px] mt-0.5" :class="step.complete ? 'text-[#0c6d57]/70' : 'text-gray-400'">
                    {{ approvedCount }}/{{ totalMembers }}
                </span>
            </div>

            <!-- Connector line -->
            <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mt-[18px] rounded" :class="steps[i + 1].complete ? 'bg-[#0c6d57]' : 'bg-gray-200'"></div>
        </template>
    </div>

    <!-- Progress bar -->
    <div class="mt-3">
        <div class="bg-gray-100 rounded-full h-1.5 overflow-hidden">
            <div
                class="h-full bg-[#0c6d57] rounded-full transition-all duration-500"
                :style="{ width: progressPercent + '%' }"
            ></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-1 text-right">
            {{ completedCount }} of {{ steps.length }} steps complete
        </p>
    </div>
</div>
</template>
