<?php
include 'db.php';
$sql = "SELECT * FROM clubs ORDER BY name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Participating Clubs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .card-gradient { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); }
        .swim-gradient { background: linear-gradient(135deg, #075985 0%, #0ea5e9 100%); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-10 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            Participating <span class="text-sky-500">Teams</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto mb-8">
            The 20 clubs competing in the 2026 Cotswold Swimming League.
        </p>
        
        <!-- Search is handled via JS locally for speed -->
        <div class="max-w-md mx-auto relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="search" class="h-5 w-5 text-slate-500"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="filterClubs()" 
                   class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl leading-5 bg-slate-800 text-slate-300 placeholder-slate-500 focus:outline-none focus:bg-slate-900 focus:border-sky-500 sm:text-sm transition duration-150 ease-in-out" 
                   placeholder="Search for a club...">
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 flex-grow">
        <div id="clubGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '
                    <div class="club-card card-gradient rounded-2xl p-6 border border-slate-700/50 hover:border-sky-500/50 transition-all duration-300 group" data-name="' . strtolower($row['name']) . '" data-pool="' . strtolower($row['pool_name']) . '">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-16 w-16 bg-white rounded-xl p-2 flex items-center justify-center overflow-hidden border border-slate-600 shadow-md group-hover:border-sky-400/50">
                                <img src="images/Teams/' . $row['logo'] . '" alt="' . $row['name'] . '" class="object-contain h-full w-full">
                            </div>
                            <a href="' . $row['website'] . '" target="_blank" class="text-xs font-medium text-sky-400 hover:text-sky-300 uppercase tracking-wider border border-sky-500/20 px-3 py-1 rounded-full hover:bg-sky-500/10 transition-colors">
                                Website
                            </a>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-1 group-hover:text-sky-400 transition-colors">' . $row['name'] . '</h3>
                        <div class="flex items-start mt-3 text-slate-400 text-sm">
                            <i data-lucide="map-pin" class="h-4 w-4 mr-2 mt-0.5 text-slate-500"></i>
                            <span>' . $row['pool_name'] . '<br><span class="text-slate-500 text-xs">' . $row['postcode'] . '</span></span>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=' . urlencode($row['pool_name'] . ' ' . $row['postcode']) . '" target="_blank" class="mt-4 block w-full text-center bg-slate-800 hover:bg-slate-700 text-slate-300 py-2 rounded-lg text-sm font-medium transition-colors">
                            Get Directions
                        </a>
                    </div>';
                }
            }
            ?>
        </div>
        
        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();
        // Nav JS logic (same as other files)
        const menuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

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

        // Filter Function
        function filterClubs() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.club-card');
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const pool = card.getAttribute('data-pool');
                if (name.includes(query) || pool.includes(query)) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>