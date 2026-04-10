<script setup>
import { computed, reactive } from 'vue';
import CycleAssignmentFields from './CycleAssignmentFields.vue';
import CycleLifecycleFields from './CycleLifecycleFields.vue';

const props = defineProps({
    cycleCode: {
        type: String,
        default: 'C-001',
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
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
    batch_code: props.oldInput.batch_code ?? props.cycleCode,
    date_of_purchase: props.oldInput.date_of_purchase ?? '',
    initial_count: props.oldInput.initial_count ?? '',
    cycle_number: props.oldInput.cycle_number ?? '',
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
    <div class="mx-auto max-w-300 space-y-5">
        <section class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0c6d57]">Cycle Registration</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-900">Create Cycle Record</h2>
                <p class="mt-1 text-sm text-gray-500">Register one livestock cycle as the main inventory record.</p>
            </div>
            <a :href="props.routes.index" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Cycles
            </a>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div v-if="firstError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ firstError }}
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="p-4 sm:p-6">
                <form :action="props.routes.store" method="POST" class="space-y-8">
                    <input type="hidden" name="_token" :value="props.csrfToken">

                    <div>
                        <h3 class="text-base font-bold text-gray-900">1. Essential Cycle Information</h3>
                        <p class="mt-1 text-xs text-gray-500">Define the identity and baseline quantity of this cycle.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Cycle Code <span class="text-rose-500" title="Required">*</span></span>
                                <input
                                    v-model="form.batch_code"
                                    type="text"
                                    name="batch_code"
                                    required
                                    placeholder="e.g. C-001"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                                <span v-if="fieldError('batch_code')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('batch_code') }}</span>
                            </label>

                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Date of Purchase <span class="text-rose-500" title="Required">*</span></span>
                                <input
                                    v-model="form.date_of_purchase"
                                    type="date"
                                    name="date_of_purchase"
                                    required
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                                <span v-if="fieldError('date_of_purchase')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('date_of_purchase') }}</span>
                            </label>

                            <label>
                                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Initial Count <span class="text-rose-500" title="Required">*</span></span>
                                </span>
                                <input
                                    v-model="form.initial_count"
                                    type="number"
                                    name="initial_count"
                                    min="1"
                                    required
                                    placeholder="Number of pigs at setup"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                >
                                <span v-if="fieldError('initial_count')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('initial_count') }}</span>
                            </label>
                        </div>
                    </div>

                    <CycleAssignmentFields :form="form" :caretakers="props.caretakers" :errors="props.errors" />

                    <CycleLifecycleFields
                        :form="form"
                        :stages="props.stages"
                        :statuses="props.statuses"
                        :errors="props.errors"
                        notes-placeholder="Context or observations about this cycle..."
                    />

                    <label class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
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
                            Save Cycle
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
