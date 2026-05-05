<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Filler,
} from 'chart.js';
import { Bar, Pie } from 'vue-chartjs';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Filler
);

const props = defineProps({
    charts: { type: Object, default: () => ({}) },
});

const hasCharts = computed(() => Object.keys(props.charts).length > 0);

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 16 } },
    },
};

const barOptions = {
    ...chartOptions,
    scales: {
        y: { beginAtZero: true },
    },
};
</script>

<template>
    <div v-if="hasCharts" class="grid gap-6 mt-6 sm:grid-cols-2">
        <div v-if="charts.expensePie" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Expense Breakdown</h4>
            <div style="height: 260px;">
                <Pie :data="charts.expensePie" :options="chartOptions" />
            </div>
        </div>

        <div v-if="charts.salesVsExpenses" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Sales vs Expenses</h4>
            <div style="height: 260px;">
                <Bar :data="charts.salesVsExpenses" :options="barOptions" />
            </div>
        </div>

        <div v-if="charts.monthlyNet" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Monthly Net</h4>
            <div style="height: 260px;">
                <Bar :data="charts.monthlyNet" :options="barOptions" />
            </div>
        </div>

        <div v-if="charts.mortalityByCause" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Mortality by Cause</h4>
            <div style="height: 260px;">
                <Pie :data="charts.mortalityByCause" :options="chartOptions" />
            </div>
        </div>

        <div v-if="charts.inventoryByStage" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Inventory by Stage</h4>
            <div style="height: 260px;">
                <Bar :data="charts.inventoryByStage" :options="barOptions" />
            </div>
        </div>

        <div v-if="charts.profitabilityPerCycle" class="rounded-2xl border border-gray-200 bg-white p-5">
            <h4 class="mb-3 text-sm font-bold text-gray-700">Profitability per Cycle</h4>
            <div style="height: 260px;">
                <Bar :data="charts.profitabilityPerCycle" :options="barOptions" />
            </div>
        </div>
    </div>
</template>
