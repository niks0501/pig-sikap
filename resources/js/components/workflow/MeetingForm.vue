<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    members: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

const form = ref({
    title: '',
    date: new Date().toISOString().split('T')[0],
    location: '',
    agenda: '',
    minutes_summary: '',
    status: 'draft',
    meeting_type: 'pig_production',
})

// Default structured agenda per meeting type
const defaultAgendas = {
    pig_production: [
        'Canvassing Assign Person',
        'Canvassing Date',
        'Number of Piglets to Buy',
        'Number of Sacks of Feeds',
        'Feed Price',
        'Medicines for Piglets',
        'Caretaker / Place of Raising',
        'Raising Duration',
        'Group Policy',
    ],
    monthly_association: [
        'Call to Order',
        'Roll Call / Attendance',
        'Reading of Previous Minutes',
        "Treasurer's Report",
        'Attendance Review & Penalties',
        'Old Business / Matters Arising',
        'New Business',
        'Adjournment',
    ],
    general: [
        'Opening / Call to Order',
        'Old Business',
        'New Business',
        'Other Matters',
        'Adjournment',
    ],
}

// Structured agenda items (sent as agenda_json to backend)
const agendaItems = ref([...defaultAgendas.pig_production])

// Watch meeting_type change and auto-fill agenda
watch(() => form.value.meeting_type, (newType) => {
    if (defaultAgendas[newType]) {
        agendaItems.value = [...defaultAgendas[newType]]
    }
})

function addAgendaItem() {
    agendaItems.value.push('')
}
function removeAgendaItem(index) {
    if (agendaItems.value.length > 1) agendaItems.value.splice(index, 1)
}

const showPenaltyPreview = ref(false)
const applyPenalties = ref(true)

const minutesFile = ref(null)
const attendees = ref(props.members.map(m => ({
    user_id: m.id, name: m.name, role: m.role, attendance_status: 'present',
})))
const submitting = ref(false)
const errors = ref({})

const meetingTypeLabels = {
    pig_production: 'Pig Production / Purchase',
    monthly_association: 'Monthly Association Meeting',
    general: 'General Meeting',
}

const presentCount = computed(() => attendees.value.filter(a => a.attendance_status === 'present').length)

const absentMembers = computed(() => attendees.value.filter(a => a.attendance_status === 'absent'))
const penaltyAmount = 50
const totalPenaltyAmount = computed(() => absentMembers.value.length * penaltyAmount)

function toggleAttendance(a) {
    const s = ['present', 'absent', 'excused']
    a.attendance_status = s[(s.indexOf(a.attendance_status) + 1) % s.length]
}

function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    if (file.size > 10485760) { errors.value.minutes_file = ['File must be 10 MB or less.']; minutesFile.value = null; return }
    minutesFile.value = file
    delete errors.value.minutes_file
}

async function submitForm() {
    submitting.value = true; errors.value = {}
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    Object.entries(form.value).forEach(([k,v]) => fd.append(k,v))
    // Send structured agenda as JSON
    const nonEmptyAgenda = agendaItems.value.filter(item => item.trim() !== '')
    fd.append('agenda_json', JSON.stringify(nonEmptyAgenda))
    if (minutesFile.value) fd.append('minutes_file', minutesFile.value)
    attendees.value.forEach((a,i) => { fd.append(`attendees[${i}][user_id]`, a.user_id); fd.append(`attendees[${i}][attendance_status]`, a.attendance_status) })
    try {
        const r = await fetch(props.routes.store, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, body: fd })
        if (r.ok) { const d = await r.json(); window.location.href = d.redirect_url || props.routes.index }
        else if (r.status === 422) { errors.value = (await r.json()).errors || {} }
        else { errors.value = { general: ['Something went wrong.'] } }
    } catch { errors.value = { general: ['Network error.'] } }
    finally { submitting.value = false }
}

const badge = { present: 'bg-emerald-100 text-emerald-700', absent: 'bg-rose-100 text-rose-700', excused: 'bg-amber-100 text-amber-700' }
</script>

