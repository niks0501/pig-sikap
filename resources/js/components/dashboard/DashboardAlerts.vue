<script setup>
defineProps({
    alerts: { type: Array, required: true },
});

const iconMap = {
    warning: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
};

const typeStyles = {
    danger: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', iconBg: 'bg-red-100', iconText: 'text-red-600' },
    warning: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', iconBg: 'bg-amber-100', iconText: 'text-amber-600' },
    info: { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700', iconBg: 'bg-blue-100', iconText: 'text-blue-600' },
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Alerts &amp; Actionable Insights
            </h3>
            <span class="bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ alerts.length }} Active</span>
        </div>

        <div class="space-y-3">
            <div
                v-for="(alert, idx) in alerts"
                :key="idx"
                :class="[typeStyles[alert.type]?.bg || 'bg-gray-50', typeStyles[alert.type]?.border || 'border-gray-200', 'border rounded-xl p-4']"
            >
                <div class="flex items-start gap-3">
                    <div :class="[typeStyles[alert.type]?.iconBg || 'bg-gray-100', typeStyles[alert.type]?.iconText || 'text-gray-600', 'p-2 rounded-lg shrink-0']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconMap[alert.icon] || iconMap.warning" />
                        </svg>
                    </div>
                    <div>
                        <p :class="[typeStyles[alert.type]?.text || 'text-gray-800', 'text-sm font-semibold']">{{ alert.title }}</p>
                        <p class="text-xs text-gray-600 mt-0.5">{{ alert.message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
