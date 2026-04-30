<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm action',
    },
    message: {
        type: String,
        default: 'Please confirm before continuing.',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    tone: {
        type: String,
        default: 'danger',
        validator: (value) => ['danger', 'warning', 'success', 'default'].includes(value),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['cancel', 'confirm', 'update:show']);

const toneConfig = computed(() => {
    const tones = {
        danger: {
            iconWrap: 'bg-rose-50',
            icon: 'text-rose-600',
            button: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-600/30',
            path: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
        },
        warning: {
            iconWrap: 'bg-amber-50',
            icon: 'text-amber-600',
            button: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-600/30',
            path: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
        },
        success: {
            iconWrap: 'bg-emerald-50',
            icon: 'text-[#0c6d57]',
            button: 'bg-[#0c6d57] hover:bg-[#0a5a48] focus:ring-[#0c6d57]/30',
            path: 'M5 13l4 4L19 7',
        },
        default: {
            iconWrap: 'bg-gray-100',
            icon: 'text-gray-600',
            button: 'bg-gray-800 hover:bg-gray-900 focus:ring-gray-800/30',
            path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
    };

    return tones[props.tone] || tones.danger;
});

const close = () => {
    if (props.loading) {
        return;
    }

    emit('update:show', false);
    emit('cancel');
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild
                as="template"
                enter="ease-out duration-200"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-150"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-200"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="ease-in duration-150"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-xl">
                            <div class="flex items-start gap-3">
                                <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-full', toneConfig.iconWrap]">
                                    <svg :class="['h-5 w-5', toneConfig.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="toneConfig.path" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <DialogTitle class="text-base font-bold text-gray-900">
                                        {{ title }}
                                    </DialogTitle>
                                    <p class="mt-2 text-sm leading-6 text-gray-600">
                                        {{ message }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    :disabled="loading"
                                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="close"
                                >
                                    {{ cancelLabel }}
                                </button>
                                <button
                                    type="button"
                                    :disabled="loading"
                                    :class="['inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70', toneConfig.button]"
                                    @click="emit('confirm')"
                                >
                                    <svg v-if="loading" class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.29A7.96 7.96 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.65z" />
                                    </svg>
                                    {{ confirmLabel }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
