<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    canvass: { type: Object, required: true },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

const statusBadge = { draft: 'bg-gray-100 text-gray-700', in_progress: 'bg-blue-100 text-blue-700', awarded: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' }

const groupedItems = computed(() => {
    const groups = {}
    props.canvass.items.forEach(item => {
        const key = item.description
        if (!groups[key]) groups[key] = { description: key, items: [] }
        groups[key].items.push(item)
    })
    return Object.values(groups)
})

async function selectItem(itemId) {
    if (!confirm('Mark this item as the winner?')) return
    try {
        const url = props.routes.selectItem.replace('__ITEM__', itemId)
        const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' } })
        if (r.ok) window.location.reload()
    } catch { /* silent */ }
}

const winnerItem = computed(() => props.canvass.items.find(i => i.is_selected))
</script>

<template>
<div class="space-y-6">
    <a :href="routes.index" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Canvasses
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ canvass.title }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ canvass.canvass_date }} · by {{ canvass.creator_name }}</p>
                <p v-if="canvass.resolution" class="text-xs text-gray-500 mt-1">Resolution: {{ canvass.resolution.resolution_number }} - {{ canvass.resolution.title }}</p>
                <p v-if="canvass.meeting" class="text-xs text-gray-500">Meeting: {{ canvass.meeting.title }}</p>
            </div>
            <span :class="statusBadge[canvass.status]" class="px-3 py-1 rounded-full text-xs font-semibold capitalize">{{ canvass.status.replace('_', ' ') }}</span>
        </div>
        <p v-if="canvass.notes" class="text-sm text-gray-600 whitespace-pre-line">{{ canvass.notes }}</p>
        <div class="flex gap-2 mt-4">
            <a :href="routes.edit" class="px-4 py-2 text-sm font-semibold text-[#0c6d57] bg-emerald-50 rounded-xl hover:bg-emerald-100 min-h-[44px] inline-flex items-center">Edit</a>
        </div>
    </div>

    <div v-if="winnerItem" class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800">
        🏆 Winner selected: <strong>{{ winnerItem.description }}</strong> at ₱{{ winnerItem.unit_cost.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}/{{ winnerItem.unit }}
        <span v-if="winnerItem.supplier"> ({{ winnerItem.supplier.name }})</span>
    </div>

    <div v-for="group in groupedItems" :key="group.description" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ group.description }}</h2>
        <div class="space-y-3">
            <div v-for="item in group.items" :key="item.id" class="flex items-center justify-between px-4 py-3 rounded-lg border"
                :class="item.is_selected ? 'border-emerald-300 bg-emerald-50' : 'border-gray-100'">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">{{ item.supplier?.name || 'No supplier' }}</span>
                        <span v-if="item.is_selected" class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Winner</span>
                    </div>
                    <p class="text-xs text-gray-500">{{ item.quantity }} {{ item.unit }} × ₱{{ item.unit_cost.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    <p v-if="item.specifications" class="text-xs text-gray-400">{{ item.specifications }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-sm font-semibold text-gray-900">₱{{ item.total.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    <button v-if="!item.is_selected && canvass.status !== 'awarded'" @click="selectItem(item.id)"
                        class="px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 min-h-[36px]">Select</button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="canvass.notes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ canvass.notes }}</p>
    </div>
</div>
</template>