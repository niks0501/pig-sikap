<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip, Legend,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({ chartData: { type: Object, required: true } });

const barData = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [{
        label: 'Pig Count',
        data: props.chartData?.data || [],
        backgroundColor: '#0c6d57',
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
        x: { grid: { display: false } },
    },
};
</script>

<template>
    <div class="h-56">
        <Bar :data="barData" :options="barOptions" />
    </div>
</template>
