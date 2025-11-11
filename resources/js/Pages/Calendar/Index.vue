<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { ref, computed, watch } from 'vue';
import OccupancySummary from '@/Components/OccupancySummary.vue';
import AdminReservationModal from '@/Components/AdminReservationModal.vue';

const props = defineProps({
    events: Array,
    spaceTypes: Array,
    spaces: Array,
});

const spaceTypesList = computed(() => props.spaceTypes ?? []);
const spacesList = computed(() => props.spaces ?? []);

const activeTab = ref(spaceTypesList.value[0]?.id ?? null);
const viewDate = ref(new Date());
const showReservationModal = ref(false);
const activeReservation = ref(null);

const eventsBySpaceType = computed(() => {
    const grouped = {};
    spaceTypesList.value.forEach(st => {
        grouped[st.id] = [];
    });

    (props.events ?? []).forEach(event => {
        const spaceTypeId = event.extendedProps?.spaceTypeId;
        if (spaceTypeId && grouped[spaceTypeId]) {
            grouped[spaceTypeId].push(event);
        }
    });

    return grouped;
});

const hasEvents = computed(() => (props.events?.length ?? 0) > 0);

watch(spaceTypesList, (newList) => {
    if (!newList.length) {
        activeTab.value = null;
        return;
    }

    if (!newList.some((type) => type.id === activeTab.value)) {
        activeTab.value = newList[0].id;
    }
}, { immediate: true });

const formatLocalDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Manila' });
};

const openReservationModal = (reservationPayload = null) => {
    if (!reservationPayload) return;
    activeReservation.value = reservationPayload;
    showReservationModal.value = true;
};

const closeReservationModal = () => {
    showReservationModal.value = false;
    activeReservation.value = null;
};

const handleReservationUpdated = () => {
    closeReservationModal();
    router.reload({ only: ['events'] });
};

const handleEventClick = (info) => {
    const extended = info.event.extendedProps || {};
    let reservation = extended.reservation ? JSON.parse(JSON.stringify(extended.reservation)) : null;

    const fallbackMeta = {
        status: extended.status ?? '',
        payment_method: extended.paymentMethod ?? '',
        amount_paid: extended.amountPaid ?? 0,
        amount_remaining: extended.amountRemaining ?? 0,
        total_cost: extended.cost ?? 0,
        notes: extended.notes ?? '',
        start_time: info.event.start ? info.event.start.toISOString() : null,
        end_time: info.event.end ? info.event.end.toISOString() : null,
        hours: extended.hours ?? null,
        pax: extended.pax ?? null,
        is_open_time: extended.is_open_time ?? false,
        space_type: extended.spaceType ? { id: extended.spaceTypeId, name: extended.spaceType } : null,
        space: extended.space ? { name: extended.space } : null,
        customer: extended.customer
            ? {
                name: extended.customer,
                email: extended.email ?? null,
                phone: extended.phone ?? null,
            }
            : null,
    };

    if (reservation) {
        reservation = {
            ...fallbackMeta,
            ...reservation,
            space_type: reservation.space_type ?? fallbackMeta.space_type,
            space: reservation.space ?? fallbackMeta.space,
            customer: reservation.customer ?? fallbackMeta.customer,
        };
        reservation.status = reservation.status ?? fallbackMeta.status;
        reservation.payment_method = reservation.payment_method ?? fallbackMeta.payment_method;
        reservation.amount_paid = reservation.amount_paid ?? fallbackMeta.amount_paid;
        reservation.amount_remaining = reservation.amount_remaining ?? fallbackMeta.amount_remaining;
        reservation.total_cost = reservation.total_cost ?? fallbackMeta.total_cost;
        reservation.notes = reservation.notes ?? fallbackMeta.notes;
        reservation.start_time = reservation.start_time ?? fallbackMeta.start_time;
        reservation.end_time = reservation.end_time ?? fallbackMeta.end_time;
        reservation.hours = reservation.hours ?? fallbackMeta.hours;
        reservation.pax = reservation.pax ?? fallbackMeta.pax;
        reservation.is_open_time = reservation.is_open_time ?? fallbackMeta.is_open_time;
        openReservationModal(reservation);
        return;
    }

    openReservationModal({
        id: info.event.id,
        ...fallbackMeta,
    });
};

