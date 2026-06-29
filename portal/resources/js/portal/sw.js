// =========================================================================
//  Customer Portal service worker
//  -------------------------------------------------------------
//  Caches the TSP shell so the form can be opened with no network. We
//  DO NOT cache POST routes — those are owned by the offline-tsr.js
//  IndexedDB queue. We only cache the read-only assets + page shells.
//
//  Versioned cache key: bump CACHE_VERSION on every release to evict
//  stale assets.
// =========================================================================

const CACHE_VERSION = 'portal-v1';
const APP_SHELL = [
    '/css/portal/app.css',
    '/js/portal/app.js',
    '/js/portal/offline-tsr.js',
    '/js/portal/sw-register.js',
    '/offline',                  // offline fallback page
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((c) => c.addAll(APP_SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;   // never cache POST/PUT/PATCH/DELETE

    const url = new URL(req.url);
    if (url.pathname.startsWith('/livewire/')) return;   // Livewire handles its own transport
    if (url.pathname.startsWith('/_debugbar/')) return;
    if (url.pathname.startsWith('/broadcasting/')) return;

    event.respondWith(
        caches.match(req).then((hit) => {
            if (hit) return hit;
            return fetch(req).then((res) => {
                // Stash successful, same-origin, basic responses for next time.
                if (res.ok && res.type === 'basic' && new URL(req.url).origin === location.origin) {
                    const copy = res.clone();
                    caches.open(CACHE_VERSION).then((c) => c.put(req, copy));
                }
                return res;
            }).catch(() => caches.match('/offline'));
        }),
    );
});
