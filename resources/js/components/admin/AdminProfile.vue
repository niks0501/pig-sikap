<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    updateProfileUrl: {
        type: String,
        required: true,
    },
    updatePasswordUrl: {
        type: String,
        required: true,
    },
});

const profileForm = reactive({
    name: props.user.name || '',
    email: props.user.email || '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const profileErrors = ref({});
const passwordErrors = ref({});
const profileMessage = ref('');
const passwordMessage = ref('');
const profileSaving = ref(false);
const passwordSaving = ref(false);

const updateProfile = async () => {
    profileSaving.value = true;
    profileMessage.value = '';
    profileErrors.value = {};

    try {
        const response = await window.axios.patch(props.updateProfileUrl, profileForm, {
            headers: {
                Accept: 'application/json',
            },
        });

        profileMessage.value = response.data.message || 'Profile updated successfully.';
    } catch (error) {
        if (error.response?.status === 422) {
            profileErrors.value = error.response.data.errors || {};
        } else {
            profileMessage.value = 'Unable to update profile right now.';
        }
    } finally {
        profileSaving.value = false;
    }
};

const updatePassword = async () => {
    passwordSaving.value = true;
    passwordMessage.value = '';
    passwordErrors.value = {};

    try {
        const response = await window.axios.put(props.updatePasswordUrl, passwordForm, {
            headers: {
                Accept: 'application/json',
            },
        });

        passwordMessage.value = response.data.message || 'Password changed successfully.';
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (error) {
        if (error.response?.status === 422) {
            passwordErrors.value = error.response.data.errors || {};
            if (error.response.data.message && !Object.keys(passwordErrors.value).length) {
                passwordMessage.value = error.response.data.message;
            }
        } else {
            passwordMessage.value = 'Unable to update password right now.';
        }
    } finally {
        passwordSaving.value = false;
    }
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Profile Details</h2>
            <form class="mt-4 space-y-3" @submit.prevent="updateProfile">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Full Name</label>
                    <input
                        v-model="profileForm.name"
                        type="text"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        required
                    >
                    <p v-if="profileErrors.name" class="mt-1 text-xs text-rose-600">{{ profileErrors.name[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Email Address</label>
                    <input
                        v-model="profileForm.email"
                        type="email"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        required
                    >
                    <p v-if="profileErrors.email" class="mt-1 text-xs text-rose-600">{{ profileErrors.email[0] }}</p>
                </div>

                <div v-if="profileMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                    {{ profileMessage }}
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#095342] disabled:opacity-60"
                    :disabled="profileSaving"
                >
                    {{ profileSaving ? 'Saving...' : 'Update Profile' }}
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Change Password</h2>
            <form class="mt-4 space-y-3" @submit.prevent="updatePassword">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Current Password</label>
                    <input
                        v-model="passwordForm.current_password"
                        type="password"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        required
                    >
                    <p v-if="passwordErrors.current_password" class="mt-1 text-xs text-rose-600">{{ passwordErrors.current_password[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">New Password</label>
                    <input
                        v-model="passwordForm.password"
                        type="password"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        required
                    >
                    <p v-if="passwordErrors.password" class="mt-1 text-xs text-rose-600">{{ passwordErrors.password[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Confirm New Password</label>
                    <input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        required
                    >
                    <p v-if="passwordErrors.password_confirmation" class="mt-1 text-xs text-rose-600">{{ passwordErrors.password_confirmation[0] }}</p>
                </div>

                <div v-if="passwordMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                    {{ passwordMessage }}
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#095342] disabled:opacity-60"
                    :disabled="passwordSaving"
                >
                    {{ passwordSaving ? 'Saving...' : 'Change Password' }}
                </button>
            </form>
        </section>
    </div>
</template>
