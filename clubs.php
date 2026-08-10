<?php
include 'db.php';
$sql = "SELECT * FROM clubs WHERE is_active = 1 ORDER BY name ASC";
$result = $conn->query($sql);
$club_count = $result ? $result->num_rows : 0;
$clubs_json_data = [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Participating Clubs</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <link rel="stylesheet" href="assets/vendor/leaflet-1.9.4.css" />
    <script src="assets/vendor/leaflet-1.9.4.js"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .card-gradient {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        }

        .swim-gradient {
            background: linear-gradient(135deg, #075985 0%, #0ea5e9 100%);
        }
    </style>
</head>

<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-10 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            Participating <span class="text-sky-500">Teams</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-3xl mx-auto mb-8 leading-relaxed">
            The Cotswold Swimming League is proudly made up of <?php echo number_format($club_count); ?> competitive clubs spanning across seven counties.
            Browse the map or the directory below to find the team closest to you and discover more about
            their programs!
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

        <!-- Interactive Map Container -->
        <div id="clubMap" class="w-full h-96 rounded-2xl border border-slate-700/50 shadow-xl mb-10 relative z-0"></div>

        <div id="clubGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $club_name = (string)$row['name'];
                    $pool_name = (string)$row['pool_name'];
                    $postcode = (string)$row['postcode'];
                    $logo_filename = basename((string)$row['logo']);
                    $website = trim((string)$row['website']);
                    $website_scheme = strtolower((string)parse_url($website, PHP_URL_SCHEME));
                    if (!filter_var($website, FILTER_VALIDATE_URL) || !in_array($website_scheme, ['http', 'https'], true)) {
                        $website = '#';
                    }

                    $clubs_json_data[] = [
                        'name' => $club_name,
                        'pool_name' => $pool_name,
                        'postcode' => $postcode,
                        'logo' => $logo_filename,
                        'lat' => $row['latitude'] ?? null,
                        'lng' => $row['longitude'] ?? null
                    ];

                    echo '
                    <div class="club-card card-gradient rounded-2xl p-6 border border-slate-700/50 hover:border-sky-500/50 transition-all duration-300 group" data-name="' . htmlspecialchars(strtolower($club_name), ENT_QUOTES, 'UTF-8') . '" data-pool="' . htmlspecialchars(strtolower($pool_name), ENT_QUOTES, 'UTF-8') . '">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-16 w-16 bg-white rounded-xl p-2 flex items-center justify-center overflow-hidden border border-slate-600 shadow-md group-hover:border-sky-400/50">
                                <img src="images/Teams/' . rawurlencode($logo_filename) . '" alt="' . htmlspecialchars($club_name, ENT_QUOTES, 'UTF-8') . '" class="object-contain h-full w-full">
                            </div>
                            <a href="' . htmlspecialchars($website, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-sky-400 hover:text-sky-300 uppercase tracking-wider border border-sky-500/20 px-3 py-1 rounded-full hover:bg-sky-500/10 transition-colors">
                                Website
                            </a>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-1 group-hover:text-sky-400 transition-colors">' . htmlspecialchars($club_name, ENT_QUOTES, 'UTF-8') . '</h3>
                        <div class="flex items-start mt-3 text-slate-400 text-sm">
                            <i data-lucide="map-pin" class="h-4 w-4 mr-2 mt-0.5 text-slate-500"></i>
                            <span>' . htmlspecialchars($pool_name, ENT_QUOTES, 'UTF-8') . '<br><span class="text-slate-500 text-xs">' . htmlspecialchars($postcode, ENT_QUOTES, 'UTF-8') . '</span></span>
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
            &copy; <?php echo (int)$current_season_year; ?> The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();

        // Initialize Map
        const clubsData = <?php echo json_encode($clubs_json_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        })[character]);
        const map = L.map('clubMap').setView([51.8, -2.1], 9);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const bounds = [];
        clubsData.forEach(club => {
            if (club.lat && club.lng) {
                const marker = L.marker([club.lat, club.lng]).addTo(map);
                bounds.push([club.lat, club.lng]);

                const googleMapsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(club.pool_name + ' ' + club.postcode)}`;
                const safeName = escapeHtml(club.name);
                const safePool = escapeHtml(club.pool_name);
                const safeLogo = encodeURIComponent(String(club.logo || ''));
                const popupContent = `
                    <div class="text-center p-2 min-w-[200px]">
                        <img src="images/Teams/${safeLogo}" alt="${safeName}" class="h-12 w-12 mx-auto mb-2 object-contain bg-white rounded-lg p-1 border border-slate-200">
                        <h4 class="font-bold text-slate-800 text-sm mb-1">${safeName}</h4>
                        <p class="text-xs text-slate-600 mb-3">${safePool}</p>
                        <a href="${googleMapsLink}" target="_blank" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold py-1.5 px-3 rounded-lg block transition-colors" style="text-decoration:none;">Get Directions</a>
                    </div>
                `;
                marker.bindPopup(popupContent);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }

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
