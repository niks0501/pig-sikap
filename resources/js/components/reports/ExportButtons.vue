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
    }, 1200);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            @click="handlePrint"
        >
            Print
        </button>
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-4 text-sm font-semibold text-red-700 hover:bg-red-100"
            :disabled="downloading === 'pdf'"
            @click="download('pdf')"
        >
            {{ downloading === 'pdf' ? 'Preparing...' : 'PDF' }}
        </button>
        <button
            type="button"
            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-[#0c6d57]/20 bg-[#0c6d57]/10 px-4 text-sm font-semibold text-[#0c6d57] hover:bg-[#0c6d57]/20"
            :disabled="downloading === 'csv'"
            @click="download('csv')"
        >
            {{ downloading === 'csv' ? 'Preparing...' : 'CSV' }}
        </button>
    </div>
</template>
