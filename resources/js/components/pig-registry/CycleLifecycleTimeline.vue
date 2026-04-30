<script setup>
import { computed } from 'vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
    },
    stages: {
        type: Array,
        default: () => ['Piglet', 'Weaning', 'Growing', 'Fattening', 'For Sale', 'Completed'],
    },
});

const displayStages = computed(() => {
    return props.stages.length > 0
        ? props.stages
        : ['Piglet', 'Weaning', 'Growing', 'Fattening', 'For Sale', 'Completed'];
});

const currentStageIndex = computed(() => {
    const index = displayStages.value.findIndex(stage => stage === props.cycle.stage);

    return index >= 0 ? index : 0;
});

const isStageComplete = (index) => {
    return index < currentStageIndex.value;
};

const isStageCurrent = (index) => {
    return index === currentStageIndex.value;
};

const getStageIcon = (index) => {
    if (isStageComplete(index)) {
        return 'M5 13l4 4L19 7';
    }
    if (isStageCurrent(index)) {
        return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    }
    return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
};

const getStageColor = (index) => {
    if (isStageComplete(index)) return 'text-emerald-600 bg-emerald-50 border-emerald-200';
    if (isStageCurrent(index)) return 'text-[#0c6d57] bg-[#0c6d57]/10 border-[#0c6d57]/30';
    return 'text-gray-400 bg-gray-50 border-gray-200';
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const stageUpdatedAt = computed(() => {
    return props.cycle.stage_updated_at
        || props.cycle.status_histories?.[0]?.created_at
        || props.cycle.last_reviewed_at
        || props.cycle.updated_at
        || props.cycle.created_at
        || null;
});

const daysInStage = computed(() => {
    if (!stageUpdatedAt.value) return 'N/A';
    const stageDate = new Date(stageUpdatedAt.value);
    const today = new Date();
    const diffTime = Math.abs(today - stageDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
});

const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${displayStages.value.length}, minmax(0, 1fr))`,
}));

const progressPercentage = computed(() => {
    const segmentCount = displayStages.value.length - 1;

    if (segmentCount <= 0) {
        return 0;
    }

    return Math.round((currentStageIndex.value / segmentCount) * 100);
});
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Cycle Lifecycle</h3>
            <span class="text-xs text-gray-500">
                {{ daysInStage === 'N/A' ? 'Stage date not recorded' : `${daysInStage} days in current stage` }}
            </span>
        </div>

        <div class="overflow-x-auto pb-1">
            <div class="relative min-w-[42rem] px-2 pt-1 sm:min-w-0">
                <div class="absolute left-7 right-7 top-6 h-1 overflow-hidden rounded-full bg-gray-200">
                    <div
                        class="h-full rounded-full bg-[#0c6d57] transition-all duration-500"
                        :style="{ width: `${progressPercentage}%` }"
                    />
                </div>

                <div class="relative z-10 grid items-start" :style="gridStyle">
                <div
                    v-for="(stage, index) in displayStages"
                    :key="index"
                    class="flex min-w-0 flex-col items-center px-1"
                >
                    <div
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 bg-white transition-all duration-300',
                            getStageColor(index),
                        ]"
                    >
                        <svg
                            v-if="isStageComplete(index)"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="getStageIcon(index)"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="getStageIcon(index)"
                            />
                        </svg>
                    </div>

                    <div class="mt-3 min-h-12 text-center">
                        <p
                            :class="[
                                'break-words text-xs font-semibold leading-tight',
                                isStageCurrent(index) ? 'text-[#0c6d57]' : 'text-gray-500',
                            ]"
                        >
                            {{ stage }}
                        </p>
                        <p v-if="isStageCurrent(index)" class="text-[10px] text-gray-400">
                            {{ formatDate(stageUpdatedAt) }}
                        </p>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xs text-gray-500">Started</p>
                    <p class="text-sm font-semibold text-gray-900">{{ formatDate(cycle.date_of_purchase) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Current Count</p>
                    <p class="text-sm font-semibold text-gray-900">{{ cycle.current_count }} / {{ cycle.initial_count }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="text-sm font-semibold text-gray-900">{{ cycle.status }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
