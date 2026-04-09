import './bootstrap';

import Alpine from 'alpinejs';
import { createApp } from 'vue';

window.Alpine = Alpine;

Alpine.start();

// Vue Component Registration
import AuditTrailList from './components/AuditTrailList.vue';
import AdminActivityLogs from './components/admin/AdminActivityLogs.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import AdminProfile from './components/admin/AdminProfile.vue';
import AdminRoleManagement from './components/admin/AdminRoleManagement.vue';
import AdminUserManagement from './components/admin/AdminUserManagement.vue';
import ForcePasswordChangeForm from './components/auth/ForcePasswordChangeForm.vue';
import PigBatchArchived from './components/pig-registry/PigBatchArchived.vue';
import PigBatchCreate from './components/pig-registry/PigBatchCreate.vue';
import PigBatchEdit from './components/pig-registry/PigBatchEdit.vue';
import PigBatchPigs from './components/pig-registry/PigBatchPigs.vue';
import PigBatchShow from './components/pig-registry/PigBatchShow.vue';
import PigBreederRegistry from './components/pig-registry/PigBreederRegistry.vue';
import PigRegistryIndex from './components/pig-registry/PigRegistryIndex.vue';

const componentRegistry = {
    'audit-trail-list': AuditTrailList,
    'admin-dashboard': AdminDashboard,
    'admin-user-management': AdminUserManagement,
    'admin-role-management': AdminRoleManagement,
    'admin-activity-logs': AdminActivityLogs,
    'admin-profile': AdminProfile,
    'force-password-change': ForcePasswordChangeForm,
    'pig-registry-index': PigRegistryIndex,
    'pig-batch-create': PigBatchCreate,
    'pig-batch-edit': PigBatchEdit,
    'pig-batch-show': PigBatchShow,
    'pig-batch-archived': PigBatchArchived,
    'pig-batch-pigs': PigBatchPigs,
    'pig-breeder-registry': PigBreederRegistry,
};

document.addEventListener('DOMContentLoaded', () => {
    const mountPoints = document.querySelectorAll('[data-vue-component]');

    mountPoints.forEach((mountPoint) => {
        const componentName = mountPoint.getAttribute('data-vue-component');
        const component = componentRegistry[componentName];

        if (!component) {
            return;
        }

        let props = {};
        const rawProps = mountPoint.getAttribute('data-props');

        if (rawProps) {
            try {
                props = JSON.parse(rawProps);
            } catch (error) {
                // Keep empty props when JSON parsing fails.
                props = {};
            }
        }

        createApp(component, props).mount(mountPoint);
    });

    // Backward compatibility for previously mounted Audit Trail view.
    const legacyEl = document.getElementById('vue-app');
    if (legacyEl && !legacyEl.hasAttribute('data-vue-component')) {
        createApp(AuditTrailList).mount(legacyEl);
    }
});
