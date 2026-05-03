<script setup>
/**
 * WithdrawalForm.vue – Fund withdrawal request form with balance display.
 */
import { ref, computed } from 'vue'

const props = defineProps({
    resolution: { type: Object, required: true },
    routes: { type: Object, default: () => ({}) },
    csrfToken: { type: String, default: '' },
})

const form = ref({ amount: '', bank_account: '', notes: '' })
const proofFile = ref(null)
const submitting = ref(false)

// All validation/server errors stored here
const fieldErrors = ref({})  // field-level errors (e.g. amount)
const alertMessages = ref([]) // top-level user-friendly messages

const fmt = v => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })

/**
 * Convert raw Laravel validation errors into user-friendly messages.
 * Hides technical field keys from the user.
 */
function parseErrors(errors) {
    fieldErrors.value = {}
    alertMessages.value = []

    const fieldLabelMap = {
        amount: 'Amount',
        bank_account: 'Bank Account',
        proof_file: 'Proof File',
        notes: 'Notes',
    }

    for (const [key, messages] of Object.entries(errors)) {
        if (key === 'resolution') {
            // Eligibility errors — shown as top-level notices
            alertMessages.value.push(...messages)
        } else if (fieldLabelMap[key]) {
            // Field-level errors — shown inline under each input
            fieldErrors.value[key] = messages
        } else {
            // Any other unknown error — show as top-level notice
            alertMessages.value.push(...messages)
        }
    }
}

// Simple client-side check before submitting
function validateForm() {
    alertMessages.value = []
    fieldErrors.value = {}

    if (!form.value.amount || form.value.amount <= 0) {
        fieldErrors.value.amount = ['Please enter a valid amount.']
        return false
    }
    if (form.value.amount > props.resolution.remaining_balance) {
        fieldErrors.value.amount = [`Amount cannot exceed the available balance of ${fmt(props.resolution.remaining_balance)}.`]
        return false
    }
    return true
}

async function submitForm() {
    if (!validateForm()) return

    submitting.value = true
    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append('amount', form.value.amount)
    fd.append('bank_account', form.value.bank_account)
    fd.append('notes', form.value.notes)
    if (proofFile.value) fd.append('proof_file', proofFile.value)

    try {
        const r = await fetch(props.routes.store, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrfToken },
            body: fd,
        })

        if (r.ok) {
            const d = await r.json()
            window.location.href = d.redirect_url || props.routes.back
        } else if (r.status === 422) {
            const d = await r.json()
            parseErrors(d.errors || {})
        } else if (r.status === 403) {
            alertMessages.value = ['You do not have permission to create a withdrawal.']
        } else {
            alertMessages.value = ['Something went wrong. Please try again or contact your officer.']
        }
    } catch {
        alertMessages.value = ['Could not connect to the server. Please check your internet connection and try again.']
    } finally {
        submitting.value = false
    }
}
</script>

<template>
<form @submit.prevent="submitForm" class="space-y-6">

    <!-- Top-level alert messages (eligibility failures, general errors) -->
    <div v-if="alertMessages.length > 0" role="alert"
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold mb-1">Unable to create withdrawal</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="msg in alertMessages" :key="msg">{{ msg }}</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Resolution Balance</h2>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-xs text-gray-500">Approved Budget</p>
                <p class="text-lg font-bold text-gray-900">{{ fmt(resolution.grand_total) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Already Withdrawn</p>
                <p class="text-lg font-bold text-gray-900">{{ fmt(resolution.total_withdrawn) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Available to Withdraw</p>
                <p class="text-lg font-bold" :class="resolution.remaining_balance > 0 ? 'text-emerald-600' : 'text-rose-500'">
                    {{ fmt(resolution.remaining_balance) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Withdrawal Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Withdrawal Details</h2>
        <div class="space-y-4">

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Withdraw *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">₱</span>
                    <input
                        v-model.number="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="resolution.remaining_balance"
                        required
                        id="withdrawal-amount"
                        aria-describedby="amount-hint"
                        :class="fieldErrors.amount ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500' : 'border-gray-300 focus:border-[#0c6d57] focus:ring-[#0c6d57]'"
                        class="w-full pl-8 pr-4 py-2.5 rounded-lg border text-sm"
                        :placeholder="'Max: ' + fmt(resolution.remaining_balance)"
                    />
                </div>
                <p id="amount-hint" class="text-xs text-gray-500 mt-1">
                    Maximum you can withdraw: <strong>{{ fmt(resolution.remaining_balance) }}</strong>
                </p>
                <p v-if="fieldErrors.amount" role="alert" class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ fieldErrors.amount[0] }}
                </p>
            </div>

            <!-- Bank Account -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account</label>
                <input
                    v-model="form.bank_account"
                    id="bank-account"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
                    placeholder="e.g. Land Bank Account #12345"
                />
                <p v-if="fieldErrors.bank_account" role="alert" class="text-xs text-rose-600 mt-1">{{ fieldErrors.bank_account[0] }}</p>
            </div>

            <!-- Proof File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Proof of Withdrawal
                    <span class="text-gray-400 font-normal">(PDF or image, optional)</span>
                </label>
                <input
                    type="file"
                    id="proof-file"
                    @change="proofFile = $event.target.files[0]"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#0c6d57]/10 file:text-[#0c6d57] hover:file:bg-[#0c6d57]/20"
                />
                <p v-if="fieldErrors.proof_file" role="alert" class="text-xs text-rose-600 mt-1">{{ fieldErrors.proof_file[0] }}</p>
                <p v-if="proofFile" class="text-xs text-emerald-600 mt-1">📎 {{ proofFile.name }}</p>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea
                    v-model="form.notes"
                    id="withdrawal-notes"
                    rows="2"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
                    placeholder="Optional remarks about this withdrawal..."
                ></textarea>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a :href="routes.back"
            class="px-5 py-2.5 bg-gray-200 text-gray-800 font-semibold rounded-xl hover:bg-gray-300 transition-colors min-h-[44px] inline-flex items-center">
            Cancel
        </a>
        <button
            type="submit"
            :disabled="submitting || resolution.remaining_balance <= 0"
            class="px-6 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[44px] disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ submitting ? 'Processing...' : 'Create Withdrawal Record' }}
        </button>
    </div>

</form>
</template>


