<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    description: '',
    location: '',
    price_per_hour: '',
    price_per_day: '',
    price_per_month: '',
    capacity: 1,
    amenities: [],
    availability_hours: {
        monday: { open: '09:00', close: '17:00', closed: false },
        tuesday: { open: '09:00', close: '17:00', closed: false },
        wednesday: { open: '09:00', close: '17:00', closed: false },
        thursday: { open: '09:00', close: '17:00', closed: false },
        friday: { open: '09:00', close: '17:00', closed: false },
        saturday: { open: '10:00', close: '16:00', closed: false },
        sunday: { open: '10:00', close: '16:00', closed: true }
    },
    notes: ''
});

const availableAmenities = [
    'WiFi', 'Parking', 'Coffee/Tea', 'Printer', 'Whiteboard', 
    'Projector', 'Air Conditioning', 'Phone Booth', 'Kitchen Access',
    'Reception Service', 'Mail Handling', 'Security Access'
];

const newAmenity = ref('');

const addAmenity = () => {
    if (newAmenity.value && !form.amenities.includes(newAmenity.value)) {
        form.amenities.push(newAmenity.value);
        newAmenity.value = '';
    }
};

const removeAmenity = (index) => {
    form.amenities.splice(index, 1);
};

const addPredefinedAmenity = (amenity) => {
    if (!form.amenities.includes(amenity)) {
        form.amenities.push(amenity);
    }
};

const submit = () => {
    form.post(route('services.store'));
};
</script>

<template>
    <Head title="Add New Service" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Co-Workspace Service</h2>
                <Link
                    :href="route('services.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Services
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Service Name -->
                                <div>
                                    <InputLabel for="name" value="Service Name *" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.name"
                                        required
                                        placeholder="e.g., Hot Desk, Private Office, Meeting Room"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <!-- Location -->
                                <div>
                                    <InputLabel for="location" value="Location" />
                                    <TextInput
                                        id="location"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.location"
                                        placeholder="e.g., Floor 2, Room 201"
                                    />
                                    <InputError class="mt-2" :message="form.errors.location" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Description" />
                                <textarea
                                    id="description"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    v-model="form.description"
                                    placeholder="Describe the service and what's included..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Pricing -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel for="price_per_hour" value="Hourly Rate ($)" />
                                    <TextInput
                                        id="price_per_hour"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model="form.price_per_hour"
                                        placeholder="0.00"
                                    />
                                    <InputError class="mt-2" :message="form.errors.price_per_hour" />
                                </div>

                                <div>
                                    <InputLabel for="price_per_day" value="Daily Rate ($)" />
                                    <TextInput
                                        id="price_per_day"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model="form.price_per_day"
                                        placeholder="0.00"
                                    />
                                    <InputError class="mt-2" :message="form.errors.price_per_day" />
                                </div>

                                <div>
                                    <InputLabel for="price_per_month" value="Monthly Rate ($)" />
                                    <TextInput
                                        id="price_per_month"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model="form.price_per_month"
                                        placeholder="0.00"
                                    />
                                    <InputError class="mt-2" :message="form.errors.price_per_month" />
                                </div>
                            </div>

                            <!-- Capacity -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="capacity" value="Capacity (Number of People) *" />
                                    <TextInput
                                        id="capacity"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                        v-model="form.capacity"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.capacity" />
                                </div>
                            </div>

                            <!-- Amenities -->
                            <div>
                                <InputLabel value="Amenities" />
                                <div class="mt-2 space-y-4">
                                    <!-- Selected Amenities -->
                                    <div v-if="form.amenities.length > 0" class="flex flex-wrap gap-2">
                                        <span
                                            v-for="(amenity, index) in form.amenities"
                                            :key="index"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
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
                                    
                                    <!-- Quick Add Amenities -->
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="amenity in availableAmenities"
                                            :key="amenity"
                                            type="button"
                                            @click="addPredefinedAmenity(amenity)"
                                            :disabled="form.amenities.includes(amenity)"
                                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            + {{ amenity }}
                                        </button>
                                    </div>

                                    <!-- Custom Amenity -->
                                    <div class="flex gap-2">
                                        <TextInput
                                            v-model="newAmenity"
                                            placeholder="Add custom amenity..."
                                            class="flex-1"
                                            @keyup.enter="addAmenity"
                                        />
                                        <button
                                            type="button"
                                            @click="addAmenity"
                                            class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                                        >
                                            Add
                                        </button>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.amenities" />
                            </div>

                            <!-- Notes -->
                            <div>
                                <InputLabel for="notes" value="Additional Notes" />
                                <textarea
                                    id="notes"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    v-model="form.notes"
                                    placeholder="Any special requirements or notes..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <div class="flex items-center justify-end mt-6 space-x-3">
                                <Link
                                    :href="route('services.index')"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Create Service
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
