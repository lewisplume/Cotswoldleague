<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// ---------------------------------------------------------
// DATABASE MIGRATION CHECK (Runs automatically if needed)
// ---------------------------------------------------------
$migration_needed = false;
$check_sql = "SHOW COLUMNS FROM venue_details LIKE 'team_1_id'";
$check_res = $conn->query($check_sql);
if ($check_res && $check_res->num_rows == 0) {
    $migration_needed = true;
}

if ($migration_needed) {
    // 1. Alter Table
    $alter_sql = "ALTER TABLE venue_details 
                  ADD COLUMN team_1_id INT DEFAULT NULL,
                  ADD COLUMN team_2_id INT DEFAULT NULL,
                  ADD COLUMN team_3_id INT DEFAULT NULL,
                  ADD COLUMN team_4_id INT DEFAULT NULL,
                  ADD COLUMN round_date VARCHAR(50) DEFAULT NULL";
    $conn->query($alter_sql);

    // 2. Fetch Club ID Map
    $club_map = [];
    $c_res = $conn->query("SELECT id, name FROM clubs");
    if ($c_res) {
        while ($r = $c_res->fetch_assoc()) {
            $club_map[$r['name']] = $r['id'];
        }
    }

    // 3. Populate from original season_data.php structure
    $initial_seed_data = [
        [
            "round" => 1,
            "date" => "31/01/2026",
            "galas" => [
                ["host" => "Cwmbran", "teams" => ["Cwmbran", "Yeovil", "Dursley", "Monnow SC"]],
                ["host" => "Backwell", "teams" => ["Backwell", "Brockworth", "Bridgwater", "Forest of Dean"]],
                ["host" => "Corsham", "teams" => ["Corsham", "Swindon ASC", "Burnham-On-Sea", "Bristol North"]],
                ["host" => "Bath Dolphin", "teams" => ["Bath Dolphin", "Clevedon", "Wells", "Newport"]],
                ["host" => "COB (City of Bristol)", "teams" => ["COB (City of Bristol)", "Academy Swim Team", "Southwold SC", "Severnside Tritons"]]
            ]
        ],
        [
            "round" => 2,
            "date" => "14/02/2026",
            "galas" => [
                ["host" => "Yeovil", "teams" => ["Yeovil", "Bath Dolphin", "Bridgwater", "Bristol North"]],
                ["host" => "Brockworth", "teams" => ["Brockworth", "COB (City of Bristol)", "Burnham-On-Sea", "Newport"]],
                ["host" => "Swindon ASC", "teams" => ["Swindon ASC", "Cwmbran", "Wells", "Severnside Tritons"]],
                ["host" => "Clevedon", "teams" => ["Clevedon", "Backwell", "Southwold SC", "Monnow SC"]],
                ["host" => "Academy Swim Team", "teams" => ["Academy Swim Team", "Corsham", "Dursley", "Forest of Dean"]]
            ]
        ],
        [
            "round" => 3,
            "date" => "07/03/2026",
            "galas" => [
                ["host" => "Dursley", "teams" => ["Dursley", "COB (City of Bristol)", "Clevedon", "Bristol North"]],
                ["host" => "Bridgwater", "teams" => ["Bridgwater", "Cwmbran", "Academy Swim Team", "Newport"]],
                ["host" => "Burnham-On-Sea", "teams" => ["Burnham-On-Sea", "Backwell", "Yeovil", "Severnside Tritons"]],
                ["host" => "Wells", "teams" => ["Wells", "Corsham", "Brockworth", "Monnow SC"]],
                ["host" => "Southwold SC", "teams" => ["Southwold SC", "Bath Dolphin", "Swindon ASC", "Forest of Dean"]]
            ]
        ],
        [
            "round" => 4,
            "date" => "28/03/2026",
            "galas" => [
                ["host" => "Monnow SC", "teams" => ["Monnow SC", "Bath Dolphin", "Academy Swim Team", "Burnham-On-Sea"]],
                ["host" => "Forest of Dean", "teams" => ["Forest of Dean", "COB (City of Bristol)", "Yeovil", "Wells"]],
                ["host" => "Bristol North", "teams" => ["Bristol North", "Cwmbran", "Brockworth", "Southwold SC"]],
                ["host" => "Newport", "teams" => ["Newport", "Backwell", "Swindon ASC", "Dursley"]],
                ["host" => "Severnside Tritons", "teams" => ["Severnside Tritons", "Corsham", "Clevedon", "Bridgwater"]]
            ]
        ]
    ];

    $update_stmt = $conn->prepare("UPDATE venue_details SET team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, round_date=? WHERE club_id=? AND round_number=?");
    
    foreach ($initial_seed_data as $rnd) {
        $rnd_num = $rnd['round'];
        $rnd_date = $rnd['date'];
        foreach ($rnd['galas'] as $gala) {
            $host_id = $club_map[$gala['host']] ?? null;
            $t1 = $club_map[$gala['teams'][0]] ?? null;
            $t2 = $club_map[$gala['teams'][1]] ?? null;
            $t3 = $club_map[$gala['teams'][2]] ?? null;
            $t4 = $club_map[$gala['teams'][3]] ?? null;
            
            if ($host_id) {
                $update_stmt->bind_param("iiiisii", $t1, $t2, $t3, $t4, $rnd_date, $host_id, $rnd_num);
                $update_stmt->execute();
            }
        }
    }
}
// ---------------------------------------------------------

