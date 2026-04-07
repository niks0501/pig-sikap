<script setup>
import { onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    fetchUrl: {
        type: String,
        required: true,
    },
});

const loading = ref(false);
const logs = ref([]);
const modules = ref([]);
const actions = ref([]);
const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const filters = reactive({
    search: '',
    module: '',
    action: '',
});

const fetchLogs = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.fetchUrl, {
            params: {
                search: filters.search,
                module: filters.module,
                action: filters.action,
                page,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        logs.value = response.data.data || [];
        meta.current_page = response.data.meta?.current_page ?? 1;
        meta.last_page = response.data.meta?.last_page ?? 1;
        meta.total = response.data.meta?.total ?? 0;
        modules.value = response.data.filters?.modules || [];
        actions.value = response.data.filters?.actions || [];
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page < 1 || page > meta.last_page) {
        return;
    }

    fetchLogs(page);
};

watch(
    () => [filters.search, filters.module, filters.action],
    () => fetchLogs(1)
);

onMounted(() => fetchLogs());
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Search action, module, user"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >

                <select
                    v-model="filters.module"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">All modules</option>
                    <option v-for="module in modules" :key="module" :value="module">{{ module }}</option>
                </select>

                <select
                    v-model="filters.action"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">All actions</option>
                    <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="p-6 text-sm text-slate-600">Loading activity logs...</div>

            <template v-else>
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Module</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-700">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ log.user }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ log.action }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ log.module }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ log.description }}</td>
                            </tr>
                            <tr v-if="logs.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No activity logs found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    <article v-for="log in logs" :key="`mobile-${log.id}`" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '-' }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ log.action }} • {{ log.module }}</p>
                        <p class="text-xs text-slate-600">{{ log.user }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ log.description }}</p>
                    </article>

                    <p v-if="logs.length === 0" class="py-4 text-center text-sm text-slate-500">No activity logs found.</p>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-sm text-slate-600">Total logs: {{ meta.total }}</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Previous
                </button>
                <span class="text-sm text-slate-600">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
