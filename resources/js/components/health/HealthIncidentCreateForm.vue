<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    cycles: {
        type: Array,
        default: () => [],
    },
    incidentTypes: {
        type: Array,
        default: () => [],
    },
    pigSpecificIncidentTypes: {
        type: Array,
        default: () => [],
    },
    selectedCycleId: {
        type: Number,
        default: 0,
    },
    selectedPigId: {
        type: Number,
        default: 0,
    },
    prefilledAffectedCount: {
        type: Number,
        default: 0,
    },
    formMode: {
        type: String,
        default: 'general',
    },
    lockedIncidentType: {
        type: String,
        default: '',
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    eventKey: {
        type: String,
        required: true,
    },
    oldInput: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const isMortalityMode = computed(() => props.formMode === 'mortality');
const normalizedLockedIncidentType = computed(() => String(props.lockedIncidentType ?? '').trim());
const isIncidentTypeLocked = computed(() => normalizedLockedIncidentType.value !== '');

const form = ref({
    cycle_id: String(props.oldInput.cycle_id ?? props.selectedCycleId ?? ''),
    incident_type: String(props.oldInput.incident_type ?? normalizedLockedIncidentType.value),
    date_reported: String(props.oldInput.date_reported ?? ''),
    affected_count: String(props.oldInput.affected_count ?? Math.max(Number(props.prefilledAffectedCount ?? 0), 1)),
    pig_id: String(props.oldInput.pig_id ?? props.selectedPigId ?? ''),
    resolution_target: String(props.oldInput.resolution_target ?? ''),
    suspected_cause: String(props.oldInput.suspected_cause ?? ''),
    treatment_given: String(props.oldInput.treatment_given ?? ''),
    remarks: String(props.oldInput.remarks ?? ''),
});

const isSubmitting = ref(false);
const isConfirmDialogOpen = ref(false);
const mediaFileInput = ref(null);
const mediaPreviewUrl = ref('');
const mediaPreviewObjectUrl = ref('');
const mediaFileName = ref('');
const mediaPreviewKind = ref('');

const selectedCycle = computed(() => {
    const cycleId = Number(form.value.cycle_id || 0);

    if (!Number.isInteger(cycleId) || cycleId < 1) {
        return null;
    }

    return props.cycles.find((cycle) => Number(cycle.id) === cycleId) ?? null;
});

const selectedCyclePigs = computed(() => {
    if (!selectedCycle.value || !Array.isArray(selectedCycle.value.pigs)) {
        return [];
    }

    return selectedCycle.value.pigs;
});

const pigOptions = computed(() => {
    if (form.value.incident_type !== 'deceased') {
        return selectedCyclePigs.value;
    }

    return selectedCyclePigs.value.filter((pig) => !['Deceased', 'Sold'].includes(String(pig.status ?? '')));
});

const isPigSpecificIncident = computed(() => props.pigSpecificIncidentTypes.includes(form.value.incident_type));
const isDeceasedIncident = computed(() => form.value.incident_type === 'deceased');
const isResolutionEvent = computed(() => ['deceased', 'recovered'].includes(form.value.incident_type));
const requiresResolutionTarget = computed(() => form.value.incident_type === 'recovered');
const allowsResolutionTarget = computed(() => isResolutionEvent.value);
const selectedCycleHasPigProfiles = computed(() => Boolean(selectedCycle.value?.has_pig_profiles));
const selectedCyclePigCount = computed(() => Number(selectedCycle.value?.pig_count ?? selectedCyclePigs.value.length ?? 0));
const hasPigRecords = computed(() => selectedCyclePigCount.value > 0);
const selectedCycleActiveHealth = computed(() => selectedCycle.value?.active_health ?? {});

const showPigSelector = computed(() => isPigSpecificIncident.value && selectedCycleHasPigProfiles.value);
const requiresPigSelection = computed(() => showPigSelector.value && hasPigRecords.value);
const allowsTemporaryCycleLevelSubmission = computed(() => showPigSelector.value && !hasPigRecords.value);
const hasPigSelection = computed(() => String(form.value.pig_id).trim() !== '');
const forceSingleAffectedCount = computed(() => isPigSpecificIncident.value && hasPigSelection.value);
const selectedResolutionTarget = computed(() => String(form.value.resolution_target ?? '').trim());
const isMediaRequired = computed(() => isDeceasedIncident.value);
const selectedMediaPresent = computed(() => mediaFileName.value.trim() !== '');
const cancelRoute = computed(() => (isMortalityMode.value ? (props.routes.mortality || props.routes.index) : props.routes.index));

const unresolvedCap = computed(() => {
    const sickCap = Number(selectedCycleActiveHealth.value.currently_sick ?? 0);
    const isolatedCap = Number(selectedCycleActiveHealth.value.currently_isolated ?? 0);

    if (!isResolutionEvent.value) {
        return Number.POSITIVE_INFINITY;
    }

    if (selectedResolutionTarget.value === 'sick') {
        return sickCap;
    }

    if (selectedResolutionTarget.value === 'isolated') {
        return isolatedCap;
    }

    if (form.value.incident_type === 'deceased') {
        return Number.POSITIVE_INFINITY;
    }

    return 0;
});

const numericAffectedCount = computed(() => {
    const value = Number(form.value.affected_count ?? 0);

    return Number.isFinite(value) ? Math.max(Math.floor(value), 0) : 0;
});

const clientSideBlocked = computed(() => {
    if (requiresPigSelection.value && !hasPigSelection.value) {
        return true;
    }

    if (requiresResolutionTarget.value && selectedResolutionTarget.value === '') {
        return true;
    }

    if (isResolutionEvent.value && numericAffectedCount.value > unresolvedCap.value) {
        return true;
    }

    if (isMediaRequired.value && !selectedMediaPresent.value) {
        return true;
    }

    return false;
});

const clientSideBlockMessage = computed(() => {
    if (requiresPigSelection.value && !hasPigSelection.value) {
        return 'Select a pig profile for this incident type before submitting.';
    }

    if (requiresResolutionTarget.value && selectedResolutionTarget.value === '') {
        return 'Recovered events must include a resolution target (sick or isolated).';
    }

    if (isResolutionEvent.value && numericAffectedCount.value > unresolvedCap.value) {
        if (selectedResolutionTarget.value) {
            return `Affected count exceeds unresolved ${selectedResolutionTarget.value} cases.`;
        }

        return 'Affected count exceeds unresolved sick and isolated cases.';
    }

    if (isMediaRequired.value && !selectedMediaPresent.value) {
        return 'Upload mortality evidence (photo or video) before submitting.';
    }

    return '';
});

const dynamicWarningMessage = computed(() => {
    if (!isPigSpecificIncident.value) {
        return '';
    }

    if (form.value.incident_type === 'deceased') {
        return 'Deceased incidents immediately reduce cycle current count, sync pig status to Deceased, and require confirmed evidence.';
    }

    if (form.value.incident_type === 'isolated') {
        return 'Isolated incidents are health records only and do not reduce cycle current count.';
    }

    if (form.value.incident_type === 'recovered') {
        return 'Recovered incidents resolve one active condition bucket (sick or isolated) per submission.';
    }

    return '';
});

watch(
    () => [form.value.incident_type, form.value.pig_id],
    () => {
        if (forceSingleAffectedCount.value) {
            form.value.affected_count = '1';
        }
    },
    { immediate: true }
);

watch(
    () => normalizedLockedIncidentType.value,
    (lockedType) => {
        if (lockedType !== '') {
            form.value.incident_type = lockedType;
        }
    },
    { immediate: true }
);

watch(
    () => [form.value.cycle_id, form.value.incident_type],
    () => {
        const pigExistsInSelectedCycle = pigOptions.value.some((pig) => String(pig.id) === form.value.pig_id);

        if (!showPigSelector.value || !pigExistsInSelectedCycle) {
            form.value.pig_id = '';
        }

        if (!allowsResolutionTarget.value) {
            form.value.resolution_target = '';
        }
    }
);

const revokeMediaPreview = () => {
    if (mediaPreviewObjectUrl.value !== '') {
        URL.revokeObjectURL(mediaPreviewObjectUrl.value);
        mediaPreviewObjectUrl.value = '';
    }
};

const clearSelectedMedia = () => {
    revokeMediaPreview();
    mediaPreviewUrl.value = '';
    mediaFileName.value = '';
    mediaPreviewKind.value = '';

    if (mediaFileInput.value && 'value' in mediaFileInput.value) {
        mediaFileInput.value.value = '';
    }
};

const handleMediaSelection = (event) => {
    const input = event?.target;

    if (!input || !input.files || input.files.length < 1) {
        clearSelectedMedia();
        return;
    }

    const selectedFile = input.files[0];

    if (!selectedFile) {
        clearSelectedMedia();
        return;
    }

    revokeMediaPreview();
    mediaFileName.value = selectedFile.name;

    const mimeType = String(selectedFile.type ?? '');

    if (!mimeType.startsWith('image/') && !mimeType.startsWith('video/')) {
        mediaPreviewKind.value = '';
        mediaPreviewUrl.value = '';
        return;
    }

    mediaPreviewObjectUrl.value = URL.createObjectURL(selectedFile);
    mediaPreviewUrl.value = mediaPreviewObjectUrl.value;
    mediaPreviewKind.value = mimeType.startsWith('video/') ? 'video' : 'image';
};

onBeforeUnmount(() => {
    revokeMediaPreview();
});

const formatPurchaseDate = (value) => {
    if (!value) {
        return 'Unknown purchase date';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const fieldError = (field) => {
    const value = props.errors?.[field];

    return typeof value === 'string' ? value : '';
};

const submitLabel = computed(() => {
    if (isSubmitting.value) {
        return isMortalityMode.value ? 'Saving Mortality Record...' : 'Saving Incident...';
    }

    return isMortalityMode.value ? 'Save Mortality Record' : 'Save Incident';
});

const showConfirmDialog = computed(() => {
    // Show confirmation for mortality or deceased incidents
    return isMortalityMode.value || form.value.incident_type === 'deceased';
});

const confirmMessage = computed(() => {
    if (isMortalityMode.value || form.value.incident_type === 'deceased') {
        return `This will record a mortality incident for ${form.value.affected_count || 1} pig(s). The current count will be reduced immediately. This action cannot be undone. Are you sure you want to proceed?`;
    }
    return 'Are you sure you want to submit this health incident?';
});

const submitForm = (event) => {
    if (clientSideBlocked.value || isSubmitting.value) {
        event.preventDefault();
        return;
    }

    // Show confirmation dialog for significant incidents
    if (showConfirmDialog.value) {
        event.preventDefault();
        isConfirmDialogOpen.value = true;
        return;
    }

    isSubmitting.value = true;
};

const confirmSubmit = () => {
    isConfirmDialogOpen.value = false;
    isSubmitting.value = true;

    // Find and submit the form
    const formEl = document.querySelector('form[action*="health/incidents"], form[action*="health-module"]');
    if (formEl) {
        formEl.submit();
    }
};

const cancelConfirm = () => {
    isConfirmDialogOpen.value = false;
};
</script>

<template>
    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
        <div v-if="props.cycles.length === 0" class="space-y-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-5">
            <p class="text-sm font-semibold text-gray-800">No active cycles are available for incident recording.</p>
            <p class="text-sm text-gray-600">Create or reactivate a cycle first, then return to this form.</p>
            <a :href="cancelRoute" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                Back
            </a>
        </div>

        <form v-if="props.cycles.length > 0" :action="props.routes.store" method="POST" enctype="multipart/form-data" class="space-y-6" @submit="submitForm">
            <input type="hidden" name="_token" :value="props.csrfToken">
            <input type="hidden" name="event_key" :value="props.eventKey">
            <input type="hidden" name="source_channel" value="health_module">
            <input type="hidden" name="form_mode" :value="props.formMode || 'general'">
            <input v-if="isIncidentTypeLocked" type="hidden" name="incident_type" :value="form.incident_type">

            <div class="sr-only" aria-live="polite">
                {{ dynamicWarningMessage }}
                <template v-if="allowsTemporaryCycleLevelSubmission">
                    This cycle has pig profiles enabled but no pig records yet. You can submit at cycle level for now.
                </template>
            </div>

            <p v-if="clientSideBlockMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">
                {{ clientSideBlockMessage }}
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Cycle *</span>
                    <select v-model="form.cycle_id" name="cycle_id" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:ring-offset-1">
                        <option value="" disabled>Select cycle...</option>
                        <option v-for="cycle in props.cycles" :key="cycle.id" :value="String(cycle.id)">
                            {{ cycle.batch_code }} • Current {{ Number(cycle.current_count || 0).toLocaleString() }} pigs • Purchased {{ formatPurchaseDate(cycle.date_of_purchase) }}
                        </option>
                    </select>
                    <p v-if="fieldError('cycle_id')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('cycle_id') }}</p>
                </label>

                <label v-if="!isIncidentTypeLocked">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Incident Type *</span>
                    <select v-model="form.incident_type" name="incident_type" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="" disabled>Select type...</option>
                        <option v-for="incidentType in props.incidentTypes" :key="incidentType" :value="incidentType">
                            {{ incidentType.charAt(0).toUpperCase() + incidentType.slice(1) }}
                        </option>
                    </select>
                    <p v-if="fieldError('incident_type')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('incident_type') }}</p>
                </label>

                <div v-else class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <span class="mb-1 block text-sm font-bold text-rose-900">Incident Type</span>
                    <p class="text-sm font-semibold text-rose-800">Deceased (locked for mortality workflow)</p>
                </div>

                <label>
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Date Reported *</span>
                    <input v-model="form.date_reported" type="date" name="date_reported" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    <p v-if="fieldError('date_reported')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('date_reported') }}</p>
                </label>

                <label v-if="allowsResolutionTarget">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">
                        Resolution Target
                        <template v-if="requiresResolutionTarget">*</template>
                    </span>
                    <select
                        v-model="form.resolution_target"
                        name="resolution_target"
                        :required="requiresResolutionTarget"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                        <option value="" :disabled="requiresResolutionTarget">Select target...</option>
                        <option value="sick">Sick</option>
                        <option value="isolated">Isolated</option>
                    </select>
                    <p v-if="fieldError('resolution_target')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('resolution_target') }}</p>
                </label>

                <div v-if="allowsResolutionTarget" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-3 text-xs font-semibold text-blue-900 sm:col-span-2">
                    <p>Open Sick Cases: {{ Number(selectedCycleActiveHealth.currently_sick || 0).toLocaleString() }}</p>
                    <p class="mt-1">Open Isolated Cases: {{ Number(selectedCycleActiveHealth.currently_isolated || 0).toLocaleString() }}</p>
                    <p class="mt-1">Current Resolution Cap: {{ Number.isFinite(unresolvedCap) ? Number(unresolvedCap || 0).toLocaleString() : 'N/A' }}</p>
                </div>

                <label v-if="showPigSelector" class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">
                        Pig Profile
                        <template v-if="requiresPigSelection">*</template>
                        <template v-else>(optional)</template>
                    </span>
                    <select v-model="form.pig_id" name="pig_id" :required="requiresPigSelection" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="" :disabled="requiresPigSelection">{{ requiresPigSelection ? 'Select pig profile...' : 'No specific pig selected' }}</option>
                        <option v-for="pig in pigOptions" :key="pig.id" :value="String(pig.id)">
                            Pig #{{ pig.pig_no }} • {{ pig.status }}
                        </option>
                    </select>
                    <p v-if="allowsTemporaryCycleLevelSubmission" class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                        Pig profiles are enabled for this cycle, but no pig records exist yet. You can submit this incident at cycle level for now. Please complete pig profiles soon.
                    </p>
                    <p v-if="fieldError('pig_id')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('pig_id') }}</p>
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Affected Count *</span>
                    <input
                        v-model="form.affected_count"
                        type="number"
                        name="affected_count"
                        min="1"
                        :readonly="forceSingleAffectedCount"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        :class="forceSingleAffectedCount ? 'cursor-not-allowed bg-gray-100 text-gray-600' : ''"
                    >
                    <p v-if="forceSingleAffectedCount" class="mt-1.5 text-xs font-medium text-gray-600">
                        Pig-linked isolated/deceased/recovered incidents are fixed to 1 affected pig.
                    </p>
                    <p v-if="fieldError('affected_count')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('affected_count') }}</p>
                </label>

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">
                        Incident Evidence
                        <template v-if="isMediaRequired">*</template>
                        <template v-else>(optional)</template>
                    </span>
                    <input
                        ref="mediaFileInput"
                        type="file"
                        name="media"
                        :required="isMediaRequired"
                        accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/avi"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0c6d57]/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#0c6d57] hover:file:bg-[#0c6d57]/20 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        @change="handleMediaSelection"
                    >
                    <p class="mt-1.5 text-xs text-gray-500">Accepted formats: JPG, PNG, WEBP, MP4, MOV, AVI. Max file size: 25MB.</p>
                    <p v-if="fieldError('media')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('media') }}</p>
                    <p v-else-if="fieldError('media_path')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('media_path') }}</p>

                    <div v-if="mediaPreviewUrl" class="mt-3 rounded-2xl border border-gray-200 bg-gray-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-gray-700">Preview: {{ mediaFileName }}</p>
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100" @click="clearSelectedMedia">
                                Remove
                            </button>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-2">
                            <img v-if="mediaPreviewKind === 'image'" :src="mediaPreviewUrl" alt="Incident media preview" class="h-64 w-full rounded-lg object-contain">
                            <video v-else controls class="h-64 w-full rounded-lg bg-black object-contain">
                                <source :src="mediaPreviewUrl">
                                Your browser does not support this video preview.
                            </video>
                        </div>
                    </div>
                </label>

                <div v-if="isPigSpecificIncident" class="sm:col-span-2">
                    <p v-if="form.incident_type === 'deceased'" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">
                        Deceased incidents immediately deduct cycle current count and should only be recorded when confirmed. You can optionally specify which active bucket is being closed.
                    </p>
                    <p v-else-if="form.incident_type === 'isolated'" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                        Isolated incidents do not deduct cycle current count. This records health status and monitoring only.
                    </p>
                    <p v-else-if="form.incident_type === 'recovered'" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">
                        Recovered incidents close one active bucket per event and do not deduct cycle current count.
                    </p>
                </div>

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">
                        Suspected Cause
                        <template v-if="isDeceasedIncident">*</template>
                    </span>
                    <textarea v-model="form.suspected_cause" name="suspected_cause" rows="2" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                    <p v-if="fieldError('suspected_cause')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('suspected_cause') }}</p>
                </label>

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Treatment Given</span>
                    <textarea v-model="form.treatment_given" name="treatment_given" rows="2" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                    <p v-if="fieldError('treatment_given')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('treatment_given') }}</p>
                </label>

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-bold text-gray-700">Remarks</span>
                    <textarea v-model="form.remarks" name="remarks" rows="3" placeholder="Physical back markings, isolation notes, observations." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                    <p v-if="fieldError('remarks')" class="mt-1.5 text-xs font-semibold text-rose-700">{{ fieldError('remarks') }}</p>
                </label>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                <button type="submit" :disabled="isSubmitting || clientSideBlocked" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                    {{ submitLabel }}
                </button>
                <a :href="cancelRoute" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                    Cancel
                </a>
            </div>
        </form>

        <TransitionRoot as="template" :show="isConfirmDialogOpen">
            <Dialog as="div" class="relative z-50" @close="cancelConfirm">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                                <DialogTitle class="text-lg font-bold text-gray-900">Confirm Submission</DialogTitle>
                                <p class="mt-2 text-sm text-gray-500">
                                    {{ confirmMessage }}
                                </p>

                                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-5 w-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <p class="text-sm text-amber-800">This action directly impacts cycle statistics and cannot be reversed.</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" @click="cancelConfirm">
                                        Cancel
                                    </button>
                                    <button type="button" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700" @click="confirmSubmit">
                                        Confirm & Save
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </section>
</template>




