<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    batch: {
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
    breeders: {
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
    breeder_id: props.oldInput.breeder_id ?? String(props.batch.breeder_id ?? ''),
    caretaker_user_id: props.oldInput.caretaker_user_id ?? String(props.batch.caretaker_user_id ?? ''),
    cycle_number: props.oldInput.cycle_number ?? props.batch.cycle_number ?? '',
    average_weight: props.oldInput.average_weight ?? props.batch.average_weight ?? '',
    stage: props.oldInput.stage ?? props.batch.stage,
    status: props.oldInput.status ?? props.batch.status,
    notes: props.oldInput.notes ?? props.batch.notes ?? '',
});

const firstError = computed(() => {
    if (props.errorMessage) {
        return props.errorMessage;
    }

    const firstField = Object.keys(props.errors)[0];

    return firstField ? props.errors[firstField][0] : '';
});

const fieldError = (name) => props.errors[name]?.[0] ?? '';

const countLabel = computed(() => Number(props.batch.current_count || 0).toLocaleString());
</script>

<template>
    <div class="space-y-6">
        <section class="flex flex-col gap-3 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Edit Batch {{ props.batch.batch_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Update non-count details. Count changes must go through adjustment flow.</p>
            </div>
            <a :href="props.routes.show" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Batch
            </a>
        </section>

        <div v-if="props.statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ props.statusMessage }}
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Current count is <strong>{{ countLabel }}</strong>. To change this value, use <strong>Adjust Count</strong> on the batch detail page.
        </div>

        <div v-if="firstError" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            {{ firstError }}
        </div>

        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="p-6 sm:p-8">
                <form :action="props.routes.update" method="POST" class="space-y-7">
                    <input type="hidden" name="_token" :value="props.csrfToken">
                    <input type="hidden" name="_method" value="PUT">

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Batch Code</span>
                            <input type="text" :value="props.batch.batch_code" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-600">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Current Count</span>
                            <input type="text" :value="countLabel" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-600">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Breeder / Inahin</span>
                            <select v-model="form.breeder_id" name="breeder_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">No linked breeder</option>
                                <option v-for="breeder in props.breeders" :key="breeder.id" :value="String(breeder.id)">
                                    {{ breeder.breeder_code }} - {{ breeder.name_or_tag }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Caretaker</span>
                            <select v-model="form.caretaker_user_id" name="caretaker_user_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">Unassigned</option>
                                <option v-for="caretaker in props.caretakers" :key="caretaker.id" :value="String(caretaker.id)">
                                    {{ caretaker.name }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Cycle Number</span>
                            <input v-model="form.cycle_number" type="number" name="cycle_number" min="1" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Average Weight (kg)</span>
                            <input v-model="form.average_weight" type="number" name="average_weight" min="0" step="0.01" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Stage *</span>
                            <select v-model="form.stage" name="stage" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option v-for="stage in props.stages" :key="stage" :value="stage">{{ stage }}</option>
                            </select>
                            <span v-if="fieldError('stage')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('stage') }}</span>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Status *</span>
                            <select v-model="form.status" name="status" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option v-for="status in props.statuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                            <span v-if="fieldError('status')" class="mt-1 block text-xs font-medium text-rose-700">{{ fieldError('status') }}</span>
                        </label>

                        <label class="sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Notes</span>
                            <textarea v-model="form.notes" name="notes" rows="4" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"></textarea>
                        </label>
                    </div>

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
