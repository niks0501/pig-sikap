<script setup>
import { ref } from 'vue';

const props = defineProps({
    monthlyUrl: { type: String, required: true },
    quarterlyUrl: { type: String, required: true },
    perCycleUrl: { type: String, required: true },
    dswdUrl: { type: String, required: true },
});

const monthlyLoading = ref(false);
const quarterlyLoading = ref(false);
const dswdLoading = ref(false);

const goMonthly = () => { monthlyLoading.value = true; window.location.href = props.monthlyUrl; };
const goQuarterly = () => { quarterlyLoading.value = true; window.location.href = props.quarterlyUrl; };
const goDswd = () => { dswdLoading.value = true; window.location.href = props.dswdUrl; };

const openCyclePicker = () => {
    window.dispatchEvent(new CustomEvent('open-cycle-picker-modal'));
};
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <button
            type="button"
            :disabled="monthlyLoading"
            class="group flex flex-col items-start gap-3 rounded-2xl border-2 border-[#0c6d57]/20 bg-white p-5 text-left shadow-sm transition hover:border-[#0c6d57]/50 hover:shadow-md disabled:opacity-60"
            @click="goMonthly"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0c6d57]/10 text-[#0c6d57]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2" /><line x1="16" y1="2" x2="16" y2="6" stroke-width="2" /><line x1="8" y1="2" x2="8" y2="6" stroke-width="2" /><line x1="3" y1="10" x2="21" y2="10" stroke-width="2" /></svg>
                </span>
                <span v-if="monthlyLoading" class="text-sm font-semibold text-gray-500">Generating...</span>
                <span v-else class="text-sm font-bold text-gray-900 group-hover:text-[#0c6d57]">Monthly Report</span>
            </div>
            <p class="text-xs text-gray-500">Current month financial summary &mdash; one tap. Ready for meetings.</p>
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#0c6d57]/10 px-2.5 py-1 text-xs font-semibold text-[#0c6d57]">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9,18 15,12 9,6" stroke-width="2.5" /></svg>
                Generate Now
            </span>
        </button>

        <button
            type="button"
            :disabled="quarterlyLoading"
            class="group flex flex-col items-start gap-3 rounded-2xl border-2 border-[#0c6d57]/20 bg-white p-5 text-left shadow-sm transition hover:border-[#0c6d57]/50 hover:shadow-md disabled:opacity-60"
            @click="goQuarterly"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0c6d57]/10 text-[#0c6d57]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-4" stroke-width="2" /></svg>
                </span>
                <span v-if="quarterlyLoading" class="text-sm font-semibold text-gray-500">Generating...</span>
                <span v-else class="text-sm font-bold text-gray-900 group-hover:text-[#0c6d57]">Quarterly Report</span>
            </div>
            <p class="text-xs text-gray-500">Current quarter aggregated summary. Perfect for quarterly reviews.</p>
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#0c6d57]/10 px-2.5 py-1 text-xs font-semibold text-[#0c6d57]">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9,18 15,12 9,6" stroke-width="2.5" /></svg>
                Generate Now
            </span>
        </button>

        <button
            type="button"
            class="group flex flex-col items-start gap-3 rounded-2xl border-2 border-amber-200 bg-white p-5 text-left shadow-sm transition hover:border-amber-400 hover:shadow-md"
            @click="openCyclePicker"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-width="2" /></svg>
                </span>
                <span class="text-sm font-bold text-gray-900 group-hover:text-amber-700">Per Cycle Report</span>
            </div>
            <p class="text-xs text-gray-500">Comprehensive single-cycle document: inventory, expenses, sales, health.</p>
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                Pick Cycle &rarr;
            </span>
        </button>

        <button
            type="button"
            :disabled="dswdLoading"
            class="group flex flex-col items-start gap-3 rounded-2xl border-2 border-blue-200 bg-white p-5 text-left shadow-sm transition hover:border-blue-400 hover:shadow-md disabled:opacity-60"
            @click="goDswd"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2" /></svg>
                </span>
                <span v-if="dswdLoading" class="text-sm font-semibold text-gray-500">Generating...</span>
                <span v-else class="text-sm font-bold text-gray-900 group-hover:text-blue-700">DSWD/LGU Summary</span>
            </div>
            <p class="text-xs text-gray-500">Zero-config compliance document. Association overview for DSWD &amp; LGU.</p>
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9,18 15,12 9,6" stroke-width="2.5" /></svg>
                Generate Now
            </span>
        </button>
    </div>
</template>
