<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { computed, ref } from 'vue';
import CycleLifecycleTimeline from './CycleLifecycleTimeline.vue';

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
const isArchiveDialogOpen = ref(false);
const isSubmittingAdjustment = ref(false);
const isSubmittingStatus = ref(false);
const archiveChecklist = ref({
    sales: false,
    expenses: false,
    mortality: false,
    profitSharing: false,
});
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
const archiveChecklistComplete = computed(() => Object.values(archiveChecklist.value).every(Boolean));

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

const manualAdjustmentReasons = computed(() => props.adjustmentReasons.filter((reason) => reason !== 'mortality'));

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

const openArchiveDialog = () => {
    if (!isArchived.value) {
        isArchiveDialogOpen.value = true;
    }
};

const closeArchiveDialog = () => {
    isArchiveDialogOpen.value = false;
    archiveChecklist.value = {
        sales: false,
        expenses: false,
        mortality: false,
        profitSharing: false,
    };
};

const confirmArchive = async () => {
    if (!archiveChecklistComplete.value) {
        return;
    }

    // Submit the archive form programmatically
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = props.routes.archive;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = props.csrfToken;
    form.appendChild(csrfInput);

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PATCH';
    form.appendChild(methodInput);

    document.body.appendChild(form);
    form.submit();
};

