<script setup>
/**
 * MeetingsList.vue – Displays meeting cards with search and pagination.
 * Mounted by the workflow/meetings-index.blade.php view.
 */
import { ref, computed } from 'vue'

const props = defineProps({
    meetings: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    search: { type: String, default: '' },
})

const searchQuery = ref(props.search)

const statusColors = {
    draft: 'bg-gray-100 text-gray-700',
    confirmed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-rose-100 text-rose-700',
}

const statusLabels = {
    draft: 'Draft',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
}

function doSearch() {
    const url = new URL(props.routes.index, window.location.origin)
    if (searchQuery.value.trim()) {
        url.searchParams.set('search', searchQuery.value.trim())
    }
    window.location.href = url.toString()
}

function goToPage(page) {
    const url = new URL(window.location.href)
    url.searchParams.set('page', page)
    window.location.href = url.toString()
}
</script>

<template>
    <!-- Search Bar -->
    <div class="mb-6">
        <form @submit.prevent="doSearch" class="flex gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search meetings by title or agenda..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 bg-white text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57] focus:ring-1 transition-colors"
                    aria-label="Search meetings"
                />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Empty state -->
    <div v-if="meetings.length === 0" class="text-center py-16">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-600 mb-1">No meetings recorded yet</h3>
        <p class="text-sm text-gray-400 mb-4">Start by recording your first meeting.</p>
        <a :href="routes.create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Record New Meeting
        </a>
    </div>

    <!-- Meetings Grid -->
    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <a
            v-for="meeting in meetings"
            :key="meeting.id"
            :href="meeting.show_url"
            class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-[#0c6d57]/30 transition-all p-5 group"
        >
            <div class="flex items-start justify-between mb-3">
                <span :class="statusColors[meeting.status] || 'bg-gray-100 text-gray-700'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ statusLabels[meeting.status] || meeting.status }}
                </span>
                <span class="text-xs text-gray-400">{{ meeting.date_formatted }}</span>
            </div>

            <h3 class="font-semibold text-gray-900 group-hover:text-[#0c6d57] transition-colors mb-1 line-clamp-2">
                {{ meeting.title }}
            </h3>

            <p v-if="meeting.location" class="text-xs text-gray-500 mb-3">
                📍 {{ meeting.location }}
            </p>

            <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-50">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    {{ meeting.present_count }}/{{ meeting.total_attendees }} present
                </span>
                <span v-if="meeting.resolutions_count > 0" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    {{ meeting.resolutions_count }} resolution{{ meeting.resolutions_count > 1 ? 's' : '' }}
                </span>
                <span v-if="meeting.has_minutes_file" class="text-emerald-600">📎 File</span>
            </div>
        </a>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2 mt-8">
        <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="goToPage(page)"
            :class="page === pagination.current_page ? 'bg-[#0c6d57] text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'"
            class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors min-w-[40px] min-h-[40px]"
        >
            {{ page }}
        </button>
    </div>
</template>
