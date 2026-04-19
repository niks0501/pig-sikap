<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { computed, ref } from 'vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
    },
    adjustmentTypes: {
        type: Array,
        default: () => [],
    },
    adjustmentReasons: {
        type: Array,
        default: () => [],
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    automation: {
        type: Object,
        default: () => ({}),
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    statusMessage: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
});

const isArchived = computed(() => props.cycle.stage === 'Completed' || ['Sold', 'Closed'].includes(props.cycle.status));
const isAdjustmentDialogOpen = ref(false);
const isStatusDialogOpen = ref(false);
const automation = computed(() => props.automation ?? {});

const countdown = computed(() => automation.value.countdown ?? {});
const suggestions = computed(() => (Array.isArray(automation.value.suggestions) ? automation.value.suggestions : []));
const warnings = computed(() => (Array.isArray(automation.value.warnings) ? automation.value.warnings : []));

const countSummary = computed(() => automation.value.counts ?? {});
const expenseSummary = computed(() => automation.value.expenses ?? {});
const profitabilitySummary = computed(() => automation.value.profitability ?? {});
const currentlySick = computed(() => Number(countSummary.value.currently_sick ?? countSummary.value.sick_count ?? 0));
const currentlyIsolated = computed(() => Number(countSummary.value.currently_isolated ?? countSummary.value.isolated_count ?? 0));
const currentlyAffected = computed(() => Number(countSummary.value.currently_affected ?? (currentlySick.value + currentlyIsolated.value)));
const healthyNow = computed(() => Number(countSummary.value.healthy_now ?? Math.max(Number(props.cycle.current_count || 0) - currentlyAffected.value, 0)));
const totalRecoveredReported = computed(() => Number(countSummary.value.total_recovered_reported ?? 0));
const totalDeceasedReported = computed(() => Number(countSummary.value.total_deceased_reported ?? countSummary.value.deceased_count ?? 0));

