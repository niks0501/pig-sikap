<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    cycles: { type: Array, default: () => [] },
});

const isOpen = ref(false);
const searchQuery = ref('');
const submitting = ref(false);

const openModal = () => {
    isOpen.value = true;
    searchQuery.value = '';
};

const closeModal = () => {
    if (!submitting.value) {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('open-cycle-picker-modal', openModal);
});

onUnmounted(() => {
    window.removeEventListener('open-cycle-picker-modal', openModal);
});

const filteredCycles = computed(() => {
    if (!searchQuery.value.trim()) return props.cycles;
    const q = searchQuery.value.toLowerCase();
    return props.cycles.filter(c =>
        c.batch_code.toLowerCase().includes(q) ||
        (c.stage || '').toLowerCase().includes(q) ||
        (c.status || '').toLowerCase().includes(q)
    );
});

const handleSelect = (cycle) => {
    submitting.value = true;
    const url = `${window.location.origin}/reports/per-cycle/quick?cycle_id=${cycle.id}`;
    window.location.href = url;
};

const statusBadgeClass = (status) => {
    const map = {
        'Active': 'bg-green-100 text-green-700',
        'Under Monitoring': 'bg-amber-100 text-amber-700',
        'Ready for Sale': 'bg-blue-100 text-blue-700',
        'Sold': 'bg-gray-100 text-gray-600',
        'Closed': 'bg-gray-100 text-gray-500',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4 sm:p-6" @click.self="closeModal">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Select a Cycle</h3>
                        <p class="mt-0.5 text-sm text-gray-500">Choose a pig cycle to generate the comprehensive report.</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        @click="closeModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18" stroke-width="2" /><line x1="6" y1="6" x2="18" y2="18" stroke-width="2" /></svg>
                    </button>
                </div>

                <div class="border-b px-6 py-3">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by batch code, stage, or status..."
                        class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]"
                    />
                </div>

                <div class="max-h-[340px] overflow-y-auto px-6 py-2">
                    <div v-if="filteredCycles.length === 0" class="py-10 text-center">
                        <p class="text-sm text-gray-500">No cycles found matching &ldquo;{{ searchQuery }}&rdquo;</p>
                    </div>

                    <button
                        v-for="cycle in filteredCycles"
                        :key="cycle.id"
                        type="button"
                        :disabled="submitting"
                        class="flex w-full items-center gap-4 rounded-xl px-3 py-3 text-left transition hover:bg-gray-50 disabled:opacity-50"
                        @click="handleSelect(cycle)"
                    >
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-[#0c6d57]/10 text-sm font-bold text-[#0c6d57]">
                            {{ (cycle.batch_code || '#')[0] }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ cycle.batch_code }}</p>
                            <p class="truncate text-xs text-gray-500">{{ cycle.stage }} &middot; {{ cycle.initial_count ?? 0 }} pigs</p>
                        </div>
                        <span :class="['ml-auto flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', statusBadgeClass(cycle.status)]">
                            {{ cycle.status }}
                        </span>
                    </button>
                </div>

                <div class="flex justify-end gap-3 border-t px-6 py-4">
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                        @click="closeModal"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>
