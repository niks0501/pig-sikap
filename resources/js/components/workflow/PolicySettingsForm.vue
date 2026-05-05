<script setup>
import { ref, reactive } from 'vue'

const props = defineProps({
    settings: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

// Flatten settings into a reactive key-value map
const values = reactive({})
const descriptions = {}
const valueTypes = {}
const defaultValues = {
    meeting_schedule_day: 'Saturday',
    meeting_quorum_percentage: '50',
    attendance_penalty_amount: '0',
    attendance_consecutive_absent_limit: '3',
    dividend_rate_percentage: '0',
    rebate_rate_percentage: '0',
    resignation_notice_days: '30',
}

props.settings.forEach(group => {
    group.items.forEach(item => {
        values[item.key] = item.value
        descriptions[item.key] = item.description
        valueTypes[item.key] = item.value_type
    })
})

const groupLabels = {
    meeting: 'Meeting Settings',
    attendance: 'Attendance Penalties',
    financial: 'Financial / Dividend / Rebate',
    membership: 'Membership & Resignation',
}

const submitting = ref(false)
const success = ref(false)

async function saveSettings() {
    submitting.value = true; success.value = false
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('_method', 'PUT')
    let idx = 0
    props.settings.forEach(group => {
        group.items.forEach(item => {
            fd.append(`settings[${idx}][key]`, item.key)
            fd.append(`settings[${idx}][value]`, values[item.key] ?? item.value)
            idx++
        })
    })
    try {
        const r = await fetch(props.routes.update, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { success.value = true; setTimeout(() => success.value = false, 3000) }
    } catch { /* silent */ }
    finally { submitting.value = false }
}

function resetDefaults() {
    if (!confirm('Reset all settings to defaults?')) return
    Object.entries(defaultValues).forEach(([k, v]) => { values[k] = v })
}
</script>

<template>
<div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Association Policy Settings</h1>

    <div v-if="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">✅ Settings saved successfully.</div>

    <div v-for="group in settings" :key="group.group" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-6 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ groupLabels[group.group] || group.group }}</h2>
        </div>
        <div class="p-6 space-y-4">
            <div v-for="item in group.items" :key="item.key" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
                <div class="md:col-span-1">
                    <label class="text-sm font-medium text-gray-700">{{ item.key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}</label>
                    <p v-if="descriptions[item.key]" class="text-xs text-gray-400">{{ descriptions[item.key] }}</p>
                </div>
                <div class="md:col-span-2">
                    <div class="relative" v-if="valueTypes[item.key] === 'float' || valueTypes[item.key] === 'integer'">
                        <span v-if="item.key.includes('rate') || item.key.includes('percentage') || item.key.includes('quorum')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">%</span>
                        <span v-if="item.key.includes('penalty_amount')" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">₱</span>
                        <input :type="valueTypes[item.key] === 'float' ? 'number' : 'number'" v-model="values[item.key]"
                            :class="item.key.includes('rate') || item.key.includes('percentage') || item.key.includes('quorum') ? 'pr-8' : (item.key.includes('penalty_amount') ? 'pl-8' : '')"
                            :step="valueTypes[item.key] === 'float' ? '0.01' : '1'" min="0" :max="item.key.includes('percentage') || item.key.includes('quorum') ? '100' : ''"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
                    </div>
                    <input v-else-if="valueTypes[item.key] === 'boolean'" type="checkbox" v-model="values[item.key]" true-value="1" false-value="0" class="rounded border-gray-300" />
                    <input v-else v-model="values[item.key]" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" />
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <button @click="resetDefaults" class="px-5 py-2.5 bg-rose-50 text-rose-700 font-semibold rounded-xl hover:bg-rose-100 min-h-[44px] text-sm">Reset to Defaults</button>
        <button @click="saveSettings" :disabled="submitting" class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] min-h-[44px] disabled:opacity-50">
            {{ submitting ? 'Saving...' : 'Save All Settings' }}
        </button>
    </div>
</div>
</template>