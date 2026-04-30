<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    type: {
        type: String,
        default: 'no-data',
        validator: (value) => ['no-data', 'no-results', 'no-connection', 'coming-soon'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionRoute: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const emit = defineEmits(['action']);

const defaultContent = computed(() => {
    const content = {
        'no-data': {
            title: props.title || 'No data yet',
            description: props.description || 'Get started by adding your first record.',
            icon: props.icon || 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
        },
        'no-results': {
            title: props.title || 'No results found',
            description: props.description || 'Try adjusting your filters or search terms.',
            icon: props.icon || 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        },
        'no-connection': {
            title: props.title || 'Connection lost',
            description: props.description || 'Please check your internet connection and try again.',
            icon: props.icon || 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3',
        },
        'coming-soon': {
            title: props.title || 'Coming soon',
            description: props.description || 'This feature is under development.',
            icon: props.icon || 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        },
    };
    return content[props.type];
});

const iconSize = computed(() => {
    const sizes = {
        sm: 'h-8 w-8',
        md: 'h-12 w-12',
        lg: 'h-16 w-16',
    };
    return sizes[props.size] || sizes.md;
});

const textSize = computed(() => {
    const sizes = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
    };
    return sizes[props.size] || sizes.md;
});

const handleAction = () => {
    if (props.actionRoute) {
        window.location.href = props.actionRoute;
    } else {
        emit('action');
    }
};
</script>

<template>
    <div v-if="show" class="flex flex-col items-center justify-center p-8 text-center">
        <div
            class="mb-4 rounded-full bg-gray-100 p-4"
            :class="type === 'no-connection' ? 'bg-rose-50' : ''"
        >
            <svg
                :class="['text-gray-400', type === 'no-connection' ? 'text-rose-400' : '', iconSize]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    :d="defaultContent.icon"
                />
            </svg>
        </div>

        <h3 :class="['font-semibold text-gray-900', textSize]">
            {{ defaultContent.title }}
        </h3>

        <p class="mt-2 text-sm text-gray-500 max-w-sm">
            {{ defaultContent.description }}
        </p>

        <button
            v-if="actionLabel"
            type="button"
            class="mt-4 inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#0a5a48]"
            @click="handleAction"
        >
            {{ actionLabel }}
        </button>
    </div>
</template>
