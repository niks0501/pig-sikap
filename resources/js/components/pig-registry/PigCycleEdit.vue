<script setup>
import { computed, reactive } from 'vue';
import CycleAssignmentFields from './CycleAssignmentFields.vue';
import CycleLifecycleFields from './CycleLifecycleFields.vue';

const props = defineProps({
    cycle: {
        type: Object,
        required: true,
    },
    stages: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    caretakers: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
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
    statusMessage: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
});

const form = reactive({
    caretaker_user_id: props.oldInput.caretaker_user_id ?? String(props.cycle.caretaker_user_id ?? ''),
    cycle_number: props.oldInput.cycle_number ?? props.cycle.cycle_number ?? '',
    average_weight: props.oldInput.average_weight ?? props.cycle.average_weight ?? '',
    stage: props.oldInput.stage ?? props.cycle.stage,
    status: props.oldInput.status ?? props.cycle.status,
    notes: props.oldInput.notes ?? props.cycle.notes ?? '',
});

const firstError = computed(() => {
    if (props.errorMessage) {
        return props.errorMessage;
    }

    const firstField = Object.keys(props.errors)[0];

    return firstField ? props.errors[firstField][0] : '';
});

const countLabel = computed(() => Number(props.cycle.current_count || 0).toLocaleString());

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <div class="mx-auto max-w-300 space-y-5">
        <section class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0c6d57]">Cycle Maintenance</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-900">Edit Cycle {{ props.cycle.batch_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Update cycle metadata. Count changes are handled in adjustment flow.</p>
            </div>
            <a :href="props.routes.show" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Cycle
            </a>
        </section>

        <div v-if="props.statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
            Current count is <strong>{{ countLabel }}</strong>. To change this value, use <strong>Adjust Count</strong> on the cycle detail page.
        </div>

        <div v-if="firstError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800">
            {{ firstError }}
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="p-4 sm:p-6">
                <form :action="props.routes.update" method="POST" class="space-y-8">
                    <input type="hidden" name="_token" :value="props.csrfToken">
                    <input type="hidden" name="_method" value="PUT">

                    <div>
                        <h3 class="text-base font-bold text-gray-900">1. Locked Information</h3>
                        <p class="mt-1 text-xs text-gray-500">These values are fixed for identity and historical integrity.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Cycle Code</span>
                                <input type="text" :value="props.cycle.batch_code" disabled class="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm font-semibold text-gray-500">
                            </label>

                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Current Count</span>
                                <input type="text" :value="countLabel" disabled class="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm font-semibold text-gray-500">
                            </label>

                            <label>
                                <span class="mb-1 block text-sm font-semibold text-gray-700">Date of Purchase</span>
                                <input type="text" :value="formatDate(props.cycle.date_of_purchase)" disabled class="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm font-semibold text-gray-500">
                            </label>
                        </div>
                    </div>

                    <CycleAssignmentFields :form="form" :caretakers="props.caretakers" :errors="props.errors" />

                    <CycleLifecycleFields
                        :form="form"
                        :stages="props.stages"
                        :statuses="props.statuses"
                        :errors="props.errors"
                        notes-placeholder="Context or observations about this cycle..."
                    />

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <a :href="props.routes.show" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
