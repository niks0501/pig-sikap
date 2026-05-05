<script setup>
const props = defineProps({
    type: { type: String, required: true },
    summary: { type: Object, default: () => ({}) },
});

const cards = () => {
    switch (props.type) {
        case 'inventory':
            return [
                { label: 'Active Cycles', value: props.summary.cycle_count ?? 0 },
                { label: 'Total Active Pigs', value: props.summary.total_active ?? 0 },
                { label: 'Total Deceased', value: props.summary.total_deceased ?? 0 },
            ];
        case 'health':
            return [
                { label: 'Cycles Covered', value: props.summary.cycle_count ?? 0 },
                { label: 'Currently Affected', value: props.summary.total_currently_affected ?? 0 },
                { label: 'Mortality', value: props.summary.total_mortality ?? 0 },
            ];
        case 'mortality':
            return [
                { label: 'Cases', value: props.summary.total_cases ?? 0 },
                { label: 'Deceased Count', value: props.summary.total_deceased ?? 0 },
                { label: 'Period', value: props.summary.period ?? 'Custom' },
            ];
        case 'expense':
            return [
                { label: 'Total Expenses', value: `PHP ${(props.summary.total_amount ?? 0).toFixed(2)}` },
                { label: 'Entry Count', value: props.summary.entry_count ?? 0 },
                { label: 'Feed Share', value: `${props.summary.feed_share_percent ?? 0}%` },
            ];
        case 'sales':
            return [
                { label: 'Total Sales', value: `PHP ${(props.summary.total_amount ?? 0).toFixed(2)}` },
                { label: 'Total Paid', value: `PHP ${(props.summary.total_paid ?? 0).toFixed(2)}` },
                { label: 'Pigs Sold', value: props.summary.total_pigs_sold ?? 0 },
            ];
        case 'monthly':
        case 'quarterly':
            return [
                { label: 'Period', value: props.summary.period ?? 'Custom' },
                { label: 'Total Sales', value: `PHP ${(props.summary.total_sales ?? 0).toFixed(2)}` },
                { label: 'Net Result', value: `PHP ${(props.summary.net_result ?? 0).toFixed(2)}` },
            ];
        case 'profitability':
            return [
                { label: 'Cycles Covered', value: props.summary.cycle_count ?? 0 },
                { label: 'Gross Income', value: `PHP ${(props.summary.gross_income ?? 0).toFixed(2)}` },
                { label: 'Net Profit/Loss', value: `PHP ${(props.summary.net_profit_or_loss ?? 0).toFixed(2)}` },
            ];
        default:
            return [];
    }
};
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <div v-for="card in cards()" :key="card.label" class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ card.label }}</p>
            <p class="mt-2 text-xl font-bold text-[#0c6d57]">{{ card.value }}</p>
        </div>
    </div>
</template>
