<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

$success_msg = "";
$error_msg = "";

// 1. Define Mapping: Full Club Name (from `clubs` table) => DB Host Name (in `venue_details`)
$club_map = [
    "Academy Swim Team" => "AST",
    "Bath Dolphin" => "Bath",
    "COB (City of Bristol)" => "City Of Bristol",
    "Burnham-On-Sea" => "Burnham",
    "Forest of Dean" => "FOD",
    "Monnow SC" => "Monnow",
    "Severnside Tritons" => "Severnside",
    "Southwold SC" => "Southwold",
    "Swindon ASC" => "Swindon"
];

// 2. Fetch All Clubs for Dropdown (Sorted)
$all_club_names = [];
$c_sql = "SELECT name FROM clubs ORDER BY name ASC";
$c_res = $conn->query($c_sql);
if ($c_res) {
    while($row = $c_res->fetch_assoc()) {
        $all_club_names[] = $row['name'];
    }
} else {
    // Fallback if clubs table fails
    $all_club_names = ["Backwell", "Brockworth", "Bridgwater", "Cheltenham", "Cirencester", "Dursley", "Gloucester", "Newport", "Tewkesbury", "Wells", "Yeovil"];
}

// 3. Handle Club Selection
$selected_club_full = $_GET['club'] ?? null; // The "Pretty" name
$selected_db_host = null; // The short code for DB

if ($selected_club_full) {
    // Map full name to short name, or use full name if no mapping exists
    $selected_db_host = $club_map[$selected_club_full] ?? $selected_club_full;
}

