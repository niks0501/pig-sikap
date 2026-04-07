<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    updateUrl: {
        type: String,
        required: true,
    },
    redirectLabel: {
        type: String,
        default: 'Continue',
    },
});

const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const show = reactive({
    current: false,
    password: false,
    confirmation: false,
});

const errors = ref({});
const message = ref('');
const saving = ref(false);

const submit = async () => {
    errors.value = {};
    message.value = '';
    saving.value = true;

    try {
        const response = await window.axios.put(
            props.updateUrl,
            form,
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        message.value = response.data.message || 'Password updated successfully.';

        if (response.data.redirect) {
            window.location.href = response.data.redirect;
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            message.value = 'Unable to update password right now. Please try again.';
        }
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700" for="current_password">
                    Current Temporary Password
                </label>
                <div class="relative">
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        :type="show.current ? 'text' : 'password'"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-12 text-sm text-slate-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500"
                        @click="show.current = !show.current"
                    >
                        {{ show.current ? 'Hide' : 'Show' }}
                    </button>
                </div>
                <p v-if="errors.current_password" class="mt-1 text-xs text-rose-600">{{ errors.current_password[0] }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700" for="password">
                    New Password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="show.password ? 'text' : 'password'"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-12 text-sm text-slate-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        autocomplete="new-password"
                        required
                    >
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500"
                        @click="show.password = !show.password"
                    >
                        {{ show.password ? 'Hide' : 'Show' }}
                    </button>
                </div>
                <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700" for="password_confirmation">
                    Confirm New Password
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="show.confirmation ? 'text' : 'password'"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-12 text-sm text-slate-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        autocomplete="new-password"
                        required
                    >
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500"
                        @click="show.confirmation = !show.confirmation"
                    >
                        {{ show.confirmation ? 'Hide' : 'Show' }}
                    </button>
                </div>
                <p v-if="errors.password_confirmation" class="mt-1 text-xs text-rose-600">{{ errors.password_confirmation[0] }}</p>
            </div>

            <div v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ message }}
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-[#0c6d57] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#095342] disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="saving"
            >
                {{ saving ? 'Updating...' : redirectLabel }}
            </button>
        </form>
    </div>
</template>
