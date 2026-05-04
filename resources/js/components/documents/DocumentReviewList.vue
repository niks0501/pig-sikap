<script setup>
import { ref, onMounted, computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import ToastNotification from '../common/ToastNotification.vue';

const props = defineProps({
  listUrl: {
    type: String,
    default: '/documents',
  },
  summaryUrl: {
    type: String,
    default: '/workflow/documents/summary',
  },
  csrfToken: {
    type: String,
    default: '',
  },
});

const uploads = ref([]);
const summary = ref({ pending: 0, approved: 0, rejected: 0, needs_resubmission: 0 });
const isLoading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const statusFilter = ref('all');

const selectedUpload = ref(null);
const showReviewModal = ref(false);
const reviewStatus = ref('approved');
const reviewComment = ref('');
const isUpdating = ref(false);
const toast = ref({ show: false, type: 'success', message: '' });

const fetchUploads = async () => {
  isLoading.value = true;
  try {
    const params = new URLSearchParams();
    if (statusFilter.value !== 'all') {
      params.append('status', statusFilter.value);
    }
    params.append('page', currentPage.value);

    const response = await window.axios.get(`${props.listUrl}?${params.toString()}`);
    uploads.value = response.data.data;
    totalPages.value = response.data.last_page;
  } catch (err) {
    showToast('error', 'Failed to load uploads.');
  } finally {
    isLoading.value = false;
  }
};

const fetchSummary = async () => {
  try {
    const response = await window.axios.get(props.summaryUrl);
    summary.value = response.data;
  } catch (err) {
    console.error('Failed to load summary');
  }
};

const onFilterChange = () => {
  currentPage.value = 1;
  fetchUploads();
};

const openReviewModal = (upload) => {
  selectedUpload.value = upload;
  reviewStatus.value = 'approved';
  reviewComment.value = '';
  showReviewModal.value = true;
};

const submitReview = async () => {
  if (!selectedUpload.value) return;

  if ((reviewStatus.value === 'rejected' || reviewStatus.value === 'needs_resubmission') && !reviewComment.value.trim()) {
    toast.value = { show: true, type: 'error', message: 'Please provide a remark.' };
    return;
  }

  isUpdating.value = true;
  try {
    await window.axios.patch(`/workflow/documents/${selectedUpload.value.id}/status`, {
      status: reviewStatus.value,
      review_comment: reviewComment.value,
    });

    showToast('success', 'Document status updated.');
    showReviewModal.value = false;
    fetchUploads();
    fetchSummary();
  } catch (err) {
    const msg = err.response?.data?.message || 'Update failed.';
    showToast('error', msg);
  } finally {
    isUpdating.value = false;
  }
};

const viewDocument = async (upload) => {
  try {
    const response = await window.axios.get(`/workflow/documents/${upload.id}/download`);
    if (response.data.url) {
      window.open(response.data.url, '_blank');
    }
  } catch (err) {
    showToast('error', 'Could not open document.');
  }
};

const showToast = (type, message) => {
  toast.value = { show: true, type, message };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

const getStatusBadgeClass = (status) => {
  const map = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    needs_resubmission: 'bg-orange-100 text-orange-800',
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
  fetchUploads();
  fetchSummary();
});
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="rounded-lg bg-white p-4 border border-gray-200">
        <p class="text-sm text-gray-500">Pending</p>
        <p class="text-2xl font-bold text-yellow-600">{{ summary.pending }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 border border-gray-200">
        <p class="text-sm text-gray-500">Approved</p>
        <p class="text-2xl font-bold text-green-600">{{ summary.approved }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 border border-gray-200">
        <p class="text-sm text-gray-500">Rejected</p>
        <p class="text-2xl font-bold text-red-600">{{ summary.rejected }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 border border-gray-200">
        <p class="text-sm text-gray-500">Resubmission</p>
        <p class="text-2xl font-bold text-orange-600">{{ summary.needs_resubmission }}</p>
      </div>
    </div>

    <div class="flex items-center gap-4">
      <label class="text-sm font-semibold text-gray-700">Filter by status:</label>
      <select
        v-model="statusFilter"
        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]"
        @change="onFilterChange"
      >
        <option value="all">All</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="needs_resubmission">Needs Resubmission</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Document</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Uploader</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="isLoading">
            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="uploads.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No documents found.</td>
          </tr>
          <tr v-for="upload in uploads" :key="upload.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="font-medium text-gray-900">{{ upload.document_type?.name }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ upload.original_name }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ upload.user?.name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ new Date(upload.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="getStatusBadgeClass(upload.status)"
              >
                {{ upload.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                class="text-[#0c6d57] hover:underline text-sm font-medium mr-3"
                @click="viewDocument(upload)"
              >
                View
              </button>
              <button
                class="text-[#0c6d57] hover:underline text-sm font-medium"
                @click="openReviewModal(upload)"
              >
                Review
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="totalPages > 1" class="flex justify-center gap-2">
      <button
        :disabled="currentPage === 1"
        class="rounded border border-gray-300 px-3 py-1 text-sm disabled:opacity-50"
        @click="currentPage--; fetchUploads()"
      >
        Previous
      </button>
      <span class="px-3 py-1 text-sm text-gray-600">Page {{ currentPage }} of {{ totalPages }}</span>
      <button
        :disabled="currentPage === totalPages"
        class="rounded border border-gray-300 px-3 py-1 text-sm disabled:opacity-50"
        @click="currentPage++; fetchUploads()"
      >
        Next
      </button>
    </div>

    <TransitionRoot appear :show="showReviewModal" as="template">
      <Dialog as="div" class="relative z-50" @close="showReviewModal = false">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/25" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-md transform rounded-xl bg-white p-6 shadow-xl transition-all">
                <DialogTitle class="text-lg font-semibold text-gray-900">
                  Review Document
                </DialogTitle>

                <div v-if="selectedUpload" class="mt-4 space-y-4">
                  <div class="rounded bg-gray-50 p-3">
                    <p class="text-sm font-medium text-gray-900">{{ selectedUpload.document_type?.name }}</p>
                    <p class="text-xs text-gray-500">{{ selectedUpload.original_name }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-2 flex gap-2">
                      <label class="flex items-center gap-2">
                        <input v-model="reviewStatus" type="radio" value="approved" class="text-[#0c6d57]" />
                        <span class="text-sm">Approve</span>
                      </label>
                      <label class="flex items-center gap-2">
                        <input v-model="reviewStatus" type="radio" value="rejected" class="text-[#0c6d57]" />
                        <span class="text-sm">Reject</span>
                      </label>
                      <label class="flex items-center gap-2">
                        <input v-model="reviewStatus" type="radio" value="needs_resubmission" class="text-[#0c6d57]" />
                        <span class="text-sm">Resubmit</span>
                      </label>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                    <textarea
                      v-model="reviewComment"
                      rows="3"
                      class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0c6d57] focus:ring-1 focus:ring-[#0c6d57]"
                      placeholder="Enter remarks if rejecting or requesting resubmission..."
                    ></textarea>
                  </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                  <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                    @click="showReviewModal = false"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    class="rounded-lg bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white"
                    :disabled="isUpdating"
                    @click="submitReview"
                  >
                    {{ isUpdating ? 'Saving...' : 'Save' }}
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <ToastNotification
      :show="toast.show"
      :type="toast.type"
      :message="toast.message"
      @close="toast.show = false"
    />
  </div>
</template>