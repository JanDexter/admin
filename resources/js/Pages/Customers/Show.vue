<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminReservationModal from '@/Components/AdminReservationModal.vue';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status) => {
    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'on_hold': 'bg-orange-100 text-orange-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'active': 'bg-green-100 text-green-800',
        'paid': 'bg-emerald-100 text-emerald-800',
        'partial': 'bg-blue-100 text-blue-800',
        'completed': 'bg-gray-100 text-gray-800',
        'cancelled': 'bg-red-100 text-red-800',
    };
    return statusColors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const statusLabels = {
        'pending': 'Pending',
        'on_hold': 'On Hold',
        'confirmed': 'Confirmed',
        'active': 'Active',
        'paid': 'Paid',
        'partial': 'Partial Payment',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
    };
    return statusLabels[status] || status;
};

const getPaymentMethodLabel = (method) => {
    const methods = {
        'cash': 'Cash',
        'gcash': 'GCash',
        'maya': 'Maya',
        'card': 'Card',
        'bank': 'Bank Transfer',
    };
    return methods[method] || method;
};

const activeReservations = computed(() => {
    return props.customer.reservations?.filter(r => 
        ['pending', 'on_hold', 'confirmed', 'active'].includes(r.status)
    ) || [];
});

const completedReservations = computed(() => {
    return props.customer.reservations?.filter(r => 
        ['paid', 'completed', 'cancelled'].includes(r.status)
    ) || [];
});

const totalRevenue = computed(() => {
    return props.customer.reservations
        ?.filter(r => ['paid', 'completed'].includes(r.status))
        .reduce((sum, r) => sum + (r.total_cost || 0), 0) || 0;
});

const unpaidAmount = computed(() => {
    return props.customer.reservations
        ?.filter(r => ['pending', 'on_hold', 'confirmed', 'active'].includes(r.status))
        .reduce((sum, r) => sum + (r.total_cost || 0), 0) || 0;
});

const showReservationModal = ref(false);
const selectedReservation = ref(null);

const buildReservationPayload = (reservation) => {
    if (!reservation) return null;

    const spaceType = reservation.space_type ?? reservation.spaceType ?? null;
    const space = reservation.space ?? null;

    return {
        id: reservation.id,
        status: reservation.status,
        payment_method: reservation.payment_method,
        amount_paid: reservation.amount_paid ?? 0,
        amount_remaining: reservation.amount_remaining ?? 0,
        total_cost: reservation.total_cost ?? 0,
        notes: reservation.notes ?? '',
        start_time: reservation.start_time ?? null,
        end_time: reservation.end_time ?? null,
        hours: reservation.hours ?? null,
        pax: reservation.pax ?? null,
        is_open_time: reservation.is_open_time ?? false,
        space_type: spaceType ? {
            id: spaceType.id,
            name: spaceType.name,
        } : null,
        space: space ? {
            id: space.id,
            name: space.name,
        } : null,
        customer: {
            id: props.customer.id,
            name: props.customer.name,
            email: props.customer.email,
            phone: props.customer.phone,
        },
    };
};

const openReservationModal = (reservation) => {
    const payload = buildReservationPayload(reservation);
    if (!payload) return;
    selectedReservation.value = JSON.parse(JSON.stringify(payload));
    showReservationModal.value = true;
};

const closeReservationModal = () => {
    showReservationModal.value = false;
    selectedReservation.value = null;
};

const handleReservationUpdated = () => {
    closeReservationModal();
    router.reload({ only: ['customer'] });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Customer: ${customer.name}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">{{ customer.name }}</h2>
                                <p v-if="customer.company_name" class="text-sm text-gray-600 mt-1">{{ customer.company_name }}</p>
                            </div>
                            <Link
                                :href="route('customers.edit', customer.id)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                            >
                                Edit Customer
                            </Link>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Contact Information</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">{{ customer.email || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">{{ customer.phone || 'N/A' }}</span>
                                </div>
                                <div v-if="customer.address" class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">{{ customer.address }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Financial Summary</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-gray-500">Total Revenue</p>
                                    <p class="text-lg font-bold text-green-600">{{ formatCurrency(totalRevenue) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Unpaid/Pending</p>
                                    <p class="text-lg font-bold text-orange-600">{{ formatCurrency(unpaidAmount) }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Additional Info</h3>
                            <div class="space-y-2">
                                <div v-if="customer.space_type">
                                    <p class="text-xs text-gray-500">Preferred Space</p>
                                    <p class="text-sm text-gray-700">{{ customer.space_type?.name || 'N/A' }}</p>
                                </div>
                                <div v-if="customer.notes">
                                    <p class="text-xs text-gray-500">Notes</p>
                                    <p class="text-sm text-gray-700">{{ customer.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active/Unpaid Transactions -->
                <div v-if="activeReservations.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Active & Unpaid Transactions
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Space</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="reservation in activeReservations"
                                        :key="reservation.id"
                                        class="hover:bg-gray-50 cursor-pointer"
                                        @click="openReservationModal(reservation)"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(reservation.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ reservation.space_type?.name || reservation.space?.name || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ reservation.hours || 0 }}h • {{ reservation.pax || 1 }} pax
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ getPaymentMethodLabel(reservation.payment_method) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <div>
                                                <span>{{ formatCurrency(reservation.total_cost) }}</span>
                                                <div v-if="reservation.amount_paid > 0 && reservation.amount_paid < reservation.total_cost" class="text-xs text-green-600 mt-0.5">
                                                    Paid: {{ formatCurrency(reservation.amount_paid) }}
                                                </div>
                                                <div v-if="reservation.amount_paid > 0 && reservation.amount_paid < reservation.total_cost" class="text-xs text-orange-600">
                                                    Bal: {{ formatCurrency(reservation.total_cost - reservation.amount_paid) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(reservation.status)]">
                                                {{ getStatusLabel(reservation.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Complete Transaction History
                        </h3>

                        <div v-if="customer.reservations && customer.reservations.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Space</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="reservation in customer.reservations"
                                        :key="reservation.id"
                                        class="hover:bg-gray-50 cursor-pointer"
                                        @click="openReservationModal(reservation)"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(reservation.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ reservation.space_type?.name || reservation.space?.name || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ reservation.hours || 0 }}h • {{ reservation.pax || 1 }} pax
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ getPaymentMethodLabel(reservation.payment_method) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <div>
                                                <span>{{ formatCurrency(reservation.total_cost) }}</span>
                                                <div v-if="reservation.amount_paid > 0 && reservation.amount_paid < reservation.total_cost" class="text-xs text-green-600 mt-0.5">
                                                    Paid: {{ formatCurrency(reservation.amount_paid) }}
                                                </div>
                                                <div v-if="reservation.amount_paid > 0 && reservation.amount_paid < reservation.total_cost" class="text-xs text-orange-600">
                                                    Bal: {{ formatCurrency(reservation.total_cost - reservation.amount_paid) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(reservation.status)]">
                                                {{ getStatusLabel(reservation.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>No transactions found for this customer.</p>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="flex justify-start">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300"
                    >
                        ← Back to Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <AdminReservationModal
        :show="showReservationModal"
        :reservation="selectedReservation"
        @close="closeReservationModal"
        @updated="handleReservationUpdated"
    />
</template>