const formatCurrency = (value) => {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const adjustmentHistory = computed(() => {
    const items = Array.isArray(props.cycle.adjustments) ? [...props.cycle.adjustments] : [];

    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const statusHistory = computed(() => {
    const items = Array.isArray(props.cycle.status_histories) ? [...props.cycle.status_histories] : [];

    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const openAdjustmentDialog = () => {
    if (!isArchived.value) {
        isAdjustmentDialogOpen.value = true;
    }
};

const openStatusDialog = () => {
    if (!isArchived.value) {
        isStatusDialogOpen.value = true;
    }
};

const closeAdjustmentDialog = () => {
    isAdjustmentDialogOpen.value = false;
};

const closeStatusDialog = () => {
    isStatusDialogOpen.value = false;
};
</script>

<template>
    <div class="mx-auto max-w-300 space-y-5">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0c6d57]">Cycle Details</p>
                    <h2 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">{{ props.cycle.batch_code }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Registered {{ formatDateTime(props.cycle.created_at) }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ props.cycle.stage }}</span>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ props.cycle.status }}</span>
                        <span v-if="isArchived" class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Archived</span>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:w-auto">
                    <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        Back to Cycles
                    </a>
                    <a :href="props.routes.profilesIndex" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        Open Profile Manager
                    </a>
                    <a v-if="!isArchived" :href="props.routes.edit" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                        Edit Cycle
                    </a>
                    <button type="button" :disabled="isArchived" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-3 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5 disabled:cursor-not-allowed disabled:opacity-60" @click="openAdjustmentDialog">
                        Adjust Count
                    </button>
                    <button type="button" :disabled="isArchived" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2" @click="openStatusDialog">
                        Update Stage / Status
                    </button>
                    <form v-if="!isArchived" :action="props.routes.archive" method="POST" class="sm:col-span-2" onsubmit="return confirm('Archive this cycle? Operational editing will be restricted.');">
                        <input type="hidden" name="_token" :value="props.csrfToken">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-gray-800 px-3 py-2 text-sm font-semibold text-white transition hover:bg-gray-900">
                            Archive / Close
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div v-if="props.errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ props.errorMessage }}
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Expected Sale Date</p>
                <p class="mt-2 text-base font-bold text-gray-900">{{ formatDate(countdown.expected_ready_for_sale_date) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Harvest Month</p>
                <p class="mt-2 text-base font-bold text-gray-900">{{ countdown.expected_harvest_month || '-' }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Days Since Acquisition</p>
                <p class="mt-2 text-lg font-bold text-blue-900">{{ countdown.days_since_acquisition ?? '-' }}</p>
            </article>
            <article class="rounded-xl border p-4 shadow-sm" :class="countdown.is_overdue_for_sale_review ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'">
                <p class="text-xs font-semibold uppercase tracking-[0.18em]" :class="countdown.is_overdue_for_sale_review ? 'text-rose-700' : 'text-emerald-700'">Days Until Ready for Sale</p>
                <p class="mt-2 text-lg font-bold" :class="countdown.is_overdue_for_sale_review ? 'text-rose-900' : 'text-emerald-900'">{{ countdown.days_until_ready_for_sale ?? '-' }}</p>
            </article>
        </section>

        <section v-if="warnings.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <h3 class="text-sm font-bold text-amber-900">Automation Warnings</h3>
            <ul class="mt-3 space-y-2 text-sm text-amber-900">
                <li v-for="warning in warnings" :key="warning.code" class="rounded-xl border border-amber-200 bg-white px-3 py-2">
                    {{ warning.message }}
                </li>
            </ul>
        </section>

        <section v-if="suggestions.length > 0" class="rounded-xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4 shadow-sm">
            <h3 class="text-sm font-bold text-[#0c6d57]">Suggested Next Actions (Requires Confirmation)</h3>
            <ul class="mt-3 space-y-2 text-sm text-[#0a5a48]">
                <li v-for="suggestion in suggestions" :key="suggestion.code" class="rounded-xl border border-[#0c6d57]/20 bg-white px-3 py-2">
                    {{ suggestion.message }}
                </li>
            </ul>
        </section>

        <section class="rounded-xl border border-[#0c6d57]/20 bg-[#0c6d57]/5 p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-[#0a5a48]">Health & Treatments</h3>
                    <p class="mt-1 text-sm text-[#0a5a48]/80">
                        Cycle health is tracked as Current Active State and Historical Totals. Use the timeline for treatment closures and incident flow.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a :href="props.routes.healthCycleTimeline || props.routes.healthIndex" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                        Open Health Timeline
                    </a>
                    <a :href="props.routes.healthIndex" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-4 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5">
                        Open Health Dashboard
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Current Count</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(props.cycle.current_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Initial Count</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(props.cycle.initial_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Current Stage</p>
                <p class="mt-2 text-lg font-bold text-blue-900">{{ props.cycle.stage || '-' }}</p>
            </article>
            <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Current Status</p>
                <p class="mt-2 text-lg font-bold text-emerald-900">{{ props.cycle.status || '-' }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Last Reviewed</p>
                <p class="mt-2 text-sm font-bold text-gray-900">{{ formatDateTime(props.cycle.last_reviewed_at) }}</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Total Expenses</p>
                <p class="mt-2 text-base font-bold text-gray-900">{{ formatCurrency(expenseSummary.total_cycle_expense) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Total Sales</p>
                <p class="mt-2 text-base font-bold text-gray-900">{{ formatCurrency(expenseSummary.total_cycle_sales) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Net Profit / Loss</p>
                <p class="mt-2 text-base font-bold" :class="Number(profitabilitySummary.net_profit_or_loss || 0) < 0 ? 'text-rose-700' : 'text-emerald-700'">
                    {{ formatCurrency(profitabilitySummary.net_profit_or_loss) }}
                </p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Caretaker Share (50%)</p>
                <p class="mt-2 text-base font-bold text-gray-900">{{ formatCurrency(profitabilitySummary.caretaker_share) }}</p>
            </article>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="mx-auto max-w-300 space-y-4 xl:col-span-2">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Cycle Information</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-gray-500">Caretaker</dt>
                            <dd class="mt-1 text-gray-800">{{ props.cycle.caretaker?.name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Date of Purchase</dt>
                            <dd class="mt-1 text-gray-800">{{ formatDate(props.cycle.date_of_purchase) }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Cycle Number</dt>
                            <dd class="mt-1 text-gray-800">{{ props.cycle.cycle_number || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Average Weight</dt>
                            <dd class="mt-1 text-gray-800">{{ props.cycle.average_weight ? `${props.cycle.average_weight} kg` : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Profiles Enabled</dt>
                            <dd class="mt-1 text-gray-800">{{ props.cycle.has_pig_profiles ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ props.cycle.notes || 'No notes for this cycle yet.' }}
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Current Active State</p>
                            <dl class="mt-2 grid gap-2 sm:grid-cols-3">
                                <div>
                                    <dt class="font-semibold text-gray-600">Healthy Now</dt>
                                    <dd class="mt-1 text-gray-900">{{ healthyNow.toLocaleString() }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Currently Sick</dt>
                                    <dd class="mt-1 text-gray-900">{{ currentlySick.toLocaleString() }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Currently Isolated</dt>
                                    <dd class="mt-1 text-gray-900">{{ currentlyIsolated.toLocaleString() }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Historical Totals</p>
                            <dl class="mt-2 grid gap-2 sm:grid-cols-3">
                                <div>
                                    <dt class="font-semibold text-gray-600">Recovered</dt>
                                    <dd class="mt-1 text-gray-900">{{ totalRecoveredReported.toLocaleString() }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Deceased</dt>
                                    <dd class="mt-1 text-gray-900">{{ totalDeceasedReported.toLocaleString() }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Sold</dt>
                                    <dd class="mt-1 text-gray-900">{{ Number(countSummary.sold_count ?? 0).toLocaleString() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </article>

                <div class="grid gap-4 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Count Adjustments</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Use this when transfers, corrections, death, or sales require a current count adjustment.
                        </p>
                        <p v-if="isArchived" class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                            Cycle is archived and final. Count adjustments are disabled.
                        </p>
                        <button type="button" :disabled="isArchived" class="mt-4 inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:bg-[#0c6d57]/60" @click="openAdjustmentDialog">
                            Open Adjust Count Form
                        </button>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Stage / Status Updates</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Move the cycle through stages and statuses without leaving this page.
                        </p>
                        <button type="button" :disabled="isArchived" class="mt-4 inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/40 bg-white px-3 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5 disabled:cursor-not-allowed disabled:opacity-60" @click="openStatusDialog">
                            Open Status Update Form
                        </button>
                    </article>
                </div>
            </section>

            <aside class="mx-auto max-w-300 space-y-4">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Adjustment History</h3>
                    <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <div v-for="adjustment in adjustmentHistory" :key="adjustment.id" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                            <p class="font-semibold text-gray-900">
                                {{ adjustment.quantity_before }} -> {{ adjustment.quantity_after }}
                                <span class="text-xs uppercase tracking-[0.14em] text-gray-500">({{ adjustment.adjustment_type }})</span>
                            </p>
                            <p class="mt-1 text-xs text-gray-600">Reason: {{ adjustment.reason }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ adjustment.created_by?.name || adjustment.created_by_name || adjustment.created_by || 'System' }} - {{ formatDateTime(adjustment.created_at) }}</p>
                        </div>
                        <p v-if="adjustmentHistory.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                            No count adjustments yet.
                        </p>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Status History</h3>
                    <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <div v-for="history in statusHistory" :key="history.id" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                            <p class="font-semibold text-gray-900">{{ history.new_stage }} / {{ history.new_status }}</p>
                            <p class="mt-1 text-xs text-gray-600">From: {{ history.old_stage || '-' }} / {{ history.old_status || '-' }}</p>
                            <p v-if="history.remarks" class="mt-1 text-xs text-gray-600">{{ history.remarks }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ history.changed_by?.name || history.changed_by || 'System' }} - {{ formatDateTime(history.created_at) }}</p>
                        </div>
                        <p v-if="statusHistory.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                            No status updates yet.
                        </p>
                    </div>
                </article>
            </aside>
        </div>

        <TransitionRoot as="template" :show="isAdjustmentDialogOpen">
            <Dialog as="div" class="relative z-50" @close="closeAdjustmentDialog">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-200"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-150"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-200"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">Adjust Count</DialogTitle>
                                <p class="mt-1 text-sm text-gray-500">Record operational changes that affect current pig count.</p>

                                <form :action="props.routes.adjustmentsStore" method="POST" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <input type="hidden" name="_token" :value="props.csrfToken">

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Type</span>
                                        <select name="adjustment_type" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                            <option v-for="type in props.adjustmentTypes" :key="type" :value="type">{{ type }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Quantity Change</span>
                                        <input type="number" name="quantity_change" required placeholder="Use + or - for correction" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Resulting Count (optional)</span>
                                        <input type="number" min="0" name="quantity_after" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Reason</span>
                                        <select name="reason" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                            <option v-for="reason in props.adjustmentReasons" :key="reason" :value="reason">{{ reason }}</option>
                                        </select>
                                    </label>

                                    <label class="block sm:col-span-2">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                        <textarea name="remarks" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                                    </label>

                                    <div class="sm:col-span-2 mt-1 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" @click="closeAdjustmentDialog">
                                            Cancel
                                        </button>
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                            Save Adjustment
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isStatusDialogOpen">
            <Dialog as="div" class="relative z-50" @close="closeStatusDialog">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-200"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-150"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-200"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">Update Stage / Status</DialogTitle>
                                <p class="mt-1 text-sm text-gray-500">Move this cycle to a new stage or operational status.</p>

                                <form :action="props.routes.statusStore" method="POST" class="mt-4 space-y-3">
                                    <input type="hidden" name="_token" :value="props.csrfToken">

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Stage</span>
                                        <select name="new_stage" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                            <option value="">Keep {{ props.cycle.stage }}</option>
                                            <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Status</span>
                                        <select name="new_status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                            <option value="">Keep {{ props.cycle.status }}</option>
                                            <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                        <textarea name="remarks" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                                    </label>

                                    <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" @click="closeStatusDialog">
                                            Cancel
                                        </button>
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                            Save Status Update
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
