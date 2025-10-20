<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    spaceType: {
        type: Object,
        required: true,
    },
    reservations: {
        type: Array,
        default: () => [],
    },
    currentDate: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
});

const emit = defineEmits(['openDetail', 'refresh']);

const selectedDate = ref(props.currentDate);
const currentTime = ref(new Date());
let timeInterval;

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatTime = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const formatDate = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Generate time slots from 9 AM to 12 AM (midnight)
const timeSlots = computed(() => {
    const slots = [];
    for (let hour = 9; hour <= 24; hour++) {
        const displayHour = hour === 24 ? 0 : hour;
        const ampm = hour < 12 ? 'AM' : (hour === 24 ? 'AM' : 'PM');
        const display12 = hour === 12 ? 12 : (hour > 12 && hour < 24 ? hour - 12 : (hour === 24 ? 12 : hour));
        slots.push({
            hour,
            time: `${String(hour).padStart(2, '0')}:00`,
            display: `${display12}:00 ${ampm}`,
        });
    }
    return slots;
});

// Filter reservations for selected date
const dayReservations = computed(() => {
    const selected = new Date(selectedDate.value);
    return props.reservations.filter(res => {
        if (!res.start_time) return false;
        const resDate = new Date(res.start_time);
        return resDate.toDateString() === selected.toDateString();
    });
});

// Map reservations to time slots
const reservationSlots = computed(() => {
    const slots = {};
    dayReservations.value.forEach(res => {
        const start = new Date(res.start_time);
        // If open-time (no end_time), use reactive current time as a moving end for display purposes
        const movingNow = currentTime.value;
        const end = res.end_time ? new Date(res.end_time) : new Date(movingNow);
        const startHour = start.getHours();
        const endHour = end.getHours();
        const duration = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60)));
        
        for (let h = startHour; h < startHour + duration && h <= 24; h++) {
            if (!slots[h]) slots[h] = [];
            slots[h].push({
                ...res,
                isStart: h === startHour,
                isEnd: h >= endHour - 1,
                duration,
                position: slots[h].length,
            });
        }
    });
    return slots;
});

// Get status color and styling
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
        partial: 'Partial',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

// Calculate remaining time for active reservations
const getRemainingTime = (reservation) => {
    // For open-time, show elapsed time instead of remaining
    if (!reservation.end_time) {
        const start = new Date(reservation.start_time);
        const now = currentTime.value;
        const diff = now - start;
        if (diff <= 0) return { expired: false, display: 'Just started' };
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        return { expired: false, display: `${hours}h ${minutes}m elapsed`, open: true };
    }
    const end = new Date(reservation.end_time);
    const now = currentTime.value;
    const diff = end - now;
    
    if (diff <= 0) return { expired: true, display: 'Expired' };
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 0) {
        return { expired: false, display: `${hours}h ${minutes}m left` };
    }
    return { expired: false, display: `${minutes}m left`, urgent: minutes < 30 };
};

// Navigate between dates
const previousDay = () => {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() - 1);
    selectedDate.value = date.toISOString().split('T')[0];
};

const nextDay = () => {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + 1);
    selectedDate.value = date.toISOString().split('T')[0];
};

const goToToday = () => {
    selectedDate.value = new Date().toISOString().split('T')[0];
};

const isToday = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return selectedDate.value === today;
});

