/**
 * GLOBALO — Service Worker v2
 * • Cache offline léger
 * • Web Push Notifications (VAPID)
 */

var CACHE_NAME = 'globalo-v5';

function getBase() {
    var path = self.location.pathname;
    return path.substring(0, path.lastIndexOf('/') + 1);
}

var BASE = getBase();
var ASSETS = [
    BASE,
    // Entrée PHP côté serveur (dans le même dossier que sw.js)
    BASE + 'index.php',
    BASE + 'assets/css/desktop.css',
    BASE + 'assets/css/mobile.css',
    BASE + 'assets/js/app.js',
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return Promise.all(ASSETS.map(function (url) {
                return cache.add(url).catch(function () {/* ignore erreurs de cache */});
            }));
        }).then(function () { return self.skipWaiting(); })
    );
});

// ── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (k) { return k !== CACHE_NAME; })
                    .map(function (k) { return caches.delete(k); })
            );
        }).then(function () { return self.clients.claim(); })
    );
});

// ── Fetch (offline fallback) ──────────────────────────────────────────────────
self.addEventListener('fetch', function (event) {
    if (event.request.mode !== 'navigate') return;
    // POST / autres méthodes (ex. paiement PayTech vers /paytech/initier + 302 externes) :
    // ne pas passer par fetch() du SW pour éviter des navigations bloquées selon navigateur.
    if (event.request.method !== 'GET') {
        return;
    }
    event.respondWith(
        fetch(event.request).catch(function () {
            return caches.match(event.request).then(function (r) {
                // Si la ressource ciblée n'est pas en cache, retomber sur l'entrypoint
                // plutôt que sur un chemin de dossier.
                return r
                    || caches.match(BASE + 'index.php')
                    || caches.match(BASE);
            });
        })
    );
});

// ── Push Notification reçue ───────────────────────────────────────────────────
self.addEventListener('push', function (event) {
    event.waitUntil(handlePush(event));
});

function handlePush(event) {
    // Tenter de lire un payload JSON directement
    var payloadData = null;
    if (event.data) {
        try { payloadData = event.data.json(); } catch (e) { /* pas de payload JSON */ }
    }

    if (payloadData && payloadData.title) {
        return showNotification(payloadData);
    }

    // Sinon, récupérer les notifications depuis l'API (résolution fiable quel que soit le dossier public)
    var apiNotifsUrl =
        typeof self.registration.scope === 'string'
            ? self.registration.scope.replace(/\/?$/, '/api/push/notifications')
            : (BASE.replace(/\/?$/, '/') + 'api/push/notifications');

    return fetch(apiNotifsUrl, {
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (!data.ok || !data.notifications || data.notifications.length === 0) {
            return showNotification({
                title: 'GLOBALO',
                body:  'Vous avez une nouvelle notification.',
                url:   BASE + '../',
                icon:  BASE + 'assets/images/logo.png',
                badge: BASE + 'assets/icons/icon.svg',
            });
        }
        var promises = data.notifications.map(function (notif) {
            return showNotification(notif);
        });
        return Promise.all(promises);
    })
    .catch(function () {
        return showNotification({
            title: 'GLOBALO',
            body:  'Vous avez une nouvelle notification.',
            url:   BASE + '../',
            icon:  BASE + 'assets/images/logo.png',
            badge: BASE + 'assets/icons/icon.svg',
        });
    });
}

function showNotification(data) {
    var options = {
        body:             data.body  || '',
        icon:             data.icon  || BASE + 'assets/images/logo.png',
        badge:            data.badge || BASE + 'assets/icons/icon.svg',
        vibrate:          [200, 100, 200],
        requireInteraction: false,
        data:             { url: data.url || BASE + '../' },
        actions: [
            { action: 'open',    title: 'Voir' },
            { action: 'dismiss', title: 'Fermer' }
        ],
        tag:  data.id ? 'notif-' + data.id : 'globalo-push',
    };
    return self.registration.showNotification(data.title || 'GLOBALO', options);
}

// ── Clic sur une notification ─────────────────────────────────────────────────
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    if (event.action === 'dismiss') return;

    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : BASE + '../';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var c = clientList[i];
                if (c.url === targetUrl && 'focus' in c) {
                    return c.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// ── Push subscription change (renouvellement automatique) ─────────────────────
self.addEventListener('pushsubscriptionchange', function (event) {
    event.waitUntil(
        self.registration.pushManager.subscribe(event.oldSubscription
            ? { userVisibleOnly: true, applicationServerKey: event.oldSubscription.options.applicationServerKey }
            : { userVisibleOnly: true }
        )
        .then(function (sub) {
            var subUrl =
                typeof self.registration.scope === 'string'
                    ? self.registration.scope.replace(/\/?$/, '/api/push/subscribe')
                    : (BASE.replace(/\/?$/, '/') + '../api/push/subscribe');

            return fetch(subUrl, {
                method:      'POST',
                credentials: 'include',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify(sub.toJSON()),
            });
        })
    );
});
