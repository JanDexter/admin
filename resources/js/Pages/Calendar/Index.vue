<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { ref } from 'vue';

const props = defineProps({
    events: Array,
});

// Modal state for event details
const showEventModal = ref(false);
const selectedEvent = ref(null);

const openEventModal = (info) => {
    const e = info.event;
    selectedEvent.value = {
        title: e.title,
        start: e.start,
        end: e.end,
        ...e.extendedProps,
    };
    showEventModal.value = true;
};

const closeEventModal = () => {
    showEventModal.value = false;
    selectedEvent.value = null;
};

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: props.events,
    editable: false,
    selectable: false,
    nowIndicator: true,
    expandRows: true,
    height: 'auto',
    contentHeight: 'auto',
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    dayMaxEventRows: false, // let events grow
    eventOverlap: true,
    eventMinHeight: 24,
    eventDidMount: (info) => {
        // Allow content to wrap and scale naturally
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
        const startTime = event.start?.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        const endTime = event.end ? event.end.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Ongoing';
        const tooltipText = [
            `Space: ${p.space || 'N/A'}${p.spaceType ? ' ('+p.spaceType+')' : ''}`,
            `Customer: ${p.customer || 'N/A'}`,
            p.contact ? `Contact: ${p.contact}` : '',
            p.email ? `Email: ${p.email}` : '',
            p.phone ? `Phone: ${p.phone}` : '',
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
        openEventModal(info);
    }
});
</script>

<template>
    <Head title="Calendar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Calendar</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div v-if="showEventModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeEventModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Reservation Details</h3>
                                <div class="mt-3 text-sm text-gray-700 space-y-1" v-if="selectedEvent">
                                    <p><span class="font-medium">Space:</span> {{ selectedEvent.space }} <span v-if="selectedEvent.spaceType" class="text-gray-500">({{ selectedEvent.spaceType }})</span></p>
                                    <p><span class="font-medium">Customer:</span> {{ selectedEvent.customer }}</p>
                                    <p v-if="selectedEvent.contact"><span class="font-medium">Contact:</span> {{ selectedEvent.contact }}</p>
                                    <p v-if="selectedEvent.email"><span class="font-medium">Email:</span> {{ selectedEvent.email }}</p>
                                    <p v-if="selectedEvent.phone"><span class="font-medium">Phone:</span> {{ selectedEvent.phone }}</p>
                                    <p><span class="font-medium">From:</span> {{ new Date(selectedEvent.start).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                                    <p><span class="font-medium">Until:</span> {{ selectedEvent.end ? new Date(selectedEvent.end).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Ongoing' }}</p>
                                    <p v-if="selectedEvent.rate"><span class="font-medium">Rate:</span> ₱{{ selectedEvent.rate }}/h</p>
                                    <p v-if="selectedEvent.cost"><span class="font-medium">Cost:</span> ₱{{ selectedEvent.cost }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button @click="closeEventModal" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>
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
