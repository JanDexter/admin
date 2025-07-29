<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    customer: Object,
});

const serviceTypes = {
    'CONFERENCE ROOM': 350,
    'SHARED SPACE': 40,
    'EXCLUSIVE SPACE': 60,
    'PRIVATE SPACE': 50,
    'DRAFTING TABLE': 50,
};

const form = useForm({
    name: props.customer.name || '',
    email: props.customer.email || '',
    phone: props.customer.phone || '',
    company: props.customer.company || '',
    address: props.customer.address || '',
    status: props.customer.status || 'active',
    service_type: props.customer.service_type || '',
    service_price: props.customer.service_price || 0,
    service_start_time: props.customer.service_start_time ? props.customer.service_start_time.slice(0, 16) : '',
    service_end_time: props.customer.service_end_time ? props.customer.service_end_time.slice(0, 16) : '',
    amount_paid: props.customer.amount_paid || 0,
});

// Watch for service type changes to auto-update price
watch(() => form.service_type, (newServiceType) => {
    if (newServiceType && serviceTypes[newServiceType]) {
        form.service_price = serviceTypes[newServiceType];
    } else {
        form.service_price = 0;
    }
});

const formattedServicePrice = computed(() => {
    return form.service_price ? `₱${form.service_price.toLocaleString()}` : '₱0';
});

const submit = () => {
    form.put(route('customers.update', props.customer.id));
};
</script>

<template>
    <Head title="Edit Customer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Customer</h2>
                <Link
                    :href="route('dashboard')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.phone }}
                                </div>
                            </div>

                            <div>
                                <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                                <input
                                    id="company"
                                    v-model="form.company"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.company" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.company }}
                                </div>
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                                <div v-if="form.errors.address" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="form.errors.status" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <!-- Service Selection Section -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Service Details</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type</label>
                                        <select
                                            id="service_type"
                                            v-model="form.service_type"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">Select a service</option>
                                            <option v-for="(price, service) in serviceTypes" :key="service" :value="service">
                                                {{ service }} - ₱{{ price }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.service_type" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.service_type }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="service_price" class="block text-sm font-medium text-gray-700">Service Price</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">₱</span>
                                            </div>
                                            <input
                                                id="service_price"
                                                v-model="form.service_price"
                                                type="number"
                                                step="0.01"
                                                class="pl-7 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                readonly
                                            />
                                        </div>
                                        <div v-if="form.errors.service_price" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.service_price }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="service_start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                                        <input
                                            id="service_start_time"
                                            v-model="form.service_start_time"
                                            type="datetime-local"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <div v-if="form.errors.service_start_time" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.service_start_time }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="service_end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                                        <input
                                            id="service_end_time"
                                            v-model="form.service_end_time"
                                            type="datetime-local"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <div v-if="form.errors.service_end_time" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.service_end_time }}
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="amount_paid" class="block text-sm font-medium text-gray-700">Amount Paid</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">₱</span>
                                            </div>
                                            <input
                                                id="amount_paid"
                                                v-model="form.amount_paid"
                                                type="number"
                                                step="0.01"
                                                class="pl-7 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </div>
                                        <div v-if="form.errors.amount_paid" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.amount_paid }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <Link
                                    :href="route('dashboard')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Updating...</span>
                                    <span v-else>Update Customer</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
