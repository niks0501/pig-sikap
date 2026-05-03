<script setup>
/**
 * MeetingDetail.vue – Shows meeting details, attendees list, and linked resolutions.
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
<div class="space-y-6">
    <!-- Meeting Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ meeting.title }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ meeting.date_formatted }} · {{ meeting.location || 'No location' }}</p>
            </div>
            <span :class="statusColors[meeting.status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">{{ meeting.status }}</span>
        </div>
        <div v-if="meeting.agenda" class="mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Agenda</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ meeting.agenda }}</p>
        </div>
        <div v-if="meeting.minutes_summary" class="mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Minutes Summary</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ meeting.minutes_summary }}</p>
        </div>
        <div v-if="meeting.minutes_file_url" class="mt-4">
            <a :href="meeting.minutes_file_url" target="_blank" class="inline-flex items-center gap-2 text-sm text-[#0c6d57] hover:underline font-medium">
                📎 View Attached Minutes
            </a>
        </div>
        <div class="text-xs text-gray-400 mt-4">Created by {{ meeting.creator_name }} · {{ meeting.created_at }}</div>
    </div>

    <!-- Attendees -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Attendees ({{ attendees.length }})</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div v-for="a in attendees" :key="a.id" class="flex items-center justify-between px-3 py-2.5 rounded-lg border border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600">{{ a.name?.charAt(0) }}</div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ a.name }}</p>
                        <p class="text-xs text-gray-500">{{ a.role }}</p>
                    </div>
                </div>
                <span :class="badge[a.attendance_status]" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">{{ a.attendance_status }}</span>
            </div>
        </div>
    </div>

    <!-- Resolutions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Resolutions</h2>
            <a :href="routes.createResolution" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Resolution
            </a>
        </div>
        <div v-if="resolutions.length === 0" class="text-center py-8 text-gray-400 text-sm">No resolutions yet. Create one from this meeting.</div>
        <div v-else class="space-y-3">
            <a v-for="r in resolutions" :key="r.id" :href="r.show_url" class="block px-4 py-3 rounded-lg border border-gray-100 hover:border-[#0c6d57]/30 hover:bg-gray-50 transition-all">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">{{ r.title }}</h3>
                    <span :class="resStatusColors[r.status]" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ r.status.replace('_', ' ') }}</span>
                </div>
            </a>
        </div>
    </div>
</div>
</template>
