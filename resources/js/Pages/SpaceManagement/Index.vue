<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import CustomerQuickCreateModal from '@/Components/CustomerQuickCreateModal.vue';

const props = defineProps({
    spaceTypes: Array,
    customers: Array,
});

const updatingPricing = ref(null);
const assigningSpace = ref(null);
const showCustomerModal = ref(false);
const selectedSpaceForAssignment = ref(null);
const showCreateCustomerForm = ref(false);
// Pricing modal state
const showPricingModalId = ref(null); // spaceTypeId or null
const pricingForm = ref({ hourly_rate: '', default_discount_hours: '', default_discount_percentage: '' });
const pricingErrors = ref({ hourly_rate: '', default_discount_hours: '', default_discount_percentage: '' });

// Local toast notifications
const toast = ref({ show: false, type: 'success', message: '' });
let toastTimerId = null;
const showToast = (message, type = 'success', duration = 3000) => {
    toast.value = { show: true, type, message };
    if (toastTimerId) clearTimeout(toastTimerId);
    toastTimerId = setTimeout(() => { toast.value.show = false; }, duration);
};

const hasCustomers = computed(() => props.customers && props.customers.length > 0);

const newCustomerForm = useForm({
    name: '',
    company_name: '',
    email: '',
    phone: '',
    status: 'active',
});

// Assignment form state
const assignment = ref({
    customer_id: null,
    start_time: new Date().toISOString().slice(0,16), // yyyy-MM-ddTHH:mm for datetime-local
    occupied_until: '',
    custom_hourly_rate: '',
});

// Global ticking ref to refresh countdowns periodically
const nowTick = ref(Date.now());
let tickIntervalId = null;

// Auto-release timers per space id
const releaseTimers = new Map();

const scheduleReleaseForSpace = (space) => {
    // Clear any existing timer for this space
    if (releaseTimers.has(space.id)) {
        clearTimeout(releaseTimers.get(space.id));
        releaseTimers.delete(space.id);
    }
    if (space.status !== 'occupied' || !space.occupied_until) return;
    const end = new Date(space.occupied_until).getTime();
    const now = Date.now();
    const delay = end - now;
    const fireIn = Math.max(0, delay);
    // Schedule a one-time release call at end time
    const timerId = setTimeout(() => {
        // Double-check condition before releasing
        if (space.status === 'occupied' && new Date(space.occupied_until).getTime() <= Date.now()) {
            router.patch(route('space-management.release-space', space.id), {}, { preserveState: false });
        }
        releaseTimers.delete(space.id);
    }, fireIn);
    releaseTimers.set(space.id, timerId);
};

const scheduleAllReleaseTimers = () => {
    // Clear all existing timers
    for (const [, id] of releaseTimers) clearTimeout(id);
    releaseTimers.clear();
    // Schedule for all spaces
    if (!props.spaceTypes) return;
    props.spaceTypes.forEach(st => {
        (st.spaces || []).forEach(scheduleReleaseForSpace);
    });
};

onMounted(() => {
    // Tick every 30s to refresh countdown text
    tickIntervalId = setInterval(() => { nowTick.value = Date.now(); }, 30000);
    scheduleAllReleaseTimers();
});

onBeforeUnmount(() => {
    if (tickIntervalId) clearInterval(tickIntervalId);
    for (const [, id] of releaseTimers) clearTimeout(id);
    releaseTimers.clear();
    if (toastTimerId) clearTimeout(toastTimerId);
});

// Reschedule timers when spaces update
watch(() => props.spaceTypes, () => {
    scheduleAllReleaseTimers();
}, { deep: true });

const customerSearch = ref('');
const filteredCustomers = computed(() => {
    if (!props.customers) return [];
    const q = customerSearch.value.toLowerCase().trim();
    if (!q) return props.customers;
    return props.customers.filter(c => [
        c.name,
        c.company_name,
        c.contact_person,
        c.email,
        c.phone
    ].filter(Boolean).some(v => String(v).toLowerCase().includes(q)));
});

const displayName = (c) => {
    return c?.name || c?.company_name || c?.contact_person || c?.email || `Customer #${c?.id}`;
};

const selectedCustomer = computed(() => {
    return filteredCustomers.value.find(c => c.id === assignment.value.customer_id) || props.customers?.find(c => c.id === assignment.value.customer_id) || null;
});

