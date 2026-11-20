const CACHE_NAME = 'epark-static-v1';
const STATIC_ASSETS = [
    '/manifest.json',
    '/favicon.svg',
    '/logo/ePark.png',
    '/logo/icon-192.png',
    '/logo/icon-512.png',
];

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ePark · Hors ligne</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f3f6fa; color: #18263a; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { background: #fff; border: 1px solid #dce3ed; border-radius: 14px; padding: 28px; max-width: 420px; width: 100%; }
        h1 { font-size: 22px; margin: 0 0 10px; }
        p { color: #566477; font-size: 14px; line-height: 1.5; margin: 0 0 18px; }
        button { background: #4338ca; color: #fff; border: 0; border-radius: 9px; padding: 12px 18px; font-weight: 700; font-size: 14px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Connexion interrompue</h1>
        <p>ePark a besoin d’une connexion réseau pour consulter les disponibilités en temps réel et confirmer vos réservations.</p>
        <button type="button" onclick="window.location.reload()">Réessayer</button>
    </main>
</body>
</html>`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Cache-first only for immutable Vite build assets and public logos/icons
    if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/logo/') || url.pathname === '/manifest.json' || url.pathname.startsWith('/favicon')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for HTML navigations with clean offline fallback (never caches private HTML responses)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => new Response(OFFLINE_HTML, {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' },
            }))
        );
    }
});