const displayDate = computed(() => {
    const date = new Date(selectedDate.value);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

// Handle reservation click
const handleReservationClick = (reservation) => {
    emit('openDetail', reservation);
};

// Update current time every minute
onMounted(() => {
    // Update every second for snappy UX on open-time counters
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (timeInterval) clearInterval(timeInterval);
});

// Get current time indicator position
const currentTimePosition = computed(() => {
    const now = new Date();
    const today = new Date().toISOString().split('T')[0];
    
    if (selectedDate.value !== today) return null;
    
    const hour = now.getHours();
    const minutes = now.getMinutes();
    
    if (hour < 9 || hour >= 24) return null;
    
    const slotIndex = hour - 9;
    const minuteOffset = (minutes / 60) * 100;
    
    return {
        top: `${slotIndex * 80 + minuteOffset * 0.8}px`,
    };
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Calendar Header -->
        <div class="bg-gradient-to-r from-[#2f4686] to-[#3956a3] px-6 py-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-xl font-bold text-white">{{ spaceType.name }}</h3>
                    <p class="text-sm text-blue-100">{{ spaceType.description || 'View and manage reservations' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-semibold">
                        {{ dayReservations.length }} {{ dayReservations.length === 1 ? 'Reservation' : 'Reservations' }}
                    </span>
                </div>
            </div>
            
            <!-- Date Navigation -->
            <div class="flex items-center justify-between gap-4">
                <button
                    type="button"
                    @click="previousDay"
                    class="p-2 hover:bg-white/20 rounded-lg transition-colors"
                    aria-label="Previous day"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <div class="flex-1 text-center">
                    <div class="text-white font-semibold text-lg">{{ displayDate }}</div>
                    <button
                        v-if="!isToday"
                        type="button"
                        @click="goToToday"
                        class="mt-1 text-xs text-blue-100 hover:text-white underline"
                    >
                        Go to Today
                    </button>
                </div>
                
                <button
                    type="button"
                    @click="nextDay"
                    class="p-2 hover:bg-white/20 rounded-lg transition-colors"
                    aria-label="Next day"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="relative max-h-[600px] overflow-y-auto">
            <!-- Current time indicator -->
            <div
                v-if="currentTimePosition"
                class="absolute left-0 right-0 z-20 pointer-events-none"
                :style="currentTimePosition"
            >
                <div class="flex items-center">
                    <div class="w-16 flex-shrink-0 pr-2 text-right">
                        <span class="inline-block px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded">
                            NOW
                        </span>
                    </div>
                    <div class="flex-1 h-0.5 bg-red-500"></div>
                </div>
            </div>

            <!-- Time slots -->
            <div class="relative">
                <div
                    v-for="slot in timeSlots"
                    :key="slot.hour"
                    class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors"
                    style="min-height: 80px;"
                >
                    <!-- Time label -->
                    <div class="w-20 flex-shrink-0 p-3 border-r border-gray-200 bg-gray-50">
                        <div class="text-xs font-semibold text-gray-600">{{ slot.display }}</div>
                    </div>
                    
                    <!-- Reservation area -->
                    <div class="flex-1 p-2 relative">
                        <div
                            v-if="reservationSlots[slot.hour] && reservationSlots[slot.hour].length"
                            class="flex flex-col gap-1"
                        >
                            <div
                                v-for="(res, idx) in reservationSlots[slot.hour]"
                                :key="`${res.id}-${idx}`"
                                v-show="res.isStart"
                                @click="handleReservationClick(res)"
                                class="cursor-pointer rounded-lg border-2 p-2 transition-all hover:shadow-md hover:scale-[1.02]"
                                :class="getStatusStyle(res.status)"
                                :style="{
                                    minHeight: `${res.duration * 60}px`,
                                }"
                            >
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold truncate">
                                            {{ res.customer?.name || res.customer_name || 'Guest' }}
                                        </div>
                                        <div class="text-[10px] opacity-75">
                                            <template v-if="res.end_time">
                                                {{ formatTime(res.start_time) }} - {{ formatTime(res.end_time) }}
                                            </template>
                                            <template v-else>
                                                Started {{ formatTime(res.start_time) }} · Open time
                                            </template>
                                        </div>
                                    </div>
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide rounded whitespace-nowrap">
                                        {{ getStatusLabel(res.status) }}
                                    </span>
                                </div>
                                
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1 text-[10px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ res.hours }}h · {{ res.pax }} pax</span>
                                    </div>
                                    
                                    <div v-if="res.status === 'active'" class="flex items-center gap-1 text-[10px] font-semibold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" :class="(!res.end_time && 'text-orange-600 animate-pulse') || (getRemainingTime(res)?.urgent ? 'text-red-600 animate-pulse' : 'text-green-600')" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span :class="(!res.end_time && 'text-orange-700') || (getRemainingTime(res)?.urgent ? 'text-red-600' : '')">
                                            {{ getRemainingTime(res)?.display }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-1 text-[10px] font-semibold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>
                                            <template v-if="!res.end_time && res.status === 'active'">
                                                <!-- Running cost for open-time: compute hours elapsed (rounded up) * rate -->
                                                {{ formatCurrency(Math.max(1, Math.ceil((currentTime - new Date(res.start_time)) / (1000 * 60 * 60))) * (res.effective_hourly_rate || res.applied_hourly_rate || 0)) }}
                                            </template>
                                            <template v-else>
                                                {{ formatCurrency(res.total_cost) }}
                                            </template>
                                        </span>
                                    </div>
                                    
                                    <div v-if="res.is_partially_paid" class="text-[9px] px-1.5 py-0.5 bg-blue-500 text-white rounded inline-block">
                                        Paid: {{ formatCurrency(res.amount_paid) }} | Bal: {{ formatCurrency(res.amount_remaining) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Empty slot indicator -->
                        <div
                            v-else
                            class="h-full flex items-center justify-center text-gray-300 text-xs"
                        >
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity">Available</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Footer -->
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded bg-green-100 border border-green-300"></div>
                    <span class="text-gray-600">Active</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded bg-sky-100 border border-sky-300"></div>
                    <span class="text-gray-600">Partial Pay</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></div>
                    <span class="text-gray-600">Paid</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded bg-yellow-100 border border-yellow-300"></div>
                    <span class="text-gray-600">Pending</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded bg-gray-100 border border-gray-300"></div>
                    <span class="text-gray-600">Completed</span>
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
