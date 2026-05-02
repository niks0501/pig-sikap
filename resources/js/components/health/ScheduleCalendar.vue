<script setup>
import { computed, ref } from 'vue';
import StatusBadge from '../common/StatusBadge.vue';

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
});

const today = computed(() => new Date().toISOString().slice(0, 10));
const viewingMonth = ref(new Date().getFullYear());
const viewingYear = ref(new Date().getMonth());

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const monthLabel = computed(() => {
    return `${monthNames[viewingYear.value]} ${viewingMonth.value}`;
});

const firstDayOfMonth = computed(() => {
    return new Date(viewingMonth.value, viewingYear.value, 1);
});

const daysInMonth = computed(() => {
    return new Date(viewingMonth.value, viewingYear.value + 1, 0).getDate();
});

const startingDayOfWeek = computed(() => {
    return firstDayOfMonth.value.getDay();
});

const calendarDays = computed(() => {
    const days = [];
    const startDay = startingDayOfWeek.value;
    const totalDays = daysInMonth.value;

    for (let i = 0; i < startDay; i++) {
        days.push({ day: null, dateStr: '' });
    }

    for (let d = 1; d <= totalDays; d++) {
        const date = new Date(viewingMonth.value, viewingYear.value, d);
        const dateStr = date.toISOString().slice(0, 10);
        days.push({ day: d, dateStr });
    }

    return days;
});

const dateNow = computed(() => {
    return new Date();
});

const groupedByDate = computed(() => {
    return props.tasks.reduce((groups, task) => {
        const key = task.planned_start_date || task.actual_date || '';
        if (key === '') return groups;
        const dateStr = typeof key === 'string' ? key.slice(0, 10) : key;
        groups[dateStr] = groups[dateStr] || [];
        groups[dateStr].push(task);
        return groups;
    }, {});
});

const selectedDate = ref('');
const selectedTasks = computed(() => {
    if (selectedDate.value === '') return [];
    return groupedByDate.value[selectedDate.value] || [];
});

const selectDate = (dateStr) => {
    selectedDate.value = dateStr;
};

const goToPrevMonth = () => {
    if (viewingYear.value === 0) {
        viewingYear.value = 11;
        viewingMonth.value -= 1;
    } else {
        viewingYear.value -= 1;
    }
};

const goToNextMonth = () => {
    if (viewingYear.value === 11) {
        viewingYear.value = 0;
        viewingMonth.value += 1;
    } else {
        viewingYear.value += 1;
    }
};

const formatMonthDate = (dateStr) => {
    if (!dateStr) return '';

    return new Date(dateStr + 'T00:00:00').toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        weekday: 'short',
    });
};

const dateLabel = (d) => {
    if (!d.dateStr) return '';
    if (d.dateStr === today.value) return 'Today';
    if (d.dateStr < today.value) return 'Past';
    return '';
};

const isToday = (dateStr) => dateStr === today.value;
const isPast = (dateStr) => dateStr !== '' && dateStr < today.value;
const isFuture = (dateStr) => dateStr !== '' && dateStr > today.value;

const taskCountForDay = (dateStr) => {
    return (groupedByDate.value[dateStr] || []).length;
};

const overdueCountForDay = (dateStr) => {
    if (!isPast(dateStr)) return 0;
    return (groupedByDate.value[dateStr] || []).filter(
        (t) => !['completed', 'skipped', 'not_applicable'].includes(t.status)
    ).length;
};
</script>

<template>
    <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-center justify-between gap-3 mb-4">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 min-h-[44px]"
                @click="goToPrevMonth"
            >
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Prev
            </button>
            <h3 class="text-lg font-bold text-gray-900">{{ monthLabel }}</h3>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 min-h-[44px]"
                @click="goToNextMonth"
            >
                Next
                <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day"
                class="py-1 text-center text-xs font-bold uppercase tracking-wide text-gray-500">
                {{ day }}
            </div>

            <button
                v-for="(d, idx) in calendarDays"
                :key="idx"
                type="button"
                :disabled="!d.dateStr"
                :class="[
                    'relative rounded-xl border p-2 text-left min-h-[64px] transition-colors',
                    !d.dateStr ? 'border-transparent bg-transparent cursor-default' : '',
                    d.dateStr === selectedDate
                        ? 'border-[#0c6d57] bg-[#0c6d57]/10 ring-1 ring-[#0c6d57]'
                        : d.dateStr && isToday(d.dateStr)
                            ? 'border-[#0c6d57]/40 bg-[#0c6d57]/5'
                            : d.dateStr
                                ? 'border-gray-100 bg-gray-50 hover:bg-gray-100'
                                : '',
                ]"
                @click="d.dateStr && selectDate(d.dateStr)"
            >
                <template v-if="d.dateStr">
                    <span
                        :class="[
                            'text-xs font-bold',
                            isToday(d.dateStr)
                                ? 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#0c6d57] text-white'
                                : isPast(d.dateStr)
                                    ? 'text-gray-400'
                                    : 'text-gray-900',
                        ]"
                    >
                        {{ d.day }}
                    </span>
                    <span
                        v-if="taskCountForDay(d.dateStr) > 0"
                        class="absolute bottom-1.5 right-2 flex items-center gap-1"
                    >
                        <span
                            v-if="overdueCountForDay(d.dateStr) > 0"
                            class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-100 px-1.5 text-[10px] font-bold text-rose-700"
                        >
                            {{ overdueCountForDay(d.dateStr) }}
                        </span>
                        <span
                            class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#0c6d57]/10 px-1.5 text-[10px] font-bold text-[#0c6d57]"
                        >
                            {{ taskCountForDay(d.dateStr) }}
                        </span>
                    </span>
                </template>
            </button>
        </div>

        <div v-if="selectedDate" class="mt-5 space-y-2 rounded-2xl border border-gray-100 bg-gray-50 p-4">
            <div class="flex items-center justify-between">
                <p class="text-sm font-bold text-gray-900">{{ formatMonthDate(selectedDate) }}</p>
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-500 hover:text-gray-700"
                    @click="selectedDate = ''"
                >
                    Close
                </button>
            </div>

            <div v-if="selectedTasks.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white px-3 py-4 text-center text-sm text-gray-500">
                No scheduled tasks for this date.
            </div>

            <article
                v-for="task in selectedTasks"
                :key="task.id"
                class="rounded-xl border border-gray-100 bg-white p-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ task.task_name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ task.cycle?.batch_code || 'No cycle' }}</p>
                    </div>
                    <StatusBadge
                        :status="task.status === 'completed' || ['skipped', 'not_applicable'].includes(task.status) ? 'success' : 'pending'"
                        size="sm"
                    >
                        {{ task.formatted_status || task.status || 'Pending' }}
                    </StatusBadge>
                </div>
            </article>
        </div>

        <p v-if="calendarDays.every(d => !d.dateStr || taskCountForDay(d.dateStr) === 0)" class="mt-4 rounded-xl border border-dashed border-gray-300 px-3 py-4 text-center text-sm text-gray-500">
            No scheduled tasks found for this month.
        </p>
    </section>
</template>