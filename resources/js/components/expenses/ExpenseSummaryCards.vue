<script setup>
import { computed } from 'vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_amount: 0,
            entry_count: 0,
            feed_share_percent: 0,
            by_category: {},
        }),
    },
    showChart: {
        type: Boolean,
        default: true,
    },
});

const categoryColors = {
    acquisition: 'bg-blue-500',
    feed: 'bg-emerald-500',
    medicine: 'bg-purple-500',
    transport: 'bg-amber-500',
    emergency: 'bg-rose-500',
};

const categoryIcons = {
    acquisition: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    feed: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    medicine: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
    transport: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    emergency: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
};

const categories = ['acquisition', 'feed', 'medicine', 'transport', 'emergency'];

const maxCategoryAmount = computed(() => {
    const values = Object.values(props.summary.by_category || {});
    return Math.max(...values, 1);
});

const categoryPercentages = computed(() => {
    const result = {};
    const max = maxCategoryAmount.value;

    categories.forEach((cat) => {
        const amount = props.summary.by_category?.[cat] || 0;
        result[cat] = Math.round((amount / max) * 100);
    });

    return result;
});

const formatCategoryLabel = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1);
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(parseFloat(amount || 0));
};

const totalFormatted = computed(() => formatAmount(props.summary.total_amount));
const entryCountFormatted = computed(() => {
    return new Intl.NumberFormat('en-PH').format(parseInt(props.summary.entry_count || 0));
});
const feedShareFormatted = computed(() => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(parseFloat(props.summary.feed_share_percent || 0));
});
</script>

<template>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
<div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
<p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Filtered Total</p>
<p class="mt-1 text-2xl font-bold text-gray-900">{{ totalFormatted }}</p>
</div>

<div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
<p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Entries</p>
<p class="mt-1 text-2xl font-bold text-gray-900">{{ entryCountFormatted }}</p>
</div>

<div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
<p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Feed Share</p>
<p class="mt-1 text-2xl font-bold text-gray-900">{{ feedShareFormatted }}%</p>
</div>

<div v-if="showChart" class="sm:col-span-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
<p class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-4">Expense Breakdown by Category</p>

            <div class="space-y-4">
                <div
                    v-for="category in categories"
                    :key="category"
                    class="flex items-center gap-4"
                >
                    <div class="w-24 shrink-0">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryIcons[category]" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">{{ formatCategoryLabel(category) }}</span>
                        </div>
                    </div>

                    <div class="flex-1 relative">
                        <div class="h-6 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="categoryColors[category]"
                                :style="{ width: `${categoryPercentages[category]}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="w-32 shrink-0 text-right">
                        <span class="text-sm font-semibold text-gray-900">{{ formatAmount(props.summary.by_category?.[category] || 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>