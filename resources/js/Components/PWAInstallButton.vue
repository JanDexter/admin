<template>
    <div v-if="showInstallPrompt" class="fixed bottom-4 right-4 z-50 max-w-sm">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl shadow-2xl p-4 border border-blue-500">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-lg mb-1">Install CO-Z App</h3>
                    <p class="text-sm text-blue-100 mb-3">
                        Access your reservations and WiFi credentials offline, even without internet!
                    </p>
                    <div class="flex gap-2">
                        <button 
                            @click="installPWA" 
                            class="flex-1 bg-white text-blue-600 font-semibold py-2 px-4 rounded-lg hover:bg-blue-50 transition-colors"
                        >
                            Install App
                        </button>
                        <button 
                            @click="dismissInstallPrompt" 
                            class="px-4 py-2 text-blue-100 hover:text-white transition-colors"
                        >
                            Later
                        </button>
                    </div>
                </div>
                <button 
                    @click="dismissPermanently" 
                    class="flex-shrink-0 text-blue-200 hover:text-white transition-colors"
                    title="Don't show again"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Status indicator when installed -->
    <div v-else-if="isInstalled" class="fixed bottom-4 right-4 z-50">
        <div class="bg-green-600 text-white rounded-full px-4 py-2 shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium">App Installed</span>
        </div>
    </div>

    <!-- Offline indicator -->
    <div v-if="!isOnline" class="fixed top-4 right-4 z-50">
        <div class="bg-orange-500 text-white rounded-lg px-4 py-2 shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
            </svg>
            <span class="text-sm font-medium">Offline Mode</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePWA } from '../composables/usePWA';

const DISMISSED_KEY = 'pwa_install_dismissed';
const DISMISS_DURATION = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds

const { isOnline, isInstallable, isInstalled, installPWA: install } = usePWA();

const showInstallPrompt = ref(false);

const checkIfDismissed = () => {
    try {
        const dismissed = localStorage.getItem(DISMISSED_KEY);
        if (!dismissed) return false;
        
        const dismissedData = JSON.parse(dismissed);
        const now = Date.now();
        
        if (dismissedData.permanent) return true;
        if (dismissedData.until && now < dismissedData.until) return true;
        
        // Expired, remove it
        localStorage.removeItem(DISMISSED_KEY);
        return false;
    } catch (error) {
        return false;
    }
};

const dismissInstallPrompt = () => {
    showInstallPrompt.value = false;
    localStorage.setItem(DISMISSED_KEY, JSON.stringify({
        until: Date.now() + DISMISS_DURATION,
        permanent: false,
    }));
};

const dismissPermanently = () => {
    showInstallPrompt.value = false;
    localStorage.setItem(DISMISSED_KEY, JSON.stringify({
        permanent: true,
    }));
};

const installPWA = async () => {
    try {
        await install();
        showInstallPrompt.value = false;
        localStorage.removeItem(DISMISSED_KEY);
    } catch (error) {
        console.error('Failed to install PWA:', error);
    }
};

onMounted(() => {
    // Show install prompt after a short delay if installable and not dismissed
    setTimeout(() => {
        if (isInstallable.value && !isInstalled.value && !checkIfDismissed()) {
            showInstallPrompt.value = true;
        }
    }, 3000); // Show after 3 seconds
});
</script>
