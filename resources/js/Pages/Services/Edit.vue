<template>
    <Head title="Edit Service" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Service
                </h2>
                <Link
                    :href="route('services.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200"
                >
                    Back to Services
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Basic Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Service Name
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            id="name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                        <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
                                    </div>

                                    <div>
                                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                            Location
                                        </label>
                                        <input
                                            v-model="form.location"
                                            type="text"
                                            id="location"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                        <div v-if="errors.location" class="text-red-500 text-sm mt-1">{{ errors.location }}</div>
                                    </div>

                                    <div>
                                        <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">
                                            Capacity
                                        </label>
                                        <input
                                            v-model="form.capacity"
                                            type="number"
                                            id="capacity"
                                            min="1"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                        <div v-if="errors.capacity" class="text-red-500 text-sm mt-1">{{ errors.capacity }}</div>
                                    </div>

                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                            Status
                                        </label>
                                        <select
                                            v-model="form.status"
                                            id="status"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="active">Active</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                        <div v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing (Optional)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                            Hourly Rate ($)
                                        </label>
                                        <input
                                            v-model="form.hourly_rate"
                                            type="number"
                                            id="hourly_rate"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <div v-if="errors.hourly_rate" class="text-red-500 text-sm mt-1">{{ errors.hourly_rate }}</div>
                                    </div>

                                    <div>
                                        <label for="daily_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                            Daily Rate ($)
                                        </label>
                                        <input
                                            v-model="form.daily_rate"
                                            type="number"
                                            id="daily_rate"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <div v-if="errors.daily_rate" class="text-red-500 text-sm mt-1">{{ errors.daily_rate }}</div>
                                    </div>

                                    <div>
                                        <label for="monthly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                            Monthly Rate ($)
                                        </label>
                                        <input
                                            v-model="form.monthly_rate"
                                            type="number"
                                            id="monthly_rate"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <div v-if="errors.monthly_rate" class="text-red-500 text-sm mt-1">{{ errors.monthly_rate }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amenities -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Amenities</h3>
                                <div class="space-y-3">
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <span
                                            v-for="(amenity, index) in form.amenities"
                                            :key="index"
                                            class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center"
                                        >
                                            {{ amenity }}
                                            <button
                                                type="button"
                                                @click="removeAmenity(index)"
                                                class="ml-2 text-blue-600 hover:text-blue-800"
                                            >
                                                ×
                                            </button>
                                        </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <input
                                            v-model="newAmenity"
                                            type="text"
                                            placeholder="Add amenity"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            @keyup.enter="addAmenity"
                                        />
                                        <button
                                            type="button"
                                            @click="addAmenity"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200"
                                        >
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Availability Hours -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Availability Hours</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="day in days" :key="day">
                                        <label class="block text-sm font-medium text-gray-700 mb-2 capitalize">
                                            {{ day }}
                                        </label>
                                        <div class="flex space-x-2">
                                            <input
                                                v-model="form.availability_hours[day].start"
                                                type="time"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                            <span class="self-center">to</span>
                                            <input
                                                v-model="form.availability_hours[day].end"
                                                type="time"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reservation Details (only show if status is reserved) -->
                            <div v-if="form.status === 'reserved'" class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reservation Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Customer
                                        </label>
                                        <select
                                            v-model="form.customer_id"
                                            id="customer_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="">Select Customer</option>
                                            <option
                                                v-for="customer in customers"
                                                :key="customer.id"
                                                :value="customer.id"
                                            >
                                                {{ customer.name }}
                                            </option>
                                        </select>
                                        <div v-if="errors.customer_id" class="text-red-500 text-sm mt-1">{{ errors.customer_id }}</div>
                                    </div>

                                    <div>
                                        <label for="reserved_from" class="block text-sm font-medium text-gray-700 mb-2">
                                            Reserved From
                                        </label>
                                        <input
                                            v-model="form.reserved_from"
                                            type="datetime-local"
                                            id="reserved_from"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <div v-if="errors.reserved_from" class="text-red-500 text-sm mt-1">{{ errors.reserved_from }}</div>
                                    </div>

                                    <div>
                                        <label for="reserved_until" class="block text-sm font-medium text-gray-700 mb-2">
                                            Reserved Until
                                        </label>
                                        <input
                                            v-model="form.reserved_until"
                                            type="datetime-local"
                                            id="reserved_until"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <div v-if="errors.reserved_until" class="text-red-500 text-sm mt-1">{{ errors.reserved_until }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3">
                                <Link
                                    :href="route('services.show', service.id)"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200 disabled:opacity-50"
                                >
                                    {{ processing ? 'Updating...' : 'Update Service' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    service: Object,
    customers: Array,
    errors: Object,
});

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const form = useForm({
    name: props.service.name,
    location: props.service.location,
    capacity: props.service.capacity,
    status: props.service.status,
    hourly_rate: props.service.hourly_rate,
    daily_rate: props.service.daily_rate,
    monthly_rate: props.service.monthly_rate,
    amenities: props.service.amenities || [],
    availability_hours: props.service.availability_hours || {
        monday: { start: '09:00', end: '17:00' },
        tuesday: { start: '09:00', end: '17:00' },
        wednesday: { start: '09:00', end: '17:00' },
        thursday: { start: '09:00', end: '17:00' },
        friday: { start: '09:00', end: '17:00' },
        saturday: { start: '10:00', end: '16:00' },
        sunday: { start: '10:00', end: '16:00' },
    },
    customer_id: props.service.customer_id,
    reserved_from: props.service.reserved_from ? props.service.reserved_from.slice(0, 16) : '',
    reserved_until: props.service.reserved_until ? props.service.reserved_until.slice(0, 16) : '',
});

const newAmenity = ref('');

const addAmenity = () => {
    if (newAmenity.value.trim() && !form.amenities.includes(newAmenity.value.trim())) {
        form.amenities.push(newAmenity.value.trim());
        newAmenity.value = '';
    }
};

const removeAmenity = (index) => {
    form.amenities.splice(index, 1);
};

const submit = () => {
    form.put(route('services.update', props.service.id));
};
</script>
