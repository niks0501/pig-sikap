<script setup>
const props = defineProps({
    preparedBy: { type: String, default: 'Prepared By' },
    notedBy: { type: String, default: 'Noted By' },
});
</script>

<template>
    <div class="grid gap-8 border-t pt-6 text-center sm:grid-cols-2">
        <div>
            <div class="mx-auto mb-2 h-10 w-full max-w-[220px] border-b-2 border-gray-700"></div>
            <p class="text-sm font-semibold text-gray-900">{{ preparedBy }}</p>
            <p class="text-xs uppercase tracking-widest text-gray-500">Prepared By</p>
        </div>
        <div>
            <div class="mx-auto mb-2 h-10 w-full max-w-[220px] border-b-2 border-gray-700"></div>
            <p class="text-sm font-semibold text-gray-900">{{ notedBy }}</p>
            <p class="text-xs uppercase tracking-widest text-gray-500">Noted By</p>
        </div>
    </div>
</template>
