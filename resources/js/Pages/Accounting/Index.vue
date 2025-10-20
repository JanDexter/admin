<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    transactions: Object,
    filters: Object,
    summary: Object,
});

const filter = ref(props.filters.filter);

watch(filter, (value) => {
    router.get(route('accounting.index'), { filter: value }, {
        preserveState: true,
        replace: true,
    });
});

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
                                        <th scope="col" class="px-6 py-3">Amount</th>
                                        <th scope="col" class="px-6 py-3">Payment Method</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="transactions.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center">No transactions found for this period.</td>
                                    </tr>
                                    <tr v-for="transaction in transactions.data" :key="transaction.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4">{{ formatDate(transaction.created_at) }}</td>
                                        <td class="px-6 py-4">{{ transaction.customer?.name || 'N/A' }}</td>
                                        <td class="px-6 py-4">{{ transaction.space?.space_type?.name || 'N/A' }}</td>
                                        <td class="px-6 py-4">{{ formatCurrency(transaction.total_cost) }}</td>
                                        <td class="px-6 py-4 uppercase">{{ transaction.payment_method }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="{
                                                    'bg-green-100 text-green-800': transaction.status === 'paid' || transaction.status === 'completed',
                                                    'bg-yellow-100 text-yellow-800': transaction.status === 'hold',
                                                    'bg-red-100 text-red-800': transaction.status === 'cancelled'
                                                  }">
                                                {{ transaction.status }}
                                            </span>
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
    </AuthenticatedLayout>
</template>
