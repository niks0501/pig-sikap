<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    actions: {
        type: Array,
        default: () => [],
    },
    position: {
        type: String,
        default: 'bottom-right',
        validator: (value) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(value),
    },
    icon: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['action']);

const isOpen = ref(false);
const menuRef = ref(null);
const triggerRef = ref(null);

const positionClasses = computed(() => {
    const positions = {
        'bottom-right': 'right-0 top-full mt-2',
        'bottom-left': 'left-0 top-full mt-2',
        'top-right': 'right-0 bottom-full mb-2',
        'top-left': 'left-0 bottom-full mb-2',
    };
    return positions[props.position] || positions['bottom-right'];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'h-10 w-10',
        md: 'h-12 w-12',
        lg: 'h-14 w-14',
    };
    return sizes[props.size] || sizes.md;
});

const textSize = computed(() => {
    const sizes = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
    };
    return sizes[props.size] || sizes.md;
});

const toggleMenu = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
};

const closeMenu = () => {
    isOpen.value = false;
};

const handleAction = (action) => {
    if (action.disabled) return;
    emit('action', action);
    closeMenu();
};

const handleClickOutside = (event) => {
    if (menuRef.value && !menuRef.value.contains(event.target) && triggerRef.value && !triggerRef.value.contains(event.target)) {
        closeMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative">
        <button
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            :class="[
                'inline-flex items-center justify-center rounded-full bg-[#0c6d57] text-white shadow-lg transition-all hover:bg-[#0a5a48] hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/50 focus:ring-offset-2',
                sizeClasses,
                disabled ? 'cursor-not-allowed opacity-50' : '',
            ]"
            @click="toggleMenu"
        >
            <svg
                v-if="icon"
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                />
            </svg>
            <span v-if="label" :class="['ml-2 font-semibold', textSize]">
                {{ label }}
            </span>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                ref="menuRef"
                :class="['absolute z-50 w-56 rounded-xl border border-gray-200 bg-white py-2 shadow-xl', positionClasses]"
            >
                <div v-if="actions.length === 0" class="px-4 py-3 text-sm text-gray-500">
                    No actions available
                </div>

                <div v-else>
                    <div
                        v-for="(action, index) in actions"
                        :key="index"
                        class="px-1"
                    >
                        <button
                            v-if="action.divider"
                            type="button"
                            disabled
                            class="w-full border-t border-gray-100 py-1"
                        />

                        <button
                            v-else
                            type="button"
                            :disabled="action.disabled"
                            :class="[
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-colors',
                                action.disabled
                                    ? 'cursor-not-allowed text-gray-400'
                                    : 'text-gray-700 hover:bg-gray-50',
                                action.danger ? 'text-rose-600 hover:bg-rose-50' : '',
                            ]"
                            @click="handleAction(action)"
                        >
                            <svg
                                v-if="action.icon"
                                class="h-5 w-5 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="action.icon"
                                />
                            </svg>
                            <div class="flex-1">
                                <p :class="['font-medium', action.disabled ? '' : 'text-gray-900']">
                                    {{ action.label }}
                                </p>
                                <p v-if="action.description" class="text-xs text-gray-500">
                                    {{ action.description }}
                                </p>
                            </div>
                            <span v-if="action.shortcut" class="text-xs text-gray-400">
                                {{ action.shortcut }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