const confirmAssign = () => {
    if (!assignment.value.customer_id) return;
    assignToCustomer(assignment.value.customer_id);
};

const resetAssignment = () => {
    assignment.value = {
        customer_id: null,
        start_time: new Date().toISOString().slice(0,16),
        occupied_until: '',
        custom_hourly_rate: '',
    };
};

const updatePricing = (spaceTypeId, newPrice) => {
    updatingPricing.value = spaceTypeId;
    router.patch(route('space-management.update-pricing', spaceTypeId), {
        default_price: newPrice
    }, {
        onFinish: () => { updatingPricing.value = null; }
    });
};

const minDateTimeLocal = () => new Date(Date.now() - (new Date().getTimezoneOffset()*60000)).toISOString().slice(0,16);

const assignSpace = (spaceId) => {
    selectedSpaceForAssignment.value = spaceId;
    resetAssignment();
    if (hasCustomers.value) {
        showCustomerModal.value = true;
    } else {
        showCreateCustomerForm.value = true;
    }
};

const assignToCustomer = (customerId) => {
    assigningSpace.value = selectedSpaceForAssignment.value;
    
    const payload = {
        customer_id: customerId,
    };
    if (assignment.value.start_time) {
        if (new Date(assignment.value.start_time) < new Date()) {
            alert('Start time must be now or in the future.');
            return;
        }
        payload.start_time = assignment.value.start_time;
    }
    if (assignment.value.occupied_until) {
        if (new Date(assignment.value.occupied_until) < new Date()) {
            alert('End time must be in the future.');
            return;
        }
        payload.occupied_until = assignment.value.occupied_until;
    }
    if (assignment.value.custom_hourly_rate) payload.custom_hourly_rate = assignment.value.custom_hourly_rate;

    router.patch(route('space-management.assign-space', selectedSpaceForAssignment.value), payload, {
        preserveState: false,
        onFinish: () => {
            assigningSpace.value = null;
            selectedSpaceForAssignment.value = null;
            showCustomerModal.value = false;
            resetAssignment();
        }
    });
};

const switchToCreateForm = () => {
    showCustomerModal.value = false;
    showCreateCustomerForm.value = true;
};

const onCustomerCreated = () => {
    showCreateCustomerForm.value = false;
    // Reload customers and re-open the assignment modal
    router.reload({ 
        only: ['customers'],
        onSuccess: () => {
            showCustomerModal.value = true;
        }
    });
};

const submitNewCustomer = () => {
    newCustomerForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newCustomerForm.reset();
            showCreateCustomerForm.value = false;
            // Reload customers and re-open the assignment modal
            router.reload({ 
                only: ['customers'],
                onSuccess: () => {
                    showCustomerModal.value = true;
                }
            });
        },
    });
};

