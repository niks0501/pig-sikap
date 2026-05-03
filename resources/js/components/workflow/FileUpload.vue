<script setup>
/**
 * FileUpload.vue – Drag-and-drop file upload component with progress indicator.
 * Used within the resolution document workflow for uploading signed documents,
 * signature sheets, and DSWD approvals.
 * Follows the design system: mobile-first, 44px touch targets, #0c6d57 accent.
 */
import { ref, computed } from 'vue'

const props = defineProps({
    /** The upload endpoint URL */
    uploadUrl: { type: String, required: true },
    /** CSRF token for form submission */
    csrfToken: { type: String, required: true },
    /** Field name for the file input */
    fieldName: { type: String, default: 'signed_document' },
    /** Accepted file types */
    accept: { type: String, default: '.pdf' },
    /** Max file size in bytes (default 10MB) */
    maxSize: { type: Number, default: 10 * 1024 * 1024 },
    /** Human-readable accepted formats */
    acceptLabel: { type: String, default: 'PDF files' },
    /** Human-readable max size */
    maxSizeLabel: { type: String, default: '10MB' },
    /** Heading label */
    label: { type: String, default: 'Upload Document' },
    /** Whether to show a description field */
    showDescription: { type: Boolean, default: false },
    /** Whether to show the optional signature sheet input */
    showSignatureSheet: { type: Boolean, default: false },
    /** Whether to show DSWD-specific fields */
    showDswdFields: { type: Boolean, default: false },
})

// ── State ──
const file = ref(null)
const signatureSheet = ref(null)
const description = ref('')
const dswdReference = ref('')
const approvalDate = ref('')
const isDragOver = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadError = ref(null)
const uploadSuccess = ref(false)

// ── Computed ──
const fileName = computed(() => file.value?.name || '')
const fileSize = computed(() => {
    if (!file.value) return ''
    const bytes = file.value.size
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return bytes + ' B'
})

const isValidFile = computed(() => {
    if (!file.value) return false
    if (file.value.size > props.maxSize) return false

    const allowedExts = props.accept.split(',').map(e => e.trim().replace('.', '').toLowerCase())
    const ext = file.value.name.split('.').pop()?.toLowerCase()

    return allowedExts.includes(ext)
})

// ── File handling ──
function onFileSelect(event) {
    const selected = event.target.files?.[0]
    if (selected) validateAndSetFile(selected)
}

function onDragOver(event) {
    event.preventDefault()
    isDragOver.value = true
}

function onDragLeave() {
    isDragOver.value = false
}

function onDrop(event) {
    event.preventDefault()
    isDragOver.value = false
    const dropped = event.dataTransfer?.files?.[0]
    if (dropped) validateAndSetFile(dropped)
}

function validateAndSetFile(f) {
    uploadError.value = null
    uploadSuccess.value = false

    if (f.size > props.maxSize) {
        uploadError.value = `File exceeds maximum size of ${props.maxSizeLabel}.`
        return
    }

    const allowedExts = props.accept.split(',').map(e => e.trim().replace('.', '').toLowerCase())
    const ext = f.name.split('.').pop()?.toLowerCase()

    if (!allowedExts.includes(ext)) {
        uploadError.value = `File type ".${ext}" is not allowed. Accepted: ${props.acceptLabel}.`
        return
    }

    file.value = f
}

function clearFile() {
    file.value = null
    uploadError.value = null
    uploadSuccess.value = false
    uploadProgress.value = 0
}

function onSignatureSheetSelect(event) {
    signatureSheet.value = event.target.files?.[0] || null
}

// ── Upload ──
async function upload() {
    if (!isValidFile.value) return

    uploading.value = true
    uploadError.value = null
    uploadProgress.value = 0

    const fd = new FormData()
    fd.append('_token', props.csrfToken)
    fd.append(props.fieldName, file.value)

    if (props.showDescription && description.value) {
        const descField = props.showDswdFields ? 'approval_notes' : 'description'
        fd.append(descField, description.value)
    }

    if (props.showSignatureSheet && signatureSheet.value) {
        fd.append('signature_sheet', signatureSheet.value)
    }

    if (props.showDswdFields) {
        if (dswdReference.value) fd.append('dswd_reference_number', dswdReference.value)
        if (approvalDate.value) fd.append('approval_date', approvalDate.value)
    }

    try {
        const xhr = new XMLHttpRequest()
        xhr.open('POST', props.uploadUrl)
        xhr.setRequestHeader('Accept', 'application/json')
        xhr.setRequestHeader('X-CSRF-TOKEN', props.csrfToken)

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                uploadProgress.value = Math.round((e.loaded / e.total) * 100)
            }
        })

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                uploadSuccess.value = true
                // Reload page after short delay to show success state
                setTimeout(() => window.location.reload(), 800)
            } else {
                const data = JSON.parse(xhr.responseText || '{}')
                uploadError.value = data.message || 'Upload failed. Please try again.'
                uploading.value = false
            }
        })

        xhr.addEventListener('error', () => {
            uploadError.value = 'Network error. Please check your connection and try again.'
            uploading.value = false
        })

        // Send the form data via XHR for progress tracking
        xhr.send(fd)
    } catch {
        uploadError.value = 'An unexpected error occurred.'
        uploading.value = false
    }
}
</script>

