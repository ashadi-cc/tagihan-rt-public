const CACHE_NAME = 'gmrt04-v31';
const urlsToCache = [
  '/css/app.css',
  '/js/app.js',
  '/dist/css/styles.css'
];

// Install service worker
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch((err) => {
        console.log('Cache addAll error:', err);
      })
  );
});

// Fetch resources
self.addEventListener('fetch', (event) => {
  const requestUrl = new URL(event.request.url);
  
  // Skip caching for HTML pages, POST requests, and same-origin requests
  // Only cache static assets (CSS, JS, images, fonts)
  if (
    event.request.method !== 'GET' ||
    requestUrl.origin !== location.origin ||
    event.request.mode === 'navigate' ||
    event.request.destination === 'document' ||
    requestUrl.pathname.endsWith('.php') ||
    requestUrl.pathname === '/' ||
    requestUrl.pathname.startsWith('/login') ||
    requestUrl.pathname.startsWith('/admin') ||
    requestUrl.pathname.startsWith('/user')
  ) {
    // Let the browser handle these requests natively (no event.respondWith).
    // This avoids trapping navigation/API errors inside the service worker,
    // so the browser can show its own offline / retry UI when the network is flaky.
    return;
  }

  // Only cache static assets (CSS, JS, images, fonts, etc.)
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) {
          return response;
        }
        
        return fetch(event.request).then(
          (response) => {
            // Check if valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Only cache static assets
            const responseUrl = new URL(response.url);
            const isStaticAsset = /\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)$/i.test(responseUrl.pathname);
            
            if (isStaticAsset) {
              const responseToCache = response.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(event.request, responseToCache);
              });
            }

            return response;
          }
        ).catch(() => {
          // Network failed, return cached version if available
          return caches.match(event.request);
        });
      })
  );
});

// Activate service worker
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});
