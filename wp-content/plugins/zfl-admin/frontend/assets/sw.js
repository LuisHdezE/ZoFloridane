/* Service Worker de Zofloridane - requerido para que la web sea instalable como app. */

var CACHE = 'zfl-v13';

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }
});
