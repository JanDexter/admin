<script setup>
import { computed, ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const otpForm = useForm({
    otp: '',
});

const resendForm = useForm({});

const submitOtp = () => {
    otpForm.post(route('otp.verify'), {
        preserveScroll: true,
        onError: () => {
            otpForm.otp = '';
        },
    });
};

const resendOtp = () => {
    resendForm.post(route('otp.resend'), {
        preserveScroll: true,
    });
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent' || props.status?.includes('verification code'),
);

const isReactivation = computed(
    () => props.status === 'reactivation-requested',
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-4 text-sm text-gray-600" v-if="!isReactivation">
            Thanks for signing up! We've sent a 6-digit verification code to your email address.
            Please enter the code below to activate your account.
        </div>

        <div class="mb-4 text-sm text-gray-600" v-if="isReactivation">
            <p class="font-semibold text-amber-600 mb-2">Account Reactivation Required</p>
            Your account has been deactivated. We've sent a 6-digit verification code to your email.
            Enter the code below to reactivate your account.
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            A new verification code has been sent to your email address.
        </div>

        <form @submit.prevent="submitOtp" class="space-y-6">
            <div>
                <InputLabel for="otp" value="Verification Code" />
                <TextInput
                    id="otp"
                    v-model="otpForm.otp"
                    type="text"
                    class="mt-1 block w-full text-center text-2xl tracking-widest font-mono"
                    maxlength="6"
                    placeholder="000000"
                    required
                    autofocus
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                />
                <InputError class="mt-2" :message="otpForm.errors.otp" />
                <p class="mt-1 text-xs text-gray-500">
                    Enter the 6-digit code sent to your email
                </p>
            </div>

            <div class="space-y-3">
                <PrimaryButton
                    :class="{ 'opacity-25': otpForm.processing }"
                    :disabled="otpForm.processing || otpForm.otp.length !== 6"
                    class="w-full justify-center py-2.5"
                >
                    Verify Code
                </PrimaryButton>

                <div class="text-center">
                    <button
                        type="button"
                        @click="resendOtp"
                        :disabled="resendForm.processing"
                        :class="{ 'opacity-25': resendForm.processing }"
                        class="text-sm text-gray-600 hover:text-gray-900 underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2f4686] rounded transition"
                    >
                        Resend Code
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-4 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >Log Out</Link
            >
        </div>
    </GuestLayout>
</template>
