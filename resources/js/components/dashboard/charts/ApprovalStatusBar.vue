<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar } from 'vue-chartjs';
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({ chartData: { type: Object, required: true } });
const data = computed(() => {
    const d = props.chartData || {};
    return {
        labels: ['Pending Approval', 'Approved', 'DSWD Submitted', 'Withdrawn', 'Finalized'],
        datasets: [{
            label: 'Resolutions',
            data: [d.pending_approval||0, d.ready_for_dswd||0, d.awaiting_dswd||0, d.ready_for_withdrawal||0, d.finalized||0],
            backgroundColor: ['#f59e0b','#3b82f6','#8b5cf6','#10b981','#64748b'],
            borderRadius: 6, borderSkipped: false,
        }],
    };
});
const options = {
    responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
    indexAxis: 'y',
    scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } }, y: { grid: { display: false }, ticks: { font: { size: 11 } } } },
};
</script>
<template><div class="h-56"><Bar :data="data" :options="options" /></div></template>
