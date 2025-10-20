<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'updated']);

const processing = ref(false);
const currentTime = ref(new Date());
const extendHours = ref(1);
const showExtendForm = ref(false);
const showPaymentForm = ref(false);
const paymentAmount = ref(0);

let timeInterval;

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDateTime = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const getStatusStyle = (status) => {
    const styles = {
        pending: 'bg-yellow-100 border-yellow-300 text-yellow-800',
        on_hold: 'bg-orange-100 border-orange-300 text-orange-800',
        confirmed: 'bg-blue-100 border-blue-300 text-blue-800',
        active: 'bg-green-100 border-green-300 text-green-800',
        paid: 'bg-emerald-100 border-emerald-300 text-emerald-800',
        partial: 'bg-sky-100 border-sky-300 text-sky-800',
        completed: 'bg-gray-100 border-gray-300 text-gray-600',
        cancelled: 'bg-red-100 border-red-300 text-red-800',
    };
    return styles[status] || styles.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Pending',
        on_hold: 'On Hold',
        confirmed: 'Confirmed',
        active: 'Active',
        paid: 'Paid',
        partial: 'Partial Payment',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

// Calculate remaining/elapsed time
const timeInfo = computed(() => {
    if (!props.reservation.end_time) return null;
    
    const start = new Date(props.reservation.start_time);
    const end = new Date(props.reservation.end_time);
    const now = currentTime.value;
    
    const totalDuration = end - start;
    const elapsed = now - start;
    const remaining = end - now;
    
    const elapsedHours = Math.floor(elapsed / (1000 * 60 * 60));
    const elapsedMinutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
    
    const remainingHours = Math.floor(remaining / (1000 * 60 * 60));
    const remainingMinutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
    
    const percentageElapsed = (elapsed / totalDuration) * 100;
    
    return {
        elapsed: {
            hours: elapsedHours,
            minutes: elapsedMinutes,
            display: `${elapsedHours}h ${elapsedMinutes}m`,
        },
        remaining: {
            hours: remainingHours,
            minutes: remainingMinutes,
            display: remaining > 0 ? `${remainingHours}h ${remainingMinutes}m` : 'Expired',
            expired: remaining <= 0,
            urgent: remaining > 0 && remaining < 30 * 60 * 1000, // Less than 30 minutes
        },
        percentage: Math.min(100, Math.max(0, percentageElapsed)),
    };
});

const canExtend = computed(() => {
    return ['active', 'paid'].includes(props.reservation.status) && 
           !timeInfo.value?.remaining.expired;
});

const canEndEarly = computed(() => {
    return ['active'].includes(props.reservation.status) && 
           !timeInfo.value?.remaining.expired;
});

const canPay = computed(() => {
    return ['pending', 'partial', 'on_hold'].includes(props.reservation.status);
});

const canCancel = computed(() => {
    return ['pending', 'on_hold', 'confirmed'].includes(props.reservation.status);
});

// Calculate extension cost
const extensionCost = computed(() => {
    const rate = props.reservation.effective_hourly_rate || 0;
    return rate * extendHours.value;
});

// Handle extend reservation
const handleExtend = () => {
    if (!extendHours.value || extendHours.value < 1) return;
    
    processing.value = true;
    router.post(route('reservations.extend', props.reservation.id), {
        hours: extendHours.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showExtendForm.value = false;
            emit('updated');
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Handle end early
const handleEndEarly = () => {
    if (!confirm('Are you sure you want to end this reservation early? Any refund will be processed according to policy.')) {
        return;
    }
    
    processing.value = true;
    router.post(route('reservations.end-early', props.reservation.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Handle payment
const handlePayment = () => {
    if (!paymentAmount.value || paymentAmount.value <= 0) return;
    
    processing.value = true;
    router.post(route('customer.reservations.pay', props.reservation.id), {
        amount: paymentAmount.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentForm.value = false;
            paymentAmount.value = 0;
            emit('updated');
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Handle cancel
const handleCancel = () => {
    if (!confirm('Are you sure you want to cancel this reservation?')) {
        return;
    }
    
    processing.value = true;
    router.delete(route('reservations.destroy', props.reservation.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const close = () => {
    if (!processing.value) {
        emit('close');
    }
};

// Set default payment amount to remaining balance
const openPaymentForm = () => {
    paymentAmount.value = props.reservation.amount_remaining || props.reservation.total_cost || 0;
    showPaymentForm.value = true;
};

onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000); // Update every second for accurate timer
});

onBeforeUnmount(() => {
    if (timeInterval) clearInterval(timeInterval);
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 transition-opacity" @click="close"></div>
            
            <!-- Modal -->
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-[#2f4686] to-[#3956a3] px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white">Reservation Details</h3>
                        <p class="text-sm text-blue-100">{{ reservation.space_type?.name || 'Space Reservation' }}</p>
                    </div>
                    <button
                        @click="close"
                        class="p-2 hover:bg-white/20 rounded-lg transition-colors"
                        aria-label="Close"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between">
                        <span class="px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-wide" :class="getStatusStyle(reservation.status)">
                            {{ getStatusLabel(reservation.status) }}
                        </span>
                        <span class="text-sm text-gray-500">ID: #{{ reservation.id }}</span>
                    </div>

                    <!-- Timer (for active reservations) -->
                    <div v-if="reservation.status === 'active' && timeInfo" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border-2 border-blue-200">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-semibold text-gray-700">Session Progress</span>
                            </div>
                            <span class="text-2xl font-bold" :class="timeInfo.remaining.urgent ? 'text-red-600 animate-pulse' : 'text-blue-600'">
                                {{ timeInfo.remaining.display }}
                            </span>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="relative h-3 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="absolute inset-y-0 left-0 rounded-full transition-all duration-1000"
                                :class="timeInfo.remaining.urgent ? 'bg-red-500' : 'bg-blue-500'"
                                :style="{ width: `${timeInfo.percentage}%` }"
                            ></div>
                        </div>
                        
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-600">
                            <span>Elapsed: {{ timeInfo.elapsed.display }}</span>
                            <span>{{ Math.round(timeInfo.percentage) }}% complete</span>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2f4686]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Customer Information
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Name</div>
                                <div class="font-medium">{{ reservation.customer?.name || reservation.customer_name || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Email</div>
                                <div class="font-medium">{{ reservation.customer?.email || reservation.customer_email || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Phone</div>
                                <div class="font-medium">{{ reservation.customer?.phone || reservation.customer_phone || 'N/A' }}</div>
                            </div>
                            <div v-if="reservation.customer?.company_name || reservation.customer_company_name">
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Company</div>
                                <div class="font-medium">{{ reservation.customer?.company_name || reservation.customer_company_name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Reservation Details -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2f4686]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Booking Information
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Start Time</div>
                                <div class="font-medium">{{ formatDateTime(reservation.start_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">End Time</div>
                                <div class="font-medium">{{ formatDateTime(reservation.end_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Duration</div>
                                <div class="font-medium">{{ reservation.hours }} hour(s)</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Pax</div>
                                <div class="font-medium">{{ reservation.pax }} person(s)</div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Payment Method</div>
                                <div class="font-medium uppercase">{{ reservation.payment_method || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2f4686]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Payment Summary
                        </h4>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Cost</span>
                                <span class="font-semibold">{{ formatCurrency(reservation.total_cost) }}</span>
                            </div>
                            <div v-if="reservation.amount_paid > 0" class="flex justify-between text-green-600">
                                <span>Amount Paid</span>
                                <span class="font-semibold">{{ formatCurrency(reservation.amount_paid) }}</span>
                            </div>
                            <div v-if="reservation.is_partially_paid" class="flex justify-between text-orange-600 font-bold">
                                <span>Remaining Balance</span>
                                <span>{{ formatCurrency(reservation.amount_remaining) }}</span>
                            </div>
                            <div v-else-if="reservation.status === 'paid' || reservation.status === 'completed'" class="flex justify-between text-green-600 font-bold">
                                <span>Status</span>
                                <span>✓ Fully Paid</span>
                            </div>
                        </div>
                    </div>

                    <!-- Extend Form -->
                    <div v-if="showExtendForm" class="bg-blue-50 rounded-xl p-4 border border-blue-200 space-y-3">
                        <h4 class="font-semibold text-gray-900">Extend Reservation</h4>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Additional Hours
                            </label>
                            <input
                                v-model.number="extendHours"
                                type="number"
                                min="1"
                                max="12"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f4686] focus:border-transparent"
                            />
                            <div class="text-sm text-gray-600">
                                Extension Cost: <span class="font-bold text-[#2f4686]">{{ formatCurrency(extensionCost) }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="handleExtend"
                                :disabled="processing || !extendHours || extendHours < 1"
                                class="flex-1 px-4 py-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ processing ? 'Processing...' : 'Confirm Extension' }}
                            </button>
                            <button
                                @click="showExtendForm = false"
                                :disabled="processing"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div v-if="showPaymentForm" class="bg-green-50 rounded-xl p-4 border border-green-200 space-y-3">
                        <h4 class="font-semibold text-gray-900">Make Payment</h4>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Payment Amount
                            </label>
                            <input
                                v-model.number="paymentAmount"
                                type="number"
                                :min="0"
                                :max="reservation.amount_remaining || reservation.total_cost"
                                step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f4686] focus:border-transparent"
                            />
                            <div class="text-xs text-gray-600">
                                Maximum: {{ formatCurrency(reservation.amount_remaining || reservation.total_cost) }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="handlePayment"
                                :disabled="processing || !paymentAmount || paymentAmount <= 0"
                                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ processing ? 'Processing...' : 'Submit Payment' }}
                            </button>
                            <button
                                @click="showPaymentForm = false"
                                :disabled="processing"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-wrap gap-2">
                    <button
                        v-if="canExtend && !showExtendForm"
                        @click="showExtendForm = true"
                        :disabled="processing"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Extend Time
                    </button>
                    
                    <button
                        v-if="canPay && !showPaymentForm"
                        @click="openPaymentForm"
                        :disabled="processing"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Pay Balance
                    </button>
                    
                    <button
                        v-if="canEndEarly"
                        @click="handleEndEarly"
                        :disabled="processing"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                        </svg>
                        End Early
                    </button>
                    
                    <button
                        v-if="canCancel"
                        @click="handleCancel"
                        :disabled="processing"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
