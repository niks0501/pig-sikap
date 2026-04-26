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
import ToastNotification from './components/common/ToastNotification.vue';
import CycleHealthPanel from './components/health/CycleHealthPanel.vue';
import HealthIncidentCreateForm from './components/health/HealthIncidentCreateForm.vue';
import PigCycleArchived from './components/pig-registry/PigCycleArchived.vue';
import PigCycleCreate from './components/pig-registry/PigCycleCreate.vue';
import PigCycleEdit from './components/pig-registry/PigCycleEdit.vue';
import PigCyclePigs from './components/pig-registry/PigCyclePigs.vue';
import PigCycleShow from './components/pig-registry/PigCycleShow.vue';
import PigBreederRegistry from './components/pig-registry/PigBreederRegistry.vue';
import PigRegistryIndex from './components/pig-registry/PigRegistryIndex.vue';
import ExpenseList from './components/expenses/ExpenseList.vue';
import ExpenseForm from './components/expenses/ExpenseForm.vue';
import ExpenseDetail from './components/expenses/ExpenseDetail.vue';
import ExpenseFilters from './components/expenses/ExpenseFilters.vue';
import ExpenseSummaryCards from './components/expenses/ExpenseSummaryCards.vue';
import ExpenseTableRow from './components/expenses/ExpenseTableRow.vue';
import ReceiptUpload from './components/expenses/ReceiptUpload.vue';

const componentRegistry = {
  'audit-trail-list': AuditTrailList,
  'admin-dashboard': AdminDashboard,
  'admin-user-management': AdminUserManagement,
  'admin-role-management': AdminRoleManagement,
  'admin-activity-logs': AdminActivityLogs,
  'admin-profile': AdminProfile,
  'force-password-change': ForcePasswordChangeForm,
  'toast-notification': ToastNotification,
  'cycle-health-panel': CycleHealthPanel,
  'health-incident-create-form': HealthIncidentCreateForm,
  'pig-registry-index': PigRegistryIndex,
  'pig-cycle-create': PigCycleCreate,
  'pig-cycle-edit': PigCycleEdit,
  'pig-cycle-show': PigCycleShow,
  'pig-cycle-archived': PigCycleArchived,
  'pig-cycle-pigs': PigCyclePigs,
  'pig-breeder-registry': PigBreederRegistry,
  'expense-list': ExpenseList,
  'expense-form': ExpenseForm,
  'expense-detail': ExpenseDetail,
  'expense-filters': ExpenseFilters,
  'expense-summary-cards': ExpenseSummaryCards,
  'expense-table-row': ExpenseTableRow,
  'receipt-upload': ReceiptUpload,
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
