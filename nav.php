<?php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$enableLogoFlair = $currentScript !== 'index.php';
?>

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
                        <a href="table"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">League
                            Table</a>
                        <a href="history"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">History</a>
                        <a href="join"
                            class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Join
                            Us</a>
                        <a href="admin"
                            class="text-slate-300 hover:text-sky-400 px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2">
                            <i data-lucide="lock" class="w-3 h-3"></i> Club Rep Portal
                        </a>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:block text-sky-500 font-bold text-sm tracking-wider uppercase">Season 2026</div>
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
        <a href="table" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">League
            Table</a>
        <a href="history"
            class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">History</a>
        <a href="join" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Join
            Us</a>
        <a href="admin"
            class="block text-sky-400 hover:text-sky-300 px-3 py-2 rounded-md text-base font-medium flex items-center gap-2">
            <i data-lucide="lock" class="w-4 h-4"></i> Club Rep Portal
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