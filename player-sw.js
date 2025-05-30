// IT4VO School Infoscherm - Service Worker
const CACHE_NAME = 'it4vo-player-v1';
const urlsToCache = [
  '/school-infoscherm/player.php',
  '/school-infoscherm/player-manifest.json',
  '/school-infoscherm/icons/icon-32.png',
  '/school-infoscherm/icons/icon-192.png',
  '/school-infoscherm/icons/icon-512.png'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        if (response) {
          return response;
        }
        
        return fetch(event.request).then(response => {
          // Don't cache non-successful responses
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          
          // Clone the response
          const responseToCache = response.clone();
          
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });
          
          return response;
        });
      }).catch(() => {
        // Show offline page for navigation requests
        if (event.request.destination === 'document') {
          return caches.match('/school-infoscherm/player.php');
        }
      })
  );
});

// Activate event - cleanup old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background sync for heartbeat when online
self.addEventListener('sync', event => {
  if (event.tag === 'heartbeat-sync') {
    event.waitUntil(doHeartbeat());
  }
});

async function doHeartbeat() {
  try {
    // Get player ID from indexed DB or cache
    const response = await fetch('/school-infoscherm/api/player_heartbeat.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        player_id: 'SW_HEARTBEAT'
      })
    });
    console.log('Background heartbeat sent');
  } catch (error) {
    console.error('Background heartbeat failed:', error);
  }
}

// Push notifications (future feature)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nieuwe content beschikbaar',
    icon: '/school-infoscherm/icons/icon-192.png',
    badge: '/school-infoscherm/icons/icon-32.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'refresh',
        title: 'Ververs Content',
        icon: '/school-infoscherm/icons/refresh.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('IT4VO Infoscherm', options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'refresh') {
    // Send message to refresh content
    event.waitUntil(
      clients.matchAll().then(clients => {
        clients.forEach(client => {
          client.postMessage({
            msg: 'refresh_content'
          });
        });
      })
    );
  } else {
    // Open the app
    event.waitUntil(
      clients.openWindow('/school-infoscherm/player.php')
    );
  }
});