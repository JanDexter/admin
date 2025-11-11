<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import logo from '../../../img/logo.png';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        default: '',
    },
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.change.update'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Change Password" />

    <div class="min-h-screen bg-gradient-to-br from-[#eef3ff] to-[#d9e7ff] flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img :src="logo" alt="CO-Z Co-Workspace & Study Hub" class="h-16 w-auto mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-[#2f4686]">Change Your Password</h1>
                <p class="text-sm text-gray-600 mt-2">CO-Z Co-Workspace & Study Hub</p>
            </div>

            <!-- Invalid Status -->
            <div v-if="status === 'invalid'" class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-3">Invalid Link</h2>
                <p class="text-gray-600 mb-6">{{ message }}</p>
                <a 
                    :href="route('customer.view')"
                    class="inline-block px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-colors"
                >
                    Return to Home
                </a>
            </div>

            <!-- Expired Status -->
            <div v-else-if="status === 'expired'" class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-orange-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-3">Link Expired</h2>
                <p class="text-gray-600 mb-6">{{ message }}</p>
                <a 
                    :href="route('customer.view')"
                    class="inline-block px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-colors"
                >
                    Return to Home
                </a>
            </div>

            <!-- Valid Status - Show Form -->
            <div v-else-if="status === 'valid'" class="bg-white rounded-2xl shadow-xl p-8">
                <div class="mb-6 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Set Your New Password</h2>
                    <p class="text-sm text-gray-600 mt-2">Choose a strong password for your account</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email (Read-only) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input 
                            type="email" 
                            :value="email" 
                            readonly
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed"
                        />
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                        <div class="relative">
                            <input 
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f4686] focus:border-[#2f4686] pr-12"
                                :class="{ 'border-red-500': form.errors.password }"
                                placeholder="Enter new password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <input 
                                :type="showConfirmPassword ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f4686] focus:border-[#2f4686] pr-12"
                                placeholder="Confirm new password"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-bold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Changing Password...' : 'Change Password' }}</span>
                    </button>

                    <div class="text-center">
                        <a 
                            :href="route('customer.view')"
                            class="text-sm text-[#2f4686] hover:text-[#3956a3] font-medium"
                        >
                            Cancel and Return to Home
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
