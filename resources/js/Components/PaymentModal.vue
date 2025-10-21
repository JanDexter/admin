<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    reservation: Object,
    customer: Object,
});

const emit = defineEmits(['close', 'paid']);

const form = useForm({
    reservation_id: null,
    amount: '',
    payment_method: 'cash',
    notes: '',
});

const calculatedAmount = computed(() => {
    if (!props.reservation) return 0;
    // Prefer backend-provided remaining amount if available
    const remaining = props.reservation.amount_remaining;
    if (typeof remaining === 'number') return Math.max(remaining, 0);
    const total = props.reservation.total_cost || props.reservation.cost || 0;
    const paid = props.reservation.amount_paid || 0;
    return Math.max(total - paid, 0);
});

const amountPaid = computed(() => {
    return props.reservation?.amount_paid || 0;
});

const totalCost = computed(() => {
    return props.reservation?.total_cost || props.reservation?.cost || 0;
});

const isPartiallyPaid = computed(() => {
    return amountPaid.value > 0 && amountPaid.value < totalCost.value;
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.amount = '';
    form.payment_method = 'cash';
};

const closeModal = () => {
    resetForm();
    emit('close');
};

const submitPayment = () => {
    if (!form.amount || form.amount <= 0) {
        form.setError('amount', 'Amount must be greater than 0');
        return;
    }

    const routeName = props.customer ? 'payments.customer' : 'payments.process';
    const routeParam = props.customer ? props.customer.id : props.reservation.id;

    if (props.customer && props.reservation) {
        form.reservation_id = props.reservation.id;
    }

    form.post(route(routeName, routeParam), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            emit('paid');
            emit('close');
        },
    });
};

const useSuggestedAmount = () => {
    form.amount = calculatedAmount.value.toFixed(2);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="submitPayment">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Process Payment
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <!-- Customer/Reservation Info -->
                                    <div v-if="customer" class="p-3 bg-gray-50 rounded-md">
                                        <p class="text-sm font-medium text-gray-900">{{ customer.company_name || customer.name }}</p>
                                        <p class="text-xs text-gray-500">{{ customer.email }}</p>
                                    </div>
                                    
                                    <div v-if="reservation && reservation.space_name" class="p-3 bg-blue-50 rounded-md">
                                        <p class="text-sm font-medium text-blue-900">{{ reservation.space_name }}</p>
                                        <p class="text-xs text-blue-600">{{ reservation.space_type }}</p>
                                    </div>

                                    <!-- Payment Summary -->
                                    <div v-if="totalCost > 0" class="space-y-2 p-3 bg-gray-50 rounded-md">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Total Cost:</span>
                                            <span class="font-semibold text-gray-900">₱{{ totalCost.toFixed(2) }}</span>
                                        </div>
                                        <div v-if="isPartiallyPaid" class="flex justify-between text-sm">
                                            <span class="text-gray-600">Already Paid:</span>
                                            <span class="font-semibold text-green-600">₱{{ amountPaid.toFixed(2) }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 pt-2 flex justify-between">
                                            <span class="text-sm font-medium text-gray-700">{{ isPartiallyPaid ? 'Remaining Balance:' : 'Amount Due:' }}</span>
                                            <span class="text-lg font-bold text-yellow-700">₱{{ calculatedAmount.toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Partial Payment Notice -->
                                    <div v-if="isPartiallyPaid" class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <p class="text-sm text-blue-700 font-medium">This transaction has a partial payment. You can pay the full remaining balance or another partial amount.</p>
                                        </div>
                                    </div>

                                    <!-- Suggested Amount -->
                                    <div v-if="calculatedAmount > 0" class="flex items-center justify-between p-3 bg-yellow-50 rounded-md">
                                        <div>
                                            <p class="text-sm font-medium text-yellow-900">{{ isPartiallyPaid ? 'Pay Remaining Balance' : 'Pay Full Amount' }}</p>
                                            <p class="text-lg font-bold text-yellow-700">₱{{ calculatedAmount.toFixed(2) }}</p>
                                        </div>
                                        <button type="button" @click="useSuggestedAmount" class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                            Use This
                                        </button>
                                    </div>

                                    <!-- Amount Input -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">
                                            Payment Amount
                                            <span class="text-xs text-gray-500">(Max: ₱{{ calculatedAmount.toFixed(2) }})</span>
                                        </label>
                                        <div class="relative mt-1">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">₱</span>
                                            <input
                                                v-model="form.amount"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                :max="calculatedAmount"
                                                required
                                                class="pl-8 block w-full rounded-md shadow-sm border"
                                                :class="form.errors.amount ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                                        <p v-else class="mt-1 text-xs text-gray-500">Enter any amount up to the remaining balance. Partial payments are allowed.</p>
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                                        <select
                                            v-model="form.payment_method"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <option value="maya">Maya</option>
                                            <option value="card">Card</option>
                                        </select>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                        <textarea
                                            v-model="form.notes"
                                            rows="2"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Additional notes..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ form.processing ? 'Processing...' : 'Process Payment' }}
                        </button>
                        <button
                            type="button"
                            @click="closeModal"
                            :disabled="form.processing"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
