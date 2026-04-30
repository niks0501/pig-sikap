<script setup>
import { computed } from 'vue';

const props = defineProps({
    healthData: {
        type: Object,
        default: () => ({}),
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
});

const totalPigs = computed(() => {
    return Number(props.healthData.total_pigs || props.healthData.total_current_pigs || 0)
        || props.cycles.reduce((sum, cycle) => sum + (cycle.current_count || 0), 0);
});

const sickPigs = computed(() => {
    return props.healthData.total_sick || props.healthData.currently_sick || 0;
});

const isolatedPigs = computed(() => {
    return props.healthData.total_isolated || props.healthData.currently_isolated || 0;
});

const deceasedPigs = computed(() => {
    return props.healthData.total_deceased || props.healthData.total_deceased_reported || 0;
});

const recoveredPigs = computed(() => {
    return props.healthData.total_recovered || props.healthData.total_recovered_reported || 0;
});

const healthPercentage = computed(() => {
    if (totalPigs.value === 0) return 100;
    const healthyPigs = totalPigs.value - sickPigs.value - isolatedPigs.value - deceasedPigs.value;
    return Math.round((healthyPigs / totalPigs.value) * 100);
});

const healthStatus = computed(() => {
    const percentage = healthPercentage.value;
    if (percentage >= 90) return { label: 'Excellent', color: 'text-emerald-600', bg: 'bg-emerald-50', border: 'border-emerald-200' };
    if (percentage >= 70) return { label: 'Good', color: 'text-blue-600', bg: 'bg-blue-50', border: 'border-blue-200' };
    if (percentage >= 50) return { label: 'Fair', color: 'text-amber-600', bg: 'bg-amber-50', border: 'border-amber-200' };
    return { label: 'Critical', color: 'text-rose-600', bg: 'bg-rose-50', border: 'border-rose-200' };
});

const cycleHealthData = computed(() => {
    return props.cycles.map(cycle => ({
        ...cycle,
        sickCount: props.healthData.cycle_sick?.[cycle.id] || 0,
        isolatedCount: props.healthData.cycle_isolated?.[cycle.id] || 0,
        deceasedCount: props.healthData.cycle_deceased?.[cycle.id] || 0,
    }));
});

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num || 0);
};

const getHealthColor = (sick, isolated, total) => {
    const affected = sick + isolated;
    if (total === 0) return 'bg-gray-200';
    const percentage = (affected / total) * 100;
    if (percentage === 0) return 'bg-emerald-500';
    if (percentage < 10) return 'bg-blue-500';
    if (percentage < 25) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getHealthyPercentage = (cycle) => {
    const total = Number(cycle.current_count || 0);

    if (total <= 0) {
        return 0;
    }

    const affected = Number(cycle.sickCount || 0) + Number(cycle.isolatedCount || 0);

    return Math.max(0, Math.min(100, Math.round(((total - affected) / total) * 100)));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Health Status Overview -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Health Status Overview</h3>
                <div
                    :class="[
                        'inline-flex items-center rounded-full border px-3 py-1 text-sm font-semibold',
                        healthStatus.bg,
                        healthStatus.border,
                        healthStatus.color,
                    ]"
                >
                    {{ healthStatus.label }} Health
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Pigs</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ formatNumber(totalPigs) }}</p>
                    <p class="mt-1 text-xs text-gray-500">All cycles</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Sick / Isolated</p>
                    <p class="mt-2 text-2xl font-bold text-amber-900">{{ formatNumber(sickPigs + isolatedPigs) }}</p>
                    <p class="mt-1 text-xs text-amber-600">{{ formatNumber(sickPigs) }} sick, {{ formatNumber(isolatedPigs) }} isolated</p>
                </div>

                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Deceased</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900">{{ formatNumber(deceasedPigs) }}</p>
                    <p class="mt-1 text-xs text-rose-600">This period</p>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Recovered</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-900">{{ formatNumber(recoveredPigs) }}</p>
                    <p class="mt-1 text-xs text-emerald-600">This period</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600">Overall Health</span>
                    <span class="font-semibold text-gray-900">{{ healthPercentage }}%</span>
                </div>
                <div class="h-3 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div
                        :class="['h-full rounded-full transition-all duration-500', healthPercentage >= 90 ? 'bg-emerald-500' : healthPercentage >= 70 ? 'bg-blue-500' : healthPercentage >= 50 ? 'bg-amber-500' : 'bg-rose-500']"
                        :style="{ width: `${healthPercentage}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- Cycle Health Breakdown -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Health by Cycle</h3>

            <div v-if="cycleHealthData.length === 0" class="text-center py-8 text-gray-500">
                No cycles available
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="cycle in cycleHealthData"
                    :key="cycle.id"
                    class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors"
                >
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="font-semibold text-gray-900">{{ cycle.batch_code }}</p>
                                <p class="text-xs text-gray-500">{{ cycle.stage }} • {{ cycle.status }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">{{ cycle.current_count }} / {{ cycle.initial_count }}</p>
                                <p class="text-xs text-gray-500">Total pigs</p>
                            </div>
                        </div>

                        <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div
                                :class="['h-full rounded-full transition-all duration-300', getHealthColor(cycle.sickCount, cycle.isolatedCount, cycle.current_count)]"
                                :style="{ width: `${getHealthyPercentage(cycle)}%` }"
                            />
                        </div>
                    </div>

                    <div class="flex gap-2 text-xs">
                        <div v-if="cycle.sickCount > 0" class="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-amber-800">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ cycle.sickCount }}
                        </div>
                        <div v-if="cycle.isolatedCount > 0" class="flex items-center gap-1 rounded-full bg-violet-100 px-2 py-1 text-violet-800">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ cycle.isolatedCount }}
                        </div>
                        <div v-if="cycle.deceasedCount > 0" class="flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-rose-800">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ cycle.deceasedCount }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid gap-4 sm:grid-cols-2">
            <a
                :href="routes.create"
                class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-[#0c6d57] hover:bg-[#0c6d57]/5"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Report Incident</p>
                    <p class="text-sm text-gray-500">Log new health issue</p>
                </div>
            </a>

            <a
                :href="routes.schedule"
                class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-[#0c6d57] hover:bg-[#0c6d57]/5"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Treatment Schedule</p>
                    <p class="text-sm text-gray-500">View upcoming treatments</p>
                </div>
            </a>
        </div>
    </div>
</template>
