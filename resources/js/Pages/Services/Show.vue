<template>
    <Head title="Service Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Service Details
                </h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('services.edit', service.id)"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200"
                    >
                        Edit Service
                    </Link>
                    <Link
                        :href="route('services.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200"
                    >
                        Back to Services
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Service Status Badge -->
                        <div class="mb-6">
                            <span
                                :class="{
                                    'bg-green-100 text-green-800': service.status === 'active',
                                    'bg-yellow-100 text-yellow-800': service.status === 'reserved',
                                    'bg-red-100 text-red-800': service.status === 'closed'
                                }"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ service.status.charAt(0).toUpperCase() + service.status.slice(1) }}
                            </span>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Name:</span>
                                        <p class="text-gray-900">{{ service.name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Location:</span>
                                        <p class="text-gray-900">{{ service.location }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Capacity:</span>
                                        <p class="text-gray-900">{{ service.capacity }} people</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Created:</span>
                                        <p class="text-gray-900">{{ formatDate(service.created_at) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing</h3>
                                <div class="space-y-3">
                                    <div v-if="service.hourly_rate">
                                        <span class="text-sm font-medium text-gray-500">Hourly Rate:</span>
                                        <p class="text-gray-900">${{ service.hourly_rate }}</p>
                                    </div>
                                    <div v-if="service.daily_rate">
                                        <span class="text-sm font-medium text-gray-500">Daily Rate:</span>
                                        <p class="text-gray-900">${{ service.daily_rate }}</p>
                                    </div>
                                    <div v-if="service.monthly_rate">
                                        <span class="text-sm font-medium text-gray-500">Monthly Rate:</span>
                                        <p class="text-gray-900">${{ service.monthly_rate }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="mb-8" v-if="service.amenities && service.amenities.length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Amenities</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="amenity in service.amenities"
                                    :key="amenity"
                                    class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"
                                >
                                    {{ amenity }}
                                </span>
                            </div>
                        </div>

                        <!-- Availability Hours -->
                        <div class="mb-8" v-if="service.availability_hours">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Availability Hours</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="(hours, day) in service.availability_hours" :key="day">
                                        <span class="text-sm font-medium text-gray-700">{{ formatDay(day) }}:</span>
                                        <span class="text-gray-900 ml-2">
                                            {{ hours.start }} - {{ hours.end }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reservation Information -->
                        <div v-if="service.status === 'reserved'" class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reservation Details</h3>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="service.customer">
                                        <span class="text-sm font-medium text-gray-700">Customer:</span>
                                        <p class="text-gray-900">{{ service.customer.name }}</p>
                                    </div>
                                    <div v-if="service.reserved_from">
                                        <span class="text-sm font-medium text-gray-700">Reserved From:</span>
                                        <p class="text-gray-900">{{ formatDate(service.reserved_from) }}</p>
                                    </div>
                                    <div v-if="service.reserved_until">
                                        <span class="text-sm font-medium text-gray-700">Reserved Until:</span>
                                        <p class="text-gray-900">{{ formatDate(service.reserved_until) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button
                                v-if="service.status === 'active'"
                                @click="reserveService"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition duration-200"
                            >
                                Reserve Service
                            </button>
                            <button
                                v-if="service.status === 'reserved'"
                                @click="makeAvailable"
                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200"
                            >
                                Make Available
                            </button>
                            <button
                                v-if="service.status !== 'closed'"
                                @click="closeService"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200"
                            >
                                Close Service
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    service: Object,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDay = (day) => {
    const days = {
        monday: 'Monday',
        tuesday: 'Tuesday',
        wednesday: 'Wednesday',
        thursday: 'Thursday',
        friday: 'Friday',
        saturday: 'Saturday',
        sunday: 'Sunday'
    };
    return days[day] || day;
};

const reserveService = () => {
    router.patch(route('services.reserve', props.service.id));
};

const makeAvailable = () => {
    router.patch(route('services.make-available', props.service.id));
};

const closeService = () => {
    if (confirm('Are you sure you want to close this service? This action cannot be undone.')) {
        router.delete(route('services.destroy', props.service.id));
    }
};
</script>
