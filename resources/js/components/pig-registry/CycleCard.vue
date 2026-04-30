<script setup>
import { computed } from 'vue';
import ActionMenu from '../common/ActionMenu.vue';
import StatusBadge from '../common/StatusBadge.vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['action']);

const showUrl = computed(() => {
    if (props.routes.showBase) {
        return `${props.routes.showBase}/${encodeURIComponent(props.cycle.batch_code)}`;
    }

    return props.routes.show || '#';
});

const countPercent = computed(() => {
    const initial = Number(props.cycle.initial_count || 0);

    if (initial <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((Number(props.cycle.current_count || 0) / initial) * 100)));
});

const statusTone = computed(() => {
    if (['Sold', 'Closed'].includes(props.cycle.status) || props.cycle.stage === 'Completed') {
        return 'archived';
    }

    if (props.cycle.status === 'Ready for Sale') {
        return 'success';
    }

    if (props.cycle.status === 'Under Monitoring') {
        return 'warning';
    }

    return 'active';
});

const actions = computed(() => [
    { label: 'Open details', key: 'open', icon: 'M13 7h5m0 0v5m0-5L10 15' },
    { label: 'Edit cycle', key: 'edit', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z' },
    { label: 'Archive', key: 'archive', danger: true, icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8' },
]);

const handleAction = (item) => {
    if (item.key === 'open') {
        window.location.href = showUrl.value;
        return;
    }

    emit('action', { action: item.key, cycle: props.cycle });
};
</script>

<template>
    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-[#0c6d57]/30 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a :href="showUrl" class="text-base font-bold text-gray-900 hover:text-[#0c6d57]">
                    {{ cycle.batch_code }}
                </a>
                <p class="mt-1 text-xs text-gray-500">
                    Cycle #{{ cycle.cycle_number || '-' }} · {{ cycle.caretaker?.name || 'Unassigned' }}
                </p>
            </div>
            <ActionMenu :items="actions" @select="handleAction" />
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <StatusBadge status="info">{{ cycle.stage || 'No stage' }}</StatusBadge>
            <StatusBadge :status="statusTone">{{ cycle.status || 'No status' }}</StatusBadge>
        </div>

        <div class="mt-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-gray-600">Inventory</span>
                <span class="font-bold text-gray-900">
                    {{ Number(cycle.current_count || 0).toLocaleString() }} / {{ Number(cycle.initial_count || 0).toLocaleString() }}
                </span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-[#0c6d57] transition-all" :style="{ width: `${countPercent}%` }" />
            </div>
        </div>
    </article>
</template>