const handleAdjustmentSubmit = async (event) => {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    // Store original button content
    const originalContent = submitBtn.innerHTML;

    // Show loading state
    isSubmittingAdjustment.value = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Saving...
    `;

    // Allow form to continue submission
    return true;
};

const handleStatusSubmit = async (event) => {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    // Show loading state
    isSubmittingStatus.value = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Saving...
    `;

    // Allow form to continue submission
    return true;
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
                    <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 transition hover:bg-gray-50 min-h-[48px]" :class="{'px-3 py-2 text-sm': false}">
                        <span class="hidden sm:inline">Back to Cycles</span>
                        <span class="sm:hidden">Back</span>
                    </a>
                    <a :href="props.routes.profilesIndex" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 transition hover:bg-gray-50 min-h-[48px]">
                        <span class="hidden sm:inline">Profile Manager</span>
                        <span class="sm:hidden">Profiles</span>
                    </a>
                    <a v-if="!isArchived" :href="props.routes.edit" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-4 py-3 text-base font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10 min-h-[48px]">
                        <span class="hidden sm:inline">Edit Cycle</span>
                        <span class="sm:hidden">Edit</span>
                    </a>
                    <button type="button" :disabled="isArchived" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-4 py-3 text-base font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5 disabled:cursor-not-allowed disabled:opacity-60 min-h-[48px]" @click="openAdjustmentDialog">
                        <span class="hidden sm:inline">Adjust Count</span>
                        <span class="sm:hidden">Adjust</span>
                    </button>
                    <a v-if="!isArchived" :href="props.routes.healthMortalityCreate" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-3 text-base font-semibold text-white transition hover:bg-rose-700 min-h-[48px]">
                        <span class="hidden sm:inline">Record Mortality</span>
                        <span class="sm:hidden">Mortality</span>
                    </a>
                    <button type="button" :disabled="isArchived" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-3 text-base font-semibold text-white transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2 min-h-[48px]" @click="openStatusDialog">
                        Update Stage / Status
                    </button>
                    <button v-if="!isArchived" type="button" class="inline-flex w-full items-center justify-center rounded-xl bg-gray-800 px-4 py-3 text-base font-semibold text-white transition hover:bg-gray-900 sm:col-span-2 min-h-[48px]" @click="openArchiveDialog">
                        <span class="hidden sm:inline">Close Cycle</span>
                        <span class="sm:hidden">Close</span>
                    </button>
                </div>
            </div>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div v-if="props.errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p class="font-semibold text-rose-800">Error</p>
                    <p class="mt-1 text-sm text-rose-700">{{ props.errorMessage }}</p>
                </div>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Expected Sale Date</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(countdown.expected_ready_for_sale_date) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Harvest Month</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ countdown.expected_harvest_month || '-' }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Days Since Acquisition</p>
                <p class="mt-2 text-lg font-semibold text-blue-900">{{ countdown.days_since_acquisition ?? '-' }}</p>
            </article>
            <article class="rounded-xl border p-4 shadow-sm" :class="countdown.is_overdue_for_sale_review ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'">
                <p class="text-xs font-semibold uppercase tracking-[0.18em]" :class="countdown.is_overdue_for_sale_review ? 'text-rose-700' : 'text-emerald-700'">Days Until Ready for Sale</p>
                <p class="mt-2 text-lg font-semibold" :class="countdown.is_overdue_for_sale_review ? 'text-rose-900' : 'text-emerald-900'">{{ countdown.days_until_ready_for_sale ?? '-' }}</p>
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

        <CycleLifecycleTimeline :cycle="props.cycle" :stages="props.stages" />

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
                    <a v-if="!isArchived" :href="props.routes.healthMortalityCreate" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Record Mortality
                    </a>
                    <a :href="props.routes.healthIndex" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-4 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5">
                        Open Health Dashboard
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Current Count</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Number(props.cycle.current_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Initial Count</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Number(props.cycle.initial_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Current Stage</p>
                <p class="mt-2 text-lg font-semibold text-blue-900">{{ props.cycle.stage || '-' }}</p>
            </article>
            <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Current Status</p>
                <p class="mt-2 text-lg font-semibold text-emerald-900">{{ props.cycle.status || '-' }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Last Reviewed</p>
                <p class="mt-2 text-sm font-semibold text-gray-900">{{ formatDateTime(props.cycle.last_reviewed_at) }}</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Total Expenses</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ formatCurrency(expenseSummary.total_cycle_expense) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Total Sales</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ formatCurrency(expenseSummary.total_cycle_sales) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Net Profit / Loss</p>
                <p class="mt-2 text-base font-semibold" :class="Number(profitabilitySummary.net_profit_or_loss || 0) < 0 ? 'text-rose-700' : 'text-emerald-700'">
                    {{ formatCurrency(profitabilitySummary.net_profit_or_loss) }}
                </p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Caretaker Share (50%)</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ formatCurrency(profitabilitySummary.caretaker_share) }}</p>
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
                            Use this when transfers, corrections, or sales require a current count adjustment.
                        </p>
                        <p class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">
                            Mortality adjustments are handled through Health Monitoring incidents.
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
                        <div v-if="adjustmentHistory.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No count adjustments yet.</p>
                            <p class="text-xs text-gray-400 mt-1">Use "Adjust Count" to record changes.</p>
                        </div>
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
                        <div v-if="statusHistory.length === 0" class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No status updates yet.</p>
                            <p class="text-xs text-gray-400 mt-1">Update status to track cycle progress.</p>
                        </div>
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
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Type</span>
                                        <select name="adjustment_type" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                            <option v-for="type in props.adjustmentTypes" :key="type" :value="type">{{ type }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Quantity Change</span>
                                        <input type="number" name="quantity_change" required placeholder="Use + or - for correction" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Resulting Count (optional)</span>
                                        <input type="number" min="0" name="quantity_after" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Reason</span>
                                        <select name="reason" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                            <option v-for="reason in manualAdjustmentReasons" :key="reason" :value="reason">{{ reason }}</option>
                                        </select>
                                    </label>

                                    <label class="block sm:col-span-2">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Remarks</span>
                                        <textarea name="remarks" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1"></textarea>
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
                                        <span class="mb-1 block text-sm font-medium text-gray-700">New Stage</span>
                                        <select name="new_stage" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                            <option value="">Keep {{ props.cycle.stage }}</option>
                                            <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">New Status</span>
                                        <select name="new_status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                                            <option value="">Keep {{ props.cycle.status }}</option>
                                            <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-gray-700">Remarks</span>
                                        <textarea name="remarks" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1"></textarea>
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

        <TransitionRoot as="template" :show="isArchiveDialogOpen">
            <Dialog as="div" class="relative z-50" @close="closeArchiveDialog">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">Close Cycle: Move to Archived Records</DialogTitle>
                                <p class="mt-2 text-sm text-gray-500">
                                    Closing <strong>{{ props.cycle.batch_code }}</strong> moves it to archived records.
                                    Editing operations will be restricted, but you can still view cycle details and historical data.
                                </p>

                                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3">
                                    <p class="text-sm font-bold text-rose-800">This action cannot be undone.</p>
                                    <p class="mt-1 text-xs text-rose-700">
                                        Once archived, the cycle becomes read-only. No new expenses, sales, health incidents, or count adjustments can be added.
                                        Please verify that all records are final before proceeding.
                                    </p>
                                </div>

                                <div class="mt-4 space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-3">
                                    <p class="text-sm font-bold text-gray-900">Before closing, confirm these records were checked:</p>
                                    <label class="flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                                        <input v-model="archiveChecklist.sales" type="checkbox" class="mt-1 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                        <span>Sales have been recorded.</span>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                                        <input v-model="archiveChecklist.expenses" type="checkbox" class="mt-1 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                        <span>Expenses have been reviewed.</span>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                                        <input v-model="archiveChecklist.mortality" type="checkbox" class="mt-1 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                        <span>Mortality/deceased records have been reviewed.</span>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                                        <input v-model="archiveChecklist.profitSharing" type="checkbox" class="mt-1 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                        <span>Profit-sharing summary has been checked.</span>
                                    </label>
                                </div>

                                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-5 w-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <p class="text-sm text-amber-800">Once archived, this cycle stays read-only unless a reopening flow already exists.</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" @click="closeArchiveDialog">
                                        Cancel
                                    </button>
                                    <button type="button" :disabled="!archiveChecklistComplete" class="inline-flex items-center justify-center rounded-xl bg-gray-800 px-3 py-2 text-sm font-semibold text-white transition hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-60" @click="confirmArchive">
                                        Close Cycle & Move to Archived Records
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
