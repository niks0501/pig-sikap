<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    authorizedWithdrawers: { type: Array, default: () => [] },
    availableMembers: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

const emit = defineEmits(['updated'])

const localAuthorized = ref([...props.authorizedWithdrawers])
const selectedUserId = ref('')
const adding = ref(false)
const error = ref('')

async function addUser() {
    if (!selectedUserId.value) return
    adding.value = true; error.value = ''
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('user_ids[0]', selectedUserId.value)
    try {
        const r = await fetch(props.routes.authorizedWithdrawersStore, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) {
            const d = await r.json()
            d.authorizations.forEach(a => {
                if (!localAuthorized.value.find(ex => ex.user_id === a.user_id)) {
                    localAuthorized.value.push({
                        id: a.id,
                        user_id: a.user.id,
                        user_name: a.user.name,
                        designated_at: a.designated_at,
                    })
                }
            })
            selectedUserId.value = ''
        } else { error.value = 'Failed to add.' }
    } catch { error.value = 'Network error.' }
    finally { adding.value = false }
}

async function revoke(auth) {
    if (!confirm(`Revoke authorization for ${auth.user_name}?`)) return
    const url = props.routes.authorizedWithdrawersRevoke?.replace('__AUTH__', auth.id)
    if (!url) return
    const fd = new FormData(); fd.append('_token', props.csrfToken); fd.append('_method', 'DELETE')
    try {
        const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { localAuthorized.value = localAuthorized.value.filter(a => a.id !== auth.id) }
    } catch { /* silent */ }
}

const available = computed(() => props.availableMembers.filter(m => !localAuthorized.value.find(a => a.user_id === m.id)))
</script>

<template>
<div class="space-y-4">
    <div v-if="error" class="text-sm text-rose-600">{{ error }}</div>

    <div class="flex gap-2">
        <select v-model="selectedUserId" class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            <option value="">-- Select a member --</option>
            <option v-for="m in available" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
        <button @click="addUser" :disabled="adding || !selectedUserId" class="px-4 py-2.5 bg-[#0c6d57] text-white text-sm font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">
            {{ adding ? '...' : 'Add' }}
        </button>
    </div>

    <div v-if="localAuthorized.length === 0" class="text-center py-6 text-gray-400 text-sm">No authorized withdrawers yet. Add members above.</div>

    <div class="space-y-2">
        <div v-for="auth in localAuthorized" :key="auth.id" class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-100">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ auth.user_name }}</p>
                <p class="text-xs text-gray-500">Authorized {{ auth.designated_at }}</p>
            </div>
            <button @click="revoke(auth)" class="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 rounded-lg hover:bg-rose-100 min-h-[36px]">Revoke</button>
        </div>
    </div>
</div>
</template>