// Fetch All Clubs for Dropdowns
$all_clubs = [];
$c_sql = "SELECT id, name FROM clubs ORDER BY name ASC";
$c_res = $conn->query($c_sql);
if ($c_res) {
    while($row = $c_res->fetch_assoc()) {
        $all_clubs[] = $row;
    }
}

// Handle Round Selection
$selected_round = $_GET['round'] ?? 1;
$selected_round = intval($selected_round);

// Handle Update POST
$success_msg = "";
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_draw'])) {
    $round_date = $_POST['round_date'];
    $updates = $_POST['venue']; 
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE venue_details SET team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, round_date=? WHERE id=?");
        
        foreach ($updates as $v_id => $data) {
            $t1 = !empty($data['team_1']) ? $data['team_1'] : null;
            $t2 = !empty($data['team_2']) ? $data['team_2'] : null;
            $t3 = !empty($data['team_3']) ? $data['team_3'] : null;
            $t4 = !empty($data['team_4']) ? $data['team_4'] : null;
            $stmt->bind_param("iiiisi", $t1, $t2, $t3, $t4, $round_date, $v_id);
            $stmt->execute();
        }
        
        // Log to audit log
        $log_sql = "INSERT INTO audit_log (club_name, action, change_details, timestamp) VALUES ('League Admin', 'Season Draw Update', 'Updated draw for Round $selected_round', NOW())";
        $conn->query($log_sql);
        
        $conn->commit();
        $success_msg = "Draw for Round $selected_round saved successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error saving draw: " . $e->getMessage();
    }
}

// Fetch Venues for the selected round
$round_venues = [];
$round_date_display = "";
$v_sql = "SELECT vd.*, c.name AS host_club_name 
          FROM venue_details vd 
          JOIN clubs c ON vd.club_id = c.id 
          WHERE vd.round_number = ? 
          ORDER BY c.name ASC";
