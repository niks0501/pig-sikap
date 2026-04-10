<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { computed } from 'vue';
import { ref } from 'vue';

const props = defineProps({
    batch: {
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
    pigStatuses: {
        type: Array,
        default: () => [],
    },
    sexOptions: {
        type: Array,
        default: () => [],
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

const isArchived = computed(() => props.batch.stage === 'Completed' || ['Sold', 'Closed'].includes(props.batch.status));

const adjustmentHistory = computed(() => {
    const items = Array.isArray(props.batch.adjustments) ? [...props.batch.adjustments] : [];

    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const statusHistory = computed(() => {
    const items = Array.isArray(props.batch.status_histories) ? [...props.batch.status_histories] : [];

    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const updatePigRoute = (pigId) => `${props.routes.pigsBase}/${pigId}`;
const deletePigRoute = (pigId) => `${props.routes.pigsBase}/${pigId}`;
const rowFormId = (pigId) => `batch-show-pig-row-form-${pigId}`;
const isDeleteModalOpen = ref(false);
const pigToDelete = ref(null);

const countsTowardBatch = (status) => !['Isolated', 'Sold', 'Deceased'].includes(status);

const statusBadgeClass = (status) => {
    if (status === 'Deceased') {
        return 'bg-rose-100 text-rose-700 border-rose-200';
    }

    if (status === 'Sold') {
        return 'bg-violet-100 text-violet-700 border-violet-200';
    }

    if (status === 'Isolated') {
        return 'bg-amber-100 text-amber-700 border-amber-200';
    }

    if (status === 'Sick') {
        return 'bg-orange-100 text-orange-700 border-orange-200';
    }

    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
};

const openDeleteModal = (pig) => {
    pigToDelete.value = pig;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    pigToDelete.value = null;
};

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
</script>

<template>
    <div class="space-y-4 max-w-[1200px] mx-auto">
        <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ props.batch.batch_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Registered {{ formatDateTime(props.batch.created_at) }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ props.batch.stage }}</span>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ props.batch.status }}</span>
                    <span v-if="isArchived" class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Archived</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Back to Registry
                </a>
                <a :href="props.routes.pigsIndex" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Manage Pig Profiles
                </a>
                <a v-if="!isArchived" :href="props.routes.edit" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                    Edit Batch
                </a>
                <form v-if="!isArchived" :action="props.routes.archive" method="POST" onsubmit="return confirm('Archive this batch? Operational editing will be restricted.');">
                    <input type="hidden" name="_token" :value="props.csrfToken">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gray-800 px-3 py-2 text-sm font-semibold text-white transition hover:bg-gray-900">
                        Archive / Close
                    </button>
                </form>
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
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Current Count</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(props.batch.current_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Initial Count</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Number(props.batch.initial_count || 0).toLocaleString() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Pig Profiles</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ Array.isArray(props.batch.pigs) ? props.batch.pigs.length : 0 }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Last Reviewed</p>
                <p class="mt-2 text-sm font-bold text-gray-900">{{ formatDateTime(props.batch.last_reviewed_at) }}</p>
            </article>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="space-y-4 max-w-[1200px] mx-auto xl:col-span-2">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Batch Information</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-gray-500">Breeder / Inahin</dt>
                            <dd class="mt-1 text-gray-800">
                                {{ props.batch.breeder?.breeder_code ?? 'No linked breeder' }}
                                <template v-if="props.batch.breeder?.name_or_tag">- {{ props.batch.breeder.name_or_tag }}</template>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Caretaker</dt>
                            <dd class="mt-1 text-gray-800">{{ props.batch.caretaker?.name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Birth Date</dt>
                            <dd class="mt-1 text-gray-800">{{ formatDate(props.batch.birth_date) }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Cycle Number</dt>
                            <dd class="mt-1 text-gray-800">{{ props.batch.cycle_number || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Average Weight</dt>
                            <dd class="mt-1 text-gray-800">{{ props.batch.average_weight ? `${props.batch.average_weight} kg` : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Profiles Enabled</dt>
                            <dd class="mt-1 text-gray-800">{{ props.batch.has_pig_profiles ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ props.batch.notes || 'No notes for this batch yet.' }}
                    </div>
                </article>

                <div class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Adjust Count</h3>
                        <p v-if="isArchived" class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                            Batch is archived. Reopen status first before adjusting count.
                        </p>
                        <form v-else :action="props.routes.adjustmentsStore" method="POST" class="mt-4 space-y-3">
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

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                <textarea name="remarks" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                            </label>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                Save Adjustment
                            </button>
                        </form>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Update Stage / Status</h3>
                        <form :action="props.routes.statusStore" method="POST" class="mt-4 space-y-3">
                            <input type="hidden" name="_token" :value="props.csrfToken">

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Stage</span>
                                <select name="new_stage" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    <option value="">Keep {{ props.batch.stage }}</option>
                                    <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Status</span>
                                <select name="new_status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    <option value="">Keep {{ props.batch.status }}</option>
                                    <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                <textarea name="remarks" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                            </label>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                Save Status Update
                            </button>
                        </form>
                    </article>
                </div>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-base font-bold text-gray-900">Pig Profiles</h3>
                        <a :href="props.routes.pigsIndex" class="text-sm font-semibold text-[#0c6d57] hover:text-[#0a5a48]">Open dedicated profile manager</a>
                    </div>

                    <form v-if="!isArchived" :action="props.routes.pigsStore" method="POST" class="mt-4 grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-2">
                        <input type="hidden" name="_token" :value="props.csrfToken">

                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig No.</span>
                            <input type="number" name="pig_no" min="1" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>
                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</span>
                            <select name="sex" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">Not set</option>
                                <option v-for="sex in props.sexOptions" :key="sex" :value="sex">{{ sex }}</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</span>
                            <input type="text" name="ear_mark_type" placeholder="e.g. Left cut" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>
                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</span>
                            <input type="text" name="ear_mark_value" placeholder="e.g. L-2" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>
                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                            <select name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option v-for="status in props.pigStatuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                            <input type="text" name="remarks" placeholder="Optional" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>
                        <div class="md:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                Add Pig Profile
                            </button>
                        </div>

                        <p class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                            Automation: setting status to Isolated, Sold, or Deceased will auto-adjust the batch current count.
                        </p>
                    </form>

                    <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig #</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="pig in props.batch.pigs" :key="pig.id" :class="!countsTowardBatch(pig.status) ? 'bg-amber-50/40' : ''">
                                    <td class="px-3 py-2 align-top">
                                        <input :form="rowFormId(pig.id)" type="number" name="pig_no" min="1" :value="pig.pig_no" class="w-16 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input :form="rowFormId(pig.id)" type="text" name="ear_mark_type" :value="pig.ear_mark_type" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input :form="rowFormId(pig.id)" type="text" name="ear_mark_value" :value="pig.ear_mark_value" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <select :form="rowFormId(pig.id)" name="sex" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                            <option value="">-</option>
                                            <option v-for="sex in props.sexOptions" :key="`${pig.id}-${sex}`" :selected="pig.sex === sex" :value="sex">{{ sex }}</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <div class="space-y-1.5">
                                            <select :form="rowFormId(pig.id)" name="status" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                                <option v-for="status in props.pigStatuses" :key="`${pig.id}-${status}`" :selected="pig.status === status" :value="status">{{ status }}</option>
                                            </select>
                                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em]" :class="statusBadgeClass(pig.status)">
                                                {{ pig.status }}
                                            </span>
                                            <p v-if="!countsTowardBatch(pig.status)" class="text-[10px] font-semibold uppercase tracking-[0.12em] text-amber-700">
                                                Excluded from active count
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input :form="rowFormId(pig.id)" type="text" name="remarks" :value="pig.remarks" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900">
                                    </td>
                                    <td class="px-3 py-2 text-right align-top">
                                        <form :id="rowFormId(pig.id)" :action="updatePigRoute(pig.id)" method="POST" class="hidden">
                                            <input type="hidden" name="_token" :value="props.csrfToken">
                                            <input type="hidden" name="_method" value="PUT">
                                        </form>
                                        <button v-if="!isArchived" type="submit" :form="rowFormId(pig.id)" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                            Update
                                        </button>
                                        <button v-if="!isArchived" type="button" class="ml-2 rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" @click="openDeleteModal(pig)">
                                            Delete
                                        </button>
                                        <span v-else class="text-xs font-medium text-gray-500">Locked</span>
                                    </td>
                                </tr>
                                <tr v-if="!props.batch.pigs || props.batch.pigs.length === 0">
                                    <td colspan="7" class="px-3 py-8 text-center text-sm font-medium text-gray-500">
                                        No pig profiles yet. Create a profile above or use the profile manager.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <aside class="space-y-4 max-w-[1200px] mx-auto">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Adjustment History</h3>
                    <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <div v-for="adjustment in adjustmentHistory" :key="adjustment.id" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                            <p class="font-semibold text-gray-900">
                                {{ adjustment.quantity_before }} -> {{ adjustment.quantity_after }}
                                <span class="text-xs uppercase tracking-[0.14em] text-gray-500">({{ adjustment.adjustment_type }})</span>
                            </p>
                            <p class="mt-1 text-xs text-gray-600">Reason: {{ adjustment.reason }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ adjustment.created_by?.name || adjustment.created_by?.name || adjustment.created_by_name || adjustment.created_by || 'System' }} - {{ formatDateTime(adjustment.created_at) }}</p>
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

        <TransitionRoot as="template" :show="isDeleteModalOpen">
            <Dialog as="div" class="relative z-50" @close="closeDeleteModal">
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
                            <DialogPanel class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">
                                    Delete Pig Profile
                                </DialogTitle>

                                <p class="mt-2 text-sm text-gray-600">
                                    Delete pig profile
                                    <span class="font-semibold text-gray-900">#{{ pigToDelete?.pig_no }}</span>
                                    from batch
                                    <span class="font-semibold text-gray-900">{{ props.batch.batch_code }}</span>?
                                </p>

                                <p class="mt-2 text-xs text-gray-500">
                                    If this pig is currently counted as active, batch count will auto-adjust.
                                </p>

                                <form
                                    v-if="pigToDelete"
                                    :action="deletePigRoute(pigToDelete.id)"
                                    method="POST"
                                    class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end"
                                >
                                    <input type="hidden" name="_token" :value="props.csrfToken">
                                    <input type="hidden" name="_method" value="DELETE">

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="closeDeleteModal"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                                    >
                                        Confirm Delete
                                    </button>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
