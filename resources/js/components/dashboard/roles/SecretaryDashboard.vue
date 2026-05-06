<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    userName: { type: String, default: 'User' },
    roleName: { type: String, default: 'User' },
    dataUrl: { type: String, required: true },
    routes: { type: Object, default: () => ({}) },
});

const loading = ref(true);
const error = ref(null);
const data = ref(null);

async function fetchData() {
    try {
        loading.value = true;
        error.value = null;
        const res = await fetch(props.dataUrl);
        if (!res.ok) throw new Error('Failed to load dashboard data');
        data.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 space-y-6">
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-gray-400 text-sm">Loading your workspace...</div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center">
            <p class="text-red-600 font-medium">Could not load dashboard</p>
            <p class="text-sm text-red-500 mt-1">{{ error }}</p>
            <button @click="fetchData" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">Retry</button>
        </div>

        <!-- Content -->
        <template v-else-if="data">
            <!-- Welcome Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Good day, {{ data.user_name || props.userName }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-medium text-gray-600">
                            {{ props.roleName }}
                        </span>
                        <span class="text-xs text-gray-400">Secretary Workspace</span>
                    </div>
                </div>
                <span v-if="data.last_updated" class="text-xs text-gray-400 shrink-0">Updated {{ new Date(data.last_updated).toLocaleTimeString() }}</span>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.pending_meetings ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Pending Meetings</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.draft_resolutions ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Draft Resolutions</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.pending_signature ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Awaiting Signature</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.submitted_to_dswd ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Submitted to DSWD</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Resolutions -->
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Recent Resolutions</h2>
                        <a v-if="routes.resolutionsIndex" :href="routes.resolutionsIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View All</a>
                    </div>
                    <div class="p-3">
                        <div v-if="!data.recent_resolutions?.length" class="text-center py-8 text-sm text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="font-medium text-gray-500">No resolutions yet</p>
                            <p class="mt-1">Create your first resolution to begin tracking documentation.</p>
                        </div>
                        <div v-for="r in data.recent_resolutions" :key="r.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ r.title }}</p>
                                <p class="text-xs text-gray-400">{{ r.meeting_title || 'No meeting' }}</p>
                            </div>
                            <span :class="{
                                'px-2 py-0.5 rounded-full text-xs font-medium': true,
                                'bg-amber-50 text-amber-700': r.workflow_status === 'draft',
                                'bg-blue-50 text-blue-700': r.workflow_status === 'generated' || r.workflow_status === 'printed',
                                'bg-emerald-50 text-emerald-700': r.workflow_status === 'dswd_pending' || r.workflow_status === 'dswd_approved',
                                'bg-gray-50 text-gray-600': !['draft','generated','printed','dswd_pending','dswd_approved'].includes(r.workflow_status),
                            }">
                                {{ r.workflow_status?.replace(/_/g, ' ') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Meetings -->
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Recent Meetings</h2>
                        <a v-if="routes.meetingsIndex" :href="routes.meetingsIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View All</a>
                    </div>
                    <div class="p-3">
                        <div v-if="!data.recent_meetings?.length" class="text-center py-6 text-sm text-gray-400">
                            No meetings recorded yet.
                        </div>
                        <div v-for="m in data.recent_meetings" :key="m.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ m.title }}</p>
                                <p class="text-xs text-gray-400">{{ m.resolutions_count ?? 0 }} resolution(s)</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ new Date(m.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a v-if="routes.meetingsCreate" :href="routes.meetingsCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">New Meeting</span>
                    </a>
                    <a v-if="routes.resolutionsCreate" :href="routes.resolutionsCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">New Resolution</span>
                    </a>
                    <a v-if="routes.documentsUpload" :href="routes.documentsUpload" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">Upload Document</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</template>
