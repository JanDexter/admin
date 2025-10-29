const CACHE_NAME = 'coz-workspace-v3';
const RUNTIME_CACHE = 'coz-runtime-v3';
const IMAGE_CACHE = 'coz-images-v3';
const STATIC_CACHE = 'coz-static-v3';

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
  const requestUrl = new URL(event.request.url);
  
  // Skip non-GET requests and different origins
  if (event.request.method !== 'GET' || 
      (requestUrl.protocol !== 'http:' && requestUrl.protocol !== 'https:') || 
      requestUrl.origin !== self.location.origin) {
    return;
  }

  // Different strategies for different resource types
  
  // 1. Images - Cache First (with fallback)
  if (event.request.destination === 'image') {
    event.respondWith(
      caches.open(IMAGE_CACHE).then(cache => {
        return cache.match(event.request).then(response => {
          if (response) return response;
          
          return fetch(event.request).then(networkResponse => {
            // Cache successful image responses
            if (networkResponse && networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          }).catch(() => {
            // Return placeholder image if offline
            return new Response(
              '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect fill="#e5e7eb" width="200" height="200"/><text x="50%" y="50%" text-anchor="middle" fill="#9ca3af" font-family="sans-serif" font-size="14">Image Offline</text></svg>',
              { headers: { 'Content-Type': 'image/svg+xml' } }
            );
          });
        });
      })
    );
    return;
  }

  // 2. Static assets (CSS, JS, fonts) - Cache First
  if (event.request.destination === 'style' || 
      event.request.destination === 'script' || 
      event.request.destination === 'font' ||
      requestUrl.pathname.includes('/build/')) {
    event.respondWith(
      caches.open(STATIC_CACHE).then(cache => {
        return cache.match(event.request).then(response => {
          if (response) return response;
          
          return fetch(event.request).then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          });
        });
      })
    );
    return;
  }

  // 3. HTML/Navigation - Network First (with offline fallback)
  if (event.request.destination === 'document' || event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Cache successful page responses
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(RUNTIME_CACHE).then(cache => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Try cache first
          return caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) return cachedResponse;
            
            // Show offline page as last resort
            return caches.match('/offline.html');
          });
        })
    );
    return;
  }

  // 4. API calls - Network First (with cache fallback for GET)
  if (requestUrl.pathname.includes('/api/') || requestUrl.pathname.includes('/coz-control/')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Only cache successful GET responses
          if (response && response.status === 200 && event.request.method === 'GET') {
            const responseClone = response.clone();
            caches.open(RUNTIME_CACHE).then(cache => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Return cached data if available
          return caches.match(event.request);
        })
    );
    return;
  }

  // 5. Everything else - Network First with runtime cache
  event.respondWith(
    fetch(event.request)
      .then(response => {
        if (response && response.status === 200) {
          const responseClone = response.clone();
          caches.open(RUNTIME_CACHE).then(cache => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => caches.match(event.request))
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
