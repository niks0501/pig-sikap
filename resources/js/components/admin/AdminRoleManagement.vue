<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    initialRoles: {
        type: Array,
        default: () => [],
    },
    fetchUrl: {
        type: String,
        required: true,
    },
});

const roles = ref(props.initialRoles);
const loading = ref(false);

const fetchRoles = async () => {
    loading.value = true;

    try {
        const response = await window.axios.get(props.fetchUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        roles.value = response.data.data || [];
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (!roles.value.length) {
        fetchRoles();
    }
});
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Available role definitions for account assignment.</p>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700" @click="fetchRoles">
                Refresh
            </button>
        </div>

        <div v-if="loading" class="text-sm text-slate-500">Loading roles...</div>

        <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="role in roles" :key="role.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-lg font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">{{ role.name }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ role.slug }}</p>
                <p class="mt-2 text-sm text-slate-700">{{ role.description || 'No description available.' }}</p>
                <div class="mt-3 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700">
                    Assigned users: {{ role.users_count ?? 0 }}
                </div>
            </article>

            <p v-if="roles.length === 0" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                No roles found.
            </p>
        </div>
    </div>
</template>
