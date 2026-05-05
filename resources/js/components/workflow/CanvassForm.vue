<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    canvass: { type: Object, default: null },
    suppliers: { type: Array, default: () => [] },
    resolutions: { type: Array, default: () => [] },
    meetings: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
    isEditing: { type: Boolean, default: false },
})

const isEdit = props.isEditing

const form = ref({
    title: props.canvass?.title || '',
    canvass_date: props.canvass?.canvass_date || new Date().toISOString().split('T')[0],
    resolution_id: props.canvass?.resolution_id || '',
    meeting_id: props.canvass?.meeting_id || '',
    notes: props.canvass?.notes || '',
})

const items = ref(props.canvass?.items?.length > 0
    ? props.canvass.items.map(i => ({ ...i }))
    : [{ description: '', specifications: '', category: '', supplier_id: '', quantity: 1, unit: 'pc', unit_cost: 0, total: 0 }]
)

const submitting = ref(false)
const errors = ref({})

function addItem() {
    items.value.push({ description: '', specifications: '', category: '', supplier_id: '', quantity: 1, unit: 'pc', unit_cost: 0, total: 0 })
}

function removeItem(idx) { if (items.value.length > 1) items.value.splice(idx, 1) }

function calcTotal(idx) {
    const i = items.value[idx]
    i.total = (parseFloat(i.quantity) || 0) * (parseFloat(i.unit_cost) || 0)
}

const grandTotal = computed(() => items.value.reduce((s, i) => s + (parseFloat(i.total) || 0), 0))

async function submitForm() {
    submitting.value = true; errors.value = {}
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('title', form.value.title)
    fd.append('canvass_date', form.value.canvass_date)
    if (form.value.resolution_id) fd.append('resolution_id', form.value.resolution_id)
    if (form.value.meeting_id) fd.append('meeting_id', form.value.meeting_id)
    if (form.value.notes) fd.append('notes', form.value.notes)
    items.value.forEach((item, i) => {
        fd.append(`items[${i}][description]`, item.description)
        if (item.specifications) fd.append(`items[${i}][specifications]`, item.specifications)
        if (item.category) fd.append(`items[${i}][category]`, item.category)
        if (item.supplier_id) fd.append(`items[${i}][supplier_id]`, item.supplier_id)
        fd.append(`items[${i}][quantity]`, item.quantity)
        fd.append(`items[${i}][unit]`, item.unit)
        fd.append(`items[${i}][unit_cost]`, item.unit_cost)
    })
    try {
        const url = isEdit ? props.routes.update : props.routes.store
        const method = isEdit ? 'POST' : 'POST'
        if (isEdit && !url) return
        if (isEdit) fd.append('_method', 'PUT')
        const r = await fetch(url, { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { window.location.href = isEdit ? props.routes.index : props.routes.index }
        else if (r.status === 422) { errors.value = (await r.json()).errors || {} }
        else { errors.value = { general: ['Something went wrong.'] } }
    } catch { errors.value = { general: ['Network error.'] } }
    finally { submitting.value = false }
}
</script>

<template>
<form @submit.prevent="submitForm" class="space-y-6">
    <div v-if="errors.general" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p v-for="e in errors.general" :key="e">{{ e }}</p></div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Canvass Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input v-model="form.title" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
                <p v-if="errors.title" class="text-xs text-rose-600 mt-1">{{ errors.title[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input v-model="form.canvass_date" type="date" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Resolution</label>
                <select v-model="form.resolution_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    <option value="">-- None --</option>
                    <option v-for="r in resolutions" :key="r.id" :value="r.id">{{ r.resolution_number }} - {{ r.title }}</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Supplier Comparison Items</h2>
            <button type="button" @click="addItem" class="px-3 py-1.5 text-xs font-semibold text-[#0c6d57] bg-emerald-50 rounded-lg hover:bg-emerald-100 min-h-[36px]">+ Add Item</button>
        </div>

        <div v-for="(item, idx) in items" :key="idx" class="border rounded-xl p-4 mb-3 bg-gray-50/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-500">Item #{{ idx + 1 }}</span>
                <button type="button" @click="removeItem(idx)" v-if="items.length > 1" class="text-xs text-rose-600 hover:underline">Remove</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Description *</label>
                    <input v-model="item.description" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Supplier</label>
                    <select v-model="item.supplier_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">-- Select --</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Category</label>
                    <input v-model="item.category" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Specifications</label>
                    <input v-model="item.specifications" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Quantity *</label>
                    <input v-model.number="item.quantity" type="number" min="0.01" step="0.01" @input="calcTotal(idx)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Unit *</label>
                    <select v-model="item.unit" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="pc">Piece</option>
                        <option value="kg">Kilogram</option>
                        <option value="m">Meter</option>
                        <option value="L">Liter</option>
                        <option value="sack">Sack</option>
                        <option value="unit">Unit</option>
                        <option value="head">Head</option>
                        <option value="set">Set</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Unit Cost *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">₱</span>
                        <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" @input="calcTotal(idx)" class="w-full pl-8 pr-3 py-2 rounded-lg border border-gray-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Total</label>
                    <p class="py-2 text-sm font-semibold text-gray-900">₱ {{ (item.total || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-gray-200 mt-4">
            <p class="text-lg font-bold text-gray-900">Grand Total: ₱ {{ grandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a :href="routes.index" class="px-5 py-2.5 bg-gray-200 text-gray-800 font-semibold rounded-xl hover:bg-gray-300 min-h-[44px] inline-flex items-center">Cancel</a>
        <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update Canvass' : 'Create Canvass') }}
        </button>
    </div>
</form>
</template>