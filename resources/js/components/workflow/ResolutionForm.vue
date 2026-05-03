<script setup>
import { ref, computed } from 'vue'
const props = defineProps({
    meeting: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})
const form = ref({
    meeting_id: props.meeting?.id || '',
    title: props.meeting ? `Resolution from: ${props.meeting.title}` : '',
    description: props.meeting?.agenda || '',
    approval_deadline: '',
})
const lineItems = ref([{ category: '', description: '', quantity: 1, unit: 'pc', unit_cost: 0 }])
const resFile = ref(null)
const submitting = ref(false)
const errors = ref({})

const grandTotal = computed(() => lineItems.value.reduce((s, li) => s + (li.quantity * li.unit_cost), 0))

function addLineItem() { lineItems.value.push({ category: '', description: '', quantity: 1, unit: 'pc', unit_cost: 0 }) }
function removeLineItem(i) { if (lineItems.value.length > 1) lineItems.value.splice(i, 1) }
function selectMeeting(id) {
    form.value.meeting_id = id
    const m = props.meetings.find(x => x.id === id)
    if (m) form.value.title = `Resolution from: ${m.title}`
}
function handleFile(e) { resFile.value = e.target.files[0] || null }

async function submitForm() {
    submitting.value = true; errors.value = {}
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('meeting_id', form.value.meeting_id)
    fd.append('title', form.value.title)
    fd.append('description', form.value.description)
    if (form.value.approval_deadline) fd.append('approval_deadline', form.value.approval_deadline)
    if (resFile.value) fd.append('resolution_file', resFile.value)
    lineItems.value.forEach((li, i) => {
        if (li.category || li.description) {
            fd.append(`line_items[${i}][category]`, li.category)
            fd.append(`line_items[${i}][description]`, li.description)
            fd.append(`line_items[${i}][quantity]`, li.quantity)
            fd.append(`line_items[${i}][unit]`, li.unit)
            fd.append(`line_items[${i}][unit_cost]`, li.unit_cost)
        }
    })
    try {
        const r = await fetch(props.routes.store, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { const d = await r.json(); window.location.href = d.redirect_url || props.routes.index }
        else if (r.status === 422) { errors.value = (await r.json()).errors || {} }
        else { errors.value = { general: ['Something went wrong.'] } }
    } catch { errors.value = { general: ['Network error.'] } }
    finally { submitting.value = false }
}

function formatCurrency(v) { return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
</script>
<template>
<form @submit.prevent="submitForm" class="space-y-6">
    <div v-if="errors.general" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p v-for="e in errors.general" :key="e">{{ e }}</p></div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Resolution Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2" v-if="!meeting">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Meeting *</label>
                <select v-model="form.meeting_id" @change="selectMeeting(Number($event.target.value))" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="">Choose a meeting...</option>
                    <option v-for="m in meetings" :key="m.id" :value="m.id">{{ m.title }} ({{ m.date }})</option>
                </select>
            </div>
            <div v-else class="md:col-span-2 bg-emerald-50 rounded-lg p-3 text-sm text-emerald-800">
                📅 From meeting: <strong>{{ meeting.title }}</strong> ({{ meeting.date }})
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input v-model="form.title" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
                <p v-if="errors.title" class="text-xs text-rose-600 mt-1">{{ errors.title[0] }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Approval Deadline</label>
                <input v-model="form.approval_deadline" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Signed Resolution (PDF/Image)</label>
                <input type="file" @change="handleFile" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0c6d57]/10 file:text-[#0c6d57]" />
            </div>
        </div>
    </div>

    <!-- Budget Line Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Budget Line Items</h2>
            <div class="text-sm font-bold text-[#0c6d57]" aria-live="polite">Total: {{ formatCurrency(grandTotal) }}</div>
        </div>
        <div class="space-y-3">
            <div v-for="(li, i) in lineItems" :key="i" class="grid grid-cols-12 gap-2 items-end">
                <div class="col-span-3"><label v-if="i===0" class="block text-xs font-medium text-gray-500 mb-1">Category</label><input v-model="li.category" placeholder="e.g. Feed" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" /></div>
                <div class="col-span-3"><label v-if="i===0" class="block text-xs font-medium text-gray-500 mb-1">Description</label><input v-model="li.description" placeholder="Details" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" /></div>
                <div class="col-span-1"><label v-if="i===0" class="block text-xs font-medium text-gray-500 mb-1">Qty</label><input v-model.number="li.quantity" type="number" min="0.01" step="0.01" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" /></div>
                <div class="col-span-1"><label v-if="i===0" class="block text-xs font-medium text-gray-500 mb-1">Unit</label><input v-model="li.unit" placeholder="pc" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" /></div>
                <div class="col-span-2"><label v-if="i===0" class="block text-xs font-medium text-gray-500 mb-1">Unit Cost</label><input v-model.number="li.unit_cost" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" /></div>
                <div class="col-span-1 text-right text-sm font-medium text-gray-700 py-2">{{ formatCurrency(li.quantity * li.unit_cost) }}</div>
                <div class="col-span-1"><button type="button" @click="removeLineItem(i)" :disabled="lineItems.length <= 1" class="p-2 text-gray-400 hover:text-rose-600 disabled:opacity-30"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>
            </div>
        </div>
        <button type="button" @click="addLineItem" class="mt-3 inline-flex items-center gap-1 text-sm text-[#0c6d57] hover:underline font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Line Item
        </button>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a :href="routes.index" class="px-5 py-2.5 bg-gray-200 text-gray-800 font-semibold rounded-xl hover:bg-gray-300 min-h-[44px] inline-flex items-center">Cancel</a>
        <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50 inline-flex items-center gap-2">
            {{ submitting ? 'Creating...' : 'Create Resolution' }}
        </button>
    </div>
</form>
</template>
