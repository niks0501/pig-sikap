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

const componentRegistry = {
    'audit-trail-list': AuditTrailList,
    'admin-dashboard': AdminDashboard,
    'admin-user-management': AdminUserManagement,
    'admin-role-management': AdminRoleManagement,
    'admin-activity-logs': AdminActivityLogs,
    'admin-profile': AdminProfile,
    'force-password-change': ForcePasswordChangeForm,
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
