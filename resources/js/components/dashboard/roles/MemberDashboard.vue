<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    userName: { type: String, default: 'User' },
    roleName: { type: String, default: 'User' },
    dataUrl: { type: String, required: true },
    routes: { type: Object, default: () => ({}) },
});

const loading = ref(true);
const error = ref(null);
const data = ref(null);

async function fetchData() {
    try {
        loading.value = true;
        error.value = null;
        const res = await fetch(props.dataUrl);
        if (!res.ok) throw new Error('Failed to load dashboard data');
        data.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 space-y-6">
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-gray-400 text-sm">Loading your workspace...</div>
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center">
            <p class="text-red-600 font-medium">Could not load dashboard</p>
            <p class="text-sm text-red-500 mt-1">{{ error }}</p>
            <button @click="fetchData" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">Retry</button>
        </div>

        <template v-else-if="data">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Good day, {{ data.user_name || props.userName }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-medium text-gray-600">
                            {{ props.roleName }}
                        </span>
                        <span class="text-xs text-gray-400">Member Workspace</span>
                    </div>
                </div>
                <span v-if="data.last_updated" class="text-xs text-gray-400 shrink-0">Updated {{ new Date(data.last_updated).toLocaleTimeString() }}</span>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0c6d57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.active_cycles ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Active Cycles</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.total_pigs ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Total Pigs</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ data.kpis?.total_members ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Active Members</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Association Info -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">About Your Association</h2>
                <p class="text-sm text-gray-600 leading-relaxed">
                    You are a member of the <strong>Elite Visionaries of Humayingan SLP Association</strong>.
                    Pig-Sikap helps the association track pig-raising cycles, monitor health, record sales and expenses,
                    and compute profit-sharing distributions.
                </p>
            </div>

            <!-- How to Join Section -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <section class="rounded-t-2xl bg-[#0c6d57] px-5 py-6 text-white sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-100">Membership Guide</p>
                    <h2 class="mt-1 text-xl font-black sm:text-2xl">How to Join Elite Visionaries Association</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-emerald-50">
                        Simple Phase 1.5 guide only. This page does not upload sensitive documents or automate DSWD approval.
                    </p>
                </section>

                <div class="p-5 sm:p-6 space-y-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0c6d57]/10 text-xs font-black text-[#0c6d57]">1</span>
                            <h3 class="mt-3 text-base font-bold text-gray-900">Coordinate With Officers</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                Talk to the current officers of Elite Visionaries Association and ask for the latest meeting schedule, requirements, and profiling instructions.
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0c6d57]/10 text-xs font-black text-[#0c6d57]">2</span>
                            <h3 class="mt-3 text-base font-bold text-gray-900">Prepare Requirements</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                Prepare paper copies only. Do not upload IDs or certificates here because this page is informational.
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0c6d57]/10 text-xs font-black text-[#0c6d57]">3</span>
                            <h3 class="mt-3 text-base font-bold text-gray-900">DSWD SLP Profiling</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                The SLP Focal Person checks if the applicant qualifies under program criteria such as Listahanan or 4Ps where applicable.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#0c6d57]">Checklist</p>
                                <h3 class="mt-1 text-lg font-bold text-gray-900">Membership File Requirements</h3>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 w-fit">Paper documents only</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm font-semibold text-gray-700">
                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                <span>Photocopy of Valid ID</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm font-semibold text-gray-700">
                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                <span>Barangay Certificate of Residency</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm font-semibold text-gray-700 sm:col-span-2">
                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]">
                                <span>Barangay Certificate of Indigency, if required for DSWD profiling</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <h3 class="text-sm font-bold text-amber-900">Privacy Reminder</h3>
                        <p class="mt-1 text-sm leading-6 text-amber-800">
                            Keep valid IDs and barangay certificates in the official paper membership file. Only add digital document upload later if the system has a secure approved pattern for sensitive files.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact Officers -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Need Help?</h2>
                <p class="text-sm text-gray-500">
                    For questions about your batch, profit sharing, or association matters,
                    please contact your association officers. The President, Secretary, and Treasurer
                    have full access to records and can assist you.
                </p>
            </div>
        </template>
    </div>
</template>
