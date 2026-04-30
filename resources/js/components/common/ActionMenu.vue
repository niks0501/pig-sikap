<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    position: {
        type: String,
        default: 'bottom-right',
        validator: (value) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(value),
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

const emit = defineEmits(['select']);

const isOpen = ref(false);
const menuRef = ref(null);
const triggerRef = ref(null);

const positionClasses = computed(() => {
    const positions = {
        'bottom-right': 'right-0 top-full mt-1',
        'bottom-left': 'left-0 top-full mt-1',
        'top-right': 'right-0 bottom-full mb-1',
        'top-left': 'left-0 bottom-full mb-1',
    };
    return positions[props.position] || positions['bottom-right'];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'min-w-[140px]',
        md: 'min-w-[180px]',
        lg: 'min-w-[220px]',
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

const handleSelect = (item) => {
    if (item.disabled) return;
    emit('select', item);
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
            class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="toggleMenu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
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
                :class="['absolute z-50 rounded-xl border border-gray-200 bg-white py-1 shadow-lg', positionClasses, sizeClasses]"
            >
                <button
                    v-for="(item, index) in items"
                    :key="index"
                    type="button"
                    :disabled="item.disabled"
                    :class="[
                        'flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors',
                        item.disabled
                            ? 'cursor-not-allowed text-gray-400'
                            : 'text-gray-700 hover:bg-gray-50',
                        item.danger ? 'text-rose-600 hover:bg-rose-50' : '',
                    ]"
                    @click="handleSelect(item)"
                >
                    <svg
                        v-if="item.icon"
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="item.icon"
                        />
                    </svg>
                    <span>{{ item.label }}</span>
                    <span v-if="item.shortcut" class="ml-auto text-xs text-gray-400">
                        {{ item.shortcut }}
                    </span>
                </button>

                <div v-if="items.length === 0" class="px-3 py-2 text-sm text-gray-500">
                    No actions available
                </div>
            </div>
        </Transition>
    </div>
</template>
