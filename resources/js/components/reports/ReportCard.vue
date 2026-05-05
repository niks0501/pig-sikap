<script setup>
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    icon: { type: String, default: 'report' },
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' },
    lockHint: { type: String, default: '' },
});

const variants = {
    primary: 'border-[#0c6d57]/15 hover:border-[#0c6d57]/40',
    secondary: 'border-gray-200 hover:border-gray-300',
    danger: 'border-red-200 hover:border-red-300',
    locked: 'border-gray-200 opacity-70',
};

const iconStyles = {
    primary: 'bg-[#0c6d57]/10 text-[#0c6d57]',
    secondary: 'bg-gray-100 text-gray-700',
    danger: 'bg-red-100 text-red-600',
    locked: 'bg-gray-100 text-gray-400',
};

const iconPath = (name) => {
    switch (name) {
        case 'inventory':
            return 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10';
        case 'health':
            return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
        case 'mortality':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
        case 'expense':
            return 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z';
        case 'sales':
            return 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z';
        case 'profitability':
            return 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'monthly':
            return 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
        case 'quarterly':
            return 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
        default:
            return 'M13 10V3L4 14h7v7l9-11h-7z';
    }
};
</script>

<template>
    <div class="flex h-full flex-col rounded-2xl border bg-white transition" :class="variants[variant] || variants.primary">
        <div class="flex-1 p-5">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl" :class="iconStyles[variant] || iconStyles.primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath(icon)"></path>
                </svg>
            </div>
            <h4 class="text-base font-bold text-gray-900">{{ title }}</h4>
            <p class="mt-1 text-xs text-gray-500">{{ description }}</p>
            <p v-if="variant === 'locked' && lockHint" class="mt-3 text-xs font-semibold text-gray-400">{{ lockHint }}</p>
        </div>
        <div class="border-t px-4 py-3">
            <a
                v-if="href"
                :href="href"
                class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-gray-50 px-4 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10"
            >
                Open Report
            </a>
            <button
                v-else
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-400"
                disabled
            >
                Locked
            </button>
        </div>
    </div>
</template>
