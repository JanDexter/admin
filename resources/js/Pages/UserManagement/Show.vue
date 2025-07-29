<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
