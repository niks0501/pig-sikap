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
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-gray-400 text-sm">Loading your workspace...</div>
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center">
            <p class="text-red-600 font-medium">Could not load dashboard</p>
            <p class="text-sm text-red-500 mt-1">{{ error }}</p>
            <button @click="fetchData" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">Retry</button>
        </div>

        <template v-else-if="data">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Good day, {{ data.user_name || props.userName }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-medium text-gray-600">
                            {{ props.roleName }}
                        </span>
                        <span class="text-xs text-gray-400">Caretaker Workspace</span>
                    </div>
                </div>
                <span v-if="data.last_updated" class="text-xs text-gray-400 shrink-0">Updated {{ new Date(data.last_updated).toLocaleTimeString() }}</span>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.healthy_pigs ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Healthy</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.sick_pigs ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Sick</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.deceased_pigs ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Deceased</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.total_pigs ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Total Pigs</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Cycles -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Cycles</h2>
                    <a v-if="routes.healthIndex" :href="routes.healthIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View Health</a>
                </div>
                <div class="p-3">
                    <div v-if="!data.active_cycles?.length" class="text-center py-8 text-sm text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        <p class="font-medium text-gray-500">No active cycles assigned</p>
                        <p class="mt-1">Contact the President to assign you to a cycle.</p>
                    </div>
                    <div v-for="c in data.active_cycles" :key="c.id" class="flex items-center justify-between px-3 py-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ c.name }}</p>
                            <p class="text-xs text-gray-400">{{ c.pigs_count }} pig(s) &middot; {{ c.status }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Treatments -->
            <div v-if="data.upcoming_treatments?.length" class="rounded-2xl border border-amber-200 bg-amber-50 shadow-sm">
                <div class="p-5 border-b border-amber-200/50">
                    <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider">Upcoming Treatments</h2>
                </div>
                <div class="p-3">
                    <div v-for="t in data.upcoming_treatments" :key="t.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-amber-100/50 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ t.description }}</p>
                            <p class="text-xs text-gray-500">{{ t.cycle_name }}</p>
                        </div>
                        <span class="text-xs text-amber-700 font-medium">{{ t.scheduled_at ? new Date(t.scheduled_at).toLocaleDateString() : 'Not scheduled' }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a v-if="routes.healthCreate" :href="routes.healthCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">Report Health Incident</span>
                    </a>
                    <a v-if="routes.healthIndex" :href="routes.healthIndex" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">View Health Records</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</template>
