const CACHE_NAME = 'coz-workspace-v7';
const RUNTIME_CACHE = 'coz-runtime-v7';
const IMAGE_CACHE = 'coz-images-v7';
const STATIC_CACHE = 'coz-static-v7';

const urlsToCache = [
  '/',
  '/offline.html',
  '/icons/favicon.svg',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
];

// Install event - cache essential resources
self.addEventListener('install', event => {
  self.skipWaiting(); // Activate immediately
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .catch(err => console.log('Cache install failed:', err))
  );
});

// Fetch event - smart caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // 1. Skip non-GET requests, cross-origin requests, and auth routes.
  // Let the browser handle these directly.
  if (request.method !== 'GET' || 
      url.origin !== self.location.origin ||
      url.pathname.includes('/auth/google/callback') || // Explicitly ignore Google callback
      url.pathname.includes('/auth/') ||
      url.pathname.includes('/login') ||
      url.pathname.includes('/logout') ||
      url.pathname.includes('/register')) {
    return; 
  }

  // For all other GET requests, use a network-first strategy.
  event.respondWith(
    caches.open(RUNTIME_CACHE).then(cache => {
      return fetch(request).then(networkResponse => {
        // If we get a valid response, cache it and return it.
        if (networkResponse && networkResponse.status === 200) {
          cache.put(request, networkResponse.clone());
        }
        return networkResponse;
      }).catch(() => {
        // If the network request fails, try to find a cached response.
        return cache.match(request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }

          // If it's a navigation request and no cache, show the offline page.
          if (request.mode === 'navigate') {
            return caches.match('/offline.html');
          }
          
          // For other assets (images, etc.), we can return a simple error
          // or a placeholder, but for now, a simple error is fine.
          return new Response('Network error and no cache available.', {
            status: 404,
            statusText: 'Not Found'
          });
        });
      });
    })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  const currentCaches = [CACHE_NAME, RUNTIME_CACHE, IMAGE_CACHE, STATIC_CACHE];
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (!currentCaches.includes(cacheName)) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      // Take control of all pages immediately
      return self.clients.claim();
    })
  );
});

// Background sync for offline form submissions
self.addEventListener('sync', event => {
  if (event.tag === 'sync-reservations') {
    event.waitUntil(syncReservations());
  }
});

// Periodic background sync for active reservations (if supported)
self.addEventListener('periodicsync', event => {
  if (event.tag === 'update-active-reservations') {
    event.waitUntil(updateActiveReservations());
  }
});

async function syncReservations() {
  try {
    // Get any pending offline reservations from IndexedDB
    const pendingReservations = await getPendingReservations();
    
    for (const reservation of pendingReservations) {
      try {
        const response = await fetch('/api/reservations', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(reservation),
        });
        
        if (response.ok) {
          await removePendingReservation(reservation.id);
        }
      } catch (error) {
        console.log('Failed to sync reservation:', error);
      }
    }
  } catch (error) {
    console.log('Background sync failed:', error);
  }
}

async function updateActiveReservations() {
  try {
    // Notify all clients about active reservations
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
      client.postMessage({
        type: 'UPDATE_ACTIVE_RESERVATIONS',
        timestamp: Date.now(),
      });
    });
  } catch (error) {
    console.log('Periodic sync failed:', error);
  }
}

// Helper functions for IndexedDB (simplified)
async function getPendingReservations() {
  // Implementation would use IndexedDB
  return [];
}

async function removePendingReservation(id) {
  // Implementation would use IndexedDB
  return true;
}

// Message handling for communication with clients
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(RUNTIME_CACHE).then(cache => {
        return cache.addAll(event.data.urls);
      })
    );
  }
  
  if (event.data && event.data.type === 'CLEAN_CACHE') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => caches.delete(cacheName))
        );
      })
    );
  }
});

// Push notifications (optional)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New notification from CO-Z',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-96x96.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View',
        icon: '/icons/icon-192x192.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/icons/icon-192x192.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('CO-Z Co-Workspace', options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});
