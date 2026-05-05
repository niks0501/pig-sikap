<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    members: {
        type: Array,
        required: true,
    },
    memberShare: {
        type: Number,
        required: true,
    },
    existingDistributions: {
        type: Array,
        default: () => [],
    },
});

const allocations = ref([]);
const quickSplitMode = ref('equal');

const totalAllocated = computed(() => {
    return allocations.value.reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);
});

const remaining = computed(() => {
    return Math.max(0, props.memberShare - totalAllocated.value);
});

const isBalanced = computed(() => {
    return Math.abs(totalAllocated.value - props.memberShare) < 0.01;
});

const allocationError = computed(() => {
    if (allocations.value.length === 0) return '';
    const diff = totalAllocated.value - props.memberShare;
    if (Math.abs(diff) < 0.01) return '';
    if (diff > 0) {
        return `Over by ₱${diff.toFixed(2)}. Reduce some amounts to match the member share.`;
    }
    return `Under by ₱${Math.abs(diff).toFixed(2)}. Add more to match the member share.`;
});

// Initialize allocations from props
function initializeAllocations() {
    if (props.existingDistributions.length > 0) {
        allocations.value = props.existingDistributions.map(d => ({
            userId: d.user_id,
            name: d.name,
            amount: parseFloat(d.allocated_amount) || 0,
        }));
    } else if (props.members.length > 0) {
        doEqualSplit();
    } else {
        allocations.value = [];
    }
}

function doEqualSplit() {
    if (props.members.length === 0) return;
    const perMember = parseFloat((props.memberShare / props.members.length).toFixed(2));
    const remainder = parseFloat((props.memberShare - perMember * props.members.length).toFixed(2));

    allocations.value = props.members.map((m, index) => ({
        userId: m.id,
        name: m.name,
        amount: index === 0 ? perMember + remainder : perMember,
    }));
    quickSplitMode.value = 'equal';
}

function doCustomSplit() {
    quickSplitMode.value = 'custom';
}

function onAmountInput(index, event) {
    const value = event.target.value.replace(/[^0-9.]/g, '');
    allocations.value[index].amount = value;
    quickSplitMode.value = 'custom';
}

function formatCurrency(value) {
    const num = parseFloat(value) || 0;
    return '₱' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Expose for the container form
function getDistributionData() {
    if (!isBalanced.value || props.memberShare <= 0) return [];
    return allocations.value
        .filter(a => (parseFloat(a.amount) || 0) > 0)
        .map(a => ({
            user_id: a.userId,
            allocated_amount: parseFloat(parseFloat(a.amount || 0).toFixed(2)),
            notes: null,
        }));
}

defineExpose({ getDistributionData });

watch(() => props.members, initializeAllocations, { immediate: true });
watch(() => props.memberShare, () => {
    if (quickSplitMode.value === 'equal') {
        doEqualSplit();
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Quick split controls -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button"
                @click="doEqualSplit"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                :class="{ 'ring-2 ring-[#0c6d57]/30 border-[#0c6d57]': quickSplitMode === 'equal' }">
                Equal Split
            </button>
            <span class="text-sm text-gray-500">
                {{ members.length }} member{{ members.length !== 1 ? 's' : '' }} · {{ formatCurrency(memberShare) }} total
            </span>
        </div>

        <!-- Member allocation rows -->
        <div class="space-y-2">
            <div v-for="(alloc, index) in allocations" :key="alloc.userId"
                class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ alloc.name }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-sm text-gray-400">₱</span>
                    <input
                        type="text"
                        inputmode="decimal"
                        :value="alloc.amount"
                        @input="e => onAmountInput(index, e)"
                        class="w-28 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-right font-semibold text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        placeholder="0.00"
                    />
                </div>
            </div>
        </div>

        <!-- Summary bar -->
        <div class="flex items-center justify-between rounded-xl border px-4 py-3"
            :class="isBalanced ? 'border-[#0c6d57]/30 bg-[#0c6d57]/5' : 'border-amber-200 bg-amber-50'">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em]"
                    :class="isBalanced ? 'text-[#0c6d57]' : 'text-amber-800'">
                    {{ isBalanced ? 'Balanced' : 'Unbalanced' }}
                </p>
                <p class="mt-0.5 text-sm"
                    :class="isBalanced ? 'text-[#0a5a48]' : 'text-amber-700'">
                    Allocated: {{ formatCurrency(totalAllocated) }} / {{ formatCurrency(memberShare) }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">Remaining</p>
                <p class="text-lg font-extrabold"
                    :class="isBalanced ? 'text-gray-400' : 'text-amber-700'">
                    {{ formatCurrency(remaining) }}
                </p>
            </div>
        </div>

        <!-- Error message -->
        <p v-if="allocationError" class="text-sm font-semibold text-rose-700">
            {{ allocationError }}
        </p>

        <!-- No members -->
        <p v-if="members.length === 0" class="text-sm text-gray-500 italic">
            No active members found. Add members to the system first.
        </p>
    </div>
</template>
