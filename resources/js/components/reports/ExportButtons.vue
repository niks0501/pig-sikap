<script setup>
import { ref } from 'vue';

const props = defineProps({
    pdfUrl: { type: String, required: true },
    csvUrl: { type: String, required: true },
});

const downloading = ref('');

const handlePrint = () => {
    window.print();
};

const download = (type) => {
    downloading.value = type;
    window.location.href = type === 'pdf' ? props.pdfUrl : props.csvUrl;
    setTimeout(() => {
        downloading.value = '';
    }, 2000);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="downloading !== ''"
            @click="handlePrint"
        >
            Print
        </button>
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="downloading !== ''"
            @click="download('pdf')"
        >
            <svg
                v-if="downloading === 'pdf'"
                class="h-4 w-4 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ downloading === 'pdf' ? 'Generating PDF...' : 'PDF' }}
        </button>
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-[#0c6d57]/20 bg-[#0c6d57]/10 px-4 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/20 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="downloading !== ''"
            @click="download('csv')"
        >
            <svg
                v-if="downloading === 'csv'"
                class="h-4 w-4 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ downloading === 'csv' ? 'Generating CSV...' : 'CSV' }}
        </button>
    </div>
</template>
