<script setup>
const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    recentLogins: {
        type: Array,
        default: () => [],
    },
    recentActivityLogs: {
        type: Array,
        default: () => [],
    },
    usersRoute: {
        type: String,
        required: true,
    },
    rolesRoute: {
        type: String,
        required: true,
    },
    logsRoute: {
        type: String,
        required: true,
    },
});

const formatDateTime = (value) => {
    if (!value) {
        return 'No data yet';
    }

    return new Date(value).toLocaleString();
};

const summaryCards = [
    {
        label: 'Total Users',
        value: props.summary.total_users ?? 0,
    },
    {
        label: 'Active Users',
        value: props.summary.active_users ?? 0,
    },
    {
        label: 'Inactive Users',
        value: props.summary.inactive_users ?? 0,
    },
    {
        label: 'Roles Count',
        value: props.summary.roles_count ?? 0,
    },
];
</script>

<template>
    <div class="space-y-5">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in summaryCards"
                :key="card.label"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">{{ card.value }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Quick Actions</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a :href="usersRoute" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-center text-sm font-semibold text-[#0c6d57] transition-colors hover:bg-emerald-100">
                    Create User
                </a>
                <a :href="usersRoute" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                    View Users
                </a>
                <a :href="rolesRoute" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                    View Roles
                </a>
                <a :href="logsRoute" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                    View Activity Logs
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Recent Logins</h2>
                <ul class="mt-3 space-y-3">
                    <li
                        v-for="login in recentLogins"
                        :key="login.id"
                        class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2"
                    >
                        <p class="text-sm font-semibold text-slate-800">{{ login.name }}</p>
                        <p class="text-xs text-slate-600">{{ login.email }} • {{ login.role || 'No role' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(login.last_login_at) }}</p>
                    </li>
                    <li v-if="recentLogins.length === 0" class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                        No login activity yet.
                    </li>
                </ul>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-base font-semibold text-slate-900" style="font-family: 'Lexend', sans-serif;">Recent Activity Logs</h2>
                <ul class="mt-3 space-y-3">
                    <li
                        v-for="log in recentActivityLogs"
                        :key="log.id"
                        class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2"
                    >
                        <p class="text-sm font-semibold text-slate-800">{{ log.action }} • {{ log.module }}</p>
                        <p class="text-xs text-slate-600">{{ log.user }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ log.description }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</p>
                    </li>
                    <li v-if="recentActivityLogs.length === 0" class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                        No activity logs yet.
                    </li>
                </ul>
            </article>
        </section>
    </div>
</template>
