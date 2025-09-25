<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    totalSpent: Number,
    points: Number,
});

const getRoleColor = (role) => {
    const colors = {
        admin: 'bg-red-100 text-red-800',
        staff: 'bg-blue-100 text-blue-800',
        customer: 'bg-green-100 text-green-800',
    };
    return colors[role.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const getStatusColor = (isActive) => {
    return isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="User Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Details: {{ user.name }}</h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('user-management.edit', user.id)"
                        class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Edit User
                    </Link>
                    <Link
                        :href="route('user-management.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Basic Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                        <dd class="text-sm text-gray-900">{{ user.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                        <dd class="text-sm text-gray-900">{{ user.email }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">User ID</dt>
                                        <dd class="text-sm text-gray-900">#{{ user.id }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Role & Status -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Role & Status</h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Role</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleColor(user.role)}`">
                                                {{ user.role.toUpperCase() }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(user.is_active)}`">
                                                {{ user.is_active ? 'ACTIVE' : 'INACTIVE' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Email Verified</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span v-if="user.email_verified_at" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                VERIFIED
                                            </span>
                                            <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                NOT VERIFIED
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Account Timestamps -->
                            <div class="md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Account History</h3>
                                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                                        <dd class="text-sm text-gray-900">{{ formatDate(user.created_at) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd class="text-sm text-gray-900">{{ formatDate(user.updated_at) }}</dd>
                                    </div>
                                    <div v-if="user.email_verified_at">
                                        <dt class="text-sm font-medium text-gray-500">Email Verified</dt>
                                        <dd class="text-sm text-gray-900">{{ formatDate(user.email_verified_at) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Permissions Summary -->
                            <div class="md:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Permissions</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div v-if="user.role === 'admin'" class="text-sm text-gray-700">
                                        <p class="font-medium text-red-600 mb-2">Administrator Access</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Full system administration</li>
                                            <li>User management</li>
                                            <li>Customer management</li>
                                            <li>Service management</li>
                                            <li>System configuration</li>
                                        </ul>
                                    </div>
                                    <div v-else-if="user.role === 'staff'" class="text-sm text-gray-700">
                                        <p class="font-medium text-blue-600 mb-2">Staff Access</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Customer management</li>
                                            <li>Service management</li>
                                            <li>View system reports</li>
                                            <li>Update customer records</li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm text-gray-700">
                                        <p class="font-medium text-green-600 mb-2">Customer Access</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>View own profile</li>
                                            <li>Update personal information</li>
                                            <li>Access customer portal</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservation History -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Reservation History</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Total Spent</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">₱{{ totalSpent.toLocaleString() }}</dd>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Reward Points</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ points }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Space</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="reservation in user.reservations" :key="reservation.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ reservation.space.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ reservation.customer.company_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(reservation.start_time) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(reservation.end_time).getHours() - new Date(reservation.start_time).getHours() }} hours
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱{{ reservation.cost }}</td>
                                </tr>
                                <tr v-if="!user.reservations.length">
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No reservations found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
