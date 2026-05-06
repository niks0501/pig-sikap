<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar } from 'vue-chartjs';
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({ chartData: { type: Object, required: true } });
const data = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [{ label: 'Incidents', data: props.chartData?.data || [], backgroundColor: props.chartData?.colors || ['#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#10b981','#64748b'], borderRadius: 6, borderSkipped: false }],
}));
const options = {
    responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } },
};
</script>
<template><div class="h-56"><Bar :data="data" :options="options" /></div></template>
