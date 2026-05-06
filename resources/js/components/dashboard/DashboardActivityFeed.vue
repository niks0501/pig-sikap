<script setup>
defineProps({
    activities: { type: Array, default: () => [] },
});

const moduleColors = {
    'Pig Registry': '#0c6d57',
    'Cycle': '#0c6d57',
    'Health': '#f59e0b',
    'Expense': '#ef4444',
    'Sales': '#3b82f6',
    'Workflow': '#8b5cf6',
    'Resolution': '#8b5cf6',
    'Meeting': '#8b5cf6',
    'Membership': '#10b981',
    'Auth': '#64748b',
    'Admin': '#64748b',
};

function getColor(module) {
    return moduleColors[module] || '#64748b';
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-full flex flex-col">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Recent Activity
        </h3>

        <div v-if="activities.length === 0" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No recent activity
        </div>

        <div v-else class="flex-1 relative border-l border-gray-200 ml-3 space-y-5 pb-2">
            <div v-for="(item, idx) in activities" :key="idx" class="relative pl-6">
                <span
                    class="absolute -left-1.5 top-1 w-3 h-3 rounded-full ring-4 ring-white"
                    :style="{ backgroundColor: getColor(item.module) }"
                ></span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ item.action }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ item.description }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-400">{{ item.created_at }}</span>
                        <span class="text-xs px-1.5 py-0.5 rounded-full font-medium" :style="{ backgroundColor: getColor(item.module) + '18', color: getColor(item.module) }">{{ item.module }}</span>
                        <span class="text-xs text-gray-400">by {{ item.user }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
