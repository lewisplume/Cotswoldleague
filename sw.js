const CACHE_NAME = 'gala-scoresheet-v3';
const CORE_ASSETS = [
    'gala_scoresheet.php',
    'gala_scoresheet.js?v=20260504-deadheat',
    'manifest.json',
    'images/league-logo.svg'
];
const OPTIONAL_CDN_ASSETS = [
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            await cache.addAll(CORE_ASSETS);
            cache.addAll(OPTIONAL_CDN_ASSETS).catch(() => {});
            await self.skipWaiting();
        })
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);
    const isSameOrigin = url.origin === self.location.origin;
    const isScoresheetRequest = isSameOrigin && (
        url.pathname.endsWith('/gala_scoresheet.php') ||
        url.pathname.endsWith('/gala_scoresheet.js') ||
        url.pathname.endsWith('/manifest.json') ||
        url.pathname.endsWith('/images/league-logo.svg')
    );
    const isScoresheetApi = isSameOrigin && (
        url.pathname.endsWith('/gala_scoresheet_api.php') ||
        url.pathname.endsWith('/gala_admin_api.php')
    );
    const isOptionalCdnAsset = OPTIONAL_CDN_ASSETS.includes(url.href);

    if (!isScoresheetRequest && !isScoresheetApi && !isOptionalCdnAsset) {
        return;
    }

    // API calls should always go to network first, then fail (or return generic error).
    // We handle the offline queuing in gala_scoresheet.js, so we don't intercept API saves here.
    if (isScoresheetApi) {
        e.respondWith(fetch(e.request));
        return;
    }

    if (isOptionalCdnAsset) {
        e.respondWith(
            fetch(e.request)
                .then((res) => {
                    const resClone = res.clone();
                    if (e.request.method === 'GET' && res.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => cache.put(e.request, resClone));
                    }
                    return res;
                })
                .catch(() => caches.match(e.request))
        );
        return;
    }

    // For scoresheet shell assets, try Network First, fallback to Cache.
    // This ensures they get the latest app version if online, but works offline.
    e.respondWith(
        fetch(e.request)
            .then((res) => {
                const resClone = res.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    // Only cache valid GET responses
                    if (e.request.method === 'GET' && res.status === 200) {
                        cache.put(e.request, resClone);
                    }
                });
                return res;
            })
            .catch(async () => {
                const cached = await caches.match(e.request);
                if (cached) return cached;
                if (e.request.mode === 'navigate') {
                    return caches.match('gala_scoresheet.php');
                }
                return new Response('', { status: 504, statusText: 'Offline asset unavailable' });
            })
    );
});
