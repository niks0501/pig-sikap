<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    storeUrl: {
        type: String,
        required: true,
    },
    toggleStatusBaseUrl: {
        type: String,
        required: true,
    },
    resetPasswordBaseUrl: {
        type: String,
        required: true,
    },
});

const loading = ref(false);
const users = ref([]);
const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const filters = reactive({
    search: '',
    role: '',
    status: 'all',
});

const modalOpen = ref(false);
const editMode = ref(false);
const saving = ref(false);
const errors = ref({});
const feedback = ref('');
const temporaryPassword = ref('');

const form = reactive({
    id: null,
    name: '',
    email: '',
    password: '',
    role_id: '',
    is_active: true,
    generate_password: true,
});

const formattedRoles = computed(() => props.roles.map((role) => ({
    id: role.id,
    name: role.name,
    slug: role.slug,
})));

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.role_id = formattedRoles.value[0]?.id ?? '';
    form.is_active = true;
    form.generate_password = true;
};

const openCreate = () => {
    resetForm();
    editMode.value = false;
    errors.value = {};
    modalOpen.value = true;
};

const openEdit = (user) => {
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role_id = formattedRoles.value.find((role) => role.slug === user.role_slug)?.id ?? '';
    form.is_active = user.is_active;
    form.generate_password = false;
    editMode.value = true;
    errors.value = {};
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    errors.value = {};
};

const fetchUsers = async (page = 1) => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.fetchUrl, {
            params: {
                search: filters.search,
                role: filters.role,
                status: filters.status,
                page,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        users.value = response.data.data || [];
        meta.current_page = response.data.meta?.current_page ?? 1;
        meta.last_page = response.data.meta?.last_page ?? 1;
        meta.total = response.data.meta?.total ?? 0;
    } finally {
        loading.value = false;
    }
};

const submitUser = async () => {
    saving.value = true;
    errors.value = {};
    feedback.value = '';
    temporaryPassword.value = '';

    const payload = {
        name: form.name,
        email: form.email,
        role_id: form.role_id,
        is_active: form.is_active,
    };

    if (!editMode.value) {
        payload.password = form.generate_password ? null : form.password;
    }

    if (editMode.value && form.password) {
        payload.password = form.password;
    }

    try {
        if (editMode.value) {
            await window.axios.put(`${props.toggleStatusBaseUrl}/${form.id}`, payload, {
                headers: {
                    Accept: 'application/json',
                },
            });
        } else {
            const response = await window.axios.post(props.storeUrl, payload, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (response.data.temporary_password) {
                temporaryPassword.value = response.data.temporary_password;
            }
        }

        feedback.value = editMode.value ? 'User updated successfully.' : 'User created successfully.';
        closeModal();
        await fetchUsers(meta.current_page);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            feedback.value = 'Unable to save user. Please try again.';
        }
    } finally {
        saving.value = false;
    }
};

const toggleStatus = async (user) => {
    feedback.value = '';

    try {
        const response = await window.axios.patch(`${props.toggleStatusBaseUrl}/${user.id}/status`, {}, {
            headers: {
                Accept: 'application/json',
            },
        });

        feedback.value = response.data.message || 'Status updated.';
        await fetchUsers(meta.current_page);
    } catch (error) {
        feedback.value = error.response?.data?.message || 'Unable to update account status.';
    }
};

const resetPassword = async (user) => {
    const confirmed = window.confirm(`Reset password for ${user.email}?`);

    if (!confirmed) {
        return;
    }

    feedback.value = '';

    try {
        const response = await window.axios.patch(`${props.resetPasswordBaseUrl}/${user.id}/reset-password`, {}, {
            headers: {
                Accept: 'application/json',
            },
        });

        feedback.value = response.data.message || 'Password reset completed.';
        temporaryPassword.value = response.data.temporary_password || '';
    } catch (error) {
        feedback.value = error.response?.data?.message || 'Unable to reset password.';
    }
};

const goToPage = (page) => {
    if (page < 1 || page > meta.last_page) {
        return;
    }

    fetchUsers(page);
};

watch(
    () => [filters.search, filters.role, filters.status],
    () => {
        fetchUsers(1);
    }
);

onMounted(() => {
    resetForm();
    fetchUsers();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 md:w-3/4">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Search name or email"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                <select
                    v-model="filters.role"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="">All roles</option>
                    <option v-for="role in formattedRoles" :key="role.id" :value="role.slug">{{ role.name }}</option>
                </select>
                <select
                    v-model="filters.status"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                >
                    <option value="all">All status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <button
                type="button"
                class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#095342]"
                @click="openCreate"
            >
                Add User
            </button>
        </div>

        <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ feedback }}
        </div>

        <div v-if="temporaryPassword" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
            Temporary password: <span class="font-semibold">{{ temporaryPassword }}</span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="loading" class="p-6 text-sm text-slate-600">Loading users...</div>

            <template v-else>
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Last Login</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ user.role || 'No role' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                        {{ user.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}</td>
                                <td class="px-4 py-3 text-right text-xs">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="rounded-lg border border-slate-300 px-2 py-1 font-semibold text-slate-700 hover:bg-slate-100" @click="openEdit(user)">Edit</button>
                                        <button type="button" class="rounded-lg border border-slate-300 px-2 py-1 font-semibold text-slate-700 hover:bg-slate-100" @click="toggleStatus(user)">
                                            {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button type="button" class="rounded-lg border border-amber-300 px-2 py-1 font-semibold text-amber-700 hover:bg-amber-50" @click="resetPassword(user)">
                                            Reset Password
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No users found for the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    <article v-for="user in users" :key="`mobile-${user.id}`" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-sm font-semibold text-slate-900">{{ user.name }}</p>
                        <p class="text-xs text-slate-600">{{ user.email }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ user.role || 'No role' }}</p>
                        <p class="mt-1 text-xs" :class="user.is_active ? 'text-emerald-700' : 'text-rose-700'">
                            {{ user.is_active ? 'Active' : 'Inactive' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700" @click="openEdit(user)">Edit</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700" @click="toggleStatus(user)">
                                {{ user.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button type="button" class="rounded-lg border border-amber-300 px-2 py-1 text-xs font-semibold text-amber-700" @click="resetPassword(user)">Reset Password</button>
                        </div>
                    </article>

                    <p v-if="users.length === 0" class="py-4 text-center text-sm text-slate-500">No users found for the selected filters.</p>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-sm text-slate-600">Total users: {{ meta.total }}</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Previous
                </button>
                <span class="text-sm text-slate-600">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-8">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeModal"></div>
            <div class="relative z-10 w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <h2 class="text-lg font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">
                    {{ editMode ? 'Edit User' : 'Create User' }}
                </h2>

                <form class="mt-4 space-y-3" @submit.prevent="submitUser">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Full Name</label>
                        <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
                        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
                        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Role</label>
                        <select v-model="form.role_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
                            <option v-for="role in formattedRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <p v-if="errors.role_id" class="mt-1 text-xs text-rose-600">{{ errors.role_id[0] }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#0c6d57]">
                            Active account
                        </label>

                        <label v-if="!editMode" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">
                            <input v-model="form.generate_password" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#0c6d57]">
                            Generate temp password
                        </label>
                    </div>

                    <div v-if="(!form.generate_password || editMode)">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">
                            {{ editMode ? 'New Password (optional)' : 'Password' }}
                        </label>
                        <input v-model="form.password" type="password" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" :required="!editMode && !form.generate_password">
                        <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="closeModal">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="saving">
                            {{ saving ? 'Saving...' : 'Save User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
