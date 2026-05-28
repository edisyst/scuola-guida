'use strict';

const CACHE_VERSION = 'sg-v1';
const CACHE_STATIC  = `${CACHE_VERSION}-static`;
const CACHE_PAGES   = `${CACHE_VERSION}-pages`;

// Resources to pre-cache during install
const PRECACHE_URLS = [
    '/offline',
    '/manifest.json',
    '/icons/icon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

// Routes that must never be served from cache
const NEVER_CACHE_PATHS = [
    '/livewire/update',
    '/livewire/upload-file',
];

const NEVER_CACHE_PREFIXES = [
    '/admin',
    '/2fa',
];

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

function shouldSkipFetch(request) {
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) return true;

    // Never cache non-GET/HEAD mutations
    if (!['GET', 'HEAD'].includes(request.method)) return true;

    const path = url.pathname;

    // Explicit exclusions
    if (NEVER_CACHE_PATHS.some(p => path === p || path.startsWith(p + '/'))) return true;
    if (NEVER_CACHE_PREFIXES.some(p => path.startsWith(p + '/'))) return true;

    return false;
}

function isViteAsset(url) {
    // Vite outputs content-hashed files under /build/assets/
    return url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/build/');
}

function isNavigationRequest(request) {
    return request.mode === 'navigate';
}

// ──────────────────────────────────────────────────────────────────────────────
// Install — pre-cache shell
// ──────────────────────────────────────────────────────────────────────────────

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then(cache => cache.addAll(
                PRECACHE_URLS.filter(url => {
                    // Skip icons that don't exist yet (PNGs need manual generation)
                    return !url.endsWith('.png') || url === '/icons/icon-192.png' || url === '/icons/icon-512.png';
                }).concat(['/offline'])
            ))
            .catch(() => {
                // Silently ignore pre-cache failures (e.g. PNG icons not generated yet)
            })
            .then(() => self.skipWaiting())
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Activate — clean up old caches
// ──────────────────────────────────────────────────────────────────────────────

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys
                .filter(key => !key.startsWith(CACHE_VERSION))
                .map(key => caches.delete(key))
        ))
        .then(() => self.clients.claim())
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Fetch — routing strategies
// ──────────────────────────────────────────────────────────────────────────────

self.addEventListener('fetch', (event) => {
    if (shouldSkipFetch(event.request)) return;

    const url = new URL(event.request.url);

    // Cache-first for Vite content-hashed assets (immutable)
    if (isViteAsset(url)) {
        event.respondWith(cacheFirst(event.request, CACHE_STATIC));
        return;
    }

    // Cache-first for other static assets (CSS/JS/images in public/)
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(event.request, CACHE_STATIC));
        return;
    }

    // Network-first for HTML navigation — fallback to /offline
    if (isNavigationRequest(event.request)) {
        event.respondWith(networkFirstWithOfflineFallback(event.request));
        return;
    }

    // Network-first for everything else (API calls, Livewire XHR, etc.)
    event.respondWith(networkFirst(event.request, CACHE_PAGES));
});

function isStaticAsset(url) {
    const ext = url.pathname.split('.').pop().toLowerCase();
    return ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'].includes(ext);
}

// Cache-first: return cache hit immediately; on miss, fetch + cache
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503 });
    }
}

// Network-first: try network; on failure, try cache
async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached || new Response('', { status: 503 });
    }
}

// Network-first for navigations; on failure, return /offline page
async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_PAGES);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        // Serve the pre-cached offline page
        const offlinePage = await caches.match('/offline');
        return offlinePage || new Response('<h1>Offline</h1>', {
            headers: { 'Content-Type': 'text/html' },
        });
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Background sync (answers queued while offline)
// ──────────────────────────────────────────────────────────────────────────────

self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-answers') {
        event.waitUntil(syncAnswersFromSW());
    }
});

async function syncAnswersFromSW() {
    // The actual sync is handled client-side via the 'online' event in pwa.js.
    // This SW handler is a fallback for browsers that support Background Sync API.
    // It sends a message to the active client to trigger the sync.
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: false });
    clients.forEach(client => client.postMessage({ type: 'SW_SYNC_ANSWERS' }));
}