const getCalendarOptions = (spaceTypeId) => {
    return {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: eventsBySpaceType.value[spaceTypeId] || [],
        editable: false,
        selectable: false,
        nowIndicator: true,
        expandRows: true,
        height: 'auto',
        contentHeight: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '26:00:00', // Extends to 2:00 AM (next day)
        slotDuration: '00:30:00',
        dayMaxEventRows: true,
        eventOverlap: true,
        eventMinHeight: 24,
        eventDidMount: (info) => {
            info.el.style.whiteSpace = 'normal';
            info.el.style.fontSize = '0.9rem';
            info.el.style.lineHeight = '1.2';
            const main = info.el.querySelector('.fc-event-main');
            if (main) {
                main.style.whiteSpace = 'normal';
            }
        },
        eventMouseEnter: (info) => {
            const el = info.el;
            const event = info.event;
            const p = event.extendedProps;
            el.style.cursor = 'pointer';
            const startTime = event.start ? formatLocalDateTime(event.start.toISOString()) : '';
            const endTime = event.end ? formatLocalDateTime(event.end.toISOString()) : 'Ongoing';
            const tooltipText = [
                `Space: ${p.space || 'N/A'}`,
                `Customer: ${p.customer || 'N/A'}`,
                p.contact ? `Contact: ${p.contact}` : '',
                `From: ${startTime}`,
                `Until: ${endTime}`,
                p.rate ? `Rate: ₱${p.rate}/h` : '',
                p.cost ? `Cost: ₱${p.cost}` : ''
            ].filter(Boolean).join('\n');
            el.setAttribute('title', tooltipText);
        },
        eventMouseLeave: (info) => {
            info.el.removeAttribute('title');
        },
        eventClick: (info) => {
            handleEventClick(info);
        },
        datesSet: (dateInfo) => {
            viewDate.value = dateInfo.view.currentStart;
        }
    };
};
</script>

<template>
    <Head title="Calendar" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Calendar</h2>
                        <p class="text-sm text-gray-600 mt-1">View and manage all reservations across different time periods</p>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Tabbed Navigation for Space Types -->
                    <div class="mb-4 border-b border-gray-200">
                        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                            <button
                                v-for="spaceType in spaceTypesList"
                                :key="spaceType.id"
                                @click="activeTab = spaceType.id"
                                :class="[
                                    activeTab === spaceType.id
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ spaceType.name }}
                            </button>
                        </nav>
                    </div>

                    <div v-if="!spaceTypesList.length" class="text-center text-gray-500 py-10">
                        <p>No space types available.</p>
                    </div>

                    <div v-else>
                        <p v-if="!hasEvents" class="text-sm text-gray-500 mb-6">
                            No reservations yet, but you can still browse each space type.
                        </p>

                        <div v-for="spaceType in spaceTypesList" :key="spaceType.id">
                            <div v-if="activeTab === spaceType.id">
                                <!-- Calendar Component -->
                                <FullCalendar :options="getCalendarOptions(spaceType.id)" />

                                <!-- Occupancy Summary Component -->
                                <div class="mt-8">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Hourly Occupancy Summary</h3>
                                    <OccupancySummary
                                        :reservations="eventsBySpaceType[spaceType.id] || []"
                                        :totalSpaces="spacesList.filter(s => s.space_type_id === spaceType.id).length"
                                        :viewDate="viewDate"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AdminReservationModal
            :show="showReservationModal"
            :reservation="activeReservation"
            @close="closeReservationModal"
            @updated="handleReservationUpdated"
        />
    </AuthenticatedLayout>
</template>

<style>
/* Improve calendar readability and prevent content clipping */
.fc .fc-toolbar-title {
    font-size: 1.25rem;
}
.fc-daygrid-event {
    white-space: normal;
}
/* Allow multi-line titles in time grid events */
.fc .fc-timegrid-event, .fc .fc-v-event {
    white-space: normal;
}
.fc .fc-timegrid-event .fc-event-main {
    overflow: visible;
}
/* Slightly increase event text size/padding */
.fc .fc-event {
    font-size: 0.9rem;
    padding: 3px 6px;
}

/* Ensure time grid events expand to show content */
.fc .fc-timegrid-event-harness, .fc .fc-v-event {
    min-height: 28px;
}

.fc .fc-timegrid-slot {
    height: 2.25rem; /* taller rows for readability */
}

/* Widen calendar view dropdown buttons */
.fc .fc-button-group .fc-button {
    min-width: 120px;
    padding: 0.375rem 1rem;
}

.fc .fc-button-group {
    gap: 2px;
}

/* Style the calendar toolbar */
.fc .fc-toolbar {
    margin-bottom: 1rem;
}

.fc .fc-toolbar-chunk {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>
