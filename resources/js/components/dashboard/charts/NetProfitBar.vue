<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar } from 'vue-chartjs';
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({ chartData: { type: Object, required: true } });
const data = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [
        { label: 'Revenue', data: props.chartData?.revenue || [], backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false },
        { label: 'Expenses', data: props.chartData?.expenses || [], backgroundColor: '#94a3b8', borderRadius: 6, borderSkipped: false },
    ],
}));
const options = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } } } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: (v) => '\u20B1' + (v/1000).toFixed(0) + 'k' }, grid: { color: '#f3f4f6' } },
        x: { grid: { display: false } },
    },
};
</script>
<template><div class="h-56"><Bar :data="data" :options="options" /></div></template>
