<?php
if (!isset($current_season_year)) {
    include_once 'db.php';
}

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$enableLogoFlair = $currentScript !== 'index.php';
$navSeasonYear = isset($current_season_year) ? (int)$current_season_year : 2026;
$hasAuthenticatedSession = session_status() === PHP_SESSION_ACTIVE && (
    !empty($_SESSION['club_logged_in'])
    || !empty($_SESSION['super_admin_logged_in'])
    || !empty($_SESSION['logged_in'])
);
?>

<?php if ($hasAuthenticatedSession): ?>
<script>
    (() => {
        const readCsrfToken = () => {
            const match = document.cookie.match(/(?:^|;\s*)cotswold_csrf=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        };

        const originalFetch = window.fetch.bind(window);
        window.fetch = (input, init = {}) => {
            const requestUrl = new URL(typeof input === 'string' ? input : input.url, window.location.href);
            const method = String(init.method || (typeof input !== 'string' && input.method) || 'GET').toUpperCase();
            if (requestUrl.origin === window.location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                const headers = new Headers(init.headers || (typeof input !== 'string' ? input.headers : undefined));
                const token = readCsrfToken();
                if (token) headers.set('X-CSRF-Token', token);
                init = { ...init, headers, credentials: init.credentials || 'same-origin' };
            }
            return originalFetch(input, init);
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || String(form.method).toUpperCase() !== 'POST') return;
            const actionUrl = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            if (actionUrl.origin !== window.location.origin) return;
            let field = form.querySelector('input[name="_csrf_token"]');
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = '_csrf_token';
                form.appendChild(field);
            }
            field.value = readCsrfToken();
        }, true);
    })();
</script>
<?php endif; ?>

<?php if ($currentScript !== 'gala_scoresheet.php'): ?>
<script>
    (() => {
        if (!('serviceWorker' in navigator)) return;
        const cleanupKey = 'cotswold_scoresheet_sw_cleanup_v1';
        navigator.serviceWorker.getRegistrations()
            .then((registrations) => Promise.all(registrations.map((registration) => {
                const activeScript = registration.active?.scriptURL || registration.waiting?.scriptURL || registration.installing?.scriptURL || '';
                const scopePath = new URL(registration.scope).pathname;
                if (!activeScript.endsWith('/sw.js') || scopePath !== '/') return Promise.resolve(false);
                return registration.unregister();
            })))
            .then((results) => {
                const removedRootScoresheetWorker = results.some(Boolean);
                if (removedRootScoresheetWorker && 'caches' in window) {
                    caches.keys()
                        .then((keys) => Promise.all(keys
                            .filter((key) => key.startsWith('gala-scoresheet-'))
                            .map((key) => caches.delete(key))))
                        .catch(() => {});
                }
                if (removedRootScoresheetWorker && !sessionStorage.getItem(cleanupKey)) {
                    sessionStorage.setItem(cleanupKey, '1');
                    window.location.reload();
                }
            })
            .catch(() => {});
    })();
</script>
<?php endif; ?>

<script>
    (() => {
        if (!navigator.onLine || !('localStorage' in window)) return;

        const apiUrl = 'gala_scoresheet_api.php';
        const pendingLanePrefix = 'pending_lanes_';
        const pendingSubmitPrefix = 'pending_submit_';
        const submitSuccessPrefix = 'submit_success_';

        function postForm(fields) {
            const fd = new FormData();
            Object.entries(fields).forEach(([key, value]) => fd.append(key, value));
            return fetch(apiUrl, { method: 'POST', body: fd })
                .then(async (response) => {
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.error || 'Scoresheet sync failed');
                    }
                    return payload;
                });
        }

        async function syncPendingLanes() {
            const keys = Object.keys(localStorage).filter((key) => key.startsWith(pendingLanePrefix));
            await Promise.all(keys.map(async (key) => {
                const scoresheetId = key.slice(pendingLanePrefix.length);
                const data = JSON.parse(localStorage.getItem(key) || '{}');
                await postForm({
                    action: 'save_lanes',
                    scoresheet_id: scoresheetId,
                    lanes: JSON.stringify(data.lanes || []),
                    recorder_name: data.recorderName || ''
                });
                localStorage.removeItem(key);
            }));
        }

        function openScoresheetDb() {
            return new Promise((resolve) => {
                if (!('indexedDB' in window)) return resolve(null);
                const request = indexedDB.open('GalaScoresheets', 1);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => resolve(null);
            });
        }

        async function readAllPendingResults(db) {
            if (!db || !db.objectStoreNames.contains('pendingSync')) return [];
            return new Promise((resolve) => {
                const tx = db.transaction('pendingSync', 'readonly');
                const request = tx.objectStore('pendingSync').getAll();
                request.onsuccess = () => resolve(request.result || []);
                request.onerror = () => resolve([]);
            });
        }

        async function clearPendingResults(db, ids) {
            if (!db || !ids.length || !db.objectStoreNames.contains('pendingSync')) return;
            return new Promise((resolve) => {
                const tx = db.transaction('pendingSync', 'readwrite');
                const store = tx.objectStore('pendingSync');
                ids.forEach((id) => store.delete(id));
                tx.oncomplete = () => resolve();
                tx.onerror = () => resolve();
            });
        }

        async function syncPendingResults() {
            const db = await openScoresheetDb();
            const pending = await readAllPendingResults(db);
            const byScoresheet = pending.reduce((groups, row) => {
                if (!row.scoresheet_id || !row.result) return groups;
                groups[row.scoresheet_id] ||= [];
                groups[row.scoresheet_id].push(row);
                return groups;
            }, {});

            for (const [scoresheetId, rows] of Object.entries(byScoresheet)) {
                await postForm({
                    action: 'save_batch',
                    scoresheet_id: scoresheetId,
                    results: JSON.stringify(rows.map((row) => row.result))
                });
                await clearPendingResults(db, rows.map((row) => row.id));
            }
        }

        async function syncPendingSubmissions() {
            const keys = Object.keys(localStorage).filter((key) => key.startsWith(pendingSubmitPrefix));
            await Promise.all(keys.map(async (key) => {
                const scoresheetId = key.slice(pendingSubmitPrefix.length);
                const data = JSON.parse(localStorage.getItem(key) || '{}');
                await postForm({
                    action: 'submit',
                    scoresheet_id: scoresheetId,
                    total_points_json: JSON.stringify(data.totalPoints || {})
                });
                localStorage.removeItem(key);
                localStorage.setItem(`${submitSuccessPrefix}${scoresheetId}`, String(Date.now()));
            }));
        }

        (async () => {
            try {
                await syncPendingLanes();
                await syncPendingResults();
                await syncPendingSubmissions();
            } catch (err) {
                console.warn('Pending scoresheet sync will retry later:', err);
            }
        })();
    })();
