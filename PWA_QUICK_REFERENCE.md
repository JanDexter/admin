# 🚀 PWA Quick Reference Card

## Essential Files

```
resources/js/utils/offlineStorage.js   - Offline storage utility
public/sw.js                           - Service worker
public/offline.html                    - Offline page
resources/js/Pages/CustomerView/Index.vue - Main integration
```

## Quick Commands

```bash
# Build frontend
npm run build

# Clear service worker (DevTools)
Application → Service Workers → Unregister

# Clear cache (DevTools)
Application → Cache Storage → Delete all

# Clear localStorage (DevTools)
Application → Local Storage → Clear All
```

## Offline Storage API

```javascript
import { offlineStorage } from '@/utils/offlineStorage';

// WiFi Credentials
offlineStorage.saveWiFiCredentials(credentials);
const wifi = offlineStorage.getWiFiCredentials();
offlineStorage.clearWiFiCredentials();

// Reservation
offlineStorage.saveReservation(reservation);
const res = offlineStorage.getReservation();
offlineStorage.clearReservation();

// Timer
offlineStorage.saveTimerState(state);
const timer = offlineStorage.getTimerState();
offlineStorage.clearTimerState();

// Cleanup
offlineStorage.cleanupExpired();
offlineStorage.clearAll();
```

## Service Worker Lifecycle

```javascript
// Register
navigator.serviceWorker.register('/sw.js');

// Update
registration.update();

// Skip waiting
self.skipWaiting();

// Claim clients
self.clients.claim();

// Send message
navigator.serviceWorker.controller.postMessage({
  type: 'CACHE_URLS',
  urls: ['/page1', '/page2']
});
```

## Cache Layers

```javascript
CACHE_NAME = 'coz-workspace-v3'    // Core cache
RUNTIME_CACHE = 'coz-runtime-v3'   // Runtime
IMAGE_CACHE = 'coz-images-v3'      // Images
STATIC_CACHE = 'coz-static-v3'     // CSS/JS
```

## Common Issues

### Service Worker Not Updating
```javascript
// In DevTools → Application → Service Workers
// Click "Update" or check "Update on reload"
```

### localStorage Not Working
```javascript
// Check if in Incognito mode
// Check browser settings
// Check quota
```

### Timer Not Restoring
```javascript
// Verify in DevTools:
localStorage.getItem('coz_offline_timer_state')
// Should return JSON with startTime, endTime
```

## Testing Offline

```javascript
// Chrome DevTools → Network tab
// Throttling: Offline

// Programmatically
if (navigator.onLine) {
  console.log('Online');
} else {
  console.log('Offline');
}
```

## Data Structure Examples

### WiFi Credentials
```json
{
  "ssid": "COZ-WORKSPACE",
  "username": "user_123_456",
  "password": "ABC123DEF456",
  "expiresAt": "2025-10-30T14:00:00Z"
}
```

### Reservation
```json
{
  "id": 123,
  "space_name": "Exclusive Space",
  "start_time": "2025-10-30T12:00:00Z",
  "end_time": "2025-10-30T14:00:00Z",
  "status": "paid"
}
```

## Console Logs

```
✅ ServiceWorker registered successfully
🔄 New content available
📡 Background sync triggered
💾 PWA install prompt available
```

## DevTools Shortcuts

```
F12              - Open DevTools
Ctrl+Shift+R     - Hard refresh
Ctrl+Shift+I     - Inspect element
```

## Useful DevTools Panels

```
Application → Service Workers
Application → Cache Storage
Application → Local Storage
Network → Throttling
Console → Errors/Logs
```

## Performance Tips

- ✅ Cache static assets
- ✅ Use Cache API for large data
- ✅ Implement versioning
- ✅ Clean up old caches
- ✅ Monitor cache size
- ✅ Use gzip compression

## Security Checklist

- ✅ HTTPS only (or localhost)
- ✅ Same-origin policy
- ✅ Auto-expire sensitive data
- ✅ No payment info in cache
- ✅ Validate data before use

## Links

- 📖 [PWA_FEATURES.md](./PWA_FEATURES.md)
- 🧪 [PWA_TESTING_GUIDE.md](./PWA_TESTING_GUIDE.md)
- ✅ [PWA_COMPLETION_SUMMARY.md](./PWA_COMPLETION_SUMMARY.md)
