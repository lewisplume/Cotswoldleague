<?php
include 'db.php';
include 'season_data.php';

// Fetch Results sorted by Total Points (Descending)
// This SQL ensures the league table is already sorted by position before the page loads
$sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4, 
       (r.round_1 + r.round_2 + r.round_3 + r.round_4) as total 
       FROM results r 
       JOIN clubs c ON r.club_id = c.id 
       WHERE r.season_year = $current_season_year
       ORDER BY total DESC, c.name ASC";
$result = $conn->query($sql);

$final_venues = [];
$final_venue_stmt = $conn->prepare("SELECT vd.gala_type, vd.venue_name FROM venue_details vd WHERE vd.season_year = ? AND (vd.round_number = 99 OR vd.gala_type IN ('a_final','b_final','c_final'))");
$final_venue_stmt->bind_param('i', $current_season_year);
$final_venue_stmt->execute();
$final_venue_result = $final_venue_stmt->get_result();
while ($venue = $final_venue_result->fetch_assoc()) {
    $final_venues[$venue['gala_type']] = trim((string)$venue['venue_name']);
}
$final_venue_stmt->close();

$club_names_by_id = [];
$club_name_result = $conn->query('SELECT id, name FROM clubs');
if ($club_name_result) {
    while ($club = $club_name_result->fetch_assoc()) {
        $club_names_by_id[(int)$club['id']] = (string)$club['name'];
    }
}

$published_finals = [];
$published_stmt = $conn->prepare("SELECT gala_type, total_points_json FROM gala_scoresheets WHERE season_year = ? AND gala_type IN ('a_final','b_final','c_final') AND status = 'published' ORDER BY updated_at DESC, id DESC");
$published_stmt->bind_param('i', $current_season_year);
$published_stmt->execute();
$published_result = $published_stmt->get_result();
while ($published = $published_result->fetch_assoc()) {
    $tier = (string)$published['gala_type'];
    if (isset($published_finals[$tier])) {
        continue;
    }
    $totals = json_decode((string)$published['total_points_json'], true);
    if (!is_array($totals)) {
        continue;
    }
    $rows = [];
    foreach ($totals as $clubId => $points) {
        $clubId = (int)$clubId;
        if (!isset($club_names_by_id[$clubId]) || !is_numeric($points)) {
            continue;
        }
        $rows[] = ['name' => $club_names_by_id[$clubId], 'points' => (float)$points];
    }
    usort($rows, static fn(array $a, array $b): int => ($b['points'] <=> $a['points']) ?: strcmp($a['name'], $b['name']));
    $published_finals[$tier] = $rows;
}
$published_stmt->close();

// Process Season Data for Table Context
$team_next_gala = [];
$team_hosting_rounds = [];
$next_round_draw = null;

// 1. Find the next round
$today = date('Y-m-d');
foreach ($season_draw as $round) {
    $r_date = DateTime::createFromFormat('d/m/Y', $round['date']);
    if ($r_date && $r_date->format('Y-m-d') > $today) {
        $next_round_draw = $round;
        // 2. Map teams to their next venue
        foreach ($round['galas'] as $gala) {
            foreach ($gala['teams'] as $team) {
                $team_next_gala[$team] = $gala['host'];
            }
        }
        break; // Found the next round, stop looking
    }
}