<template>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-base font-semibold text-gray-900 mb-1">{{ label }}</h3>
    <p class="text-xs text-gray-500 mb-4">Accepted: {{ acceptLabel }} · Max size: {{ maxSizeLabel }}</p>

    <!-- Error alert -->
    <div v-if="uploadError" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-start gap-2">
        <span class="flex-shrink-0 mt-0.5">⚠️</span>
        <div class="flex-1">
            <p class="font-medium">Upload Failed</p>
            <p class="text-xs mt-0.5">{{ uploadError }}</p>
        </div>
        <button @click="uploadError = null" class="text-rose-400 hover:text-rose-600 flex-shrink-0">✕</button>
    </div>

    <!-- Success alert -->
    <div v-if="uploadSuccess" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
        <span>✅</span>
        <p class="font-medium">Document uploaded successfully! Refreshing...</p>
    </div>

    <!-- Drag & Drop Zone -->
    <div v-if="!file"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        class="relative border-2 border-dashed rounded-xl px-6 py-8 text-center transition-all cursor-pointer"
        :class="isDragOver
            ? 'border-[#0c6d57] bg-emerald-50/50'
            : 'border-gray-200 hover:border-[#0c6d57]/40 hover:bg-gray-50/50'">
        <input
            type="file"
            :accept="accept"
            @change="onFileSelect"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        />
        <div class="space-y-2">
            <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center"
                :class="isDragOver ? 'bg-emerald-100' : ''">
                <svg class="w-6 h-6" :class="isDragOver ? 'text-[#0c6d57]' : 'text-gray-400'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <p class="text-sm font-medium" :class="isDragOver ? 'text-[#0c6d57]' : 'text-gray-600'">
                {{ isDragOver ? 'Drop file here' : 'Drag & drop or click to browse' }}
            </p>
            <p class="text-xs text-gray-400">{{ acceptLabel }} up to {{ maxSizeLabel }}</p>
        </div>
    </div>

    <!-- Selected File Preview -->
    <div v-else class="space-y-4">
        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-gray-200">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                <span class="text-red-600 font-bold text-xs">PDF</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ fileName }}</p>
                <p class="text-xs text-gray-500">{{ fileSize }}</p>
            </div>
            <button v-if="!uploading" @click="clearFile"
                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 min-h-[44px] min-w-[44px] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Optional Signature Sheet -->
        <div v-if="showSignatureSheet">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Signature Sheet (Optional)</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onSignatureSheetSelect"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />
            <p v-if="signatureSheet" class="text-xs text-gray-500 mt-1">{{ signatureSheet.name }}</p>
        </div>

        <!-- DSWD-specific fields -->
        <div v-if="showDswdFields" class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">DSWD Reference Number</label>
                <input v-model="dswdReference" type="text" placeholder="e.g., DSWD-2026-0001"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Approval Date</label>
                <input v-model="approvalDate" type="date"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>
        </div>

        <!-- Description -->
        <div v-if="showDescription">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes (Optional)</label>
            <textarea v-model="description" rows="2" placeholder="Add any notes about this upload..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
            </textarea>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600">Uploading...</span>
                <span class="font-semibold text-[#0c6d57]">{{ uploadProgress }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0c6d57] rounded-full transition-all duration-300"
                    :style="{ width: uploadProgress + '%' }"></div>
            </div>
        </div>

        <!-- Upload Button -->
        <button v-if="!uploading" @click="upload" :disabled="!isValidFile"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-white bg-[#0c6d57] rounded-xl hover:bg-[#0a5a48] transition-colors min-h-[48px] disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload Document
        </button>
    </div>
</div>
</template>
