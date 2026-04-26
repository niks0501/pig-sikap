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

const hasReceipt = computed(() => {
    return props.expense?.receipt_url && props.expense.receipt_url.trim() !== '';
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
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ formatCategoryLabel(expense.category) }}
                        </p>
                        <span v-if="hasReceipt" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5">
                            <svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-gray-900 truncate">{{ expense.notes }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ expense.cycle?.batch_code || 'Unknown cycle' }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">
                        By {{ expense.created_by_name || 'System' }} • {{ formatDate(expense.expense_date) }}
                    </p>
                </div>
            </div>

            <div class="text-right shrink-0">
                <p class="text-base font-bold text-gray-900">{{ formatAmount(expense.amount) }}</p>
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