const releaseSpace = (spaceId) => {
    if (confirm('Are you sure you want to release this space?')) {
        router.patch(route('space-management.release-space', spaceId), {}, {
            preserveState: false, // Reload page to update calendar
        });
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

// Open modal populated with current values
const openPricingModal = (spaceType) => {
    showPricingModalId.value = spaceType.id;
    pricingForm.value = {
        hourly_rate: spaceType.hourly_rate ?? spaceType.default_price ?? '',
        default_discount_hours: spaceType.default_discount_hours ?? '',
        default_discount_percentage: spaceType.default_discount_percentage ?? '',
    };
    pricingErrors.value = { hourly_rate: '', default_discount_hours: '', default_discount_percentage: '' };
};

const closePricingModal = () => {
    showPricingModalId.value = null;
};

const validatePricing = () => {
    pricingErrors.value = { hourly_rate: '', default_discount_hours: '', default_discount_percentage: '' };
    const v = pricingForm.value || {};
    let ok = true;
    const rate = v.hourly_rate;
    const hours = v.default_discount_hours;
    const percent = v.default_discount_percentage;

    if (rate === '' || isNaN(rate) || Number(rate) < 0) {
        pricingErrors.value.hourly_rate = 'Rate must be a number greater than or equal to 0.';
        ok = false;
    }

    // Discount fields are optional, but when provided must be valid and both should be set
    const hasHours = hours !== '' && hours !== null && hours !== undefined;
    const hasPercent = percent !== '' && percent !== null && percent !== undefined;

    if (hasHours) {
        if (isNaN(hours) || Number(hours) < 1) {
            pricingErrors.value.default_discount_hours = 'Discount starts after at least 1 hour.';
            ok = false;
        }
    }
    if (hasPercent) {
        if (isNaN(percent) || Number(percent) < 0 || Number(percent) > 100) {
            pricingErrors.value.default_discount_percentage = 'Discount percent must be between 0 and 100.';
            ok = false;
        }
    }
    if (hasHours !== hasPercent) {
        if (!hasHours) pricingErrors.value.default_discount_hours = 'Provide hours when setting a discount percent.';
        if (!hasPercent) pricingErrors.value.default_discount_percentage = 'Provide a percent when setting discount hours.';
        ok = false;
    }

    return ok;
};

const savePricingModal = () => {
    const id = showPricingModalId.value;
    if (!id) return;
    if (!validatePricing()) return;
    const val = pricingForm.value || {};
    updatingPricing.value = id;
    const payload = {
        hourly_rate: Number(val.hourly_rate),
        default_discount_hours: val.default_discount_hours === '' ? null : Number(val.default_discount_hours),
        default_discount_percentage: val.default_discount_percentage === '' ? null : Number(val.default_discount_percentage),
    };
    router.patch(route('space-management.update-pricing', id), payload, {
        preserveScroll: true,
        onError: (errors) => {
            // Map server-side validation errors (422) to inline errors
            pricingErrors.value.hourly_rate = errors?.hourly_rate ?? pricingErrors.value.hourly_rate;
            pricingErrors.value.default_discount_hours = errors?.default_discount_hours ?? pricingErrors.value.default_discount_hours;
            pricingErrors.value.default_discount_percentage = errors?.default_discount_percentage ?? pricingErrors.value.default_discount_percentage;
            showToast('Failed to save pricing. Please check the form and try again.', 'error');
            updatingPricing.value = null;
        },
        onSuccess: () => {
            updatingPricing.value = null;
            closePricingModal();
            showToast('Pricing updated successfully.', 'success');
            // Refresh only the spaceTypes list so new values are reflected
            router.reload({ only: ['spaceTypes'] });
        },
    });
};

const getTimeUntilFree = (space) => {
    if (!space.occupied_until) return null;
    // Reference nowTick to make this reactive over time
    void nowTick.value;
    const until = new Date(space.occupied_until);
    const now = new Date(nowTick.value);
    
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

// Removed inline edit state in favor of modal

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

watch(() => props.customers, () => {
    // no-op: selectedCustomer is computed; simply ensures reactivity chain runs
});

const refreshCustomers = () => {
    router.reload({ only: ['customers'] });
};

// --- Create/extend Space Type (batch sub spaces) ---
const showCreateType = ref(false);
const createTypeForm = useForm({
    name: '',
    description: '',
    hourly_rate: '',
    default_discount_hours: '',
    default_discount_percentage: '',
    initial_slots: 1,
});
const submitCreateType = () => {
    createTypeForm.post(route('space-management.store-space-type'), {
        preserveScroll: true,
        onSuccess: () => {
            createTypeForm.reset();
            createTypeForm.initial_slots = 1;
            showCreateType.value = false;
        }
    });
};

// --- Add a single space under a space type ---
const showAddSpace = ref({}); // keyed by spaceTypeId -> bool
const addSpaceValues = ref({}); // keyed by spaceTypeId -> { name, hourly_rate, discount_hours, discount_percentage }
const toggleAddSpace = (spaceTypeId) => {
    showAddSpace.value[spaceTypeId] = !showAddSpace.value[spaceTypeId];
    if (showAddSpace.value[spaceTypeId] && !addSpaceValues.value[spaceTypeId]) {
        addSpaceValues.value[spaceTypeId] = { name: '', hourly_rate: '', discount_hours: '', discount_percentage: '' };
    }
};
const submittingAddSpace = ref({}); // keyed by spaceTypeId -> bool
const submitAddSpace = (spaceTypeId) => {
    const payload = { ...addSpaceValues.value[spaceTypeId] };
    submittingAddSpace.value[spaceTypeId] = true;
    router.post(route('space-management.store-space', spaceTypeId), payload, {
        preserveScroll: true,
        onFinish: () => { submittingAddSpace.value[spaceTypeId] = false; },
        onSuccess: () => {
            addSpaceValues.value[spaceTypeId] = { name: '', hourly_rate: '', discount_hours: '', discount_percentage: '' };
            showAddSpace.value[spaceTypeId] = false;
        }
    });
};

// --- Delete single space and bulk remove subspaces ---
const deletingSpace = ref({}); // keyed by spaceId -> bool
const deleteSpace = (space) => {
    if (space.status !== 'available') {
        alert('Release the space before deleting.');
        return;
    }
    if (!confirm(`Permanently delete ${space.name}? This cannot be undone.`)) return;
    deletingSpace.value[space.id] = true;
    router.delete(route('space-management.destroy-space', space.id), {
        preserveScroll: true,
        onFinish: () => { deletingSpace.value[space.id] = false; }
    });
};

const bulkRemoving = ref({}); // keyed by spaceTypeId -> bool
const bulkRemoveCount = ref({}); // keyed by spaceTypeId -> number
const bulkRemove = (spaceType) => {
    const count = parseInt(bulkRemoveCount.value[spaceType.id] || 1, 10);
    if (!count || count < 1) return;
    if (!confirm(`Remove ${count} available space(s) from ${spaceType.name}? This cannot be undone.`)) return;
    bulkRemoving.value[spaceType.id] = true;
    router.delete(route('space-management.bulk-destroy-spaces', spaceType.id), {
        data: { count },
        preserveScroll: true,
        onFinish: () => { bulkRemoving.value[spaceType.id] = false; }
    });
};

// --- Delete entire space type ---
const deletingType = ref({}); // keyed by spaceTypeId -> bool
const deleteSpaceType = (spaceType) => {
    if (!confirm(`Delete the entire space type "${spaceType.name}"? All its spaces will be removed. This cannot be undone.`)) return;
    deletingType.value[spaceType.id] = true;
    router.delete(route('space-management.destroy-space-type', spaceType.id), {
        preserveScroll: true,
        onFinish: () => { deletingType.value[spaceType.id] = false; }
    });
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

                <!-- Create/Extend Space Type -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Create or Extend Space Type</h3>
                            <button
                                @click="showCreateType = !showCreateType"
                                class="text-sm px-3 py-1 rounded border border-gray-300 hover:bg-gray-50"
                            >
                                {{ showCreateType ? 'Hide' : 'Open' }}
                            </button>
                        </div>
                        <div v-if="showCreateType" class="mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700">Type Name</label>
                                    <input v-model="createTypeForm.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="e.g., CAR, BIKE" />
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-xs font-medium text-gray-700">Rate (₱/h)</label>
                                    <input v-model.number="createTypeForm.hourly_rate" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Disc. After (h)</label>
                                    <input v-model.number="createTypeForm.default_discount_hours" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Discount (%)</label>
                                    <input v-model.number="createTypeForm.default_discount_percentage" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Initial Slots</label>
                                    <input v-model.number="createTypeForm.initial_slots" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs font-medium text-gray-700">Description (optional)</label>
                                    <input v-model="createTypeForm.description" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Short description" />
                                </div>
                                <div class="md:col-span-6 flex justify-end">
                                    <button @click="submitCreateType" :disabled="createTypeForm.processing" class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white text-sm py-2 px-4 rounded">
                                        {{ createTypeForm.processing ? 'Saving…' : 'Save Type & Create Slots' }}
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-xxs text-gray-500">Tip: If a type with the same name exists, this will add the specified number of slots to it.</p>
                            <div v-if="createTypeForm.errors && Object.keys(createTypeForm.errors).length" class="mt-3 text-xs text-red-600">
                                <div v-for="(msg, key) in createTypeForm.errors" :key="key">• {{ msg }}</div>
                            </div>
                        </div>
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
            <div class="p-6">                            <div class="flex items-center justify-between gap-4 mb-6 flex-nowrap">
                <h3 class="text-xl font-semibold text-gray-900 whitespace-nowrap truncate flex-1 min-w-0">{{ spaceType.name }}</h3>
                                
                                <!-- Actions -->
                                <div class="ml-auto flex flex-row items-center gap-3 flex-none">
                                    <button @click="openPricingModal(spaceType)" class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700">Edit Rate & Discount</button>
                                    <button @click="toggleAddSpace(spaceType.id)" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50 whitespace-nowrap">{{ showAddSpace[spaceType.id] ? 'Cancel' : 'Add Space' }}</button>
                                    <div class="flex items-center gap-2 flex-none">
                                        <label class="text-xs text-gray-600">Remove</label>
                                        <input type="number" min="1" :max="getAvailableSpaces(spaceType)" class="w-16 px-2 py-1 border border-gray-300 rounded text-xs" v-model.number="bulkRemoveCount[spaceType.id]" placeholder="1" />
                                        <button @click="bulkRemove(spaceType)" :disabled="bulkRemoving[spaceType.id] || !getAvailableSpaces(spaceType)" class="text-xs px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50 whitespace-nowrap">{{ bulkRemoving[spaceType.id] ? 'Removing…' : 'Remove Available' }}</button>
                                    </div>
                                    <button @click="deleteSpaceType(spaceType)" :disabled="deletingType[spaceType.id]" class="text-xs px-3 py-1.5 rounded border border-red-300 text-red-700 hover:bg-red-50 whitespace-nowrap">
                                        {{ deletingType[spaceType.id] ? 'Deleting…' : 'Delete Type' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Add Space Form -->
                            <div v-if="showAddSpace[spaceType.id]" class="mb-4 p-3 rounded border border-gray-200 bg-gray-50">
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Name (optional)</label>
                                        <input v-model="addSpaceValues[spaceType.id].name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" :placeholder="spaceType.name + ' ' + (spaceType.spaces.length + 1)" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Rate (₱/h)</label>
                                        <input v-model.number="addSpaceValues[spaceType.id].hourly_rate" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Use default" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Disc. After (h)</label>
                                        <input v-model.number="addSpaceValues[spaceType.id].discount_hours" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Use default" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Discount (%)</label>
                                        <input v-model.number="addSpaceValues[spaceType.id].discount_percentage" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Use default" />
                                    </div>
                                    <div class="flex md:justify-end">
                                        <button @click="submitAddSpace(spaceType.id)" :disabled="submittingAddSpace[spaceType.id]" class="self-end bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white text-sm py-2 px-4 rounded w-full md:w-auto">{{ submittingAddSpace[spaceType.id] ? 'Adding…' : 'Create Space' }}</button>
                                    </div>
                                </div>
                                <p class="mt-2 text-xxs text-gray-500">Leave fields blank to inherit from this type.</p>
                            </div>

                            <!-- Individual Spaces Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <div 
                                    v-for="space in spaceType.spaces" 
                                    :key="space.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow overflow-hidden"
                                ><div class="flex justify-between items-start mb-3">
                                        <h4 class="font-medium text-gray-900 whitespace-nowrap truncate max-w-[60%]">{{ space.name }}</h4>
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
                                            class="relative flex-1 bg-red-500 hover:bg-red-600 text-white text-xs py-1 px-2 rounded"
                                        >
                                            Release
                                            <span v-if="space.occupied_until" class="absolute -top-2 -right-2 bg-gray-800 text-white text-xxs px-1 rounded-full">
                                                {{ getTimeUntilFree(space) }}
                                            </span>
                                        </button>

                                        <button
                                            v-if="space.status === 'available'"
                                            @click="deleteSpace(space)"
                                            class="flex-1 bg-white border border-red-300 text-red-700 hover:bg-red-50 text-xs py-1 px-2 rounded"
                                            :disabled="deletingSpace[space.id]"
                                        >
                                            {{ deletingSpace[space.id] ? 'Deleting…' : 'Delete' }}
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
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Assign Customer to Space</h3>
                                            <button @click="refreshCustomers" class="text-xs text-blue-600 hover:text-blue-700">Refresh list</button>
                                        </div>

                                        <!-- Scheduling & Rate -->
                                        <div class="mb-4 grid grid-cols-1 gap-3">
                                            <div class="flex items-start gap-2">
                                                <div class="flex-1">
                                                    <label class="block text-xs font-medium text-gray-700">Start</label>
                                                    <input type="datetime-local" :min="minDateTimeLocal()" v-model="assignment.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                                </div>
                                                <div class="flex-1">
                                                    <label class="block text-xs font-medium text-gray-700">End</label>
                                                    <input type="datetime-local" :min="assignment.start_time || minDateTimeLocal()" v-model="assignment.occupied_until" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Custom Hourly Rate (optional)</label>
                                                <div class="relative mt-1">
                                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500">₱</span>
                                                    <input type="number" step="0.01" min="0" v-model="assignment.custom_hourly_rate" class="pl-6 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Use default if empty" />
                                                </div>
                                                <p class="mt-1 text-xxs text-gray-500 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                                                    Leave blank to use the space or space-type default rate. Discounts apply after configured hours.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Customer selection -->
                                        <div class="space-y-2">
                                            <label class="block text-xs font-medium text-gray-700">Select Customer</label>
                                            <input type="text" v-model="customerSearch" placeholder="Search name, company, email, phone" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" />
                                            <select v-model="assignment.customer_id" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm text-sm" :aria-label="'Customer select, '+filteredCustomers.length+' results'">
                                                <option :value="null" disabled>Select a customer…</option>
                                                <option v-for="c in filteredCustomers" :key="c.id" :value="c.id">
                                                    {{ displayName(c) }} — {{ c.company_name || 'No company' }} ({{ c.email }}{{ c.phone ? ', '+c.phone : '' }})
                                                </option>
                                            </select>
                                            <div v-if="selectedCustomer" class="mt-3 p-3 rounded border border-gray-200 bg-gray-50 text-sm">
                                                <div class="font-medium text-gray-900">{{ displayName(selectedCustomer) }}</div>
                                                <div class="text-gray-700">{{ selectedCustomer.company_name || '—' }}</div>
                                                <div class="text-gray-600">{{ selectedCustomer.contact_person || '—' }}</div>
                                                <div class="text-gray-500">{{ selectedCustomer.email }} • {{ selectedCustomer.phone || '—' }}</div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row gap-2 sm:justify-between">
                                            <button
                                                @click="switchToCreateForm"
                                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded inline-flex items-center justify-center"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Add New Customer
                                            </button>
                                            <button
                                                :disabled="!assignment.customer_id"
                                                @click="confirmAssign"
                                                class="flex-1 bg-blue-500 disabled:bg-blue-300 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded"
                                            >
                                                Assign
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
                </div>

                <!-- Create Customer Form Modal (shared component) -->
                <CustomerQuickCreateModal :show="showCreateCustomerForm" @close="showCreateCustomerForm = false" @created="onCustomerCreated" />

                <!-- Pricing Modal -->
                <div v-if="showPricingModalId !== null" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closePricingModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Rate & Discount</h3>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Rate (₱/h)</label>
                                                <input 
                                                    v-model.number="pricingForm.hourly_rate" 
                                                    type="number" min="0" step="0.01" 
                                                    class="mt-1 block w-full rounded-md shadow-sm text-sm border"
                                                    :class="pricingErrors.hourly_rate ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300'"
                                                />
                                                <p v-if="pricingErrors.hourly_rate" class="mt-1 text-xxs text-red-600">{{ pricingErrors.hourly_rate }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700">Disc. After (h)</label>
                                                    <input 
                                                        v-model.number="pricingForm.default_discount_hours" 
                                                        type="number" min="1" 
                                                        class="mt-1 block w-full rounded-md shadow-sm text-sm border"
                                                        :class="pricingErrors.default_discount_hours ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300'"
                                                    />
                                                    <p v-if="pricingErrors.default_discount_hours" class="mt-1 text-xxs text-red-600">{{ pricingErrors.default_discount_hours }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700">Discount (%)</label>
                                                    <input 
                                                        v-model.number="pricingForm.default_discount_percentage" 
                                                        type="number" min="0" max="100" step="0.01" 
                                                        class="mt-1 block w-full rounded-md shadow-sm text-sm border"
                                                        :class="pricingErrors.default_discount_percentage ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300'"
                                                    />
                                                    <p v-if="pricingErrors.default_discount_percentage" class="mt-1 text-xxs text-red-600">{{ pricingErrors.default_discount_percentage }}</p>
                                                </div>
                                            </div>
                                            <p class="text-xxs text-gray-500">Applies to future reservations. Ongoing assignments retain their current rate.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button @click="savePricingModal" :disabled="updatingPricing === showPricingModalId" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                    {{ updatingPricing === showPricingModalId ? 'Saving…' : 'Save' }}
                                </button>
                                <button @click="closePricingModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toasts -->
                <div v-if="toast.show" class="fixed top-4 right-4 z-[60]">
                    <div 
                        class="px-4 py-3 rounded shadow border text-sm"
                        :class="toast.type === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'"
                    >
                        {{ toast.message }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
