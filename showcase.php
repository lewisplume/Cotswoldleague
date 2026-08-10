<?php
include 'db.php';

// Fetch all clubs for dropdown
$clubs = [];
$sql_clubs = "SELECT id, name, logo FROM clubs ORDER BY name ASC";
$res_clubs = $conn->query($sql_clubs);
if ($res_clubs) {
    while ($c = $res_clubs->fetch_assoc()) {
        $clubs[] = $c;
    }
}

// Ensure results are handled if POST is sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $auto_play = isset($_POST['auto_play']);
    $reveal_order = $_POST['reveal_order'];
    $display_results = [];
    
    if ($mode === 'db') {
        $source = $_POST['data_source'];
        $filter = $_POST['filter'];
        
        $sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4, c.logo, 
                (r.round_1 + r.round_2 + r.round_3 + r.round_4) as total 
                FROM results r JOIN clubs c ON r.club_id = c.id 
                WHERE r.season_year = ?
                ORDER BY total DESC, c.name ASC";

        $results_stmt = $conn->prepare($sql);
        $results_stmt->bind_param('i', $current_season_year);
        $results_stmt->execute();
        $res = $results_stmt->get_result();
        $all_teams = [];
        $pos = 1;
        if ($res) {
            while($row = $res->fetch_assoc()) {
                $points = 0;
                if ($source === 'total') $points = $row['total'];
                else $points = $row[$source];
                
                $add = false;
                if ($filter === 'all') $add = true;
                elseif ($filter === 'a_final' && $pos <= 8) $add = true;
                elseif ($filter === 'b_final' && $pos > 8 && $pos <= 14) $add = true;
                elseif ($filter === 'c_final' && $pos > 14) $add = true;
                
                // Only include if they scored points in this round (or total)
                if ($add && $points > 0) { 
                    $all_teams[] = [
                        'name' => $row['name'],
                        'points' => (int)$points,
                        'logo' => $row['logo']
                    ];
                }
                $pos++;
            }
        }
        
        // Sort by the selected round's points descending
        usort($all_teams, function($a, $b) {
            return $b['points'] <=> $a['points'];
        });
        
        $display_results = $all_teams;
        
    } elseif ($mode === 'custom') { 
        // Custom Mode
        $custom_clubs = $_POST['custom_club'] ?? [];
        $custom_points = $_POST['custom_points'] ?? [];
        
        $club_map = [];
        foreach($clubs as $c) {
            $club_map[$c['id']] = $c;
        }
        
        for ($i=0; $i<count($custom_clubs); $i++) {
            $cid = $custom_clubs[$i];
            if (!empty($cid) && isset($club_map[$cid])) {
                $display_results[] = [
                    'name' => $club_map[$cid]['name'],
                    'points' => (float)$custom_points[$i],
                    'logo' => $club_map[$cid]['logo']
                ];
            }
        }
        
        // Sort descending by points
        usort($display_results, function($a, $b) {
            return $b['points'] <=> $a['points'];
        });
    } elseif ($mode === 'custom_finals') {
        // Tri-Finals Mode
        $finals_data = ['C' => [], 'B' => [], 'A' => []];
        $club_map = [];
        foreach($clubs as $c) $club_map[$c['id']] = $c;
        
        $tiers = ['c' => 'C', 'b' => 'B', 'a' => 'A'];
        foreach ($tiers as $prefix => $tname) {
            $c_arr = $_POST["custom_{$prefix}_club"] ?? [];
            $p_arr = $_POST["custom_{$prefix}_points"] ?? [];
            $res = [];
            for ($i=0; $i<count($c_arr); $i++) {
                $cid = $c_arr[$i];
                if (!empty($cid) && isset($club_map[$cid])) {
                    $res[] = [
                        'name' => $club_map[$cid]['name'],
                        'points' => (float)$p_arr[$i],
                        'logo' => $club_map[$cid]['logo']
                    ];
                }
            }
            usort($res, function($a, $b) { return $b['points'] <=> $a['points']; });
            $rank = 1;
            foreach ($res as &$r) $r['rank'] = $rank++;
            unset($r);
            if ($reveal_order === 'last_to_first') $res = array_reverse($res);
            $finals_data[$tname] = $res;
        }
        require 'showcase_finals_presentation.php';
        exit;
    }

    if ($mode !== 'custom_finals') {
        // Assign standard Rank based on sorted order.
        $rank = 1;
        foreach ($display_results as &$r) {
            $r['rank'] = $rank++;
        }
        unset($r);

        if ($reveal_order === 'last_to_first') {
            $display_results = array_reverse($display_results);
        }
        
        require 'showcase_presentation.php';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showcase Dashboard | Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <script src="assets/vendor/alpine-3.16.0.min.js" defer></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-12 px-4 max-w-5xl mx-auto w-full flex-grow">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-white mb-2">
                    Showcase <span class="text-sky-500">Dashboard</span>
                </h1>
                <p class="text-slate-400">Configure your showcase and hit launch to prepare for screen recording.</p>
            </div>
            <i data-lucide="monitor-play" class="w-12 h-12 text-sky-500 opacity-20"></i>
        </div>

        <form method="POST" action="showcase.php" x-data="{ 
            mode: 'db', 
            teams: [{ id: Date.now(), club_id: '', points: '' }],
            c_teams: Array.from({length: 6}, () => ({ id: Math.random(), club_id: '', points: '' })),
            b_teams: Array.from({length: 6}, () => ({ id: Math.random(), club_id: '', points: '' })),
            a_teams: Array.from({length: 8}, () => ({ id: Math.random(), club_id: '', points: '' }))
        }">
            
            <!-- Global Settings Panel -->
            <div class="glass-panel p-6 rounded-2xl mb-6 shadow-xl">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5 text-sky-500"></i> Global Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Showcase Title</label>
                        <input type="text" name="title" value="A Final Results" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all font-bold text-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Showcase Subtitle</label>
                        <input type="text" name="subtitle" value="Official Standings" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Reveal Order</label>
                        <select name="reveal_order" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none">
                            <option value="last_to_first">Build Suspense (Last Place 👉 First Place)</option>
                            <option value="first_to_last">Standard (First Place 👉 Last Place)</option>
                        </select>
                    </div>
                    <div class="flex items-center mt-6">
                        <label class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" name="auto_play" class="sr-only" checked>
                                <div class="block bg-slate-700 w-14 h-8 rounded-full"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition transform translate-x-6 bg-sky-500"></div>
                            </div>
                            <div class="ml-3 text-slate-300 font-medium">Automatic Reveal Mode (Auto-Play)</div>
                        </label>
                        <style>
                            input:checked ~ .dot { transform: translateX(1.5rem); background-color: #0ea5e9; }
                        </style>
                    </div>
                </div>
            </div>

            <!-- Mode Selector -->
            <div class="flex flex-wrap gap-2 p-1 bg-slate-800/50 rounded-xl mb-6 border border-slate-700 w-fit">
                <button type="button" @click="mode = 'db'" :class="mode === 'db' ? 'bg-sky-500 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-6 py-2.5 rounded-lg font-bold text-sm transition-all focus:outline-none">
                    <i data-lucide="database" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i> From Database
                </button>
                <button type="button" @click="mode = 'custom'" :class="mode === 'custom' ? 'bg-sky-500 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-6 py-2.5 rounded-lg font-bold text-sm transition-all focus:outline-none">
                    <i data-lucide="edit-3" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i> Blank Slate (Normal)
                </button>
                <button type="button" @click="mode = 'custom_finals'" :class="mode === 'custom_finals' ? 'bg-sky-500 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-6 py-2.5 rounded-lg font-bold text-sm transition-all focus:outline-none">
                    <i data-lucide="layers" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i> Blank Slate (Tri-Finals)
                </button>
            </div>
            
            <input type="hidden" name="mode" :value="mode">

            <!-- DB Mode Panel -->
            <div x-show="mode === 'db'" class="glass-panel p-6 rounded-2xl mb-8 shadow-xl border-t-4 border-t-sky-500">
                <h3 class="text-lg font-bold mb-4 text-sky-400">Database Extraction</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Data Source (Round)</label>
                        <select name="data_source" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none cursor-pointer">
                            <option value="total">Overall Totals (For Finals)</option>
                            <option value="round_1">Round 1 Scores</option>
                            <option value="round_2">Round 2 Scores</option>
                            <option value="round_3">Round 3 Scores</option>
                            <option value="round_4">Round 4 Scores</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Finals / Tiers Filter</label>
                        <select name="filter" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none cursor-pointer">
                            <option value="all">Display All Teams</option>
                            <option value="a_final">A Final Teams (Top 8)</option>
                            <option value="b_final">B Final Teams (Places 9-14)</option>
                            <option value="c_final">C Final Teams (Places 15+)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Custom Mode Panel -->
            <div x-show="mode === 'custom'" x-cloak style="display: none;" class="glass-panel p-6 rounded-2xl mb-8 shadow-xl border-t-4 border-t-purple-500">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-purple-400">Custom Leaderboard</h3>
                    <button type="button" @click="teams.push({ id: Date.now(), club_id: '', points: '' })" class="bg-purple-600/20 text-purple-400 hover:bg-purple-600/40 px-4 py-2 rounded-lg text-sm font-bold border border-purple-500/30 transition-colors flex items-center gap-1">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Team
                    </button>
                </div>
                
                <div class="space-y-3">
                    <template x-for="(team, index) in teams" :key="team.id">
                        <div class="flex gap-4 items-center bg-slate-800/50 p-3 border border-slate-700 rounded-xl">
                            <div class="font-bold text-slate-500 w-8 text-center" x-text="'#' + (index + 1)"></div>
                            
                            <div class="flex-grow">
                                <select name="custom_club[]" x-model="team.club_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2.5 text-white focus:outline-none focus:border-purple-500" :required="mode === 'custom'">
                                    <option value="" disabled>Select Club...</option>
                                    <?php foreach ($clubs as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="w-32">
                                <input type="number" name="custom_points[]" x-model="team.points" placeholder="Points" step="0.5" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2.5 text-white focus:outline-none focus:border-purple-500 font-mono font-bold" :required="mode === 'custom'">
                            </div>
                            
                            <button type="button" @click="if(teams.length > 1) teams.splice(index, 1)" class="text-rose-500 hover:text-rose-400 p-2 opacity-70 hover:opacity-100 transition-opacity">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <div class="mt-4 text-xs text-slate-400 italic">Note: Teams will automatically be sorted highest-to-lowest points. Focus on accurate points!</div>
            </div>

            <!-- Tri-Finals Mode Panel -->
            <div x-show="mode === 'custom_finals'" x-cloak style="display: none;" class="glass-panel p-6 rounded-2xl mb-8 shadow-xl border-t-4 border-t-amber-500">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-amber-400 mb-1">Tri-Finals Configuration</h3>
                    <p class="text-sm text-slate-400">Configure the C, B, and A finals. They will be revealed sequentially starting with the C final.</p>
                </div>
                
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- C Final -->
                    <div class="bg-slate-800/40 rounded-xl p-4 border border-slate-700">
                        <h4 class="font-bold text-slate-200 mb-4 pb-2 border-b border-slate-700">C Final (6 Teams)</h4>
                        <div class="space-y-2">
                            <template x-for="(team, index) in c_teams" :key="index">
                                <div class="flex gap-2">
                                    <select name="custom_c_club[]" x-model="team.club_id" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-white focus:outline-none focus:border-amber-500" :required="mode === 'custom_finals'">
                                        <option value="" disabled>Select...</option>
                                        <?php foreach ($clubs as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                                    </select>
                                    <input type="number" name="custom_c_points[]" x-model="team.points" placeholder="Pts" step="0.5" class="w-16 bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-center text-white focus:outline-none focus:border-amber-500" :required="mode === 'custom_finals'">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- B Final -->
                    <div class="bg-slate-800/40 rounded-xl p-4 border border-slate-700">
                        <h4 class="font-bold text-slate-200 mb-4 pb-2 border-b border-slate-700">B Final (6 Teams)</h4>
                        <div class="space-y-2">
                            <template x-for="(team, index) in b_teams" :key="index">
                                <div class="flex gap-2">
                                    <select name="custom_b_club[]" x-model="team.club_id" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-white focus:outline-none focus:border-amber-500" :required="mode === 'custom_finals'">
                                        <option value="" disabled>Select...</option>
                                        <?php foreach ($clubs as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                                    </select>
                                    <input type="number" name="custom_b_points[]" x-model="team.points" placeholder="Pts" step="0.5" class="w-16 bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-center text-white focus:outline-none focus:border-amber-500" :required="mode === 'custom_finals'">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- A Final -->
                    <div class="bg-slate-800/40 rounded-xl p-4 border border-sky-500/30">
                        <h4 class="font-bold text-sky-400 mb-4 pb-2 border-b border-sky-500/30">A Final (8 Teams)</h4>
                        <div class="space-y-2">
                            <template x-for="(team, index) in a_teams" :key="index">
                                <div class="flex gap-2">
                                    <select name="custom_a_club[]" x-model="team.club_id" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-white focus:outline-none focus:border-sky-500" :required="mode === 'custom_finals'">
                                        <option value="" disabled>Select...</option>
                                        <?php foreach ($clubs as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                                    </select>
                                    <input type="number" name="custom_a_points[]" x-model="team.points" placeholder="Pts" step="0.5" class="w-16 bg-slate-900 border border-slate-600 rounded-lg px-2 py-2 text-sm text-center text-white focus:outline-none focus:border-sky-500" :required="mode === 'custom_finals'">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Launch Button -->
            <button type="submit" class="w-full bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-black text-xl py-5 rounded-2xl shadow-lg shadow-sky-500/25 transition-all transform hover:scale-[1.01] hover:-translate-y-1 flex items-center justify-center gap-3">
                <i data-lucide="play-circle" class="w-6 h-6"></i> Launch Presentation
            </button>

        </form>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
