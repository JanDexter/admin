<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { usePWA } from '@/composables/usePWA.js';

const props = defineProps({
    stats: Object,
    customers: Object,
    spaceTypes: Array,
});

const { isOnline, isInstallable, isInstalled, installPWA } = usePWA();

const searchQuery = ref('');

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-red-100 text-red-800',
        closed: 'bg-red-100 text-red-800',
        reserved: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return colors[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

const getTotalSpaces = (spaceType) => {
    return spaceType?.spaces?.length || 0;
};

const getOccupiedSpaces = (spaceType) => {
    return spaceType?.spaces?.filter(space => space.status === 'occupied').length || 0;
};

const getAvailableSpaces = (spaceType) => {
    return spaceType?.spaces?.filter(space => space.status === 'available').length || 0;
};

const getOccupancyPercentage = (spaceType) => {
    const total = getTotalSpaces(spaceType);
    const occupied = getOccupiedSpaces(spaceType);
    return total > 0 ? Math.round((occupied / total) * 100) : 0;
};

const getOccupancyFraction = (spaceType) => {
    const occupied = getOccupiedSpaces(spaceType);
    const total = getTotalSpaces(spaceType);
    return `${occupied}/${total}`;
};

const getNextAvailableTime = (spaceType) => {
    if (!spaceType?.spaces) return null;
    
    const occupiedSpaces = spaceType.spaces
        .filter(space => space.status === 'occupied' && space.occupied_until)
        .sort((a, b) => new Date(a.occupied_until) - new Date(b.occupied_until));
    
    if (occupiedSpaces.length === 0) {
        return null;
    }
    
    const nextFree = new Date(occupiedSpaces[0].occupied_until);
    const now = new Date();
    
    if (nextFree <= now) {
        return 'Available now';
    }
    
    const diff = nextFree - now;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
};

const getTimeUntilFree = (space) => {
    if (!space.occupied_until) return null;
    
    const until = new Date(space.occupied_until);
    const now = new Date();
    
    if (until <= now) return 'Available now';
    
    const diff = until - now;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
};

// Helper functions for overall space statistics
const getAllSpaces = () => {
    if (!props.spaceTypes) return [];
    return props.spaceTypes.flatMap(spaceType => spaceType.spaces || []);
};

const getAvailableSpacesCount = () => {
    return getAllSpaces().filter(space => space.status === 'available').length;
};

const getOccupiedSpacesCount = () => {
    return getAllSpaces().filter(space => space.status === 'occupied').length;
};

const getOverallOccupancyPercentage = () => {
    const total = getAllSpaces().length;
    const occupied = getOccupiedSpacesCount();
    return total > 0 ? Math.round((occupied / total) * 100) : 0;
};

const getSlotAvailabilityColor = (spaceType) => {
    const total = getTotalSpaces(spaceType);
    const available = getAvailableSpaces(spaceType);
    const availabilityPercentage = total > 0 ? (available / total) * 100 : 0;
    
    if (availabilityPercentage >= 60) {
        return 'bg-green-100'; // Many slots available - green
    } else if (availabilityPercentage >= 20) {
        return 'bg-yellow-100'; // Some slots available - yellow
    } else {
        return 'bg-red-100'; // Few/no slots available - red
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-4">
                        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Dashboard</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- PWA Status Indicators -->
                        <div class="flex items-center gap-2 text-sm">
                            <!-- Online/Offline Status -->
                            <div class="flex items-center gap-1">
                                <div :class="isOnline ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
                                <span class="text-gray-600">{{ isOnline ? 'Online' : 'Offline' }}</span>
                            </div>
                            
                            <!-- PWA Install Button -->
                            <button
                                v-if="isInstallable && !isInstalled"
                                @click="installPWA"
                                class="bg-indigo-500 hover:bg-indigo-700 text-white text-xs px-2 py-1 rounded"
                            >
                                Install App
                            </button>
                            
                            <!-- PWA Installed Indicator -->
                            <div v-if="isInstalled" class="flex items-center gap-1 text-indigo-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                </svg>
                                <span class="text-xs">App Installed</span>
                            </div>
                        </div>
                        
                        <Link
                            :href="route('customers.create')"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Customer
                        </Link>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.total_customers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Customers</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.active_customers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2L3 7v11a2 2 0 002 2h4v-6h2v6h4a2 2 0 002-2V7l-7-5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Inactive Customers</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.inactive_customers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Space Slots by Type (Admin only) -->
                <div v-if="($page.props.auth.user.role === 'admin' || $page.props.auth.can.admin_access) && spaceTypes && spaceTypes.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Space Slots</h3>
                            <Link
                                :href="route('space-management.index')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm"
                            >
                                Manage Slots
                            </Link>
                        </div>
                        
                        <!-- Space Type Slots Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                            <div 
                                v-for="spaceType in spaceTypes" 
                                :key="spaceType.id"
                                class="rounded-lg p-4 hover:shadow-sm transition-shadow"
                                :class="getSlotAvailabilityColor(spaceType)"
                            >
                                <div class="text-center">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">{{ spaceType.name }}</h4>
                                    <div class="text-2xl font-bold text-gray-900 mb-1">{{ getOccupancyFraction(spaceType) }}</div>
                                    <div class="text-sm text-gray-600 mb-2">slots occupied</div>
                                    <div class="text-sm text-gray-500">₱{{ spaceType.hourly_rate || spaceType.default_price }}/hr</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Search and Filter Bar -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <button class="p-2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search customers..."
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <span>#</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <span>Name</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Availed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <span>Status</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(customer, index) in customers.data" :key="customer.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ index + 1 + (customers.current_page - 1) * customers.per_page }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                                            <div class="text-sm text-gray-500">{{ customer.phone || customer.email }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ customer.service_type || 'No service selected' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(customer.status)}`">
                                            {{ customer.status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(customer.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ customer.service_start_time ? new Date(customer.service_start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₱{{ customer.amount_paid ? Number(customer.amount_paid).toLocaleString() : '0' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <Link
                                                :href="route('customers.show', customer.id)"
                                                class="text-blue-600 hover:text-blue-500"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                :href="route('customers.edit', customer.id)"
                                                class="text-indigo-600 hover:text-indigo-500"
                                            >
                                                Edit
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-700">
                                    {{ customers.from }}-{{ customers.to }} of {{ customers.total }} customers
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-700">Rows per page:</span>
                                <select class="border border-gray-300 rounded text-sm">
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                </select>
                                <div class="flex items-center space-x-1 ml-4">
                                    <button
                                        v-if="customers.prev_page_url"
                                        @click="router.get(customers.prev_page_url)"
                                        class="p-1 text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <span class="text-sm text-gray-700">{{ customers.current_page }}/{{ customers.last_page }}</span>
                                    <button
                                        v-if="customers.next_page_url"
                                        @click="router.get(customers.next_page_url)"
                                        class="p-1 text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
