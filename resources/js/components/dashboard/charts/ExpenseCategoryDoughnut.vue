<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS, ArcElement, Tooltip, Legend,
} from 'chart.js';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({ chartData: { type: Object, required: true } });

const donutData = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [{
        data: props.chartData?.data || [],
        backgroundColor: props.chartData?.colors || ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#64748b', '#ef4444'],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'right', labels: { padding: 10, usePointStyle: true, pointStyleWidth: 8, font: { size: 11 } } },
    },
};
</script>

<template>
    <div class="h-56">
        <Doughnut :data="donutData" :options="options" />
    </div>
</template>
