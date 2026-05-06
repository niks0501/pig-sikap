<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
} from 'chart.js';
import { Doughnut, Bar, Line } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const props = defineProps({
    chartData: { type: Object, required: true },
});

defineEmits(['filter']);

const donutData = computed(() => ({
    labels: props.chartData?.labels || [],
    datasets: [{
        data: props.chartData?.data || [],
        backgroundColor: props.chartData?.colors || ['#10b981', '#f59e0b', '#8b5cf6', '#3b82f6', '#ef4444'],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'right',
            labels: { padding: 10, usePointStyle: true, pointStyleWidth: 8, font: { size: 11 } },
        },
    },
};
</script>

<template>
    <div class="h-56">
        <Doughnut :data="donutData" :options="donutOptions" />
    </div>
</template>
