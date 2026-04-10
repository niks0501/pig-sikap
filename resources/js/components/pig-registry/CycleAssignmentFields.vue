<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    caretakers: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const fieldError = (name) => props.errors[name]?.[0] ?? '';
</script>

<template>
    <div>
        <h3 class="mb-4 border-b border-gray-100 pb-2 text-base font-bold text-gray-900">2. Sourcing & Assignment</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                    <span>Cycle Number</span>
                    <span class="text-xs font-normal text-gray-400">Optional</span>
                </span>
                <input
                    v-model="props.form.cycle_number"
                    type="number"
                    name="cycle_number"
                    min="1"
                    placeholder="e.g. 5"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                <span v-if="fieldError('cycle_number')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('cycle_number') }}</span>
            </label>

            <label>
                <span class="mb-1 flex justify-between text-sm font-semibold text-gray-700">
                    <span>Assigned Caretaker</span>
                    <span class="text-xs font-normal text-gray-400">Optional</span>
                </span>
                <select
                    v-model="props.form.caretaker_user_id"
                    name="caretaker_user_id"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition-all hover:bg-gray-50 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">Unassigned (None)</option>
                    <option v-for="caretaker in props.caretakers" :key="caretaker.id" :value="String(caretaker.id)">
                        {{ caretaker.name }}
                    </option>
                </select>
                <span v-if="fieldError('caretaker_user_id')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('caretaker_user_id') }}</span>
            </label>
        </div>
    </div>
</template>
