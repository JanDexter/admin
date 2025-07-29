<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    services: Object,
});

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        reserved: 'bg-blue-100 text-blue-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const closeService = (serviceId) => {
    if (confirm('Are you sure you want to close this service transaction?')) {
        router.patch(route('services.close', serviceId), {}, {
            preserveScroll: true
        });
    }
};

const deleteService = (serviceId) => {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        router.delete(route('services.destroy', serviceId), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Services" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Co-Workspace Services</h2>
                <Link
                    :href="route('services.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Add New Service
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Service
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pricing
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reservation
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="service in services.data" :key="service.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ service.name }}</div>
                                                <div class="text-sm text-gray-500">{{ service.description }}</div>
                                                <div class="text-xs text-gray-400">Capacity: {{ service.capacity }} people</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ service.location || 'Not specified' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div v-if="service.price_per_hour" class="text-xs">{{ '$' + service.price_per_hour }}/hr</div>
                                            <div v-if="service.price_per_day" class="text-xs">{{ '$' + service.price_per_day }}/day</div>
                                            <div v-if="service.price_per_month" class="text-xs">{{ '$' + service.price_per_month }}/month</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusColor(service.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ service.status.charAt(0).toUpperCase() + service.status.slice(1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div v-if="service.status === 'reserved' && service.customer">
                                                <div class="font-medium">{{ service.customer.name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    Until: {{ new Date(service.reserved_until).toLocaleDateString() }}
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                :href="route('services.show', service.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                :href="route('services.edit', service.id)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                v-if="service.status !== 'closed'"
                                                @click="closeService(service.id)"
                                                class="text-orange-600 hover:text-orange-900"
                                            >
                                                Close
                                            </button>
                                            <button
                                                @click="deleteService(service.id)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="services.links.length > 3" class="mt-6">
                            <nav class="flex justify-center">
                                <div class="flex space-x-1">
                                    <Link
                                        v-for="link in services.links"
                                        :key="link.label"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 text-sm',
                                            link.active
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50',
                                            !link.url ? 'cursor-not-allowed opacity-50' : ''
                                        ]"
                                        class="border border-gray-300"
                                        v-html="link.label"
                                    />
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
