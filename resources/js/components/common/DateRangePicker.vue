<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ start: '', end: '' }),
    },
    label: {
        type: String,
        default: 'Date range',
    },
    presets: {
        type: Array,
        default: () => ['today', 'this_week', 'this_month', 'last_month', 'this_quarter', 'this_year'],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const range = reactive({
    start: '',
    end: '',
});

const formatInputDate = (date) => date.toISOString().slice(0, 10);

const presetOptions = computed(() => {
    const today = new Date();
    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay());
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    const allPresets = {
        today: {
            label: 'Today',
            start: formatInputDate(today),
            end: formatInputDate(today),
        },
        this_week: {
            label: 'This week',
            start: formatInputDate(startOfWeek),
            end: formatInputDate(today),
        },
        this_month: {
            label: 'This month',
            start: formatInputDate(startOfMonth),
            end: formatInputDate(today),
        },
        last_month: {
            label: 'Last month',
            start: formatInputDate(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
            end: formatInputDate(new Date(today.getFullYear(), today.getMonth(), 0)),
        },
        this_quarter: {
            label: 'This quarter',
            start: formatInputDate(new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1)),
            end: formatInputDate(today),
        },
        this_year: {
            label: 'This year',
            start: formatInputDate(new Date(today.getFullYear(), 0, 1)),
            end: formatInputDate(today),
        },
    };

    return props.presets.map((preset) => allPresets[preset]).filter(Boolean);
});

watch(
    () => props.modelValue,
    (value) => {
        range.start = value?.start || '';
        range.end = value?.end || '';
    },
    { immediate: true, deep: true }
);

const emitChange = () => {
    const payload = { start: range.start, end: range.end };
    emit('update:modelValue', payload);
    emit('change', payload);
};

const applyPreset = (preset) => {
    range.start = preset.start;
    range.end = preset.end;
    emitChange();
};
</script>

<template>
    <fieldset class="rounded-xl border border-gray-200 bg-white p-3">
        <legend v-if="label" class="px-1 text-sm font-semibold text-gray-700">
            {{ label }}
        </legend>

        <div class="grid gap-3 sm:grid-cols-2">
            <label>
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Start</span>
                <input
                    v-model="range.start"
                    type="date"
                    :disabled="disabled"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 disabled:cursor-not-allowed disabled:bg-gray-50"
                    @change="emitChange"
                >
            </label>

            <label>
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">End</span>
                <input
                    v-model="range.end"
                    type="date"
                    :disabled="disabled"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 disabled:cursor-not-allowed disabled:bg-gray-50"
                    @change="emitChange"
                >
            </label>
        </div>

        <div v-if="presetOptions.length > 0" class="mt-3 flex flex-wrap gap-2">
            <button
                v-for="preset in presetOptions"
                :key="preset.label"
                type="button"
                :disabled="disabled"
                class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 hover:text-[#0c6d57] disabled:cursor-not-allowed disabled:opacity-60"
                @click="applyPreset(preset)"
            >
                {{ preset.label }}
            </button>
        </div>
    </fieldset>
</template>
