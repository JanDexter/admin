<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';

const props = defineProps({
    spaceTypes: Array,
});

const selectedSpaceType = ref(null);
const availableSpaces = ref([]);

const form = useForm({
    name: '',
    company_name: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    status: 'active',
    space_type_id: '',
    amount_paid: 0,
    notes: '',
});

// Watch for space type changes to update available spaces
watch(() => form.space_type_id, (newSpaceTypeId) => {
    if (newSpaceTypeId) {
        selectedSpaceType.value = props.spaceTypes.find(st => st.id == newSpaceTypeId);
        availableSpaces.value = selectedSpaceType.value?.spaces || [];
    } else {
        selectedSpaceType.value = null;
        availableSpaces.value = [];
    }
});

const submit = () => {
    form.post(route('customers.store'));
};

const onPhoneInput = (e) => {
    let v = e.target.value.replace(/\D/g, '');
    if (/^63(9\d{9})$/.test(v)) v = '0' + RegExp.$1;
    if (/^9\d{9}$/.test(v)) v = '0' + v;
    if (v.length > 11) v = v.slice(0, 11);
    form.phone = v;
};
const phoneError = computed(() => {
    if (!form.phone) return '';
    return /^09\d{9}$/.test(form.phone) ? '' : 'Phone must start with 09 and be 11 digits.';
});
</script>

<template>
    <Head title="Add Customer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Customer</h2>
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
                                <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                                <input
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.company_name" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.company_name }}
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
                                    @input="onPhoneInput"
                                    inputmode="numeric"
                                    pattern="09\\d{9}"
                                    maxlength="11"
                                    placeholder="09XXXXXXXXX"
                                    type="tel"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.phone || phoneError" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.phone || phoneError }}
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

                            <!-- Space Type and Amount Paid Section -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Details</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Space Type Selection -->
                                    <div>
                                        <label for="space_type_id" class="block text-sm font-medium text-gray-700">Space Type</label>
                                        <select
                                            id="space_type_id"
                                            v-model="form.space_type_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">Select a space type</option>
                                            <option v-for="spaceType in spaceTypes" :key="spaceType.id" :value="spaceType.id">
                                                {{ spaceType.name }} - ₱{{ spaceType.default_price }} ({{ spaceType.spaces.length }} available)
                                            </option>
                                        </select>
                                        <div v-if="form.errors.space_type_id" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.space_type_id }}
                                        </div>
                                    </div>

                                    <!-- Specific Space Selection -->
                                    <div v-if="availableSpaces.length > 0" class="text-sm text-gray-600">
                                        <p>{{ availableSpaces.length }} spaces available in this category</p>
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
                                    <span v-if="form.processing">Creating...</span>
                                    <span v-else>Create Customer</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>