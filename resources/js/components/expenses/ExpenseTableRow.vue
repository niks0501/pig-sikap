<script setup>
import { computed } from 'vue';

const props = defineProps({
    expense: {
        type: Object,
        required: true,
    },
    isSelected: {
        type: Boolean,
        default: false,
    },
    showCheckbox: {
        type: Boolean,
        default: false,
    },
    routes: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['toggle-select']);

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(parseFloat(amount || 0));
};

const formatCategoryLabel = (category) => {
    return category?.charAt(0).toUpperCase() + category?.slice(1) || '';
};

const categoryMeta = computed(() => {
    const meta = {
        acquisition: {
            classes: 'border-sky-200 bg-sky-50 text-sky-700',
            icon: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        },
        feed: {
            classes: 'border-emerald-200 bg-emerald-50 text-[#0c6d57]',
            icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        },
        medicine: {
            classes: 'border-violet-200 bg-violet-50 text-violet-700',
            icon: 'M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5C21.846 17.846 20.954 20 19.172 20H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        },
        transport: {
            classes: 'border-amber-200 bg-amber-50 text-amber-700',
            icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        },
        emergency: {
            classes: 'border-rose-200 bg-rose-50 text-rose-700',
            icon: 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
        },
    };

    return meta[props.expense.category] || meta.feed;
});

</script>

<template>
    <div
        :class="[
            'rounded-xl border p-4 transition-all duration-200',
            isSelected
                ? 'border-[#0c6d57] bg-[#0c6d57]/5'
                : 'border-gray-100 bg-white hover:border-[#0c6d57]/40',
        ]"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0">
                <input
                    v-if="showCheckbox"
                    type="checkbox"
                    :checked="isSelected"
                    class="mt-1 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]"
                    @change="emit('toggle-select')"
                >

                <div class="min-w-0">
                    <p :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold', categoryMeta.classes]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryMeta.icon" />
                        </svg>
                        {{ formatCategoryLabel(expense.category) }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 truncate">{{ expense.notes }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ expense.cycle?.batch_code || 'Unknown cycle' }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">
                        {{ formatDate(expense.expense_date) }}
                    </p>
                </div>
            </div>

            <div class="text-right shrink-0">
                <p class="text-xl font-black text-gray-900">{{ formatAmount(expense.amount) }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Total Amount</p>
                <a
                    :href="props.routes.show?.replace('_ID_', expense.id)"
                    class="mt-2 inline-flex items-center text-xs font-semibold text-[#0c6d57] hover:text-[#0a5a48]"
                >
                    Details
                    <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</template>
