<script setup>
import { ref, computed } from 'vue';
import { 
    Dialog, 
    DialogPanel, 
    DialogTitle, 
    TransitionRoot, 
    TransitionChild 
} from '@headlessui/vue';

// Mock data for the audit trail
const activities = ref([
    {
        id: 1,
        user: 'Juan Dela Cruz',
        user_role: 'Caretaker',
        action: 'Added new batch',
        target: 'Batch B-001',
        timestamp: '2026-04-07 08:30:00',
        details: 'Registered 8 piglets from Inahin A.'
    },
    {
        id: 2,
        user: 'Admin',
        user_role: 'System Admin',
        action: 'Updated expenses',
        target: 'Expense EX-124',
        timestamp: '2026-04-06 15:45:00',
        details: 'Changed feed cost from ₱500 to ₱550.'
    },
    {
        id: 3,
        user: 'Maria Clara',
        user_role: 'Manager',
        action: 'Recorded mortality',
        target: 'Piglet in Batch B-002',
        timestamp: '2026-04-05 10:15:00',
        details: 'Cause of death: Unknown illness. Isolation recommended.'
    },
    {
        id: 4,
        user: 'Juan Dela Cruz',
        user_role: 'Caretaker',
        action: 'Administered Treatment',
        target: 'Batch B-001',
        timestamp: '2026-04-04 14:20:00',
        details: 'Vaccinated 8 piglets (Iron injection).'
    },
    {
        id: 5,
        user: 'Manager',
        user_role: 'Manager',
        action: 'Approved Resolution',
        target: 'Res-2026-01',
        timestamp: '2026-04-01 09:00:00',
        details: 'Approved budget for new feeder installation.'
    }
]);

const searchQuery = ref('');
const filterRole = ref('all');

const filteredActivities = computed(() => {
    return activities.value.filter(activity => {
        const matchesSearch = 
            activity.user.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            activity.action.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            activity.target.toLowerCase().includes(searchQuery.value.toLowerCase());
            
        const matchesRole = filterRole.value === 'all' || activity.user_role === filterRole.value;
        
        return matchesSearch && matchesRole;
    });
});

const isDrawerOpen = ref(false);
const selectedActivity = ref(null);

const viewDetails = (activity) => {
    selectedActivity.value = activity;
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedActivity.value = null; // Clear after transition
    }, 300);
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

// Helper for UI icons based on action type
const getActionIcon = (action) => {
    const act = action.toLowerCase();
    if (act.includes('add') || act.includes('registered')) return 'plus-circle';
    if (act.includes('update') || act.includes('edit')) return 'pencil-square';
    if (act.includes('delete') || act.includes('remove')) return 'trash';
    if (act.includes('approved')) return 'check-circle';
    return 'document-text';
};
</script>

