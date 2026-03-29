<?php
include 'db.php';
include 'season_data.php';

// Fetch Results sorted by Total Points (Descending)
// This SQL ensures the league table is already sorted by position before the page loads
$sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4, 
       (r.round_1 + r.round_2 + r.round_3 + r.round_4) as total 
       FROM results r 
       JOIN clubs c ON r.club_id = c.id 
       ORDER BY total DESC, c.name ASC";
$result = $conn->query($sql);

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
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
        echo '<td class="px-4 py-3 text-sm font-bold text-white">' . $row["name"] . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_1"] > 0 ? $row["round_1"] : '-') . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_2"] > 0 ? $row["round_2"] : '-') . '</td>';
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . ($row["round_3"] > 0 ? $row["round_3"] : '-') . '</td>';
        $round_4_display = ($row["round_4"] > 0 ? $row["round_4"] : '-');
        if ($row["name"] === 'Burnham-On-Sea') {
            $round_4_display .= '<sup class="text-amber-400 font-bold ml-0.5">*</sup>';
        }
        elseif ($row["name"] === 'Yeovil') {
            $round_4_display .= '<sup class="text-amber-400 font-bold ml-0.5">*</sup>';
        }
        echo '<td class="px-4 py-3 text-sm text-slate-600 text-center">' . $round_4_display . '</td>';
        echo '<td class="px-4 py-3 text-sm font-black text-sky-500 text-center count-up" data-target="' . $row["total"] . '">0</td>';
        echo '</tr>';
    }
}
?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-white/5 text-[11px] text-slate-400">
                <p>* Burnham-On-Sea R4: Virtual scores used using their Round 1 times.</p>
                <p>* Yeovil R4: Virtual scores used using their Round 2 times.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></div>
                        <h4 class="font-bold text-sm">A Final - Hutton Moore Leisure Centre</h4>
                    </div>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($a_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo $t['name']; ?>
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
                    <h4 class="font-bold text-sm">B Final - Pontypool Leisure Centre</h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($b_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo $t['name']; ?>
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
                    <h4 class="font-bold text-sm">C Final - Easton Leisure Centre</h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach ($c_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span>
                            <?php echo $t['name']; ?>
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
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
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