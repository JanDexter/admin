<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import PaymentModal from '@/Components/PaymentModal.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';

const showDetailModal = ref(false);
const selectedTransactionDetail = ref(null);

const onRowClick = (transaction, event) => {
    if (event.target.closest('a, button, input')) {
        return;
    }
    // Open detail modal instead of navigating
    selectedTransactionDetail.value = transaction;
    showDetailModal.value = true;
};

const props = defineProps({
    transactions: Object,
    filters: Object,
    summary: Object,
});

const filter = ref(props.filters.filter);
const showPaymentModal = ref(false);
const selectedTransaction = ref(null);

watch(filter, (value) => {
    router.get(route('accounting.index'), { filter: value }, {
        preserveState: true,
        replace: true,
    });
});

const openPaymentModal = (transaction) => {
    selectedTransaction.value = {
        id: transaction.id,
        customer_name: transaction.customer?.name,
        space_name: transaction.space?.name || transaction.space_type?.name,
        space_type: transaction.space?.space_type?.name || transaction.space_type?.name,
        total_cost: transaction.total_cost,
        cost: transaction.total_cost,
        amount_paid: transaction.amount_paid || 0,
        amount_remaining: transaction.amount_remaining ?? (transaction.total_cost - (transaction.amount_paid || 0)),
        status: transaction.status,
    };
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedTransaction.value = null;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedTransactionDetail.value = null;
};

const handleTransactionUpdated = () => {
    router.reload({ only: ['transactions', 'summary'] });
};

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
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Accounting" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Accounting</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        
                        <div class="mb-4 flex justify-between items-center">
                            <div>
                                <label for="filter" class="mr-2">Filter by:</label>
                                <select id="filter" v-model="filter" class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                <Link :href="route('accounting.export', { filter: filter })" class="ml-4 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Export to XLSX
                                </Link>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold">{{ formatCurrency(summary.totalRevenue) }}</p>
                                <p class="text-sm text-gray-500">{{ summary.transactionCount }} transactions</p>
                            </div>
                        </div>

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Date</th>
                                        <th scope="col" class="px-6 py-3">Customer</th>
                                        <th scope="col" class="px-6 py-3">Service</th>
                                        <th scope="col" class="px-6 py-3">Total Cost</th>
                                        <th scope="col" class="px-6 py-3">Amount Paid</th>
                                        <th scope="col" class="px-6 py-3">Payment Method</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="transactions.data.length === 0">
                                        <td colspan="8" class="px-6 py-4 text-center">No transactions found for this period.</td>
                                    </tr>
                                    <tr v-for="transaction in transactions.data" :key="transaction.id" class="bg-white border-b hover:bg-gray-50 cursor-pointer" @click="onRowClick(transaction, $event)">
                                        <td class="px-6 py-4">{{ formatDate(transaction.created_at) }}</td>
                                        <td class="px-6 py-4">
                                            <Link :href="route('customers.show', transaction.customer.id)" class="text-blue-600 hover:underline">{{ transaction.customer?.name || 'N/A' }}</Link>
                                        </td>
                                        <td class="px-6 py-4">{{ transaction.space?.space_type?.name || transaction.space_type?.name || 'N/A' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="font-semibold">{{ formatCurrency(transaction.total_cost) }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <span class="font-semibold" :class="transaction.amount_paid > 0 ? 'text-green-600' : 'text-gray-400'">
                                                    {{ formatCurrency(transaction.amount_paid || 0) }}
                                                </span>
                                                <div v-if="transaction.amount_paid > 0 && transaction.amount_paid < transaction.total_cost" class="text-xs text-orange-600 mt-0.5">
                                                    Balance: {{ formatCurrency(transaction.total_cost - transaction.amount_paid) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 uppercase">{{ transaction.payment_method || 'N/A' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="{
                                                    'bg-green-100 text-green-800': transaction.status === 'paid',
                                                    'bg-gray-100 text-gray-800': transaction.status === 'completed',
                                                    'bg-blue-100 text-blue-800': transaction.status === 'partial',
                                                    'bg-yellow-100 text-yellow-800': transaction.status === 'pending',
                                                    'bg-orange-100 text-orange-800': transaction.status === 'hold' || transaction.status === 'on_hold',
                                                    'bg-sky-100 text-sky-800': transaction.status === 'confirmed',
                                                    'bg-emerald-100 text-emerald-800': transaction.status === 'active',
                                                    'bg-red-100 text-red-800': transaction.status === 'cancelled'
                                                  }">
                                                {{ transaction.status === 'partial' ? 'Partial Payment' : transaction.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button
                                                v-if="transaction.status !== 'paid' && transaction.status !== 'completed' && transaction.status !== 'cancelled'"
                                                @click="openPaymentModal(transaction)"
                                                class="text-green-600 hover:text-green-800 font-medium text-sm"
                                            >
                                                {{ transaction.status === 'partial' ? 'Pay Balance' : 'Pay Now' }}
                                            </button>
                                            <span v-else-if="transaction.status === 'paid' || transaction.status === 'completed'" class="text-green-600 text-sm font-medium">✓ Paid</span>
                                            <span v-else class="text-gray-400 text-sm">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <Pagination :links="transactions.links" class="mt-6" />

                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Modal -->
        <PaymentModal
            :show="showPaymentModal"
            :reservation="selectedTransaction"
            @close="closePaymentModal"
            @paid="router.reload()"
        />
        
        <!-- Transaction Detail Modal -->
        <TransactionDetailModal
            :show="showDetailModal"
            :transaction="selectedTransactionDetail"
            @close="closeDetailModal"
            @updated="handleTransactionUpdated"
        />
    </AuthenticatedLayout>
</template>
