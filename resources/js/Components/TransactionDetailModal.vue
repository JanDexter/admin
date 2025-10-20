<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    transaction: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'updated']);

const isEditing = ref(false);
const isSaving = ref(false);

const editForm = ref({
    status: '',
    payment_method: '',
    amount_paid: 0,
    notes: '',
});

watch(() => props.transaction, (transaction) => {
    if (transaction) {
        editForm.value = {
            status: transaction.status || 'pending',
            payment_method: transaction.payment_method || 'cash',
            amount_paid: transaction.amount_paid || 0,
            notes: transaction.notes || '',
        };
        isEditing.value = false;
    }
}, { immediate: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const closeModal = () => {
    isEditing.value = false;
    emit('close');
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
};

const saveChanges = () => {
    if (!props.transaction?.id) return;
    
    isSaving.value = true;
    
    router.put(route('accounting.update', props.transaction.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            emit('updated');
        },
        onError: (errors) => {
            console.error('Error updating transaction:', errors);
            alert('Failed to update transaction. Please try again.');
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

const remainingBalance = computed(() => {
    if (!props.transaction) return 0;
    return props.transaction.total_cost - (editForm.value.amount_paid || 0);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
                    
                    <!-- Modal -->
                    <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <h3 class="text-xl font-semibold text-gray-900">Transaction Details</h3>
                            <button
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6" v-if="transaction">
                            <div class="space-y-4">
                                <!-- Customer & Service Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                                        <p class="text-gray-900">{{ transaction.customer?.name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                                        <p class="text-gray-900">{{ transaction.space?.space_type?.name || transaction.space_type?.name || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Date & Time -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                        <p class="text-gray-900">{{ formatDate(transaction.start_time) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                        <p class="text-gray-900">{{ formatDate(transaction.end_time) }}</p>
                                    </div>
                                </div>

                                <!-- Duration & Pax -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                                        <p class="text-gray-900">{{ transaction.hours || 0 }} hour(s)</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pax</label>
                                        <p class="text-gray-900">{{ transaction.pax || 1 }}</p>
                                    </div>
                                </div>

                                <!-- Cost Breakdown -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Total Cost</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(transaction.total_cost) }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="!isEditing">
                                            <span class="text-sm text-gray-600">Amount Paid</span>
                                            <span class="text-sm font-semibold text-green-600">{{ formatCurrency(transaction.amount_paid || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="!isEditing">
                                            <span class="text-sm text-gray-600">Balance</span>
                                            <span class="text-sm font-semibold text-orange-600">{{ formatCurrency(transaction.total_cost - (transaction.amount_paid || 0)) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Editable Fields -->
                                <div v-if="isEditing" class="space-y-4 border-t pt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select v-model="editForm.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial Payment</option>
                                            <option value="paid">Paid</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                        <select v-model="editForm.payment_method" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <option value="maya">Maya</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
                                        <input
                                            v-model.number="editForm.amount_paid"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :max="transaction.total_cost"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Remaining: {{ formatCurrency(remainingBalance) }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <textarea
                                            v-model="editForm.notes"
                                            rows="3"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Add notes about this transaction..."
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- Non-editable fields -->
                                <div v-else class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                        <p class="text-gray-900 uppercase">{{ transaction.payment_method || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="{
                                                'bg-green-100 text-green-800': transaction.status === 'paid' || transaction.status === 'completed',
                                                'bg-blue-100 text-blue-800': transaction.status === 'partial',
                                                'bg-yellow-100 text-yellow-800': transaction.status === 'hold' || transaction.status === 'pending',
                                                'bg-red-100 text-red-800': transaction.status === 'cancelled'
                                              }">
                                            {{ transaction.status === 'partial' ? 'Partial Payment' : transaction.status }}
                                        </span>
                                    </div>
                                    <div v-if="transaction.notes">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <p class="text-gray-900 whitespace-pre-wrap">{{ transaction.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                            <button
                                v-if="!isEditing"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Close
                            </button>
                            
                            <button
                                v-if="!isEditing"
                                @click="toggleEdit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Edit
                            </button>

                            <template v-else>
                                <button
                                    @click="toggleEdit"
                                    :disabled="isSaving"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                
                                <button
                                    @click="saveChanges"
                                    :disabled="isSaving"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSaving">Saving...</span>
                                    <span v-else>Save Changes</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
