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
                        <span class="text-xs text-gray-400">Canvassing Officer Workspace</span>
                    </div>
                </div>
                <span v-if="data.last_updated" class="text-xs text-gray-400 shrink-0">Updated {{ new Date(data.last_updated).toLocaleTimeString() }}</span>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.open_canvasses ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Open Canvasses</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.total_canvasses ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Total Canvasses</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.total_suppliers ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Suppliers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Canvasses -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Recent Canvasses</h2>
                    <a v-if="routes.canvassesIndex" :href="routes.canvassesIndex" class="text-xs text-[#0c6d57] hover:underline font-medium">View All</a>
                </div>
                <div class="p-3">
                    <div v-if="!data.recent_canvasses?.length" class="text-center py-8 text-sm text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <p class="font-medium text-gray-500">No canvass records yet</p>
                        <p class="mt-1">Create your first canvass to compare supplier prices.</p>
                    </div>
                    <div v-for="c in data.recent_canvasses" :key="c.id" class="flex items-center justify-between px-3 py-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ c.title }}</p>
                            <p class="text-xs text-gray-400">{{ c.resolution_title || 'No resolution' }} &middot; {{ c.items_count }} item(s)</p>
                        </div>
                        <span :class="{
                            'px-2 py-0.5 rounded-full text-xs font-medium': true,
                            'bg-emerald-50 text-emerald-700': c.has_selected,
                            'bg-amber-50 text-amber-700': !c.has_selected,
                        }">
                            {{ c.has_selected ? 'Selected' : 'Open' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a v-if="routes.canvassesCreate" :href="routes.canvassesCreate" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">New Canvass</span>
                    </a>
                    <a v-if="routes.suppliersIndex" :href="routes.suppliersIndex" class="flex flex-col items-center p-4 rounded-xl border border-gray-200 hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-[#0c6d57]">View Suppliers</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</template>