</script>

<style>
    .league-logo-link {
        display: inline-flex;
        align-items: center;
    }

    .league-logo-fx .league-logo-img {
        transform-origin: left center;
        transition: transform 550ms cubic-bezier(0.22, 1, 0.36, 1), filter 350ms ease;
        will-change: transform, filter;
    }

    .league-logo-fx:not(.is-compact) .league-logo-img {
        transform: translateY(18px) scale(1.85);
        filter: drop-shadow(0 12px 24px rgba(14, 116, 144, 0.35));
    }

    @media (max-width: 768px) {
        .league-logo-fx:not(.is-compact) .league-logo-img {
            transform: translateY(10px) scale(1.35);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .league-logo-fx .league-logo-img {
            transition: none;
            transform: none !important;
        }
    }
</style>

<nav class="border-b border-slate-800 bg-slate-900/95 backdrop-blur-md sticky top-0 z-50 print:hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index" class="league-logo-link<?php echo $enableLogoFlair ? ' league-logo-fx' : ''; ?>">
                    <img class="h-10 w-auto league-logo-img" src="images/league-logo.svg" alt="Cotswold League">
                </a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Home</a>
                        <a href="clubs"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Clubs</a>
                        <a href="spectators"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Spectators</a>
                        <a href="season-draw"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Season Draw</a>
                        <a href="table"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">League
                            Table</a>
                        <a href="history"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">History</a>
                        <a href="join"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Join
                            Us</a>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:block text-sky-500 font-bold text-sm tracking-wider uppercase">Season <?php echo $navSeasonYear; ?></div>
                <a href="teamportal"
                    class="hidden md:inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-500/25 transition-all hover:bg-sky-400 hover:shadow-sky-400/30 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                    <i data-lucide="lock" class="w-3 h-3"></i> Team Login
                </a>
                <button id="mobile-menu-button" class="md:hidden text-slate-300 hover:text-white p-2 transition-colors">
                    <i data-lucide="menu" id="menu-icon"></i>
                    <i data-lucide="x" id="close-icon" class="hidden"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile Menu Container -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-2 pb-6 space-y-1">
        <a href="index"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Home</a>
        <a href="clubs"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Clubs</a>
        <a href="spectators"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Spectators</a>
        <a href="season-draw"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Season Draw</a>
        <a href="table" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">League
            Table</a>
        <a href="history"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">History</a>
        <a href="join" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Join
            Us</a>
        <a href="teamportal"
            class="block rounded-md bg-sky-500 px-3 py-2 text-base font-semibold text-white shadow-lg shadow-sky-500/20 transition-colors hover:bg-sky-400 flex items-center gap-2">
            <i data-lucide="lock" class="w-4 h-4"></i> Team Login
        </a>
    </div>
    <script>
        // Mobile Menu Toggle Logic
        const menuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                const isHidden = mobileMenu.classList.contains('hidden');
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    menuIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            });
        }

        // Logo flair: start larger and settle to navbar size once the user scrolls.
        const animatedLogoLink = document.querySelector('.league-logo-fx');
        if (animatedLogoLink) {
            const syncLogoState = () => {
                if (window.scrollY > 28) {
                    animatedLogoLink.classList.add('is-compact');
                } else {
                    animatedLogoLink.classList.remove('is-compact');
                }
            };

            syncLogoState();
            window.addEventListener('scroll', syncLogoState, { passive: true });
        }
    </script>
</nav>
