<script setup>
/**
 * MeetingDetail.vue – Shows meeting details, attendees list, and linked resolutions.
 *
 * Desktop: 2-column grid (info + attendees side-by-side, resolutions full-width).
 * Mobile:  single-column stack.
 */
import { ref } from 'vue'

const props = defineProps({
    meeting: { type: Object, required: true },
    attendees: { type: Array, default: () => [] },
    resolutions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
})

const badge = { present: 'bg-emerald-100 text-emerald-700', absent: 'bg-rose-100 text-rose-700', excused: 'bg-amber-100 text-amber-700' }
const statusColors = { draft: 'bg-gray-100 text-gray-700', confirmed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' }

const resStatusColors = {
    draft: 'bg-gray-100 text-gray-700', pending_approval: 'bg-amber-100 text-amber-700',
    approved: 'bg-blue-100 text-blue-700', dswd_submitted: 'bg-indigo-100 text-indigo-700',
    withdrawn: 'bg-emerald-100 text-emerald-700', finalized: 'bg-emerald-100 text-emerald-800',
}
</script>

<template>
<div class="space-y-4 lg:space-y-0 lg:grid lg:grid-cols-2 lg:gap-5">
    <!-- Meeting Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-3">
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-gray-900 truncate">{{ meeting.title }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">{{ meeting.date_formatted }} · {{ meeting.location || 'No location' }}</p>
            </div>
            <span :class="statusColors[meeting.status]" class="shrink-0 ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">{{ meeting.status }}</span>
        </div>
        <div v-if="meeting.agenda" class="mb-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-0.5 uppercase tracking-wide">Agenda</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line line-clamp-4">{{ meeting.agenda }}</p>
        </div>
        <div v-if="meeting.minutes_summary" class="mb-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-0.5 uppercase tracking-wide">Minutes Summary</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line line-clamp-4">{{ meeting.minutes_summary }}</p>
        </div>
        <div v-if="meeting.minutes_file_url">
            <a :href="meeting.minutes_file_url" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-[#0c6d57] hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View Attached Minutes
            </a>
        </div>
        <div class="text-[11px] text-gray-400 mt-3 pt-3 border-t border-gray-50">Created by {{ meeting.creator_name }} · {{ meeting.created_at }}</div>
    </div>

    <!-- Attendees -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:max-h-[calc(100vh-10rem)] lg:overflow-y-auto">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Attendees ({{ attendees.length }})</h2>
        <div class="space-y-1.5">
            <div v-for="a in attendees" :key="a.id" class="flex items-center justify-between px-2.5 py-2 rounded-lg border border-gray-100">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 shrink-0 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600">{{ a.name?.charAt(0) }}</div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ a.name }}</p>
                        <p class="text-[11px] text-gray-500 truncate">{{ a.role }}</p>
                    </div>
                </div>
                <span :class="badge[a.attendance_status]" class="shrink-0 ml-2 px-2 py-0.5 rounded-full text-[11px] font-medium capitalize">{{ a.attendance_status }}</span>
            </div>
        </div>
    </div>

    <!-- Resolutions (spans full width) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-900">Resolutions</h2>
            <a :href="routes.createResolution" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#0c6d57] text-white text-xs font-semibold rounded-lg hover:bg-[#0a5a48] transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Resolution
            </a>
        </div>
        <div v-if="resolutions.length === 0" class="text-center py-6 text-gray-400 text-sm">No resolutions yet. Create one from this meeting.</div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <a v-for="r in resolutions" :key="r.id" :href="r.show_url" class="block px-3.5 py-2.5 rounded-lg border border-gray-100 hover:border-[#0c6d57]/30 hover:bg-gray-50 transition-all">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-medium text-gray-900 truncate">{{ r.title }}</h3>
                    <span :class="resStatusColors[r.status]" class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-medium whitespace-nowrap">{{ r.status.replace('_', ' ') }}</span>
                </div>
            </a>
        </div>
    </div>
</div>
</template>