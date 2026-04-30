<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '0.00',
    },
    currency: {
        type: String,
        default: 'PHP',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'blur']);

const inputValue = ref('');

const normalizedValue = computed(() => {
    const raw = String(inputValue.value || '').replace(/[^\d.]/g, '');
    const parts = raw.split('.');
    const whole = parts.shift() || '';
    const decimals = parts.join('').slice(0, 2);

    return decimals.length > 0 ? `${whole}.${decimals}` : whole;
});

const prefix = computed(() => {
    if (props.currency === 'PHP') {
        return 'PHP';
    }

    return props.currency;
});

watch(
    () => props.modelValue,
    (value) => {
        inputValue.value = value === null || value === undefined || value === '' ? '' : String(value);
    },
    { immediate: true }
);

const handleInput = (event) => {
    inputValue.value = event.target.value;
    emit('update:modelValue', normalizedValue.value);
};

const handleBlur = () => {
    if (normalizedValue.value !== '') {
        inputValue.value = Number(normalizedValue.value).toFixed(2);
        emit('update:modelValue', inputValue.value);
    }

    emit('blur', inputValue.value);
};
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
        </span>
        <span class="relative block">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-gray-500">
                {{ prefix }}
            </span>
            <input
                :name="name"
                :value="inputValue"
                type="text"
                inputmode="decimal"
                :required="required"
                :disabled="disabled"
                :placeholder="placeholder"
                :class="[
                    'w-full rounded-xl border bg-white py-2.5 pl-14 pr-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20',
                    error ? 'border-rose-300 focus:border-rose-500' : 'border-gray-300 focus:border-[#0c6d57]',
                    disabled ? 'cursor-not-allowed bg-gray-50 text-gray-500' : '',
                ]"
                @input="handleInput"
                @blur="handleBlur"
            >
        </span>
        <p v-if="error" class="mt-1 text-sm font-medium text-rose-600">
            {{ error }}
        </p>
    </label>
</template>
