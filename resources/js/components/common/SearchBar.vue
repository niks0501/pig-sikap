<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search...',
    },
    recentSearches: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Array,
        default: () => [],
    },
    showFilters: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'search', 'filter', 'clear']);

const isOpen = ref(false);
const searchRef = ref(null);
const dropdownRef = ref(null);

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2.5 text-sm',
        lg: 'px-5 py-3 text-base',
    };
    return sizes[props.size] || sizes.md;
});

const hasRecentSearches = computed(() => props.recentSearches.length > 0);
const hasFilters = computed(() => props.filters.length > 0);

const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
    isOpen.value = true;
};

const handleSearch = () => {
    emit('search', props.modelValue);
    isOpen.value = false;
};

const handleClear = () => {
    emit('update:modelValue', '');
    emit('clear');
    searchRef.value?.focus();
};

const handleFilterSelect = (filter) => {
    emit('filter', filter);
    isOpen.value = false;
};

const handleRecentSearchClick = (search) => {
    emit('update:modelValue', search);
    emit('search', search);
    isOpen.value = false;
};

const handleFocus = () => {
    if (!props.disabled) {
        isOpen.value = true;
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target) && searchRef.value && !searchRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

const handleKeyDown = (event) => {
    if (event.key === 'Enter') {
        handleSearch();
    } else if (event.key === 'Escape') {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative">
        <div class="relative">
            <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>

            <input
                ref="searchRef"
                :value="modelValue"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="[
                    'w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 font-medium focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 transition-all',
                    sizeClasses,
                    disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '',
                ]"
                @input="handleInput"
                @focus="handleFocus"
                @keydown="handleKeyDown"
            >

            <button
                v-if="modelValue"
                type="button"
                :disabled="disabled"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-50"
                @click="handleClear"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen && (hasRecentSearches || hasFilters)"
                ref="dropdownRef"
                class="absolute z-50 mt-2 w-full rounded-xl border border-gray-200 bg-white py-2 shadow-lg"
            >
                <div v-if="hasRecentSearches" class="px-3 py-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Recent Searches
                    </p>
                    <div class="mt-2 space-y-1">
                        <button
                            v-for="(search, index) in recentSearches.slice(0, 5)"
                            :key="index"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-700 transition-colors hover:bg-gray-50"
                            @click="handleRecentSearchClick(search)"
                        >
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span>{{ search }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="hasFilters && showFilters" class="border-t border-gray-100 px-3 py-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Quick Filters
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="(filter, index) in filters"
                            :key="index"
                            type="button"
                            class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:border-[#0c6d57] hover:bg-[#0c6d57]/5 hover:text-[#0c6d57]"
                            @click="handleFilterSelect(filter)"
                        >
                            {{ filter.label }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