$stmt = $conn->prepare($v_sql);
$stmt->bind_param("i", $selected_round);
$stmt->execute();
$v_res = $stmt->get_result();
if ($v_res) {
    while ($row = $v_res->fetch_assoc()) {
        $round_venues[] = $row;
        if (!empty($row['round_date'])) {
            $round_date_display = $row['round_date'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Season Draw Dashboard</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="../assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="../assets/vendor/lucide-1.31.0.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="max-w-7xl mx-auto w-full px-4 py-8 flex-grow">
        
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold flex items-center gap-3">
                Season Draw <span class="text-sky-500">Dashboard</span>
            </h1>
            <div class="text-end">
                 <p class="text-xs text-slate-500 uppercase tracking-widest">Logged in as Admin</p>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 p-4 rounded-xl mb-6 flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl mb-6 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- STEP 1: SELECT ROUND -->
        <div class="glass-panel p-6 rounded-2xl border border-white/5 mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="calendar-days" class="w-6 h-6 text-sky-400"></i>
                <div>
                    <h2 class="text-lg font-bold text-white">Select Round</h2>
                    <p class="text-xs text-slate-400">Choose a round to configure the participating teams.</p>
                </div>
            </div>
            
            <div class="flex gap-2 bg-slate-900 overflow-hidden border border-slate-700/50 p-1 rounded-xl">
                <?php for($i = 1; $i <= 4; $i++): ?>
                    <a href="?round=<?php echo $i; ?>" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all <?php echo $selected_round === $i ? 'bg-sky-600 text-white shadow-lg' : 'text-slate-400 hover:text-white hover:bg-slate-800'; ?>">
                        Round <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- STEP 2: EDIT DRAW -->
        <form method="POST" class="space-y-6 relative">
            <div class="glass-panel p-6 rounded-2xl border border-white/5 bg-slate-900/50 flex flex-col md:flex-row justify-between items-center gap-4 sticky top-4 z-20 shadow-2xl backdrop-blur-xl">
                <div>
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        Configure Round <?php echo $selected_round; ?>
                    </h2>
                    <p class="text-xs text-slate-400 text-left mt-1">Updates to this page immediately reflect across the site.</p>
                </div>
                
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="flex items-center gap-3 w-full">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider flex-shrink-0">Round Date:</label>
                        <input type="text" name="round_date" value="<?php echo htmlspecialchars($round_date_display); ?>" class="w-48 bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="e.g. 31/01/2026">
                    </div>
                
                    <button type="submit" name="update_draw" class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20 whitespace-nowrap">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Round <?php echo $selected_round; ?>
                    </button>
                </div>
            </div>

            <?php if (empty($round_venues)): ?>
                 <div class="text-center py-12 glass-panel rounded-2xl border border-white/5">
                    <div class="bg-slate-800/50 rounded-full p-4 inline-block mb-4">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-slate-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">No Venues Found</h3>
                    <p class="text-slate-400 text-sm max-w-md mx-auto">There are no host venues configured for Round <?php echo $selected_round; ?> yet. Please set up the hosts in the Host Venue Management page first.</p>
                 </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php foreach ($round_venues as $venue): ?>
                        <div class="glass-panel p-6 rounded-2xl border border-white/5 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-r from-sky-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                            
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
                                <div class="flex items-center gap-3">
                                    <div class="bg-sky-500/20 p-2.5 rounded-lg border border-sky-500/20">
                                        <i data-lucide="map-pin" class="w-5 h-5 text-sky-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($venue['host_club_name']); ?></h3>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($venue['venue_name'] ?: 'Venue TBA'); ?></p>
                                    </div>
                                </div>
                                <span class="bg-slate-800 text-slate-400 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Host</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
                                <?php for($t = 1; $t <= 4; $t++): 
                                    $col_name = "team_{$t}_id";
                                    $team_val = $venue[$col_name];
                                ?>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Team <?php echo $t; ?></label>
                                        <select name="venue[<?php echo $venue['id']; ?>][team_<?php echo $t; ?>]" class="w-full bg-slate-900 border border-slate-700 text-white rounded-lg px-3 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors appearance-none cursor-pointer">
                                            <option value="">-- TBA --</option>
                                            <?php foreach ($all_clubs as $club): ?>
                                                <option value="<?php echo $club['id']; ?>" <?php echo ($team_val == $club['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($club['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Ensure footer is pushed down -->
    <footer class="mt-auto text-center text-slate-600 text-[10px] uppercase tracking-[0.3em] py-8">
        &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
