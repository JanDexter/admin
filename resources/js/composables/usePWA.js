import { ref, onMounted } from 'vue'

export function usePWA() {
    const isOnline = ref(navigator.onLine)
    const isInstallable = ref(false)
    const isInstalled = ref(false)
    
    let deferredPrompt = null

    // Check if app is already installed
    const checkIfInstalled = () => {
        if (window.matchMedia('(display-mode: standalone)').matches) {
            isInstalled.value = true
        }
    }

    // Install PWA
    const installPWA = async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt()
            const result = await deferredPrompt.userChoice
            
            if (result.outcome === 'accepted') {
                console.log('PWA installed')
                isInstalled.value = true
            }
            
            deferredPrompt = null
            isInstallable.value = false
        }
    }

    // Request notification permission
    const requestNotificationPermission = async () => {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission()
            return permission === 'granted'
        }
        return false
    }

    // Show notification
    const showNotification = (title, options = {}) => {
        if ('serviceWorker' in navigator && 'Notification' in window) {
            navigator.serviceWorker.ready.then(registration => {
                registration.showNotification(title, {
                    body: options.body || '',
                    icon: '/icons/icon-192x192.png',
                    badge: '/icons/icon-96x96.png',
                    vibrate: [200, 100, 200],
                    ...options
                })
            })
        }
    }

    // Handle online/offline status
    const handleOnline = () => {
        isOnline.value = true
        showNotification('Connection Restored', {
            body: 'You are back online!'
        })
    }

    const handleOffline = () => {
        isOnline.value = false
        showNotification('Connection Lost', {
            body: 'You are now offline. Some features may be limited.'
        })
    }

    // Background sync for offline actions
    const scheduleBackgroundSync = (tag = 'background-sync') => {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            navigator.serviceWorker.ready.then(registration => {
                return registration.sync.register(tag)
            })
        }
    }

    onMounted(() => {
        checkIfInstalled()

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault()
            deferredPrompt = e
            isInstallable.value = true
        })

        // Listen for app installed
        window.addEventListener('appinstalled', () => {
            isInstalled.value = true
            isInstallable.value = false
        })

        // Listen for online/offline events
        window.addEventListener('online', handleOnline)
        window.addEventListener('offline', handleOffline)
    })

    return {
        isOnline,
        isInstallable,
        isInstalled,
        installPWA,
        requestNotificationPermission,
        showNotification,
        scheduleBackgroundSync
    }
}