// 3. Map hosting duties
foreach ($season_draw as $round) {
    foreach ($round['galas'] as $gala) {
        $team_hosting_rounds[$gala['host']][] = $round['round'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | League Table</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .table-row-hover:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>

<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            League <span class="text-sky-500">Table</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Season standings and progression towards the Finals.
        </p>
    </div>

    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 flex-grow">

        <!-- Main League Table -->
        <div class="glass-panel backdrop-blur-md rounded-3xl overflow-hidden shadow-2xl h-fit mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/50 border-b border-white/5">
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500">Pos</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500">Club Name
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500 text-center">
                                R1</th>
                            <th
                                class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500 text-center">
                                R2</th>
                            <th
                                class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500 text-center">
                                R3</th>
                            <th
                                class="px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-500 text-center">
                                R4</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-sky-500 text-center">
                                Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
$pos = 1;
$a_final = [];
$b_final = [];
$c_final = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($pos <= 8) {
            $a_final[] = $row;
        }
        elseif ($pos <= 14) {
            $b_final[] = $row;
        }
        else {
            $c_final[] = $row;
        }

        echo '<tr class="table-row-hover transition-colors">';
        echo '<td class="px-4 py-3 text-sm font-medium text-slate-500 italic">' . $pos++ . '</td>';
        echo '<td class="px-4 py-3 text-sm font-bold text-white">' . htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_1"] > 0 ? $row["round_1"] : '-') . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_2"] > 0 ? $row["round_2"] : '-') . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_3"] > 0 ? $row["round_3"] : '-') . '</td>';
        $round_4_display = ($row["round_4"] > 0 ? $row["round_4"] : '-');
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . $round_4_display . '</td>';
        echo '<td class="px-4 py-3 text-sm font-black text-sky-500 text-center count-up" data-target="' . $row["total"] . '">0</td>';
        echo '</tr>';
    }
}
?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></div>
                        <h4 class="font-bold text-sm">A Final - <?php echo htmlspecialchars($final_venues['a_final'] ?? 'Venue to be confirmed', ENT_QUOTES, 'UTF-8'); ?></h4>
                    </div>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($a_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="font-bold text-emerald-400">
                            <?php echo $t['total']; ?>
                        </span>
                    </li>
                    <?php
endforeach; ?>
                </ul>
            </div>
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-2 h-2 rounded-full bg-amber-500 shadow-lg shadow-amber-500/50"></div>
                    <h4 class="font-bold text-sm">B Final - <?php echo htmlspecialchars($final_venues['b_final'] ?? 'Venue to be confirmed', ENT_QUOTES, 'UTF-8'); ?></h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($b_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="font-bold text-amber-400">
                            <?php echo $t['total']; ?>
                        </span>
                    </li>
                    <?php
endforeach; ?>
                </ul>
            </div>
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-2 h-2 rounded-full bg-rose-500 shadow-lg shadow-rose-500/50"></div>
                    <h4 class="font-bold text-sm">C Final - <?php echo htmlspecialchars($final_venues['c_final'] ?? 'Venue to be confirmed', ENT_QUOTES, 'UTF-8'); ?></h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($c_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="font-bold text-rose-400">
                            <?php echo $t['total']; ?>
                        </span>
                    </li>
                    <?php
endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Finals Results -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl mb-2">
                Finals <span class="text-sky-500">Results</span>
            </h2>
            <p class="text-sm text-slate-400">Cotswold Swimming Series <?php echo $current_season_year; ?> Finals Day</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <?php
            $finalCards = [
                'a_final' => ['label' => 'A Final', 'colour' => 'emerald', 'icon' => 'trophy'],
                'b_final' => ['label' => 'B Final', 'colour' => 'amber', 'icon' => 'medal'],
                'c_final' => ['label' => 'C Final', 'colour' => 'rose', 'icon' => 'award'],
            ];
            foreach ($finalCards as $tier => $card):
                $tierResults = $published_finals[$tier] ?? [];
            ?>
            <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-<?php echo $card['colour']; ?>-500">
                <h3 class="font-bold text-xl mb-4 text-<?php echo $card['colour']; ?>-400 flex items-center gap-2">
                    <i data-lucide="<?php echo $card['icon']; ?>" class="w-5 h-5"></i> <?php echo $card['label']; ?>
                </h3>
                <?php if ($tierResults === []): ?>
                    <p class="text-sm text-slate-500">Results not yet published for this season.</p>
                <?php else: ?>
                <ul class="space-y-2 text-sm text-slate-300">
                    <?php foreach ($tierResults as $position => $finalResult): ?>
                    <li class="flex justify-between gap-3">
                        <span><?php echo $position < 3 ? ['🥇', '🥈', '🥉'][$position] . ' ' : ''; ?><?php echo htmlspecialchars($finalResult['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="font-bold text-<?php echo $card['colour']; ?>-400"><?php echo htmlspecialchars((string)$finalResult['points'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Next Round Draw Table -->
        <div class="w-full max-w-4xl mx-auto">
            <?php if ($next_round_draw): ?>
            <div class="glass-panel backdrop-blur-md rounded-3xl overflow-hidden shadow-2xl">
                <div class="bg-slate-900/50 border-b border-white/5 px-6 py-5 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-widest text-sky-500">Next Round Draw</h3>
                    <span class="text-xs font-bold text-slate-500">
                        <?php echo $next_round_draw['date']; ?>
                    </span>
                </div>
                <div class="divide-y divide-white/5">
                    <?php foreach ($next_round_draw['galas'] as $gala): ?>
                    <div class="p-5 hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="map-pin" class="w-3 h-3 text-sky-500"></i>
                            <span class="text-sm font-bold text-white">
                                <?php echo $gala['host']; ?>
                            </span>
                            <span
                                class="text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded uppercase tracking-wider">Host</span>
                        </div>
                        <div class="pl-5">
                            <p class="text-xs text-slate-400 leading-relaxed mb-2">
                                <?php echo explode('.', $gala['details'])[0]; ?>.
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($gala['teams'] as $team): ?>
                                <?php if ($team !== $gala['host']): ?>
                                <span
                                    class="text-[10px] font-bold text-slate-400 bg-slate-800/50 border border-slate-700 px-2 py-1 rounded-full">
                                    <?php echo $team; ?>
                                </span>
                                <?php
            endif; ?>
                                <?php
        endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                </div>
            </div>
            <?php
else: ?>
            <div class="glass-panel p-8 rounded-3xl text-center">
                <i data-lucide="flag" class="w-12 h-12 text-slate-600 mx-auto mb-4"></i>
                <h3 class="text-lg font-bold text-white">Season Complete</h3>
                <p class="text-sm text-slate-400 mt-2">All preliminary rounds have been completed.</p>
            </div>
            <?php
endif; ?>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em] py-8">
            &copy; <?php echo $current_season_year; ?> The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();



        // NEW: Number Count-Up Animation
        document.addEventListener('DOMContentLoaded', () => {
            const counters = document.querySelectorAll('.count-up');
            // speed determines how fast the count happens (lower = faster)
            const speed = 200;

            counters.forEach(counter => {
                const updateCount = () => {
                    // Get the target number from data attribute
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;

                    // Calculate increment 
                    const inc = Math.max(1, Math.ceil(target / speed));

                    if (count < target) {
                        // Add increment and run function again
                        counter.innerText = count + inc > target ? target : count + inc;
                        setTimeout(updateCount, 15);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });
        });
    </script>
</body>

</html>
