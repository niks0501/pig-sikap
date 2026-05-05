<script setup>
import { ref } from 'vue';
import ExportButtons from './ExportButtons.vue';
import ReportFilterPanel from './ReportFilterPanel.vue';

const props = defineProps({
    type: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    previewUrl: { type: String, required: true },
    pdfUrl: { type: String, required: true },
    csvUrl: { type: String, required: true },
    report: { type: Object, default: () => ({}) },
    cycles: { type: Array, default: () => [] },
});

const openFilters = ref(false);
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Report Actions</p>
                <h3 class="text-lg font-bold text-gray-900">Export & Adjust Filters</h3>
            </div>
            <export-buttons :pdf-url="pdfUrl" :csv-url="csvUrl" />
        </div>

        <button
            type="button"
            class="mt-4 inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-600"
            @click="openFilters = !openFilters"
        >
            {{ openFilters ? 'Hide Filters' : 'Adjust Filters' }}
        </button>

        <div v-if="openFilters" class="mt-4">
            <report-filter-panel
                :type="type"
                :cycles="cycles"
                :initial-filters="filters"
                :action-url="previewUrl"
                submit-label="Update Preview"
            />
        </div>
    </div>
</template>
