<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    refunds: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const processing = ref(false);
const selectedRefund = ref(null);
const showProcessModal = ref(false);
const showRejectModal = ref(false);
const notes = ref('');
const rejectReason = ref('');

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const filterRefunds = () => {
    router.get(route('refunds.index'), {
        search: search.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openProcessModal = (refund) => {
    selectedRefund.value = refund;
    notes.value = '';
    showProcessModal.value = true;
};

const openRejectModal = (refund) => {
    selectedRefund.value = refund;
    rejectReason.value = '';
    showRejectModal.value = true;
};

const processRefund = () => {
    if (!selectedRefund.value) return;
    
    processing.value = true;
    router.post(route('refunds.process', selectedRefund.value.id), {
        notes: notes.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showProcessModal.value = false;
            selectedRefund.value = null;
            notes.value = '';
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const rejectRefund = () => {
    if (!selectedRefund.value || !rejectReason.value.trim()) return;
    
    processing.value = true;
    router.post(route('refunds.reject', selectedRefund.value.id), {
        reason: rejectReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            selectedRefund.value = null;
            rejectReason.value = '';
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Refund Management" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Refund Management</h2>
                <p class="text-sm text-gray-600 mt-1">Review and process refund requests from customers</p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Filters -->
                        <div class="flex gap-4 mb-6">
                            <input
                                v-model="search"
                                @input="filterRefunds"
                                type="text"
                                placeholder="Search by reference number or customer..."
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <select
                                v-model="statusFilter"
                                @change="filterRefunds"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <!-- Refunds Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Space</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="refund in refunds.data" :key="refund.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ refund.reference_number }}</div>
                                            <div class="text-xs text-gray-500">{{ refund.refund_method?.toUpperCase() }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ refund.customer_name }}</div>
                                            <div class="text-xs text-gray-500">{{ refund.customer_email }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ refund.space_type }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-green-600">{{ formatCurrency(refund.refund_amount) }}</div>
                                            <div v-if="refund.cancellation_fee > 0" class="text-xs text-orange-600">Fee: {{ formatCurrency(refund.cancellation_fee) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(refund.status)">
                                                {{ refund.status.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <div>{{ formatDate(refund.created_at) }}</div>
                                            <div v-if="refund.processed_at" class="text-xs text-gray-400">
                                                Processed: {{ formatDate(refund.processed_at) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <div class="flex gap-2">
                                                <button
                                                    v-if="refund.status === 'pending'"
                                                    @click="openProcessModal(refund)"
                                                    class="text-green-600 hover:text-green-800 font-medium"
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    v-if="refund.status === 'pending'"
                                                    @click="openRejectModal(refund)"
                                                    class="text-red-600 hover:text-red-800 font-medium"
                                                >
                                                    Reject
                                                </button>
                                                <span v-else class="text-gray-400">—</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!refunds.data || refunds.data.length === 0">
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                            No refunds found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="refunds.links" class="mt-6 flex justify-center">
                            <nav class="flex gap-1">
                                <template v-for="(link, index) in refunds.links" :key="index">
                                    <a
                                        v-if="link.url"
                                        :href="link.url"
                                        class="px-3 py-1 rounded"
                                        :class="link.active ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Refund Modal -->
        <div v-if="showProcessModal && selectedRefund" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900">Approve Refund</h3>
                
                <div class="space-y-2 text-sm">
                    <p><strong>Reference:</strong> {{ selectedRefund.reference_number }}</p>
                    <p><strong>Customer:</strong> {{ selectedRefund.customer_name }}</p>
                    <p><strong>Amount:</strong> {{ formatCurrency(selectedRefund.refund_amount) }}</p>
                    <p><strong>Method:</strong> {{ selectedRefund.refund_method?.toUpperCase() }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (optional)</label>
                    <textarea
                        v-model="notes"
                        rows="3"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Add any processing notes..."
                    ></textarea>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="showProcessModal = false"
                        :disabled="processing"
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="processRefund"
                        :disabled="processing"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ processing ? 'Processing...' : 'Approve Refund' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Refund Modal -->
        <div v-if="showRejectModal && selectedRefund" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-red-600">Reject Refund</h3>
                
                <div class="space-y-2 text-sm">
                    <p><strong>Reference:</strong> {{ selectedRefund.reference_number }}</p>
                    <p><strong>Customer:</strong> {{ selectedRefund.customer_name }}</p>
                    <p><strong>Amount:</strong> {{ formatCurrency(selectedRefund.refund_amount) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Explain why this refund is being rejected..."
                        required
                    ></textarea>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="showRejectModal = false"
                        :disabled="processing"
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="rejectRefund"
                        :disabled="processing || !rejectReason.trim()"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ processing ? 'Rejecting...' : 'Reject Refund' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
