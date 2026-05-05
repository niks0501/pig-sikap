<script setup>
import ReportFilterPanel from './ReportFilterPanel.vue';

const props = defineProps({
    type: { type: String, required: true },
    cycles: { type: Array, default: () => [] },
    actionUrl: { type: String, required: true },
});
</script>

<template>
    <report-filter-panel
        :type="type"
        :cycles="cycles"
        :action-url="actionUrl"
    />
</template>
