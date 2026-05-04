const CACHE_NAME = 'gala-scoresheet-v1';
const STATIC_ASSETS = [
    'gala_scoresheet.php',
    'gala_scoresheet.js',
    'manifest.json',
    'images/league-logo.svg',
    // We use CDN for tailwind and lucide, we could cache them but browser usually does.
    // We'll let the browser cache the CDNs naturally, but we'll try to intercept them.
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            );
        })
    );
});

self.addEventListener('fetch', (e) => {
    // API calls should always go to network first, then fail (or return generic error).
    // We handle the offline queuing in gala_scoresheet.js, so we don't intercept API saves here.
    if (e.request.url.includes('gala_scoresheet_api.php') || e.request.url.includes('gala_admin_api.php')) {
        e.respondWith(fetch(e.request));
        return;
    }

    // For everything else (HTML, JS, Images), try Network First, fallback to Cache.
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
            .catch(() => caches.match(e.request))
    );
});
