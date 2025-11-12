<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import logo from '../../../img/logo.png';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const title = computed(() => {
    return {
        503: 'We\'re Taking a Quick Break!',
        500: 'Oops! Our Servers Are Having a Moment',
        404: 'Well, This Is Awkward...',
        403: 'Hold Up! Access Denied',
    }[props.status] || 'Something Went Wrong';
});

const description = computed(() => {
    return {
        503: '🔧 We\'re currently doing some maintenance to make things even better for you. Grab a coffee and check back in a few minutes!',
        500: '😅 Something unexpected happened on our end. Don\'t worry, it\'s not you - it\'s us. Our team has been notified and we\'re on it!',
        404: '🤔 Looks like this page is playing hide and seek with us! Maybe it\'s booking a private space or just taking a study break.',
        403: '🚫 This area is off-limits! You need special permissions to access this page. If you think this is a mistake, please contact us.',
    }[props.status] || '😕 Something unexpected happened, but we\'re working on it!';
});

const subtitle = computed(() => {
    return {
        503: 'Our co-workspace is temporarily closed for improvements',
        500: 'Even the best co-working spaces have technical difficulties',
        404: 'This page seems to have wandered off somewhere',
        403: 'You don\'t have a membership pass for this area',
    }[props.status] || 'But don\'t worry, we\'re here to help';
});

const icon = computed(() => {
    if (props.status === 503) {
        return `<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>`;
    }
    if (props.status === 500) {
        return `<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>`;
    }
    if (props.status === 403) {
        return `<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>`;
    }
    return `<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>`;
});
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#eef3ff] to-[#d4e3ff] px-4">
        <Head :title="title" />
        
        <div class="max-w-2xl w-full text-center">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <img :src="logo" alt="CO-Z Logo" class="h-20 w-auto" />
            </div>

            <!-- Error Illustration -->
            <div class="mb-8">
                <svg class="w-64 h-64 mx-auto text-[#2f4686] opacity-20" fill="currentColor" viewBox="0 0 24 24" v-html="icon">
                </svg>
            </div>

            <!-- Error Message -->
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
                <h1 class="text-6xl md:text-8xl font-bold text-[#2f4686] mb-4">{{ status }}</h1>
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-4">{{ title }}</h2>
                <p class="text-gray-600 text-lg mb-4 max-w-lg mx-auto">
                    {{ description }}
                </p>
                <p class="text-gray-500 text-base mb-8 max-w-md mx-auto italic">
                    {{ subtitle }}
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <Link
                        :href="route('customer.view')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Back to Home
                    </Link>

                    <button
                        v-if="status !== 503"
                        @click="() => window.history.back()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-[#2f4686] font-semibold rounded-lg border-2 border-[#2f4686] transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Go Back
                    </button>
                </div>

                <!-- Help Text -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        Need help? 
                        <Link :href="route('customer.view')" class="text-[#2f4686] hover:text-[#3956a3] font-medium underline">
                            Contact us
                        </Link>
                        or visit our homepage.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-8 text-sm text-gray-600">
                © {{ new Date().getFullYear() }} CO-Z Co-Workspace & Study Hub. All rights reserved.
            </p>
        </div>
    </div>
</template>
