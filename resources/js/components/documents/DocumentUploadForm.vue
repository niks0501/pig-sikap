<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  documentTypes: {
    type: Array,
    default: () => [],
  },
  uploadUrl: {
    type: String,
    default: '/workflow/documents/upload',
  },
});

const emit = defineEmits(['upload-success', 'upload-error']);

const selectedTypeId = ref('');
const fileInput = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const selectedType = computed(() => {
  return props.documentTypes.find(t => t.id == selectedTypeId.value);
});

const allowedTypesDisplay = computed(() => {
  if (!selectedType.value) return '';
  return selectedType.value.allowed_file_types.map(t => t.toUpperCase()).join(', ');
});

const maxSizeDisplay = computed(() => {
  if (!selectedType.value) return '';
  const kb = selectedType.value.max_size_kb;
  return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb} KB`;
});

const isValidFile = computed(() => {
  if (!selectedFile.value || !selectedType.value) return false;
  const ext = '.' + selectedFile.value.name.split('.').pop()?.toLowerCase();
  const allowed = selectedType.value.allowed_file_types.map(t => '.' + t);
  if (!allowed.includes(ext)) return false;
  if (selectedFile.value.size > selectedType.value.max_size_kb * 1024) return false;
  return true;
});

const handleFileSelection = (event) => {
  const input = event.target;
  if (input.files && input.files.length > 0) {
    selectedFile.value = input.files[0];
    errorMessage.value = '';
  }
};

const selectDocumentType = (id) => {
  selectedTypeId.value = id;
  selectedFile.value = null;
  errorMessage.value = '';
  successMessage.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const submitUpload = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (!selectedTypeId.value) {
    errorMessage.value = 'Please select a document type.';
    return;
  }

  if (!selectedFile.value) {
    errorMessage.value = 'Please select a file to upload.';
    return;
  }

  if (!isValidFile.value) {
    const ext = '.' + selectedFile.value.name.split('.').pop()?.toLowerCase();
    const allowed = selectedType.value.allowed_file_types.map(t => '.' + t);
    if (!allowed.includes(ext)) {
      errorMessage.value = `Invalid file type. Allowed: ${allowedTypesDisplay.value}`;
    } else if (selectedFile.value.size > selectedType.value.max_size_kb * 1024) {
      errorMessage.value = `File too large. Maximum size: ${maxSizeDisplay.value}`;
    }
    return;
  }

  isUploading.value = true;

  const formData = new FormData();
  formData.append('document_type_id', selectedTypeId.value);
  formData.append('file', selectedFile.value);

  try {
    const response = await window.axios.post(props.uploadUrl, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        Accept: 'application/json',
      },
    });

    successMessage.value = response.data.message || 'Document uploaded successfully.';
    emit('upload-success', response.data.upload);
    selectedFile.value = null;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.file?.[0] || 'Upload failed. Please try again.';
    errorMessage.value = msg;
    emit('upload-error', msg);
  } finally {
    isUploading.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <div>
      <label class="block text-sm font-bold text-gray-700 mb-2">Select Document Type</label>
      <select
        v-model="selectedTypeId"
        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]"
        @change="selectDocumentType(selectedTypeId)"
      >
        <option value="" disabled>-- Choose required document --</option>
        <option v-for="dtype in documentTypes" :key="dtype.id" :value="dtype.id">
          {{ dtype.name }}
        </option>
      </select>
    </div>

    <div v-if="selectedType" class="rounded-lg bg-gray-50 p-4 border border-gray-200">
      <h3 class="font-semibold text-gray-900">{{ selectedType.name }}</h3>
      <p v-if="selectedType.description" class="mt-1 text-sm text-gray-600">{{ selectedType.description }}</p>
      <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">
        <span>Allowed: {{ allowedTypesDisplay }}</span>
        <span>Max size: {{ maxSizeDisplay }}</span>
      </div>
    </div>

    <div>
      <input
        ref="fileInput"
        type="file"
        :accept="selectedType ? selectedType.allowed_file_types.map(t => '.' + t).join(',') : '*'"
        class="hidden"
        @change="handleFileSelection"
      >

      <div
        v-if="!selectedFile"
        class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200 border-gray-300 hover:border-gray-400 hover:bg-gray-50"
        role="button"
        tabindex="0"
        :class="{ 'border-rose-400 bg-rose-50': errorMessage && !selectedFile }"
        @click="$refs.fileInput.click()"
        @keydown.enter.prevent="$refs.fileInput.click()"
      >
        <div class="flex flex-col items-center gap-2">
          <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <div>
            <p class="text-sm font-semibold text-gray-700">Click to select file</p>
            <p v-if="selectedType" class="mt-1 text-xs text-gray-500">
              Accepted: {{ allowedTypesDisplay }} • Max {{ maxSizeDisplay }}
            </p>
          </div>
        </div>
      </div>

      <div v-else class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            <svg class="h-8 w-8 shrink-0 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-gray-900">{{ selectedFile.name }}</p>
              <p class="text-xs text-gray-500">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
            @click="selectedFile = null; $refs.fileInput.value = ''"
          >
            Change
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-lg bg-rose-50 border border-rose-200 p-3">
      <p class="text-sm font-semibold text-rose-700">{{ errorMessage }}</p>
    </div>

    <div v-if="successMessage" class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
      <p class="text-sm font-semibold text-emerald-700">{{ successMessage }}</p>
    </div>

    <button
      type="button"
      :disabled="isUploading || !selectedFile"
      class="w-full rounded-lg bg-[#0c6d57] px-4 py-3 font-semibold text-white transition hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-50"
      @click="submitUpload"
    >
      <span v-if="isUploading">Uploading...</span>
      <span v-else>Upload Document</span>
    </button>
  </div>
</template>