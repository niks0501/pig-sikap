<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({ data: [], meta: {} }),
    },
    search: {
        type: String,
        default: '',
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

const loading = ref(false);
const rows = ref(props.initialData?.data ?? []);
const flashStatus = ref(props.statusMessage || '');
const flashError = ref(props.errorMessage || '');
const isDeleteModalOpen = ref(false);
const cycleToDelete = ref(null);
const deleteCountdown = ref(0);
const form = reactive({ search: props.search || '' });
const meta = reactive({
    current_page: props.initialData?.meta?.current_page ?? 1,
    last_page: props.initialData?.meta?.last_page ?? 1,
    total: props.initialData?.meta?.total ?? 0,
});

let debounceTimer = null;
let deleteCountdownTimer = null;

const fetchRows = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.routes.archived, {
            params: {
                search: form.search,
                page,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        rows.value = response.data?.data || [];
        meta.current_page = response.data?.meta?.current_page ?? 1;
        meta.last_page = response.data?.meta?.last_page ?? 1;
        meta.total = response.data?.meta?.total ?? 0;
    } catch (error) {
        flashError.value = 'Unable to load archived cycles right now.';
    } finally {
        loading.value = false;
    }
};

watch(
    () => form.search,
    () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(() => {
            fetchRows(1);
        }, 300);
    }
);

const showCycleUrl = (cycleCode) => `${props.routes.showBase}/${encodeURIComponent(cycleCode)}`;
const deleteCycleUrl = (cycleCode) => `${props.routes.destroyBase}/${encodeURIComponent(cycleCode)}`;

const startDeleteCountdown = () => {
    deleteCountdown.value = 5;

    if (deleteCountdownTimer) {
        window.clearInterval(deleteCountdownTimer);
    }

    deleteCountdownTimer = window.setInterval(() => {
        if (deleteCountdown.value <= 1) {
            deleteCountdown.value = 0;
            window.clearInterval(deleteCountdownTimer);
            deleteCountdownTimer = null;

            return;
        }

        deleteCountdown.value -= 1;
    }, 1000);
};

const openDeleteModal = (cycle) => {
    cycleToDelete.value = cycle;
    isDeleteModalOpen.value = true;
    startDeleteCountdown();
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    cycleToDelete.value = null;
    deleteCountdown.value = 0;

    if (deleteCountdownTimer) {
        window.clearInterval(deleteCountdownTimer);
        deleteCountdownTimer = null;
    }
};

onBeforeUnmount(() => {
    if (deleteCountdownTimer) {
        window.clearInterval(deleteCountdownTimer);
    }
});

const goToPage = (page) => {
    if (page < 1 || page > meta.last_page || loading.value) {
        return;
    }

    fetchRows(page);
};

const formatDate = (value) => {
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
    <div class="mx-auto max-w-300 space-y-4">
        <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Archived Pig Cycles</h2>
                <p class="mt-1 text-sm text-gray-500">Completed and closed cycle records.</p>
            </div>
            <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Active Cycles
            </a>
        </section>

        <div v-if="flashStatus" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ flashStatus }}
        </div>

        <div v-if="flashError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ flashError }}
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search archived cycle</span>
                <input v-model="form.search" type="text" placeholder="Cycle code or caretaker" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
            </label>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Cycle</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Caretaker</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Final Count</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Stage / Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Updated</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-if="loading && rows.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">Loading archived records...</td>
                        </tr>
                        <tr v-for="cycle in rows" :key="cycle.batch_code">
                            <td class="px-3 py-2 font-bold text-gray-900">{{ cycle.batch_code }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ cycle.caretaker?.name || 'Unassigned' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ Number(cycle.current_count || 0).toLocaleString() }} / {{ Number(cycle.initial_count || 0).toLocaleString() }}</td>
                            <td class="px-3 py-2 text-gray-700">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ cycle.stage }}</span>
                                <span class="ml-1 rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-800">{{ cycle.status }}</span>
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ formatDate(cycle.updated_at) }}</td>
                            <td class="px-3 py-2 text-right">
                                <a :href="showCycleUrl(cycle.batch_code)" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                    View
                                </a>
                                <button type="button" class="ml-2 inline-flex items-center justify-center rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" @click="openDeleteModal(cycle)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">No archived cycles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-3 py-2">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-gray-500">
                    Page {{ meta.current_page }} of {{ meta.last_page }}
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1 || loading" @click="goToPage(meta.current_page - 1)">
                        Previous
                    </button>
                    <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page || loading" @click="goToPage(meta.current_page + 1)">
                        Next
                    </button>
                </div>
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
                                    Delete Archived Cycle
                                </DialogTitle>

                                <p class="mt-2 text-sm text-gray-600">
                                    You are about to permanently delete archived cycle
                                    <span class="font-semibold text-gray-900">{{ cycleToDelete?.batch_code }}</span>.
                                    This cannot be undone.
                                </p>

                                <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                    Safety countdown is required before delete can be confirmed.
                                </p>

                                <form
                                    v-if="cycleToDelete"
                                    :action="deleteCycleUrl(cycleToDelete.batch_code)"
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
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300"
                                        :disabled="deleteCountdown > 0"
                                    >
                                        {{ deleteCountdown > 0 ? `Delete in ${deleteCountdown}s` : 'Confirm Delete' }}
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
