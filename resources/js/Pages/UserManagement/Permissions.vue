<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    user: Object,
    allPermissions: Object,
    currentPermissions: Array,
    presets: Object,
    roles: Array,
});

const selectedRoles = ref([...props.roles]);
const selectedPermissions = ref([...props.currentPermissions]);
const adminLevel = ref(props.user.admin?.permission_level || 'admin');
const showPresetModal = ref(false);

const canBookSpaces = computed(() => {
    return selectedRoles.value.includes('customer');
});

const isAdminSelected = computed(() => {
    return selectedRoles.value.includes('admin');
});

const updatePermissions = () => {
    router.put(route('user-permissions.update', props.user.id), {
        roles: selectedRoles.value,
        admin_level: adminLevel.value,
        permissions: selectedPermissions.value,
    });
};

const applyPreset = (presetType) => {
    router.post(route('user-permissions.apply-preset', props.user.id), {
        preset: presetType,
    });
    showPresetModal.value = false;
};

const togglePermission = (permission) => {
    const index = selectedPermissions.value.indexOf(permission);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(permission);
    }
};

const hasPermission = (permission) => {
    return selectedPermissions.value.includes(permission) || selectedPermissions.value.includes('*');
};

const toggleRole = (role) => {
    const index = selectedRoles.value.indexOf(role);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else {
        selectedRoles.value.push(role);
    }
};

const formatCategory = (category) => {
    return category.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <Head title="Manage User Permissions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manage Permissions: {{ user.name }}
                </h2>
                <button
                    @click="showPresetModal = true"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Apply Preset
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- User Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">User Information</h3>
                            <p class="text-sm text-gray-600">{{ user.email }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                v-for="role in roles"
                                :key="role"
                                class="px-3 py-1 rounded-full text-xs font-semibold"
                                :class="{
                                    'bg-red-100 text-red-800': role === 'admin',
                                    'bg-blue-100 text-blue-800': role === 'staff',
                                    'bg-green-100 text-green-800': role === 'customer',
                                }"
                            >
                                {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                            </span>
                        </div>
                    </div>

                    <!-- Booking Status Warning -->
                    <div v-if="!canBookSpaces && (selectedRoles.includes('admin') || selectedRoles.includes('staff'))" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Note:</strong> This admin/staff user cannot book spaces. Add the "Customer" role to enable booking.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">User Roles</h3>
                    <p class="text-sm text-gray-600 mb-4">Select which roles this user should have. Users can have multiple roles.</p>
                    
                    <div class="space-y-3">
                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                value="admin"
                                :checked="selectedRoles.includes('admin')"
                                @change="toggleRole('admin')"
                                class="mt-1 rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Administrator</span>
                                <p class="text-xs text-gray-500">Full system access and management capabilities</p>
                            </div>
                        </label>

                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                value="staff"
                                :checked="selectedRoles.includes('staff')"
                                @change="toggleRole('staff')"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Staff Member</span>
                                <p class="text-xs text-gray-500">Operational access for front desk and customer service</p>
                            </div>
                        </label>

                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                value="customer"
                                :checked="selectedRoles.includes('customer')"
                                @change="toggleRole('customer')"
                                class="mt-1 rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Customer</span>
                                <p class="text-xs text-gray-500">Can book spaces and manage reservations</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Admin Level Selection (only if admin role selected) -->
                <div v-if="isAdminSelected" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Level</h3>
                    
                    <div class="space-y-3">
                        <label class="flex items-start">
                            <input
                                type="radio"
                                value="super_admin"
                                v-model="adminLevel"
                                class="mt-1 border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Super Administrator</span>
                                <p class="text-xs text-gray-500">All permissions (cannot be restricted)</p>
                            </div>
                        </label>

                        <label class="flex items-start">
                            <input
                                type="radio"
                                value="admin"
                                v-model="adminLevel"
                                class="mt-1 border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Administrator</span>
                                <p class="text-xs text-gray-500">Customizable permissions</p>
                            </div>
                        </label>

                        <label class="flex items-start">
                            <input
                                type="radio"
                                value="moderator"
                                v-model="adminLevel"
                                class="mt-1 border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
                            />
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-700">Moderator</span>
                                <p class="text-xs text-gray-500">Limited administrative access</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Permission Management (only if admin and not super_admin) -->
                <div v-if="isAdminSelected && adminLevel !== 'super_admin'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Permissions</h3>
                    <p class="text-sm text-gray-600 mb-6">Select specific permissions for this administrator.</p>

                    <div class="space-y-6">
                        <div v-for="(permissions, category) in allPermissions" :key="category">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                {{ formatCategory(category) }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pl-4 mb-4">
                                <label
                                    v-for="(description, permission) in permissions"
                                    :key="permission"
                                    class="flex items-start"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="hasPermission(permission)"
                                        @change="togglePermission(permission)"
                                        class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <div class="ml-3">
                                        <span class="text-sm text-gray-700">{{ description }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4">
                    <a
                        :href="route('user-management.show', user.id)"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded"
                    >
                        Cancel
                    </a>
                    <button
                        @click="updatePermissions"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Preset Modal -->
        <div
            v-if="showPresetModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="showPresetModal = false"
        >
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Apply Permission Preset</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Warning: This will replace all current roles and permissions with the selected preset.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="(preset, key) in presets"
                            :key="key"
                            class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition"
                            @click="applyPreset(key)"
                        >
                            <h4 class="font-semibold text-gray-900">{{ preset.name }}</h4>
                            <p class="text-sm text-gray-600 mt-1">{{ preset.description }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ preset.permissions.length }} permissions included
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            @click="showPresetModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
