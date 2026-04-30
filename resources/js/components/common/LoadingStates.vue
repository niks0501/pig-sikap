<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'skeleton',
        validator: (value) => ['skeleton', 'spinner', 'dots', 'pulse'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
    },
    text: {
        type: String,
        default: '',
    },
    fullScreen: {
        type: Boolean,
        default: false,
    },
    overlay: {
        type: Boolean,
        default: false,
    },
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
        xl: 'h-12 w-12',
    };
    return sizes[props.size] || sizes.md;
});

const textSizeClasses = computed(() => {
    const sizes = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
        xl: 'text-lg',
    };
    return sizes[props.size] || sizes.md;
});
</script>

<template>
    <div
        v-if="fullScreen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm"
    >
        <div class="flex flex-col items-center gap-3">
            <div
                v-if="type === 'spinner'"
                :class="['animate-spin rounded-full border-2 border-gray-200 border-t-[#0c6d57]', sizeClasses]"
            />
            <div
                v-else-if="type === 'dots'"
                class="flex gap-1"
            >
                <div
                    v-for="i in 3"
                    :key="i"
                    :class="['animate-bounce rounded-full bg-[#0c6d57]', sizeClasses]"
                    :style="{ animationDelay: `${i * 0.1}s` }"
                />
            </div>
            <div
                v-else-if="type === 'pulse'"
                :class="['animate-pulse rounded-full bg-[#0c6d57]/50', sizeClasses]"
            />
            <div
                v-else
                :class="['animate-pulse rounded bg-gray-200', sizeClasses]"
            />
            <p v-if="text" :class="['text-gray-600 font-medium', textSizeClasses]">
                {{ text }}
            </p>
        </div>
    </div>

    <div
        v-else-if="overlay"
        class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px]"
    >
        <div class="flex flex-col items-center gap-2">
            <div
                v-if="type === 'spinner'"
                :class="['animate-spin rounded-full border-2 border-gray-200 border-t-[#0c6d57]', sizeClasses]"
            />
            <div
                v-else-if="type === 'dots'"
                class="flex gap-1"
            >
                <div
                    v-for="i in 3"
                    :key="i"
                    :class="['animate-bounce rounded-full bg-[#0c6d57]', sizeClasses]"
                    :style="{ animationDelay: `${i * 0.1}s` }"
                />
            </div>
            <div
                v-else-if="type === 'pulse'"
                :class="['animate-pulse rounded-full bg-[#0c6d57]/50', sizeClasses]"
            />
            <div
                v-else
                :class="['animate-pulse rounded bg-gray-200', sizeClasses]"
            />
            <p v-if="text" :class="['text-gray-600 font-medium', textSizeClasses]">
                {{ text }}
            </p>
        </div>
    </div>

    <div
        v-else
        class="flex items-center gap-2"
    >
        <div
            v-if="type === 'spinner'"
            :class="['animate-spin rounded-full border-2 border-gray-200 border-t-[#0c6d57]', sizeClasses]"
        />
        <div
            v-else-if="type === 'dots'"
            class="flex gap-1"
        >
            <div
                v-for="i in 3"
                :key="i"
                :class="['animate-bounce rounded-full bg-[#0c6d57]', sizeClasses]"
                :style="{ animationDelay: `${i * 0.1}s` }"
            />
        </div>
        <div
            v-else-if="type === 'pulse'"
            :class="['animate-pulse rounded-full bg-[#0c6d57]/50', sizeClasses]"
        />
        <div
            v-else
            :class="['animate-pulse rounded bg-gray-200', sizeClasses]"
        />
        <p v-if="text" :class="['text-gray-600 font-medium', textSizeClasses]">
            {{ text }}
        </p>
    </div>
</template>