// 4. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_venue'])) {
    $id = $_POST['venue_id'];
    $venue_name = $_POST['venue_name'];
    $address = $_POST['address'];
    $warm_up = $_POST['warmup_time'];
    $start_time = $_POST['start_time'];
    $payment = $_POST['payment_info'];
    $parking = $_POST['parking_info'];
    $rep_name = $_POST['club_rep'];
    
    // Maintain selection state
    $selected_club_full = $_POST['original_club_full'];
    $selected_db_host = $club_map[$selected_club_full] ?? $selected_club_full;
    $target_host_name = $_POST['target_host_name']; // For audit log clarity

    // Get old values for audit log
    $old_sql = "SELECT * FROM venue_details WHERE id = ?";
    $old_stmt = $conn->prepare($old_sql);
    $old_stmt->bind_param("i", $id);
    $old_stmt->execute();
    $old_res = $old_stmt->get_result();
    $old_row = $old_res->fetch_assoc();

    // Calculate changes
    $changes = [];
    if ($old_row['venue_name'] != $venue_name) $changes[] = "Name: '{$old_row['venue_name']}' -> '$venue_name'";
    if ($old_row['address'] != $address) $changes[] = "Address: '{$old_row['address']}' -> '$address'";
    if ($old_row['warmup_time'] != $warm_up) $changes[] = "WarmUp: '{$old_row['warmup_time']}' -> '$warm_up'";
    if ($old_row['start_time'] != $start_time) $changes[] = "Start: '{$old_row['start_time']}' -> '$start_time'";
    if ($old_row['payment_info'] != $payment) $changes[] = "Payment: '{$old_row['payment_info']}' -> '$payment'";
    if ($old_row['parking_info'] != $parking) $changes[] = "Parking: '{$old_row['parking_info']}' -> '$parking'";

    if (empty($changes)) {
        $error_msg = "No changes detected.";
    } else {
        // Update DB
        $update_sql = "UPDATE venue_details SET venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=? WHERE id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssi", $venue_name, $address, $warm_up, $start_time, $payment, $parking, $id);
        
        if ($stmt->execute()) {
            $success_msg = "Venue details updated successfully.";
            
            // Audit Log - Prefix with Target Club Name
            $change_str = "[$target_host_name] " . implode(", ", $changes);
            
            $log_sql = "INSERT INTO audit_log (club_name, action, change_details, timestamp) VALUES (?, 'Venue Update', ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            if ($log_stmt) {
                $log_stmt->bind_param("ss", $rep_name, $change_str);
                $log_stmt->execute();
            }
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// 5. Fetch Venues for Selected Club (Using DB Host Name)
$venues = [];
if ($selected_db_host) {
    $sql = "SELECT * FROM venue_details WHERE host_club = ? ORDER BY round_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_db_host);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $venues[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Edit Venues</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="max-w-4xl mx-auto w-full px-4 py-8 flex-grow">
        
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <a href="admin.php" class="p-2 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-slate-400"></i>
                </a>
                Host Venue <span class="text-sky-500">Management</span>
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

        <!-- STEP 1: SELECT HOST CLUB -->
        <div class="glass-panel p-6 rounded-2xl border border-white/5 mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i data-lucide="search" class="w-5 h-5 text-sky-400"></i> Select Host Club
            </h2>
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-grow">
                     <select name="club" onchange="this.form.submit()" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
                        <option value="" disabled <?php echo !$selected_club_full ? 'selected' : ''; ?>>-- Choose a Club --</option>
                        <?php foreach ($all_club_names as $club): ?>
                            <option value="<?php echo htmlspecialchars($club); ?>" <?php echo $selected_club_full === $club ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($club); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <noscript><button type="submit" class="bg-sky-600 px-6 py-2 rounded-lg font-bold">Go</button></noscript>
            </form>
            <?php if (!$selected_club_full): ?>
                <p class="text-slate-400 text-sm mt-3 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i> Please select the club you wish to update venues for.
                </p>
            <?php endif; ?>
        </div>

        <!-- STEP 2: EDIT VENUES -->
        <?php if ($selected_club_full && !empty($venues)): ?>
            <div class="space-y-6">
                <?php foreach ($venues as $venue): ?>
                    <div class="glass-panel p-6 rounded-2xl border border-white/5 relative overflow-hidden">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 border-b border-white/5 pb-4">
                            <div>
                                <span class="bg-sky-500/20 text-sky-400 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider mb-2 inline-block">Round <?php echo $venue['round_number']; ?></span>
                                <h2 class="text-xl font-bold text-white"><?php echo htmlspecialchars($venue['host_club']); ?></h2> 
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1">
                                <i data-lucide="database" class="w-3 h-3"></i> ID: #<?php echo $venue['id']; ?>
                            </div>
                        </div>

                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                            <input type="hidden" name="original_club_full" value="<?php echo htmlspecialchars($selected_club_full); ?>">
                            <input type="hidden" name="target_host_name" value="<?php echo htmlspecialchars($venue['host_club']); ?>">
                            
                            <!-- Col 1 -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Venue Name</label>
                                    <div class="relative">
                                        <i data-lucide="building-2" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                        <input type="text" name="venue_name" value="<?php echo htmlspecialchars($venue['venue_name'] ?? ''); ?>" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="Leisure Centre Name">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Address</label>
                                    <div class="relative">
                                        <i data-lucide="map-pin" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                        <textarea name="address" rows="3" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="Full Address with Postcode"><?php echo htmlspecialchars($venue['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 2 -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Warm Up</label>
                                        <div class="relative">
                                            <i data-lucide="clock" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                            <input type="text" name="warmup_time" value="<?php echo htmlspecialchars($venue['warmup_time'] ?? ''); ?>" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="18:00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Start Time</label>
                                        <div class="relative">
                                            <i data-lucide="play-circle" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                            <input type="text" name="start_time" value="<?php echo htmlspecialchars($venue['start_time'] ?? ''); ?>" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="18:30">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Payment Info</label>
                                    <div class="relative">
                                        <i data-lucide="credit-card" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                        <input type="text" name="payment_info" value="<?php echo htmlspecialchars($venue['payment_info'] ?? ''); ?>" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="Cash Only / Card Accepted">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Parking & Other Info</label>
                                    <div class="relative">
                                        <i data-lucide="car" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                        <input type="text" name="parking_info" value="<?php echo htmlspecialchars($venue['parking_info'] ?? ''); ?>" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors" placeholder="Free 3hrs / Pay & Display / Other notes">
                                    </div>
                                </div>
                            </div>

                            <!-- Footer / Actions -->
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-white/5 flex flex-col md:flex-row items-center gap-4 justify-between">
                                <div class="w-full md:w-auto">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Updating on behalf of:</label>
                                    <select name="club_rep" class="bg-slate-900 border border-slate-700 text-white text-sm rounded-lg focus:ring-sky-500 focus:border-sky-500 block w-full p-2.5">
                                        <option value="League Admin">League Admin</option>
                                        <?php foreach ($all_club_names as $c_name): ?>
                                            <option value="<?php echo htmlspecialchars($c_name); ?>" <?php echo $selected_club_full === $c_name ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="update_venue" class="w-full md:w-auto bg-sky-600 hover:bg-sky-500 text-white font-bold py-2.5 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-sky-900/20">
                                    <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($selected_club_full): ?>
             <div class="text-center py-12">
                <div class="bg-slate-800/50 rounded-full p-4 inline-block mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8 text-slate-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white">No Venues Found</h3>
                <p class="text-slate-400">This club does not appear to be hosting any rounds in the current database.</p>
             </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
