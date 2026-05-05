<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'default',
        validator: (value) => [
            'default', 'success', 'warning', 'danger', 'info',
            'paid', 'partial', 'pending', 'active', 'archived',
            'sick', 'isolated', 'recovered', 'deceased',
            'draft', 'in_progress', 'awarded', 'cancelled',
            'waived',
        ].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    pulse: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: Boolean,
        default: false,
    },
});

const statusConfig = computed(() => {
    const configs = {
        default: {
            classes: 'bg-gray-100 text-gray-700 border-gray-200',
            icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        success: {
            classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            icon: 'M5 13l4 4L19 7',
        },
        warning: {
            classes: 'bg-amber-100 text-amber-800 border-amber-200',
            icon: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        },
        danger: {
            classes: 'bg-rose-100 text-rose-800 border-rose-200',
            icon: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        info: {
            classes: 'bg-blue-100 text-blue-800 border-blue-200',
            icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        paid: {
            classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            icon: 'M5 13l4 4L19 7',
        },
        partial: {
            classes: 'bg-amber-100 text-amber-800 border-amber-200',
            icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        pending: {
            classes: 'bg-rose-100 text-rose-800 border-rose-200',
            icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        active: {
            classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            icon: 'M5 13l4 4L19 7',
        },
        archived: {
            classes: 'bg-gray-100 text-gray-700 border-gray-200',
            icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
        },
        sick: {
            classes: 'bg-amber-100 text-amber-800 border-amber-200',
            icon: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        isolated: {
            classes: 'bg-violet-100 text-violet-800 border-violet-200',
            icon: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        recovered: {
            classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            icon: 'M5 13l4 4L19 7',
        },
        deceased: {
            classes: 'bg-rose-100 text-rose-800 border-rose-200',
            icon: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
    };
    return configs[props.status] || configs.default;
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-xs',
        lg: 'px-3 py-1.5 text-sm',
    };
    return sizes[props.size] || sizes.md;
});

const iconSize = computed(() => {
    const sizes = {
        sm: 'h-3 w-3',
        md: 'h-3.5 w-3.5',
        lg: 'h-4 w-4',
    };
    return sizes[props.size] || sizes.md;
});

const label = computed(() => {
    return props.status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border font-bold transition-all',
            sizeClasses,
            statusConfig.classes,
            pulse ? 'animate-pulse' : ''
        ]"
    >
        <svg
            v-if="icon"
            :class="iconSize"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                :d="statusConfig.icon"
            />
        </svg>
        <slot>{{ label }}</slot>
    </span>
</template>
