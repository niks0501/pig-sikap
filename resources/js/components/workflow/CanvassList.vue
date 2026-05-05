<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    canvasses: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const search = ref('')
const statusFilter = ref('')

const filtered = computed(() => props.canvasses.filter(c => {
    if (search.value && !c.title.toLowerCase().includes(search.value.toLowerCase())) return false
    if (statusFilter.value && c.status !== statusFilter.value) return false
    return true
}))

const statusBadge = { draft: 'bg-gray-100 text-gray-700', in_progress: 'bg-blue-100 text-blue-700', awarded: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' }
</script>

<template>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Canvassing Records</h1>
        <a :href="routes.create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px]">+ New Canvass</a>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <input v-model="search" placeholder="Search canvasses..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
        <select v-model="statusFilter" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="in_progress">In Progress</option>
            <option value="awarded">Awarded</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div v-if="filtered.length === 0" class="text-center py-12 text-gray-400 text-sm">No canvasses found.</div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="c in filtered" :key="c.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ c.title }}</h3>
                <span :class="statusBadge[c.status]" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">{{ c.status.replace('_', ' ') }}</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">{{ c.canvass_date }}</p>
            <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
                <span>{{ c.item_count }} item(s)</span>
                <span v-if="c.winner_count > 0">🏆 {{ c.winner_count }} winner(s)</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">by {{ c.creator_name }}</span>
                <a :href="routes.show.replace('__ID__', c.id)" class="text-xs font-semibold text-[#0c6d57] hover:underline">View Details →</a>
            </div>
        </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 mt-6">
        <a v-for="p in pagination.last_page" :key="p" :href="pagination.path + '?page=' + p"
            class="px-3 py-1.5 rounded-lg text-sm" :class="p === pagination.current_page ? 'bg-[#0c6d57] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">{{ p }}</a>
    </div>
</div>
</template>