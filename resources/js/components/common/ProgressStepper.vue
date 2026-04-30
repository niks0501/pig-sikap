<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStep: {
        type: Number,
        default: 1,
    },
    totalSteps: {
        type: Number,
        default: 3,
    },
    steps: {
        type: Array,
        default: () => [],
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: {
            circle: 'h-6 w-6 text-xs',
            line: 'h-0.5',
            label: 'text-xs',
        },
        md: {
            circle: 'h-8 w-8 text-sm',
            line: 'h-1',
            label: 'text-sm',
        },
        lg: {
            circle: 'h-10 w-10 text-base',
            line: 'h-1.5',
            label: 'text-base',
        },
    };
    return sizes[props.size] || sizes.md;
});

const stepItems = computed(() => {
    if (props.steps.length > 0) {
        return props.steps;
    }
    return Array.from({ length: props.totalSteps }, (_, i) => ({
        label: `Step ${i + 1}`,
        value: i + 1,
    }));
});

const getStepStatus = (index) => {
    const stepNumber = index + 1;
    if (stepNumber < props.currentStep) return 'completed';
    if (stepNumber === props.currentStep) return 'current';
    return 'pending';
};

const getStepClasses = (status) => {
    const classes = {
        completed: {
            circle: 'bg-[#0c6d57] text-white border-[#0c6d57]',
            line: 'bg-[#0c6d57]',
            label: 'text-[#0c6d57] font-semibold',
        },
        current: {
            circle: 'bg-[#0c6d57] text-white border-[#0c6d57] ring-4 ring-[#0c6d57]/20',
            line: 'bg-gray-200',
            label: 'text-gray-900 font-semibold',
        },
        pending: {
            circle: 'bg-white text-gray-400 border-gray-300',
            line: 'bg-gray-200',
            label: 'text-gray-500',
        },
    };
    return classes[status];
};

const progressPercentage = computed(() => {
    if (props.totalSteps <= 1) {
        return props.currentStep > 1 ? 100 : 0;
    }

    return ((props.currentStep - 1) / (props.totalSteps - 1)) * 100;
});
</script>

<template>
    <div class="w-full">
        <div class="relative flex items-center justify-between">
            <div
                class="absolute left-0 top-1/2 h-0.5 w-full -translate-y-1/2 bg-gray-200"
                :class="sizeClasses.line"
            >
                <div
                    class="h-full bg-[#0c6d57] transition-all duration-300"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>

            <div
                v-for="(step, index) in stepItems"
                :key="index"
                class="relative z-10 flex flex-col items-center"
            >
                <div
                    :class="[
                        'flex items-center justify-center rounded-full border-2 font-semibold transition-all duration-300',
                        sizeClasses.circle,
                        getStepClasses(getStepStatus(index)).circle,
                    ]"
                >
                    <svg
                        v-if="getStepStatus(index) === 'completed'"
                        class="h-3 w-3"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span v-else>{{ step.value || index + 1 }}</span>
                </div>

                <span
                    v-if="step.label"
                    :class="['mt-2 text-center', sizeClasses.label, getStepClasses(getStepStatus(index)).label]"
                >
                    {{ step.label }}
                </span>
            </div>
        </div>
    </div>
</template>
