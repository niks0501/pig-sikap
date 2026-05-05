<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    fetchUrl: { type: String, required: true },
});

const logs = ref([]);
const loading = ref(false);

onMounted(async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(props.fetchUrl, {
            params: { per_page: 10 },
            headers: { Accept: 'application/json' },
        });
        logs.value = response.data.data || [];
    } finally {
        loading.value = false;
    }
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

const getActionIcon = (action) => {
    const act = (action || '').toLowerCase();
    if (act.includes('created') || act.includes('add') || act.includes('registered') || act.includes('stored')) return 'plus';
    if (act.includes('update') || act.includes('edit') || act.includes('changed')) return 'edit';
    if (act.includes('delete') || act.includes('remove') || act.includes('destroy')) return 'delete';
    if (act.includes('approved') || act.includes('verify') || act.includes('finalize')) return 'check';
    return 'info';
};
</script>

<template>
    <div>
        <div v-if="loading" class="text-sm text-gray-500 py-4 text-center">Loading audit history...</div>

        <div v-else-if="logs.length === 0" class="text-sm text-gray-400 py-6 text-center bg-gray-50 rounded-xl border border-gray-100">
            No audit activity recorded yet.
        </div>

        <ul v-else class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
            <li
                v-for="log in logs"
                :key="log.id"
                class="px-4 py-3 bg-white hover:bg-gray-50 transition-colors"
            >
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="mt-0.5 shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
                        :class="{
                            'bg-emerald-50 text-[#0c6d57]': getActionIcon(log.action) === 'plus',
                            'bg-amber-50 text-amber-600': getActionIcon(log.action) === 'edit',
                            'bg-red-50 text-red-500': getActionIcon(log.action) === 'delete',
                            'bg-blue-50 text-blue-600': getActionIcon(log.action) === 'check',
                            'bg-gray-50 text-gray-500': getActionIcon(log.action) === 'info',
                        }"
                    >
                        <svg v-if="getActionIcon(log.action) === 'plus'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        <svg v-else-if="getActionIcon(log.action) === 'edit'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        <svg v-else-if="getActionIcon(log.action) === 'delete'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <svg v-else-if="getActionIcon(log.action) === 'check'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline gap-2">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ log.action }}</p>
                            <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ formatDate(log.created_at) }}</span>
                        </div>
                        <p class="text-sm text-gray-500 truncate mt-0.5">{{ log.description }}</p>
                        <p class="text-xs text-[#0c6d57] font-medium mt-0.5">{{ log.user }}</p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
