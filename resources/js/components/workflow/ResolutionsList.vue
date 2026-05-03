<script setup>
import { ref, computed } from 'vue'
const props = defineProps({
    resolutions: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})
const statusColors = {
    draft: 'bg-gray-100 text-gray-700', pending_approval: 'bg-amber-100 text-amber-700',
    approved: 'bg-blue-100 text-blue-700', dswd_submitted: 'bg-indigo-100 text-indigo-700',
    withdrawn: 'bg-emerald-100 text-emerald-700', finalized: 'bg-emerald-200 text-emerald-800',
}
const statusLabels = {
    draft: 'Draft', pending_approval: 'Pending Approval', approved: 'Approved',
    dswd_submitted: 'DSWD Submitted', withdrawn: 'Withdrawn', finalized: 'Finalized',
}
function goToPage(p) { const u = new URL(window.location.href); u.searchParams.set('page', p); window.location.href = u.toString() }
</script>
<template>
<div>
    <div v-if="resolutions.length === 0" class="text-center py-16">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <h3 class="text-lg font-semibold text-gray-600 mb-1">No resolutions yet</h3>
        <p class="text-sm text-gray-400 mb-4">Create a resolution from a confirmed meeting.</p>
        <a :href="routes.create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48]">New Resolution</a>
    </div>
    <div v-else class="space-y-3">
        <a v-for="r in resolutions" :key="r.id" :href="r.show_url"
            class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-[#0c6d57]/30 transition-all p-5">
            <div class="flex items-start justify-between mb-2">
                <span :class="statusColors[r.status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium">{{ statusLabels[r.status] || r.status }}</span>
                <span class="text-xs text-gray-400">{{ r.created_at }}</span>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ r.title }}</h3>
            <div class="flex items-center gap-4 text-xs text-gray-500 mt-3">
                <span v-if="r.meeting_title">📅 {{ r.meeting_title }}</span>
                <span>👤 {{ r.creator_name }}</span>
                <span>✅ {{ r.approvals_count }} approvals</span>
            </div>
        </a>
    </div>
    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2 mt-8">
        <button v-for="p in pagination.last_page" :key="p" @click="goToPage(p)"
            :class="p === pagination.current_page ? 'bg-[#0c6d57] text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'"
            class="px-3.5 py-2 rounded-lg text-sm font-medium min-w-[40px] min-h-[40px]">{{ p }}</button>
    </div>
</div>
</template>
