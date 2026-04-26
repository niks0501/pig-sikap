<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'success',
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    duration: {
        type: Number,
        default: 5000,
    },
});

const emit = defineEmits(['close', 'action']);

let timeoutId = null;

const toneClasses = computed(() => {
    if (props.type === 'error') {
        return 'border-rose-200 bg-rose-50 text-rose-900';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-900';
});

const iconClasses = computed(() => {
    return props.type === 'error' ? 'text-rose-600' : 'text-[#0c6d57]';
});

const resetTimer = () => {
    if (timeoutId) {
        window.clearTimeout(timeoutId);
        timeoutId = null;
    }

    if (props.show && props.duration > 0) {
        timeoutId = window.setTimeout(() => emit('close'), props.duration);
    }
};

watch(() => props.show, resetTimer);

onBeforeUnmount(() => {
    if (timeoutId) {
        window.clearTimeout(timeoutId);
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed left-1/2 top-4 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2"
        role="status"
        aria-live="polite"
    >
        <div :class="['rounded-xl border px-4 py-3 shadow-lg', toneClasses]">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0" :class="iconClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path v-if="type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <div class="min-w-0 flex-1">
                    <p v-if="title" class="text-sm font-bold">{{ title }}</p>
                    <p v-if="message" class="mt-0.5 text-sm opacity-90">{{ message }}</p>
                    <button
                        v-if="actionLabel"
                        type="button"
                        class="mt-2 text-sm font-bold underline underline-offset-2"
                        @click="emit('action')"
                    >
                        {{ actionLabel }}
                    </button>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-1 opacity-70 transition hover:bg-white/70 hover:opacity-100"
                    aria-label="Close notification"
                    @click="emit('close')"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
