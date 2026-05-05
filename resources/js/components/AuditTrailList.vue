<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionRoot,
    TransitionChild
} from '@headlessui/vue';

const props = defineProps({
    fetchUrl: { type: String, required: true },
    exportUrl: { type: String, required: true },
});

const logs = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

let searchTimeout = null;

const fetchLogs = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.fetchUrl, {
            params: {
                search: searchQuery.value,
                page,
                per_page: 15,
            },
            headers: { Accept: 'application/json' },
        });

        logs.value = response.data.data || [];
        currentPage.value = response.data.meta?.current_page ?? 1;
        lastPage.value = response.data.meta?.last_page ?? 1;
        total.value = response.data.meta?.total ?? 0;
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    fetchLogs(page);
};

// Debounced search — fires server request after typing stops
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchLogs(1), 300);
});

onMounted(() => fetchLogs());

// Drawer state
const isDrawerOpen = ref(false);
const selectedActivity = ref(null);

const viewDetails = (activity) => {
    selectedActivity.value = activity;
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedActivity.value = null;
    }, 300);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

const getActionIcon = (action) => {
    const act = (action || '').toLowerCase();
    if (act.includes('created') || act.includes('add') || act.includes('registered') || act.includes('stored')) return 'plus-circle';
    if (act.includes('update') || act.includes('edit') || act.includes('changed')) return 'pencil-square';
    if (act.includes('delete') || act.includes('remove') || act.includes('destroy')) return 'trash';
    if (act.includes('approved') || act.includes('verify') || act.includes('finalize')) return 'check-circle';
    if (act.includes('login') || act.includes('logout')) return 'key';
    return 'document-text';
};

// Human-readable module label
const formatModule = (module) => {
    const labels = {
        pig_registry: 'Pig Registry',
        workflow: 'Workflow',
        health: 'Health',
        expenses: 'Expenses',
        sales: 'Sales',
        profitability: 'Profitability',
        reports: 'Reports',
        auth: 'Authentication',
        admin: 'Admin',
        documents: 'Documents',
    };
    return labels[module] || module;
};

const hasPrevPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < lastPage.value);
</script>