<template>
<form @submit.prevent="submitForm" class="space-y-6">
    <div v-if="errors.general" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p v-for="e in errors.general" :key="e">{{ e }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Meeting Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input v-model="form.title" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" placeholder="e.g. Regular Monthly Meeting" />
                <p v-if="errors.title" class="text-xs text-rose-600 mt-1">{{ errors.title[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input v-model="form.date" type="date" required :max="new Date().toISOString().split('T')[0]" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input v-model="form.location" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" placeholder="e.g. Barangay Hall" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meeting Type</label>
                <select v-model="form.meeting_type" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="pig_production">{{ meetingTypeLabels.pig_production }}</option>
                    <option value="monthly_association">{{ meetingTypeLabels.monthly_association }}</option>
                    <option value="general">{{ meetingTypeLabels.general }}</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Selecting a type will auto-fill the structured agenda below.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="form.status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="draft">Draft</option>
                    <option value="confirmed">Confirmed</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Agenda Items</label>
                <p class="text-xs text-gray-500 mb-2">Edit the default agenda fields. These match the association's resolution template.</p>
                <div class="space-y-2">
                    <div v-for="(item, i) in agendaItems" :key="i" class="flex items-center gap-2">
                        <input v-model="agendaItems[i]" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" :placeholder="'Agenda item ' + (i + 1)" />
                        <button type="button" @click="removeAgendaItem(i)" :disabled="agendaItems.length <= 1" class="p-1.5 text-gray-400 hover:text-rose-600 disabled:opacity-30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <button type="button" @click="addAgendaItem" class="mt-2 inline-flex items-center gap-1 text-sm text-[#0c6d57] hover:underline font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Agenda Item
                </button>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Agenda Notes (free-text)</label>
                <textarea v-model="form.agenda" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" placeholder="Additional notes or free-text agenda..."></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Minutes Summary</label>
                <textarea v-model="form.minutes_summary" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" placeholder="Summary of discussion and decisions..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Attach Minutes (PDF/Image)</label>
                <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0c6d57]/10 file:text-[#0c6d57]" />
                <p v-if="errors.minutes_file" class="text-xs text-rose-600 mt-1">{{ errors.minutes_file[0] }}</p>
                <p v-if="minutesFile" class="text-xs text-emerald-600 mt-1">📎 {{ minutesFile.name }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Attendance Checklist</h2>
            <span class="text-sm text-gray-500">{{ presentCount }}/{{ attendees.length }} present</span>
        </div>
        <div class="space-y-2">
            <button v-for="a in attendees" :key="a.user_id" type="button" @click="toggleAttendance(a)"
                class="w-full flex items-center justify-between px-4 py-3 rounded-lg border transition-colors hover:bg-gray-50 min-h-[48px]"
                :class="a.attendance_status === 'present' ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200'">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                        :class="a.attendance_status === 'present' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                        {{ a.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-900">{{ a.name }}</p>
                        <p class="text-xs text-gray-500">{{ a.role }}</p>
                    </div>
                </div>
                <span :class="badge[a.attendance_status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">{{ a.attendance_status }}</span>
            </button>
        </div>
    </div>
    <div class="flex items-center justify-end gap-3">
        <a :href="routes.index" class="px-5 py-2.5 bg-gray-200 text-gray-800 font-semibold rounded-xl hover:bg-gray-300 transition-colors min-h-[44px] inline-flex items-center">Cancel</a>
        <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px] disabled:opacity-50 inline-flex items-center gap-2">
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ submitting ? 'Saving...' : 'Save Meeting' }}
        </button>
    </div>

    <!-- Penalty Preview Panel -->
    <div v-if="form.status === 'confirmed' && absentMembers.length > 0" class="rounded-xl border-2 border-amber-300 bg-amber-50/50 p-6">
        <h3 class="text-sm font-semibold text-amber-800 mb-2">⚠️ Attendance Penalty Preview</h3>
        <p class="text-xs text-amber-700 mb-3">{{ absentMembers.length }} absent member(s) will receive a penalty of ₱{{ penaltyAmount }} each (₱{{ totalPenaltyAmount }} total).</p>
        <div class="space-y-1 mb-4">
            <div v-for="a in absentMembers" :key="a.user_id" class="flex items-center gap-2 px-3 py-1.5 bg-white/80 rounded-lg">
                <span class="text-xs font-medium text-gray-900">{{ a.name }}</span>
                <span class="text-xs text-amber-600">₱{{ penaltyAmount }}</span>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" v-model="applyPenalties" class="rounded border-gray-300" />
            Auto-apply penalties on save
        </label>
        <div class="flex gap-2 mt-3">
            <button type="submit" :disabled="submitting" class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-xl hover:bg-amber-700 min-h-[44px]">
                {{ submitting ? 'Saving...' : 'Confirm & Apply Penalties' }}
            </button>
        </div>
    </div>
</form>
</template>
