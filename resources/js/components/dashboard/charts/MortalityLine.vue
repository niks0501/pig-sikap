<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler } from 'chart.js';
import { Line } from 'vue-chartjs';
ChartJS.register(LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const props = defineProps({ chartData: { type: Object, required: true } });
const data = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [{ label: 'Deaths', data: props.chartData?.data || [], borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3, pointRadius: 4, pointBackgroundColor: '#ef4444' }],
}));
const options = {
    responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } },
};
</script>
<template><div class="h-56"><Line :data="data" :options="options" /></div></template>
