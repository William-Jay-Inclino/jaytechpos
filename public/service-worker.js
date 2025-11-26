// ...existing code...
const CACHE_NAME = 'jaytech-static-v1';
const ASSETS = [
  '/',
  '/index.html',
  '/logo-192.png',
  '/logo-512.png',
  '/site.webmanifest'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((resp) => {
            return resp || fetch(event.request);
        })
    );
});
// ...existing code...