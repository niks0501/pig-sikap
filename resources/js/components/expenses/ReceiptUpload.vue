<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    currentReceiptUrl: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
    maxFileSize: {
        type: Number,
        default: 8 * 1024,
    },
    acceptedTypes: {
        type: String,
        default: '.jpg,.jpeg,.png,.webp,.pdf',
    },
});

const emit = defineEmits(['receipt-selected', 'receipt-removed']);

const fileInput = ref(null);
const previewUrl = ref('');
const previewObjectUrl = ref('');
const fileName = ref('');
const isDragging = ref(false);
const isRemoving = ref(false);
const internalError = ref('');

const acceptedTypesArray = computed(() => {
    return props.acceptedTypes.split(',').map((t) => t.trim().toLowerCase());
});

const acceptedMimeTypes = computed(() => {
    const mimeMap = {
        '.jpg': 'image/jpeg',
        '.jpeg': 'image/jpeg',
        '.png': 'image/png',
        '.webp': 'image/webp',
        '.pdf': 'application/pdf',
    };

    return acceptedTypesArray.value
        .map((ext) => mimeMap[ext] || ext)
        .join(',');
});

const hasExistingReceipt = computed(() => {
    return props.currentReceiptUrl && props.currentReceiptUrl.trim() !== '';
});

const hasPreview = computed(() => {
    return previewUrl.value !== '';
});

const isPdf = computed(() => {
    if (fileName.value) {
        return fileName.value.toLowerCase().endsWith('.pdf');
    }

    return props.currentReceiptUrl.toLowerCase().split('?')[0].endsWith('.pdf');
});

const hasFileDisplay = computed(() => {
    return fileName.value !== '' || (hasExistingReceipt.value && !isRemoving.value);
});

const displayPreviewUrl = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value;
    }

    if (hasExistingReceipt.value && !isRemoving.value) {
        return props.currentReceiptUrl;
    }

    return '';
});

const fieldError = computed(() => {
    return internalError.value || props.errorMessage;
});

const revokeObjectUrl = () => {
    if (previewObjectUrl.value) {
        URL.revokeObjectURL(previewObjectUrl.value);
        previewObjectUrl.value = '';
    }
};

const clearSelectedFile = () => {
    revokeObjectUrl();
    previewUrl.value = '';
    fileName.value = '';
    internalError.value = '';

    if (fileInput.value && 'value' in fileInput.value) {
        fileInput.value.value = '';
    }

    emit('receipt-removed', false);
};

const validateFile = (file) => {
    if (!file) {
        internalError.value = 'No file selected.';
        return false;
    }

    const extension = '.' + file.name.split('.').pop()?.toLowerCase();

    if (!acceptedTypesArray.value.includes(extension)) {
        internalError.value = `Invalid file type. Accepted types: ${props.acceptedTypes}`;
        return false;
    }

    if (file.size > props.maxFileSize * 1024) {
        const maxSizeMB = (props.maxFileSize / 1024).toFixed(1);
        internalError.value = `File too large. Maximum size is ${maxSizeMB}MB.`;
        return false;
    }

    internalError.value = '';
    return true;
};

const handleFileSelection = (event) => {
    const input = event?.target;

    if (!input || !input.files || input.files.length < 1) {
        clearSelectedFile();
        return;
    }

    const selectedFile = input.files[0];

    if (!validateFile(selectedFile)) {
        clearSelectedFile();
        return;
    }

    revokeObjectUrl();
    fileName.value = selectedFile.name;
    isRemoving.value = false;

    const mimeType = String(selectedFile.type || '');

    if (mimeType.startsWith('image/')) {
        previewObjectUrl.value = URL.createObjectURL(selectedFile);
        previewUrl.value = previewObjectUrl.value;
    } else {
        previewUrl.value = '';
    }

    emit('receipt-selected', selectedFile);
};

const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = false;

    const droppedFiles = event.dataTransfer?.files;

    if (!droppedFiles || droppedFiles.length < 1) {
        return;
    }

    if (fileInput.value) {
        const dataTransfer = new DataTransfer();
        for (let i = 0; i < droppedFiles.length; i++) {
            dataTransfer.items.add(droppedFiles[i]);
        }
        fileInput.value.files = dataTransfer.files;

        const inputEvent = new Event('change', { bubbles: true });
        fileInput.value.dispatchEvent(inputEvent);
    }
};

const openFilePicker = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const handleRemoveExisting = () => {
    isRemoving.value = true;
    revokeObjectUrl();
    previewUrl.value = '';
    fileName.value = '';
    internalError.value = '';

    if (fileInput.value && 'value' in fileInput.value) {
        fileInput.value.value = '';
    }

    emit('receipt-removed', true);
};

onBeforeUnmount(() => {
    revokeObjectUrl();
});
</script>

<template>
    <div class="space-y-2">
        <label class="block">
            <span class="mb-1.5 block text-sm font-bold text-gray-700">
                Receipt
                <span class="font-normal text-gray-500">(optional)</span>
            </span>

            <input
                ref="fileInput"
                type="file"
                name="receipt"
                :accept="acceptedMimeTypes"
                class="hidden"
                @change="handleFileSelection"
            >

            <div
                v-if="!hasFileDisplay"
                class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200"
                :class="[
                    isDragging
                        ? 'border-[#0c6d57] bg-[#0c6d57]/5'
                        : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50',
                    fieldError ? 'border-rose-400 bg-rose-50' : '',
                ]"
                role="button"
                tabindex="0"
                aria-label="Upload receipt file"
                @click="openFilePicker"
                @keydown.enter.prevent="openFilePicker"
                @keydown.space.prevent="openFilePicker"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
            >
                <div class="flex flex-col items-center gap-2">
                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">
                            Drag and drop receipt here, or click to browse
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Accepted: {{ acceptedTypes }} • Max {{ (maxFileSize / 1024).toFixed(0) }}MB
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg v-if="isPdf" class="h-8 w-8 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="h-8 w-8 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ fileName || 'Current Receipt' }}</p>
                            <p class="text-xs text-gray-500">{{ hasExistingReceipt && !fileName ? 'Click to view' : 'Click to change' }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
                            @click.stop="openFilePicker"
                        >
                            Change
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                            @click.stop="fileName ? clearSelectedFile() : handleRemoveExisting()"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <div
                    v-if="!isPdf && displayPreviewUrl"
                    class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white"
                >
                    <img
                        :src="displayPreviewUrl"
                        alt="Receipt preview"
                        class="max-h-48 w-full object-contain"
                    >
                </div>

                <a
                    v-if="isPdf && hasExistingReceipt && !fileName"
                    :href="displayPreviewUrl"
                    target="_blank"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    View PDF
                </a>
            </div>
        </label>

        <p v-if="fieldError" class="text-xs font-semibold text-rose-700">
            {{ fieldError }}
        </p>
    </div>
</template>