<template>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <!-- Controls & Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <!-- Search bar -->
            <div class="relative w-full sm:w-96 shrink-0">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] sm:text-sm shadow-sm transition-colors" 
                    placeholder="Search user, action, target..."
                >
            </div>

            <!-- Role Filter -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select 
                    v-model="filterRole"
                    class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 rounded-xl bg-white text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20 focus:border-[#0c6d57] shadow-sm"
                >
                    <option value="all">All Roles</option>
                    <option value="Caretaker">Caretaker</option>
                    <option value="Manager">Manager</option>
                    <option value="System Admin">System Admin</option>
                </select>
            </div>
        </div>

        <!-- Logbook List View (Mobile-first card design) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div 
                v-if="filteredActivities.length === 0" 
                class="p-8 text-center text-gray-500"
            >
                No audit logs found matching your filters.
            </div>
            
            <ul v-else class="divide-y divide-gray-100">
                <li 
                    v-for="activity in filteredActivities" 
                    :key="activity.id"
                    @click="viewDetails(activity)"
                    class="p-4 sm:px-6 hover:bg-gray-50 transition-colors cursor-pointer group"
                >
                    <div class="flex items-start gap-4">
                        <!-- Icon Indicator -->
                        <div class="mt-1 shrink-0 bg-emerald-50 text-[#0c6d57] w-10 h-10 rounded-xl flex items-center justify-center">
                            <!-- SVG dynamically based on action (simplified) -->
                            <svg v-if="getActionIcon(activity.action) === 'plus-circle'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else-if="getActionIcon(activity.action) === 'pencil-square'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <svg v-else-if="getActionIcon(activity.action) === 'check-circle'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-1">
                                <p class="text-sm font-bold text-gray-900 truncate">
                                    {{ activity.action }} <span class="font-normal text-gray-500">on</span> {{ activity.target }}
                                </p>
                                <div class="text-xs text-gray-400 whitespace-nowrap shrink-0 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ formatDate(activity.timestamp) }}
                                </div>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-medium text-[#0c6d57]">{{ activity.user }}</span>
                                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full font-medium">{{ activity.user_role }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500 line-clamp-1 sm:hidden">
                                {{ activity.details }}
                            </p>
                        </div>

                        <!-- Chevron -->
                        <div class="shrink-0 self-center text-gray-300 group-hover:text-[#0c6d57] transition-colors ml-4 hidden sm:block">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Filtered Audit Details Drawer (Headless UI) -->
        <TransitionRoot as="template" :show="isDrawerOpen">
            <Dialog as="div" class="relative z-50" @close="closeDrawer">
                <TransitionChild 
                    as="template" 
                    enter="ease-in-out duration-300" 
                    enter-from="opacity-0" 
                    enter-to="opacity-100" 
                    leave="ease-in-out duration-300" 
                    leave-from="opacity-100" 
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <TransitionChild 
                                as="template" 
                                enter="transform transition ease-in-out duration-300 sm:duration-400" 
                                enter-from="translate-x-full" 
                                enter-to="translate-x-0" 
                                leave="transform transition ease-in-out duration-300 sm:duration-400" 
                                leave-from="translate-x-0" 
                                leave-to="translate-x-full"
                            >
                                <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl">
                                        <!-- Header -->
                                        <div class="bg-[#0c6d57] px-4 py-6 sm:px-6">
                                            <div class="flex items-center justify-between">
                                                <DialogTitle class="text-lg font-bold text-white">Log Details</DialogTitle>
                                                <div class="ml-3 flex h-7 items-center">
                                                    <button type="button" class="relative text-[#0c6d57] bg-white hover:bg-gray-100 rounded-full p-1.5 focus:outline-none focus:ring-2 focus:ring-white transition-colors" @click="closeDrawer">
                                                        <span class="absolute -inset-2.5" />
                                                        <span class="sr-only">Close panel</span>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-1">
                                                <p class="text-sm text-emerald-100">Detailed view of the specific system activity.</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="relative flex-1 px-4 py-6 sm:px-6 bg-gray-50" v-if="selectedActivity">
                                            
                                            <!-- Basic Info Card -->
                                            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm mb-4">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Activity Overview</h3>
                                                
                                                <div class="space-y-4">
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Action Performed</span>
                                                        <span class="block text-sm font-bold text-gray-900">{{ selectedActivity.action }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Target Object</span>
                                                        <span class="block text-sm font-medium text-[#0c6d57]">{{ selectedActivity.target }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs text-gray-500 font-medium mb-1">Timestamp</span>
                                                        <span class="block text-sm text-gray-700">{{ formatDate(selectedActivity.timestamp) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- User Info Card -->
                                            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm mb-4">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">User Information</h3>
                                                
                                                <div class="flex items-center gap-3">
                                                    <div class="h-10 w-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center font-bold text-gray-600">
                                                        {{ selectedActivity.user.substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-gray-900">{{ selectedActivity.user }}</p>
                                                        <p class="text-xs text-gray-500 font-medium">{{ selectedActivity.user_role }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Specific Details Card (The Payload) -->
                                            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Event Payload / Details</h3>
                                                
                                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                                    <p class="text-sm text-gray-700 leading-relaxed">{{ selectedActivity.details }}</p>
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
