<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    notesPlaceholder: {
        type: String,
        default: 'Context or observations about this cycle...',
    },
});

const fieldError = (name) => props.errors[name]?.[0] ?? '';
</script>

<template>
    <div>
        <h3 class="mb-4 border-b border-gray-100 pb-2 text-base font-bold text-gray-900">3. Life Stage & Description</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="mb-1 block text-sm font-semibold text-gray-700">Current Stage <span class="text-rose-500" title="Required">*</span></span>
                <select
                    v-model="props.form.stage"
                    name="stage"
                    required
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                </select>
                <span v-if="fieldError('stage')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('stage') }}</span>
            </label>

            <label>
                <span class="mb-1 block text-sm font-semibold text-gray-700">Health / Visibility Status <span class="text-rose-500" title="Required">*</span></span>
                <select
                    v-model="props.form.status"
                    name="status"
                    required
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                </select>
                <span v-if="fieldError('status')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('status') }}</span>
            </label>

            <label class="sm:col-span-2 md:col-span-1">
                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                    <span>Average Weight (kg)</span>
                    <span class="text-xs font-normal text-gray-400">Optional</span>
                </span>
                <input
                    v-model="props.form.average_weight"
                    type="number"
                    name="average_weight"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                <span v-if="fieldError('average_weight')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('average_weight') }}</span>
            </label>

            <label class="sm:col-span-2">
                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                    <span>Remarks & Notes</span>
                    <span class="text-xs font-normal text-gray-400">Optional</span>
                </span>
                <textarea
                    v-model="props.form.notes"
                    name="notes"
                    rows="3"
                    :placeholder="props.notesPlaceholder"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                />
                <span v-if="fieldError('notes')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('notes') }}</span>
            </label>
        </div>
    </div>
</template>
