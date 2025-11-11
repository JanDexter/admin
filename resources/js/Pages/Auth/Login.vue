<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ModernAuthLayout from '@/Layouts/ModernAuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <ModernAuthLayout>
        <Head title="Log in" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
            <p class="mt-1 text-sm text-gray-600">Sign in to your CO-Z account</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800">
            {{ status }}
        </div>

        <!-- Google Sign In -->
        <a
            :href="route('auth.google.redirect', { intent: 'customer' })"
            class="w-full flex items-center justify-center gap-3 rounded-lg border-2 border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2f4686] focus:ring-offset-2 mb-6"
        >
            <svg class="h-5 w-5" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.28 9.21 3.77l6.84-6.84C35.1 2.53 29.92 0 24 0 14.64 0 6.51 5.38 2.69 13.22l7.98 6.2C12.37 13.99 17.74 9.5 24 9.5z" />
                <path fill="#4285F4" d="M46.5 24c0-1.63-.15-3.2-.42-4.71H24v9.02h12.7c-.55 2.97-2.21 5.5-4.7 7.19l7.18 5.59C43.73 36.79 46.5 30.86 46.5 24z" />
                <path fill="#FBBC05" d="M10.67 28.02c-.48-1.45-.76-3-.76-4.59s.27-3.14.76-4.59l-7.98-6.2C.96 15.82 0 19.29 0 23c0 3.71.96 7.18 2.69 10.36l7.98-5.34z" />
                <path fill="#34A853" d="M24 46c5.92 0 11.09-1.95 14.78-5.29l-7.18-5.59c-2 1.35-4.56 2.14-7.6 2.14-6.26 0-11.62-4.49-13.35-10.52l-7.98 5.34C6.51 42.62 14.64 48 24 48z" />
                <path fill="none" d="M0 0h48v48H0z" />
            </svg>
            Continue with Google
        </a>

        <!-- Divider -->
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white px-4 text-gray-500">Or continue with email</span>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <div class="relative">
                    <TextInput
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pr-12"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-2 flex items-center rounded p-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#2f4686]"
                        :aria-pressed="showPassword"
                        aria-label="Toggle password visibility"
                    >
                        <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.597m2.14-1.685A9.969 9.969 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.06 10.06 0 01-1.223 2.516M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-[#2f4686] hover:text-[#3956a3] focus:outline-none focus:underline"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="pt-4">
                <PrimaryButton 
                    class="w-full justify-center" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>

            <div class="text-center text-sm">
                <span class="text-gray-600">Don't have an account?</span>
                <Link
                    :href="route('register')"
                    class="ml-1 font-semibold text-[#2f4686] hover:text-[#3956a3] focus:outline-none focus:underline"
                >
                    Create one
                </Link>
            </div>
        </form>
    </ModernAuthLayout>
</template>
