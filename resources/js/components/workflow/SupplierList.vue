<script setup>
import { ref } from 'vue'

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

const showForm = ref(false)
const editingId = ref(null)
const form = ref({ name: '', contact_person: '', contact_number: '', address: '', notes: '' })
const submitting = ref(false)
const errors = ref({})

function reset() { form.value = { name: '', contact_person: '', contact_number: '', address: '', notes: '' }; errors.value = {}; editingId.value = null }

function startEdit(s) {
    editingId.value = s.id; form.value = { name: s.name, contact_person: s.contact_person || '', contact_number: s.contact_number || '', address: s.address || '', notes: s.notes || '' }
}

async function submitForm() {
    submitting.value = true; errors.value = {}
    const fd = new FormData(); fd.append('_token', props.csrfToken)
    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v))
    try {
        const isEdit = editingId.value !== null
        const url = isEdit ? props.routes.update.replace('__ID__', editingId.value) : props.routes.store
        if (isEdit) fd.append('_method', 'PUT')
        const r = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { window.location.reload() }
        else if (r.status === 422) { errors.value = (await r.json()).errors || {} }
    } catch { errors.value = { general: ['Network error.'] } }
    finally { submitting.value = false }
}

async function deleteSupplier(s) {
    if (!confirm(`Delete "${s.name}"?`)) return
    const fd = new FormData(); fd.append('_token', props.csrfToken); fd.append('_method', 'DELETE')
    const r = await fetch(props.routes.delete.replace('__ID__', s.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
    if (r.ok) window.location.reload()
}
</script>

<template>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Suppliers</h1>
        <button @click="showForm = !showForm; if(showForm) reset()" class="px-4 py-2.5 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px]">
            {{ showForm ? 'Cancel' : '+ Add Supplier' }}
        </button>
    </div>

    <div v-if="showForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">New Supplier</h2>
        <div v-if="errors.general" class="mb-3 text-sm text-rose-600">{{ errors.general }}</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input v-model="form.name" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
                <p v-if="errors.name" class="text-xs text-rose-600 mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                <input v-model="form.contact_person" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input v-model="form.contact_number" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="form.address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></textarea>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button @click="submitForm" :disabled="submitting" class="px-5 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">
                {{ submitting ? 'Saving...' : 'Save Supplier' }}
            </button>
        </div>
    </div>

    <div v-if="suppliers.length === 0" class="text-center py-12 text-gray-400 text-sm">No suppliers yet.</div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="s in suppliers" :key="s.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div v-if="editingId === s.id">
                <div class="space-y-2">
                    <input v-model="form.name" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm" />
                    <input v-model="form.contact_person" placeholder="Contact person" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm" />
                    <input v-model="form.contact_number" placeholder="Contact number" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm" />
                    <textarea v-model="form.address" placeholder="Address" rows="2" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                    <textarea v-model="form.notes" placeholder="Notes" rows="2" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="editingId = null" class="px-3 py-1.5 text-xs bg-gray-200 rounded-lg min-h-[36px]">Cancel</button>
                    <button @click="submitForm" :disabled="submitting" class="px-3 py-1.5 text-xs bg-[#0c6d57] text-white rounded-lg min-h-[36px]">{{ submitting ? '...' : 'Save' }}</button>
                </div>
            </div>
            <div v-else>
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-900">{{ s.name }}</h3>
                </div>
                <p v-if="s.contact_person" class="text-xs text-gray-500">Contact: {{ s.contact_person }}</p>
                <p v-if="s.contact_number" class="text-xs text-gray-500">
                    <a :href="'tel:' + s.contact_number" class="text-[#0c6d57] hover:underline">{{ s.contact_number }}</a>
                </p>
                <p v-if="s.address" class="text-xs text-gray-400 mt-1">{{ s.address }}</p>
                <p v-if="s.notes" class="text-xs text-gray-400 mt-1">{{ s.notes }}</p>
                <div class="flex gap-2 mt-3">
                    <button @click="startEdit(s)" class="text-xs text-[#0c6d57] hover:underline">Edit</button>
                    <button @click="deleteSupplier(s)" class="text-xs text-rose-600 hover:underline">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>