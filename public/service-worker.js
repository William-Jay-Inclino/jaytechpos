// ...existing code...
const CACHE_NAME = 'jaytech-static-v1';
const ASSETS = [
  '/',
  '/logo-192.png',
  '/logo-512.png',
  '/site.webmanifest'
];

self.addEventListener('install', (event) => {
    console.log('[SW] install event');
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_NAME);
        const results = await Promise.all(ASSETS.map(async (asset) => {
            try {
                const req = new Request(asset, { cache: 'no-cache' });
                const response = await fetch(req);
                // Accept successful responses or opaque (cross-origin no-cors) responses
                if (!response || (!(response.ok) && response.type !== 'opaque')) {
                    throw new Error(`Bad response for ${asset}: ${response && response.status}`);
                }
                await cache.put(asset, response.clone());
                return { asset, ok: true };
            } catch (err) {
                console.warn('[SW] Failed to cache', asset, err);
                return { asset, ok: false, error: err && err.message };
            }
        }));

        const failed = results.filter(r => !r.ok);
        if (failed.length) {
            console.warn('[SW] Some assets failed to cache:', failed.map(f => f.asset));
        } else {
            console.log('[SW] All assets cached');
        }
    })());
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[SW] activate event');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // optional: log only navigations to reduce noise
    if (event.request.mode === 'navigate') {
        console.log('[SW] fetch (navigation):', event.request.url);
    }

    event.respondWith((async () => {
        const cached = await caches.match(event.request);
        if (cached) {
            return cached;
        }
        try {
            return await fetch(event.request);
        } catch (err) {
            // When fetch fails (offline), try to serve index.html for navigations
            if (event.request.mode === 'navigate') {
                const fallback = await caches.match('/index.html');
                if (fallback) {
                    return fallback;
                }
            }
            throw err;
        }
    })());
});
// ...existing code...