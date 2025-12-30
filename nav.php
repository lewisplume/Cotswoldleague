<nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex-shrink-0">
                    <img class="h-10 w-auto" src="images/league-logo.png" alt="Cotswold League">
                </a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Home</a>
                        <a href="clubs.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Clubs</a>
                        <a href="spectators.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Spectators</a>
                        <a href="table.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">League Table</a>
                        <a href="history.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">History</a>
                        <a href="join.php" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Join Us</a>
                        <a href="admin.php" class="text-slate-300 hover:text-sky-400 px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2">
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
        <a href="index.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Home</a>
        <a href="clubs.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Clubs</a>
        <a href="spectators.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Spectators</a>
        <a href="table.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">League Table</a>
        <a href="history.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">History</a>
        <a href="join.php" class="block text-slate-300 hover:text-white px-3 py-2 rounded-md text-base font-medium">Join Us</a>
        <a href="admin.php" class="block text-sky-400 hover:text-sky-300 px-3 py-2 rounded-md text-base font-medium flex items-center gap-2">
            <i data-lucide="lock" class="w-4 h-4"></i> Club Rep Portal
        </a>
    </div>
</nav>