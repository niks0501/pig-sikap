<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const incident = computed(() => props.item?.incident ?? {});
const timelineDateLabel = computed(() => props.item?.timeline_date_label ?? 'Timeline Date');
const timelineDateText = computed(() => formatDate(props.item?.timeline_date));
const isResolutionEvent = computed(() => Boolean(incident.value.is_resolution_event));

const incidentToneClass = computed(() => {
    if (incident.value.incident_type === 'deceased') {
        return 'border-rose-200 bg-rose-50/60';
    }

    if (incident.value.incident_type === 'recovered') {
        return 'border-emerald-200 bg-emerald-50/60';
    }

    if (incident.value.incident_type === 'isolated') {
        return 'border-amber-200 bg-amber-50/60';
    }

    return 'border-orange-200 bg-orange-50/60';
});

const incidentDotClass = computed(() => {
    if (incident.value.incident_type === 'deceased') {
        return 'bg-rose-500';
    }

    if (incident.value.incident_type === 'recovered') {
        return 'bg-emerald-500';
    }

    if (incident.value.incident_type === 'isolated') {
        return 'bg-amber-500';
    }

    return 'bg-orange-500';
});

const incidentStateBadgeClass = computed(() => {
    if (isResolutionEvent.value) {
        return 'bg-gray-100 text-gray-700';
    }

    return 'bg-orange-100 text-orange-800';
});

const resolutionTargetLabel = computed(() => {
    const target = String(incident.value.resolution_target ?? '').trim();

    if (target === '') {
        return '';
    }

    return `${target.charAt(0).toUpperCase()}${target.slice(1)}`;
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <article class="relative rounded-2xl border p-4 shadow-sm sm:p-5" :class="incidentToneClass">
        <span class="absolute left-3 top-5 h-2.5 w-2.5 rounded-full" :class="incidentDotClass" />

        <div class="pl-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-base font-bold text-gray-900">{{ incident.incident_type_label }} Incident</h4>
                    <span class="inline-flex rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-700">
                        {{ Number(incident.affected_count || 0).toLocaleString() }} pig(s) affected
                    </span>
                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold" :class="incidentStateBadgeClass">
                        {{ isResolutionEvent ? 'Resolved Event' : 'Active Case Event' }}
                    </span>
                    <span v-if="resolutionTargetLabel" class="inline-flex rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-700">
                        Target: {{ resolutionTargetLabel }}
                    </span>
                </div>

                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ timelineDateLabel }}: {{ timelineDateText }}
                </span>
            </div>

            <p v-if="incident.suspected_cause" class="mt-1 text-sm text-gray-700"><span class="font-semibold text-gray-900">Cause:</span> {{ incident.suspected_cause }}</p>
            <p v-if="incident.treatment_given" class="mt-1 text-sm text-gray-700"><span class="font-semibold text-gray-900">Treatment:</span> {{ incident.treatment_given }}</p>

            <p v-if="incident.remarks" class="mt-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                {{ incident.remarks }}
            </p>

            <div v-if="incident.media_url" class="mt-3 rounded-xl border border-gray-200 bg-white p-2">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Incident Photo</p>
                <a :href="incident.media_url" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-2">
                    <img :src="incident.media_url" alt="Incident uploaded photo" class="h-64 w-full rounded-md object-contain">
                </a>
            </div>
        </div>
    </article>
</template>
