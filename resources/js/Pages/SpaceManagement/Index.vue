<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    spaceTypes: Array,
    customers: Array,
});

const updatingPricing = ref(null);
const assigningSpace = ref(null);
const showCustomerModal = ref(false);
const selectedSpaceForAssignment = ref(null);
const showCreateCustomerModal = ref(false);
const editingPricing = ref({});

const updatePricing = (spaceTypeId, newPrice) => {
    updatingPricing.value = spaceTypeId;
    
    router.patch(route('space-management.update-pricing', spaceTypeId), {
        default_price: newPrice
    }, {
        onFinish: () => {
            updatingPricing.value = null;
        }
    });
};

const assignSpace = (spaceId) => {
    selectedSpaceForAssignment.value = spaceId;
    showCustomerModal.value = true;
};

const assignToCustomer = (customerId) => {
    if (!selectedSpaceForAssignment.value) return;
    
    assigningSpace.value = selectedSpaceForAssignment.value;
    
    router.patch(route('space-management.assign-space', selectedSpaceForAssignment.value), {
        customer_id: customerId
    }, {
        onFinish: () => {
            assigningSpace.value = null;
            selectedSpaceForAssignment.value = null;
            showCustomerModal.value = false;
        }
    });
};

const openCreateCustomerModal = () => {
    showCustomerModal.value = false;
    showCreateCustomerModal.value = true;
};

const releaseSpace = (spaceId) => {
    if (confirm('Are you sure you want to release this space?')) {
        router.patch(route('space-management.release-space', spaceId));
    }
};

const getStatusColor = (status) => {
    return status === 'available' 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800';
};

const getTotalSpaces = (spaceType) => {
    return spaceType.spaces.length;
};

const getOccupiedSpaces = (spaceType) => {
    return spaceType.spaces.filter(space => space.status === 'occupied').length;
};

const getAvailableSpaces = (spaceType) => {
    return spaceType.spaces.filter(space => space.status === 'available').length;
};

const getOccupancyFraction = (spaceType) => {
    const occupied = getOccupiedSpaces(spaceType);
    const total = getTotalSpaces(spaceType);
    return `${occupied}/${total}`;
};

