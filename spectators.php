<?php
include 'db.php';
include 'season_data.php';

// Fetch Round 1, Round 2, Round 3, and Round 4 points for Spectators page
$completed_points = [];
$sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4 FROM results r JOIN clubs c ON r.club_id = c.id WHERE r.season_year = ?";
$points_stmt = $conn->prepare($sql);
$points_stmt->bind_param('i', $current_season_year);
$points_stmt->execute();
$result = $points_stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $completed_points[1][$row['name']] = $row['round_1'];
        $completed_points[2][$row['name']] = $row['round_2'];
        $completed_points[3][$row['name']] = $row['round_3'];
        $completed_points[4][$row['name']] = $row['round_4'];
    }
}

// Name mapping no longer needed with JOIN

// Fetch Venue Details from DB
$venue_db = [];
$v_sql = "SELECT vd.*, c.name AS host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id WHERE vd.season_year = ?";
$venue_stmt = $conn->prepare($v_sql);
$venue_stmt->bind_param('i', $current_season_year);
$venue_stmt->execute();
$v_res = $venue_stmt->get_result();
if ($v_res && $v_res->num_rows > 0) {
    while ($row = $v_res->fetch_assoc()) {
        // Key by round and host for lookup
        $key = $row['host_club_name'] . '_' . $row['round_number'];
        $venue_db[$key] = $row;
    }
}

// Helper to get points
function getPoints($round, $team, $completed_points)
{
    if (isset($completed_points[$round][$team])) {
        return $completed_points[$round][$team];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Spectators</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .card-gradient {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="text-white font-sans min-h-screen">

    <?php include 'nav.php'; ?>

    <!-- HEADER -->
    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            Spectator <span class="text-sky-500">Information</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Everything you need to know for the <?php echo (int)$current_season_year; ?> Cotswold League rounds.
        </p>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="space-y-6 mb-12">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="info" class="text-sky-500"></i> Essential Gala Guide
                </h2>

                <div class="glass-panel rounded-2xl p-6 md:p-7">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="bg-sky-500/15 p-2 rounded-lg border border-sky-500/30">
                            <i data-lucide="calendar-range" class="w-5 h-5 text-sky-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">How The League Season Works</h3>
                            <p class="text-sm text-slate-400">A quick overview for parents and supporters.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4">
                            <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">League Format</p>
                            <p class="text-slate-300 leading-relaxed">The season has 4 preliminary rounds, then Finals. After Round 4, teams are placed into Final galas (A, B or C) based on season performance.</p>
                        </div>
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4">
                            <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">Events Per Gala</p>
                            <p class="text-slate-300 leading-relaxed">Each gala includes 53 races, covering individual swims and relays across all age groups.</p>
                        </div>
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4 md:col-span-2">
                            <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">Galas vs Open Meets</p>
                            <p class="text-slate-300 leading-relaxed">League galas are team competitions where clubs race head-to-head for points. Open meets focus on individual entries, personal bests, and finals by qualifying time rather than club match scoring.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2 flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> Age Groups</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Events are grouped as 11/u, 13/u, 15/u and Open. Swimmers race in their eligible age band,
                            helping keep races fair and competitive across the gala.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2 flex items-center gap-2"><i data-lucide="timer-off" class="w-4 h-4"></i> Speeding Tickets</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            In this league, each event has a time limit. If a swimmer goes faster than that limit,
                            they can be disqualified from scoring points for that race.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2 flex items-center gap-2"><i data-lucide="git-branch" class="w-4 h-4"></i> Relays Explained</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            In freestyle relays, each swimmer completes their leg in freestyle before the next teammate
                            dives. Relay takeovers are crucial, and smooth changes can decide close races.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2 flex items-center gap-2"><i data-lucide="waves" class="w-4 h-4"></i> Medley Relays</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Medley relay order is always Backstroke, Breaststroke, Butterfly, then Freestyle.
                            Each swimmer does one stroke, so team balance across all four strokes really matters.
                        </p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl">
                    <h3 class="font-bold text-sky-400 mb-3 flex items-center gap-2"><i data-lucide="clipboard-list" class="w-4 h-4"></i> Practical Parent Tips</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4 text-slate-300">Arrive early for team check-in and warm-up announcements.</div>
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4 text-slate-300">Bring layers, snacks, and water. Pool balconies can get warm or cold quickly.</div>
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4 text-slate-300">Galas usually last between 1.30 and 2 hours, so plan food and travel around a full evening.</div>
                        <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4 text-slate-300">Follow host rules on photography, seating and poolside access.</div>
                    </div>
                </div>
        </div>

        <div class="mb-12">
            <div class="glass-panel rounded-2xl p-6 border border-sky-500/30">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <i data-lucide="file-text" class="w-5 h-5 text-sky-400"></i>
                            <h3 class="text-lg font-bold text-white">Spectator Programme</h3>
                        </div>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Get the full race list, time limits, and printable gala notes in one place.
                        </p>
                    </div>
                    <a href="spectator-programme.php?from=spectators" target="_blank"
                        class="inline-flex items-center justify-center gap-2 py-3 px-5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 font-bold text-sm border border-sky-500/40 transition-all whitespace-nowrap">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Open Programme
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">Best viewed on mobile before the gala, or printed for poolside use.</p>
            </div>
        </div>

        <div class="space-y-8">
            <div class="glass-panel rounded-2xl p-6 md:p-7 border border-sky-500/30">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <i data-lucide="calendar-days" class="w-5 h-5 text-sky-400"></i>
                            <h3 class="text-lg font-bold text-white">Season Draw</h3>
                        </div>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            View all round fixtures, venues, and Finals location details on the dedicated Season Draw page.
                        </p>
                    </div>
                    <a href="season-draw.php"
                        class="inline-flex items-center justify-center gap-2 py-3 px-5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 font-bold text-sm border border-sky-500/40 transition-all whitespace-nowrap">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        Open Season Draw
                    </a>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">You can also access live results links from the Season Draw page.</p>
            </div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; <?php echo (int)$current_season_year; ?> The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
