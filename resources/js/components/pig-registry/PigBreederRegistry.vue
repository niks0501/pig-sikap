<script setup>
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({ data: [], meta: {} }),
    },
    search: {
        type: String,
        default: '',
    },
    reproductiveStatuses: {
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
    oldInput: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
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

const searchForm = reactive({ search: props.search || '' });
const createForm = reactive({
    breeder_code: props.oldInput.breeder_code ?? '',
    name_or_tag: props.oldInput.name_or_tag ?? '',
    reproductive_status: props.oldInput.reproductive_status ?? 'Active',
    acquisition_date: props.oldInput.acquisition_date ?? '',
    expected_farrowing_date: props.oldInput.expected_farrowing_date ?? '',
    notes: props.oldInput.notes ?? '',
});

const meta = reactive({
    current_page: props.initialData?.meta?.current_page ?? 1,
    last_page: props.initialData?.meta?.last_page ?? 1,
    total: props.initialData?.meta?.total ?? 0,
});

let debounceTimer = null;

const fetchRows = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.routes.index, {
            params: {
                search: searchForm.search,
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
        flashError.value = 'Unable to refresh breeder registry right now.';
    } finally {
        loading.value = false;
    }
};

watch(
    () => searchForm.search,
    () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(() => {
            fetchRows(1);
        }, 300);
    }
);

const goToPage = (page) => {
    if (page < 1 || page > meta.last_page || loading.value) {
        return;
    }

    fetchRows(page);
};

const fieldError = (name) => props.errors[name]?.[0] ?? '';

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
</script>

<template>
    <div class="space-y-6">
        <section class="flex items-center gap-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <a :href="props.routes.batches" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Breeder Registry</h2>
                <p class="mt-1 text-sm text-gray-500">Maintain inahin records linked to pig batches.</p>
            </div>
        </section>

        <div v-if="flashStatus" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ flashStatus }}
        </div>

        <div v-if="flashError" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            {{ flashError }}
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-1">
                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Register Breeder</h3>
                    <form :action="props.routes.store" method="POST" class="mt-4 space-y-3">
                        <input type="hidden" name="_token" :value="props.csrfToken">

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Breeder Code *</span>
                            <input v-model="createForm.breeder_code" type="text" name="breeder_code" required placeholder="e.g. INA-001" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <span v-if="fieldError('breeder_code')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('breeder_code') }}</span>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Name / Tag *</span>
                            <input v-model="createForm.name_or_tag" type="text" name="name_or_tag" required placeholder="e.g. Inahin A" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <span v-if="fieldError('name_or_tag')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('name_or_tag') }}</span>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Reproductive Status *</span>
                            <select v-model="createForm.reproductive_status" name="reproductive_status" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option v-for="item in props.reproductiveStatuses" :key="item" :value="item">{{ item }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Acquisition Date</span>
                            <input v-model="createForm.acquisition_date" type="date" name="acquisition_date" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Expected Farrowing Date</span>
                            <input v-model="createForm.expected_farrowing_date" type="date" name="expected_farrowing_date" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Notes</span>
                            <textarea v-model="createForm.notes" name="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                        </label>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Save Breeder
                        </button>
                    </form>
                </article>
            </section>

            <section class="xl:col-span-2">
                <article class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search breeders</span>
                            <input v-model="searchForm.search" type="text" placeholder="Code, tag, or status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Tag Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Acquired</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Expected Farrowing</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-sm">
                                <tr v-if="loading && rows.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-gray-500">Loading breeder records...</td>
                                </tr>
                                <tr v-for="breeder in rows" :key="breeder.id">
                                    <td class="px-4 py-3 font-bold text-gray-900">{{ breeder.breeder_code }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ breeder.name_or_tag }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ breeder.reproductive_status }}</span></td>
                                    <td class="px-4 py-3 text-gray-700">{{ formatDate(breeder.acquisition_date) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ formatDate(breeder.expected_farrowing_date) }}</td>
                                </tr>
                                <tr v-if="!loading && rows.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-gray-500">No breeder records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
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
                </article>
            </section>
        </div>
    </div>
</template>
