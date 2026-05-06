<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const props = defineProps({ chartData: { type: Object, required: true } });

const data = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [
        {
            label: 'Sales', data: props.chartData?.sales || [],
            borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)',
            fill: true, tension: 0.3, pointRadius: 3, pointHoverRadius: 5,
        },
        {
            label: 'Expenses', data: props.chartData?.expenses || [],
            borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.05)',
            fill: true, tension: 0.3, pointRadius: 3, pointHoverRadius: 5,
        },
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

<template>
    <div class="h-56"><Line :data="data" :options="options" /></div>
</template>
