<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { ref } from 'vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
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

const updateRoute = (pigId) => `${props.routes.profilesBase}/${pigId}`;
const deleteRoute = (pigId) => `${props.routes.profilesBase}/${pigId}`;
const isArchived = () => props.cycle.stage === 'Completed' || ['Sold', 'Closed'].includes(props.cycle.status);
const rowFormId = (pigId) => `pig-profile-row-form-${pigId}`;
const isDeleteModalOpen = ref(false);
const pigToDelete = ref(null);

const countsTowardCycle = (status) => !['Isolated', 'Sold', 'Deceased'].includes(status);

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
</script>

<template>
    <div class="mx-auto max-w-300 space-y-4">
        <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Dedicated Profile Manager: {{ props.cycle.batch_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Manage pig profiles separately from cycle details to keep workflows clean.</p>
            </div>
            <a :href="props.routes.show" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Cycle Detail
            </a>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div v-if="props.errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ props.errorMessage }}
        </div>

        <section v-if="!isArchived()" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-gray-900">Add Pig Profile</h3>
            <form :action="props.routes.store" method="POST" class="mt-4 grid gap-3 md:grid-cols-3">
                <input type="hidden" name="_token" :value="props.csrfToken">

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig No.</span>
                    <input type="number" min="1" name="pig_no" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</span>
                    <input type="text" name="ear_mark_type" placeholder="Left/Right cut" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</span>
                    <input type="text" name="ear_mark_value" placeholder="L-1" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</span>
                    <select name="sex" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">Not set</option>
                        <option v-for="sex in props.sexOptions" :key="sex" :value="sex">{{ sex }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                    <select name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option v-for="status in props.pigStatuses" :key="status" :value="status">{{ status }}</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                    <input type="text" name="remarks" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>

                <div class="md:col-span-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                        Add Pig Profile
                    </button>
                </div>

                <p class="md:col-span-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                    Automation: setting status to Isolated, Sold, or Deceased will auto-adjust the cycle current count.
                </p>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
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
                        <tr v-for="pig in props.cycle.pigs" :key="pig.id" :class="[!countsTowardCycle(pig.status) ? 'bg-amber-50/40' : '', 'transition-colors hover:bg-gray-50/50']">
                            <td class="px-3 py-2 align-top">
                                <input :form="rowFormId(pig.id)" type="number" min="1" name="pig_no" :value="pig.pig_no" class="w-16 rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input :form="rowFormId(pig.id)" type="text" name="ear_mark_type" :value="pig.ear_mark_type" placeholder="None" class="w-24 rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input :form="rowFormId(pig.id)" type="text" name="ear_mark_value" :value="pig.ear_mark_value" placeholder="None" class="w-24 rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                            </td>
                            <td class="px-3 py-2 align-top">
                                <select :form="rowFormId(pig.id)" name="sex" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                                    <option value="">-</option>
                                    <option v-for="sex in props.sexOptions" :key="`${pig.id}-${sex}`" :selected="pig.sex === sex" :value="sex">{{ sex }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex flex-col items-start gap-2">
                                    <select :form="rowFormId(pig.id)" name="status" class="w-32 rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                                        <option v-for="status in props.pigStatuses" :key="`${pig.id}-${status}`" :selected="pig.status === status" :value="status">{{ status }}</option>
                                    </select>
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.12em]" :class="statusBadgeClass(pig.status)">
                                            {{ pig.status }}
                                        </span>
                                        <span v-if="!countsTowardCycle(pig.status)" class="flex items-center gap-1 text-[10px] font-semibold text-amber-600" title="Excluded from active count">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0ZM8.94 6.94a.75.75 0 1 1-1.061-1.061 3 3 0 1 1 2.871 5.026v.345a.75.75 0 0 1-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 1 0 8.94 6.94ZM10 15a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                                            Excluded
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input :form="rowFormId(pig.id)" type="text" name="remarks" :value="pig.remarks" placeholder="Add remark" class="w-40 xl:w-full rounded-lg border border-gray-300 px-2 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]/20 transition-all">
                            </td>
                            <td class="px-3 py-2 text-right align-top">
                                <form :id="rowFormId(pig.id)" :action="updateRoute(pig.id)" method="POST" class="hidden">
                                    <input type="hidden" name="_token" :value="props.csrfToken">
                                    <input type="hidden" name="_method" value="PUT">
                                </form>
                                <div v-if="!isArchived()" class="flex w-full flex-col items-end gap-2">
                                    <button type="submit" :form="rowFormId(pig.id)" class="w-20 justify-center rounded-lg border border-emerald-600 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:ring-2 focus:ring-emerald-600/20">
                                        Update
                                    </button>
                                    <button type="button" class="w-20 justify-center rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 focus:ring-2 focus:ring-rose-600/20" @click="openDeleteModal(pig)">
                                        Delete
                                    </button>
                                </div>
                                <span v-else class="flex items-center justify-end rounded-lg bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-500">
                                    Locked
                                </span>
                            </td>
                        </tr>
                        <tr v-if="props.cycle.pigs.length === 0">
                            <td colspan="7" class="bg-gray-50/50 px-4 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-3 rounded-full bg-gray-100 p-3 text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900">No pig profiles recorded</p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">This cycle currently has no individual pig records. Use the Add Pig Profile form above to create identity records.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

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
                                    from cycle
                                    <span class="font-semibold text-gray-900">{{ props.cycle.batch_code }}</span>?
                                </p>

                                <p class="mt-2 text-xs text-gray-500">
                                    If this pig is currently counted as active, cycle count will auto-adjust.
                                </p>

                                <form
                                    v-if="pigToDelete"
                                    :action="deleteRoute(pigToDelete.id)"
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