const getNextAvailableTime = (spaceType) => {
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

const updatePricingFields = (spaceTypeId, field, value) => {
    updatingPricing.value = spaceTypeId;
    
    router.patch(route('space-management.update-pricing', spaceTypeId), {
        [field]: value
    }, {
        onFinish: () => {
            updatingPricing.value = null;
        }
    });
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

const toggleEditPricing = (spaceTypeId) => {
    editingPricing.value[spaceTypeId] = !editingPricing.value[spaceTypeId];
};

const savePricing = (spaceTypeId, field, value) => {
    updatePricingFields(spaceTypeId, field, value);
    editingPricing.value[spaceTypeId] = false;
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
    <Head title="Space Management" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header matching Dashboard style -->
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-4">
                        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Space Management</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('dashboard')"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded text-sm"
                        >
                            ← Back to Dashboard
                        </Link>
                    </div>
                </div>

                <!-- Space Slots Management (aligned with statistics cards) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Space Slots</h3>
                        
                        <!-- Space Type Slots Grid - Same styling as Dashboard -->
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

                <!-- Detailed Space Management -->
                <div class="space-y-8">
                    <div v-for="spaceType in spaceTypes" :key="spaceType.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">                            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6 gap-4">
                                <h3 class="text-xl font-semibold text-gray-900">{{ spaceType.name }}</h3>
                                
                                <!-- Pricing Controls - Responsive Layout -->
                                <div class="flex flex-col md:flex-row items-start md:items-center gap-3 md:gap-4">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-gray-600 whitespace-nowrap">Rate:</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            :value="spaceType.hourly_rate || spaceType.default_price"
                                            @blur="updatePricingFields(spaceType.id, 'hourly_rate', $event.target.value)"
                                            @keyup.enter="updatePricingFields(spaceType.id, 'hourly_rate', $event.target.value)"
                                            class="w-20 px-2 py-1 border border-gray-300 rounded text-xs"
                                            :disabled="updatingPricing === spaceType.id"
                                        />
                                        <span class="text-xs text-gray-600">₱/h</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-gray-600 whitespace-nowrap">Discount after:</label>
                                        <input 
                                            type="number" 
                                            :value="spaceType.default_discount_hours"
                                            @blur="updatePricingFields(spaceType.id, 'default_discount_hours', $event.target.value)"
                                            @keyup.enter="updatePricingFields(spaceType.id, 'default_discount_hours', $event.target.value)"
                                            class="w-16 px-2 py-1 border border-gray-300 rounded text-xs"
                                            :disabled="updatingPricing === spaceType.id"
                                        />
                                        <span class="text-xs text-gray-600">h</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-gray-600">Discount:</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            :value="spaceType.default_discount_percentage"
                                            @blur="updatePricingFields(spaceType.id, 'default_discount_percentage', $event.target.value)"
                                            @keyup.enter="updatePricingFields(spaceType.id, 'default_discount_percentage', $event.target.value)"
                                            class="w-16 px-2 py-1 border border-gray-300 rounded text-xs"
                                            :disabled="updatingPricing === spaceType.id"
                                        />
                                        <span class="text-xs text-gray-600">%</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="toggleEditPricing(spaceType.id)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white text-xs py-1 px-3 rounded whitespace-nowrap"
                                            :disabled="updatingPricing === spaceType.id"
                                        >
                                            {{ editingPricing[spaceType.id] ? 'Save' : 'Edit' }}
                                        </button>
                                        
                                        <span v-if="updatingPricing === spaceType.id" class="text-xs text-blue-600 whitespace-nowrap">Updating...</span>
                                    </div>
                                </div>
                            </div>                            <!-- Individual Spaces Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <div 
                                    v-for="space in spaceType.spaces" 
                                    :key="space.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow overflow-hidden"
                                ><div class="flex justify-between items-start mb-3">
                                        <h4 class="font-medium text-gray-900">{{ space.name }}</h4>
                                        <div class="text-right">
                                            <span 
                                                :class="getStatusColor(space.status)"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mb-1"
                                            >
                                                {{ space.status.toUpperCase() }}
                                            </span>
                                            <div v-if="space.status === 'occupied' && space.occupied_until" class="text-xs text-gray-500">
                                                Free in: {{ getTimeUntilFree(space) }}
                                            </div>
                                        </div>
                                    </div>                                    <!-- Space Details -->
                                    <div v-if="space.status === 'occupied' && space.current_customer" class="mb-3">
                                        <p class="text-sm text-gray-600">Occupied by:</p>
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ space.current_customer.company_name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ space.current_customer.contact_person }}</p><div class="flex flex-col gap-1 text-xs text-gray-500 mt-1">
                                            <span v-if="space.occupied_from" class="truncate">
                                                From: {{ new Date(space.occupied_from).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                            </span>
                                            <span v-if="space.occupied_until" class="truncate">
                                                Until: {{ new Date(space.occupied_until).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Pricing Information -->
                                    <div v-if="space.status === 'available'" class="mb-3 p-2 bg-green-50 rounded">
                                        <p class="text-xs text-green-700 font-medium">Available for booking</p>
                                        <p class="text-xs text-green-600">
                                            ₱{{ space.hourly_rate || spaceType.hourly_rate || spaceType.default_price }}/hour
                                        </p>
                                        <p v-if="spaceType.default_discount_hours" class="text-xs text-green-600">
                                            {{ spaceType.default_discount_percentage }}% off after {{ spaceType.default_discount_hours }}h
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <button
                                            v-if="space.status === 'available'"
                                            @click="assignSpace(space.id)"
                                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-xs py-1 px-2 rounded"
                                            :disabled="assigningSpace === space.id"
                                        >
                                            {{ assigningSpace === space.id ? 'Assigning...' : 'Assign' }}
                                        </button>
                                        
                                        <button
                                            v-if="space.status === 'occupied'"
                                            @click="releaseSpace(space.id)"
                                            class="flex-1 bg-red-500 hover:bg-red-600 text-white text-xs py-1 px-2 rounded"
                                        >
                                            Release
                                        </button>
                                    </div>
                                </div>                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Assignment Modal -->
                <div v-if="showCustomerModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showCustomerModal = false"></div>
                        
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                            Assign Customer to Space
                                        </h3>
                                        
                                        <div class="space-y-3 max-h-60 overflow-y-auto">
                                            <div 
                                                v-for="customer in customers" 
                                                :key="customer.id"
                                                @click="assignToCustomer(customer.id)"
                                                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                                            >
                                                <div class="font-medium text-gray-900">{{ customer.company_name }}</div>
                                                <div class="text-sm text-gray-500">{{ customer.contact_person }}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <button
                                                @click="openCreateCustomerModal"
                                                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-flex items-center justify-center"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Add New Customer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button 
                                    @click="showCustomerModal = false"
                                    type="button" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>                <!-- Create Customer Modal -->
                <div v-if="showCreateCustomerModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showCreateCustomerModal = false"></div>
                        
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Customer</h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    Please visit the 
                                    <Link :href="route('customers.create')" class="text-blue-600 hover:text-blue-500 underline">
                                        Add Customer page
                                    </Link>
                                    to create a new customer. After creating the customer, return here to assign them to the space.
                                </p>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <Link
                                    :href="route('customers.create')"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Go to Add Customer
                                </Link>
                                <button 
                                    @click="showCreateCustomerModal = false"
                                    type="button" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
