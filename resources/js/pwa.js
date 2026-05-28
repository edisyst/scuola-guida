'use strict';

// ──────────────────────────────────────────────────────────────────────────────
// Service Worker registration
// ──────────────────────────────────────────────────────────────────────────────

let deferredInstallPrompt = null;

document.addEventListener('DOMContentLoaded', () => {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then(registration => {
            // Listen for SW messages (e.g. background sync trigger)
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data?.type === 'SW_SYNC_ANSWERS') {
                    window.dispatchEvent(new CustomEvent('pwa:sync-answers'));
                }
            });

            // Check for SW updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker?.addEventListener('statechange', () => {
                    if (newWorker.statechange === 'installed' && navigator.serviceWorker.controller) {
                        window.dispatchEvent(new CustomEvent('pwa:update-available'));
                    }
                });
            });
        })
        .catch(() => {
            // SW registration failed — app continues without offline support
        });
});

// ──────────────────────────────────────────────────────────────────────────────
// Install prompt (beforeinstallprompt / appinstalled)
// ──────────────────────────────────────────────────────────────────────────────

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.__pwaInstallPrompt = event;
    document.dispatchEvent(new CustomEvent('pwa:installable'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt    = null;
    window.__pwaInstallPrompt = null;
    document.dispatchEvent(new CustomEvent('pwa:installed'));
});
