<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    batchCode: {
        type: String,
        default: 'B-001',
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    breeders: {
        type: Array,
        default: () => [],
    },
    caretakers: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    oldInput: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    statusMessage: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
});

const form = reactive({
    batch_code: props.oldInput.batch_code ?? props.batchCode,
    birth_date: props.oldInput.birth_date ?? '',
    initial_count: props.oldInput.initial_count ?? '',
    cycle_number: props.oldInput.cycle_number ?? '',
    breeder_id: props.oldInput.breeder_id ?? '',
    caretaker_user_id: props.oldInput.caretaker_user_id ?? '',
    stage: props.oldInput.stage ?? 'Piglet',
    status: props.oldInput.status ?? 'Active',
    average_weight: props.oldInput.average_weight ?? '',
    notes: props.oldInput.notes ?? '',
    has_pig_profiles: String(props.oldInput.has_pig_profiles ?? '') === '1',
});

const firstError = computed(() => {
    if (props.errorMessage) {
        return props.errorMessage;
    }

    const firstField = Object.keys(props.errors)[0];

    return firstField ? props.errors[firstField][0] : '';
});

const fieldError = (name) => props.errors[name]?.[0] ?? '';
</script>

<template>
    <div class="space-y-4 max-w-[1200px] mx-auto">
        <section class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Create Pig Batch</h2>
                <p class="mt-1 text-sm text-gray-500">Register one litter/group as the main inventory record.</p>
            </div>
            <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Registry
            </a>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div v-if="firstError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ firstError }}
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="p-5">
                <form :action="props.routes.store" method="POST" class="space-y-8">
                    <input type="hidden" name="_token" :value="props.csrfToken">

                    <!-- Logical Group 1: General Info -->
                    <div>
                        <h3 class="mb-4 text-base font-bold text-gray-900 border-b border-gray-100 pb-2">1. Essential Batch Information</h3>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Batch Code <span class="text-rose-500" title="Required">*</span></span>
                                <input v-model="form.batch_code" type="text" name="batch_code" required placeholder="e.g. B-001" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                <span v-if="fieldError('batch_code')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('batch_code') }}</span>
                            </label>

                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Birth Date <span class="text-rose-500" title="Required">*</span></span>
                                <input v-model="form.birth_date" type="date" name="birth_date" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                <span v-if="fieldError('birth_date')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('birth_date') }}</span>
                            </label>

                            <label>
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Initial Count <span class="text-rose-500" title="Required">*</span></span>
                                </span>
                                <input v-model="form.initial_count" type="number" name="initial_count" min="1" required placeholder="Number of pigs at setup" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                <span v-if="fieldError('initial_count')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('initial_count') }}</span>
                            </label>

                            <label>
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Average Weight (kg)</span>
                                    <span class="text-xs font-normal text-gray-400">Optional</span>
                                </span>
                                <input v-model="form.average_weight" type="number" name="average_weight" step="0.01" min="0" placeholder="0.00" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                            </label>
                        </div>
                    </div>

                    <!-- Logical Group 2: Sourcing & Responsibilities -->
                    <div>
                        <h3 class="mb-4 text-base font-bold text-gray-900 border-b border-gray-100 pb-2">2. Sourcing & Assignment</h3>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label>
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Breeder / Inahin</span>
                                    <span class="text-xs font-normal text-gray-400">Optional</span>
                                </span>
                                <select v-model="form.breeder_id" name="breeder_id" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                    <option value="">No linked breeder</option>
                                    <option v-for="breeder in props.breeders" :key="breeder.id" :value="String(breeder.id)">
                                        {{ breeder.breeder_code }} - {{ breeder.name_or_tag }}
                                    </option>
                                </select>
                            </label>

                            <label>
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Cycle Number</span>
                                    <span class="text-xs font-normal text-gray-400">Optional</span>
                                </span>
                                <input v-model="form.cycle_number" type="number" name="cycle_number" min="1" placeholder="e.g. 5" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                            </label>

                            <label class="sm:col-span-2 md:col-span-1">
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Assigned Caretaker</span>
                                    <span class="text-xs font-normal text-gray-400">Optional</span>
                                </span>
                                <select v-model="form.caretaker_user_id" name="caretaker_user_id" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                    <option value="">Unassigned (None)</option>
                                    <option v-for="caretaker in props.caretakers" :key="caretaker.id" :value="String(caretaker.id)">
                                        {{ caretaker.name }}
                                    </option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <!-- Logical Group 3: Real-Time State -->
                    <div>
                        <h3 class="mb-4 text-base font-bold text-gray-900 border-b border-gray-100 pb-2">3. Life Stage & Description</h3>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Current Stage <span class="text-rose-500" title="Required">*</span></span>
                                <select v-model="form.stage" name="stage" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                    <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                                </select>
                            </label>

                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Health / Visibility Status <span class="text-rose-500" title="Required">*</span></span>
                                <select v-model="form.status" name="status" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50">
                                    <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </label>

                            <label class="sm:col-span-2">
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Remarks & Notes</span>
                                    <span class="text-xs font-normal text-gray-400">Optional</span>
                                </span>
                                <textarea v-model="form.notes" name="notes" rows="3" placeholder="Context or observations about this batch..." class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all hover:bg-gray-50"></textarea>
                            </label>
                        </div>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                        <input v-model="form.has_pig_profiles" type="checkbox" name="has_pig_profiles" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]/40">
                        <span>
                            <span class="block text-sm font-semibold text-emerald-900">Auto-generate pig profiles</span>
                            <span class="mt-1 block text-xs text-emerald-700">If checked, the system creates pig profile rows from 1 up to initial count.</span>
                        </span>
                    </label>

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Save Batch
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