<template>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <!-- Controls & Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <!-- Search bar -->
            <div class="relative w-full sm:w-96 shrink-0">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm shadow-sm transition-colors"
                    placeholder="Search activities..."
                >
            </div>

            <!-- Export button -->
            <a
                :href="exportUrl + '?search=' + encodeURIComponent(searchQuery)"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm gap-2 shrink-0"
            >
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Export CSV
            </a>
        </div>

        <!-- Loading state -->
        <div v-if="loading && logs.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            Loading audit trail...
        </div>

        <!-- Logbook List View (Mobile-first card design) -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div
                v-if="logs.length === 0"
                class="p-8 text-center text-gray-500"
            >
                No audit logs found.
            </div>

            <ul v-else class="divide-y divide-gray-100">
                <li
                    v-for="log in logs"
                    :key="log.id"
                    @click="viewDetails(log)"
                    class="p-4 sm:px-6 hover:bg-gray-50 transition-colors cursor-pointer group"
                >
                    <div class="flex items-start gap-4">
                        <!-- Icon Indicator -->
                        <div class="mt-1 shrink-0 bg-emerald-50 text-[#0c6d57] w-10 h-10 rounded-xl flex items-center justify-center">
                            <svg v-if="getActionIcon(log.action) === 'plus-circle'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else-if="getActionIcon(log.action) === 'pencil-square'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <svg v-else-if="getActionIcon(log.action) === 'trash'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <svg v-else-if="getActionIcon(log.action) === 'check-circle'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else-if="getActionIcon(log.action) === 'key'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-1">
                                <p class="text-sm font-bold text-gray-900 truncate">
                                    {{ log.action }}
                                    <span v-if="log.reference" class="font-normal text-gray-500">on {{ log.reference }}</span>
                                </p>
                                <div class="text-xs text-gray-400 whitespace-nowrap shrink-0 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ formatDate(log.created_at) }}
                                </div>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-medium text-[#0c6d57]">{{ log.user }}</span>
                                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full font-medium">{{ formatModule(log.module) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500 line-clamp-1 sm:hidden">
                                {{ log.description }}
                            </p>
                        </div>

                        <!-- Chevron -->
                        <div class="shrink-0 self-center text-gray-300 group-hover:text-[#0c6d57] transition-colors ml-4 hidden sm:block">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="flex items-center justify-between mt-4 text-sm">
            <span class="text-gray-500">
                Showing {{ logs.length }} of {{ total }} entries
            </span>
            <div class="flex items-center gap-2">
                <button
                    :disabled="!hasPrevPage"
                    @click="goToPage(currentPage - 1)"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    Previous
                </button>
                <span class="text-gray-500 px-2">Page {{ currentPage }} of {{ lastPage }}</span>
                <button
                    :disabled="!hasNextPage"
                    @click="goToPage(currentPage + 1)"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Detail Drawer (Headless UI) -->
        <TransitionRoot as="template" :show="isDrawerOpen">
            <Dialog as="div" class="relative z-50" @close="closeDrawer">
                <TransitionChild
                    as="template"
                    enter="ease-in-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in-out duration-300"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <TransitionChild
                                as="template"
                                enter="transform transition ease-in-out duration-300 sm:duration-400"
                                enter-from="translate-x-full"
                                enter-to="translate-x-0"
                                leave="transform transition ease-in-out duration-300 sm:duration-400"
                                leave-from="translate-x-0"
                                leave-to="translate-x-full"
                            >
                                <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl">
                                        <!-- Header -->
                                        <div class="bg-[#0c6d57] px-4 py-6 sm:px-6">
                                            <div class="flex items-center justify-between">
                                                <DialogTitle class="text-lg font-bold text-white">Log Details</DialogTitle>
                                                <div class="ml-3 flex h-7 items-center">
                                                    <button type="button" class="relative text-[#0c6d57] bg-white hover:bg-gray-100 rounded-full p-1.5 focus:outline-none focus:ring-2 focus:ring-white transition-colors" @click="closeDrawer">
                                                        <span class="absolute -inset-2.5" />
                                                        <span class="sr-only">Close panel</span>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-1">
                                                <p class="text-sm text-emerald-100">Detailed view of the specific system activity.</p>
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="relative flex-1 px-4 py-6 sm:px-6 bg-gray-50" v-if="selectedActivity">
                                            <!-- Activity Overview Card -->
                                            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm mb-4">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Activity Overview</h3>
                                                <div class="space-y-4">
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Action</span>
                                                        <span class="block text-sm font-bold text-gray-900">{{ selectedActivity.action }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Module</span>
                                                        <span class="block text-sm font-medium text-[#0c6d57]">{{ formatModule(selectedActivity.module) }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Description</span>
                                                        <span class="block text-sm text-gray-700">{{ selectedActivity.description }}</span>
                                                    </div>
                                                    <div v-if="selectedActivity.reference">
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Reference</span>
                                                        <span class="block text-sm text-gray-700">{{ selectedActivity.reference }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Date/Time</span>
                                                        <span class="block text-sm text-gray-700">{{ formatDate(selectedActivity.created_at) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- User Info Card -->
                                            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm mb-4">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">User Information</h3>
                                                <div class="flex items-center gap-3">
                                                    <div class="h-10 w-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center font-bold text-gray-600">
                                                        {{ (selectedActivity.user || 'S').substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-gray-900">{{ selectedActivity.user }}</p>
                                                        <p class="text-xs text-gray-500 font-medium">{{ selectedActivity.email }}</p>
                                                    </div>
                                                </div>
                                                <div v-if="selectedActivity.ip_address" class="mt-3 pt-3 border-t border-gray-100">
                                                    <span class="block text-xs text-gray-500 font-medium mb-1">IP Address</span>
                                                    <span class="block text-sm text-gray-700 font-mono">{{ selectedActivity.ip_address }}</span>
                                                </div>
                                            </div>

                                            <!-- Context Details -->
                                            <div v-if="selectedActivity.context_json && Object.keys(selectedActivity.context_json).length > 0" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Context Data</h3>
                                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                                    <div v-for="(value, key) in selectedActivity.context_json" :key="key" class="flex justify-between py-1.5 text-sm" :class="{ 'border-b border-gray-200': key !== Object.keys(selectedActivity.context_json).at(-1) }">
                                                        <span class="text-gray-500 font-medium">{{ key }}</span>
                                                        <span class="text-gray-900 font-mono text-xs max-w-[60%] text-right break-all">{{ value }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
